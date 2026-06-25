<?php
/**
 * 監査ロガー（Database層フック）
 *
 * Database::execute() / insert() から呼ばれ、書き込みSQL（INSERT/UPDATE/DELETE）を
 * 解析して admin_activity_logs に差分付きで記録する。
 *
 * 設計:
 *   - 幹部セッション中のみ記録（会員ポータル・公開フォーム・未ログインは対象外）。
 *   - UPDATE/DELETE は実行直前に対象行（before）を取得し、after と比較して差分を残す。
 *   - admin_activity_logs 自身への書き込みは記録しない（無限ループ防止）。
 *   - 記録の失敗は本処理に影響させない（例外は握りつぶす）。
 *   - 同じPDO接続を使うが、ログのINSERTは即時実行。トランザクション中に呼ばれても
 *     本処理のロールバック/コミット制御は変えない（rowCountに影響しないINSERTのみ）。
 *
 * PIIの扱い: 値も含めて記録する方針（ユーザー選択）。
 *   ただし password 等の認証系カラムだけは値を伏せる（[REDACTED]）。
 */
class AuditLogger
{
    /** ログ対象外テーブル（記録自身・高頻度ノイズ） */
    private const SKIP_TABLES = [
        'admin_activity_logs',  // 無限ループ防止
        'admin_sessions',       // セッション監視（ログイン/最終アクセス）は別系統で記録
    ];

    /** 値を必ず伏せるカラム（テーブル横断・認証/秘匿系） */
    private const REDACT_COLUMNS = [
        'password', 'setting_value', 'google_sub', 'access_token', 'refresh_token',
    ];

    /** 多重記録の抑止フラグ（ログ記録中の内部SELECT/INSERTを除外） */
    private static bool $recording = false;

    /** group() のネスト深さ（>0 の間は自動記録を抑止し、操作をまとめる） */
    private static int $groupDepth = 0;

    /** 直近に自動記録したログの (id, table, op)。同一テーブル・同一操作の連続を集約するために保持 */
    private static ?array $lastAuto = null;

    /**
     * 自動記録の抑止フラグを切り替える。
     *
     * 複数の書き込みSQLを1操作として「まとめて記録」したい場合に、
     * 個別SQLの自動記録を一時的に止めるために使う（権限の一括差し替え等）。
     *
     * @param  bool $on   true で抑止ON
     * @return bool       変更前の状態（finally で元に戻すために使う）
     */
    public static function suppress(bool $on): bool
    {
        $prev = self::$recording;
        self::$recording = $on;
        return $prev;
    }

    /**
     * 複数の書き込みSQLを「1つの操作＝1行」としてまとめて記録する。
     *
     * 渡したコールバック内で発生する INSERT/UPDATE/DELETE の自動記録は抑止し、
     * 代わりにここで指定したラベル・対象・差分で1行だけ記録する。
     * 権限の一括差し替え・物販注文の作成・車割の自動作成など、業務的に1操作だが
     * 内部で複数SQLに分かれる処理をこのメソッドで包む。
     *
     * 使用例:
     *   AuditLogger::group([
     *       'feature'      => 'merchandise',
     *       'method'       => 'POST',
     *       'action_label' => '物販注文を作成',
     *       'target_table' => 'merchandise_orders',
     *       'target_id'    => $orderId,         // コールバック後に確定するなら下記 result を使う
     *       'changes'      => ['商品' => $title, '点数' => $qty],
     *   ], function () { ... 複数の書き込み ... });
     *
     * target_id / changes をコールバックの戻り値から決めたい場合は、
     * meta に 'resolve' => fn($result) => ['target_id'=>..., 'changes'=>...] を渡す。
     *
     * @param array    $meta 記録メタ（feature/method/action_label/target_table/target_id/changes/resolve）
     * @param callable $fn   まとめたい処理（戻り値はそのまま group() の戻り値になる）
     * @return mixed         $fn の戻り値
     */
    public static function group(array $meta, callable $fn)
    {
        $prevRecording = self::$recording;
        self::$groupDepth++;
        self::$recording = true; // 内部SQLの自動記録を止める

        $result = null;
        $error  = null;
        try {
            $result = $fn();
        } catch (Throwable $e) {
            $error = $e;
        }

        self::$groupDepth--;
        self::$recording = ($prevRecording || self::$groupDepth > 0);

        // ネスト最上位（depth 0）かつ抑止解除済みのときだけ記録する
        if (self::$groupDepth === 0 && !self::$recording) {
            try {
                // resolve コールバックで target_id/changes を後から確定
                if (isset($meta['resolve']) && is_callable($meta['resolve'])) {
                    $resolved = (array)$meta['resolve']($result);
                    $meta = array_merge($meta, $resolved);
                }
                self::writeGrouped($meta);
            } catch (Throwable $e) {
                error_log('AuditLogger::group write failed: ' . $e->getMessage());
            }
        }

        if ($error !== null) throw $error; // 本処理の例外はそのまま伝播
        return $result;
    }

    /**
     * group() のメタから1行記録する。
     */
    private static function writeGrouped(array $meta): void
    {
        if (!class_exists('Auth') || !Auth::isAdminContext()) return;

        $table   = $meta['target_table'] ?? null;
        $targetId = $meta['target_id']   ?? null;
        $feature = $meta['feature']
            ?? ($table ? AdminActivityLog::featureFromTable($table) : null);

        // 対象名（誰の・何を）をラベルに添える
        $label = (string)($meta['action_label'] ?? '');
        $targetName = $meta['target_name']
            ?? (($table && $targetId !== null) ? self::resolveTargetName($table, (int)$targetId) : '');
        if ($targetName !== '' && strpos($label, (string)$targetName) === false) {
            $label = $targetName . '：' . $label;
        }

        $changes = $meta['changes'] ?? null;

        self::$recording = true;
        (new AdminActivityLog())->log([
            'admin_user_id' => $_SESSION['admin_id']        ?? null,
            'admin_email'   => $_SESSION['admin_email']      ?? null,
            'admin_name'    => $_SESSION['admin_name']       ?? null,
            'login_type'    => $_SESSION['admin_login_type'] ?? null,
            'method'        => $meta['method'] ?? 'POST',
            'path'          => $_SERVER['REQUEST_URI'] ?? '',
            'feature'       => $feature,
            'action_label'  => $label,
            'target_table'  => $table,
            'target_id'     => $targetId,
            'changes_json'  => $changes ? json_encode($changes, JSON_UNESCAPED_UNICODE) : null,
            'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
        self::$recording = false;
    }

    /**
     * target_table + target_id から人間向けの対象名を引く（会員名・商品名・遠征名等）。
     * 解決できなければ空文字。失敗してもログ本処理を止めない。
     */
    public static function resolveTargetName(string $table, int $id): string
    {
        if ($id <= 0) return '';

        // テーブルごとに「名前として使うカラム」を定義（先頭から最初に非空のものを使う）
        static $map = [
            'members'                 => ['name'],
            'admin_users'             => ['name', 'email'],
            'expeditions'             => ['title', 'name'],
            'camps'                   => ['title', 'name'],
            'events'                  => ['title', 'name'],
            'merchandise'             => ['title', 'name'],
            'merchandise_orders'      => ['purchaser_name', 'pending_student_id'],
            'suggestions'             => ['title'],
        ];
        $cols = $map[$table] ?? null;
        if ($cols === null) return '';

        try {
            $wasRecording = self::$recording;
            self::$recording = true;
            $select = implode(', ', array_map(fn($c) => "`{$c}`", $cols));
            $row = Database::getInstance()->fetch(
                "SELECT {$select} FROM {$table} WHERE id = ? LIMIT 1",
                [$id]
            );
            self::$recording = $wasRecording;
            if (!$row) return '';
            foreach ($cols as $c) {
                if (isset($row[$c]) && (string)$row[$c] !== '') {
                    return (string)$row[$c];
                }
            }
        } catch (Throwable $e) {
            self::$recording = false;
            return '';
        }
        return '';
    }

    /**
     * 書き込みSQLを記録する。Database から実行後に呼ばれる。
     *
     * @param PDO    $pdo        実行に使われたPDO接続（before取得に同じ接続を使う）
     * @param string $sql        実行されたSQL
     * @param array  $params     バインドパラメータ
     * @param array  $beforeRows execute前に取得した対象行（UPDATE/DELETE時）。なければ空配列
     * @param ?int   $insertId   INSERT時の lastInsertId（INSERT以外は null）
     */
    public static function record(PDO $pdo, string $sql, array $params, array $beforeRows, ?int $insertId): void
    {
        if (self::$recording) return;

        try {
            // 幹部セッション中のみ記録
            if (!class_exists('Auth') || !Auth::isAdminContext()) {
                return;
            }

            $parsed = self::parse($sql);
            if ($parsed === null) return;                 // 書き込みSQLでない
            [$op, $table] = [$parsed['op'], $parsed['table']];

            if (in_array($table, self::SKIP_TABLES, true)) return;

            self::$recording = true;

            $diff = self::buildDiff($op, $sql, $params, $beforeRows, $insertId, $pdo, $table);

            // --- 自動集約（保険）: group() で包み忘れても行が爆発しないように ---
            // 直前の自動記録が「同じテーブル・同じ操作」なら、新規行を作らず件数を加算する。
            // ループ内の一括INSERT/UPDATE/DELETE（明細登録・並び順更新など）が1行にまとまる。
            if (self::$lastAuto !== null
                && self::$lastAuto['table'] === $table
                && self::$lastAuto['op'] === $op) {
                $model = new AdminActivityLog();
                $newCount = $model->bumpRowCount((int)self::$lastAuto['id']);
                if ($newCount > 0) {
                    self::$recording = false;
                    return; // 既存行に集約済み
                }
            }

            $feature = AdminActivityLog::featureFromTable($table);

            // 単発記録: 対象名（誰の・何を）をラベルに添える
            $label   = self::actionLabel($op, $table, $diff);
            $targetId = $diff['target_id'] ?? null;
            $targetName = ($targetId !== null) ? self::resolveTargetName($table, (int)$targetId) : '';
            if ($targetName !== '' && strpos($label, $targetName) === false) {
                $label = $targetName . '：' . $label;
            }

            // 認証系（settings/oauth）など機能カテゴリ未定義のテーブルは「その他」で記録
            $newId = (new AdminActivityLog())->log([
                'admin_user_id' => $_SESSION['admin_id']        ?? null,
                'admin_email'   => $_SESSION['admin_email']      ?? null,
                'admin_name'    => $_SESSION['admin_name']       ?? null,
                'login_type'    => $_SESSION['admin_login_type'] ?? null,
                'method'        => self::opToMethod($op),
                'path'          => $_SERVER['REQUEST_URI'] ?? '',
                'feature'       => $feature,
                'action_label'  => $label,
                'target_table'  => $table,
                'target_id'     => $targetId,
                'changes_json'  => $diff['changes'] ? json_encode($diff['changes'], JSON_UNESCAPED_UNICODE) : null,
                'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            // 次回の集約判定用に記録（id が取れたときだけ）
            self::$lastAuto = $newId > 0
                ? ['id' => $newId, 'table' => $table, 'op' => $op]
                : null;
        } catch (Throwable $e) {
            error_log('AuditLogger::record failed: ' . $e->getMessage());
        } finally {
            self::$recording = false;
        }
    }

    /**
     * execute() に渡されるSQLが UPDATE/DELETE の場合、実行前に対象行を取得する。
     * INSERT・SELECT・記録中は空配列を返す。
     *
     * Database::execute() が呼ぶ。WHERE句とパラメータをそのまま使い SELECT * に置換する。
     */
    public static function fetchBefore(PDO $pdo, string $sql, array $params): array
    {
        if (self::$recording) return [];

        try {
            if (!class_exists('Auth') || !Auth::isAdminContext()) return [];

            $parsed = self::parse($sql);
            if ($parsed === null) return [];
            if (!in_array($parsed['op'], ['UPDATE', 'DELETE'], true)) return [];
            if (in_array($parsed['table'], self::SKIP_TABLES, true)) return [];

            // WHERE 以降を取り出して SELECT * FROM <table> <where...> に組み替える
            if (!preg_match('/\bWHERE\b(.*)$/is', $sql, $m)) {
                return []; // WHERE無し（全件更新）は対象行不定のため before は取らない
            }
            $whereClause = $m[1];

            // UPDATE は SET句のパラメータが先、WHERE句のパラメータが後ろ。
            // WHERE句のプレースホルダ数だけ末尾から取る。
            $whereParamCount = substr_count($whereClause, '?');
            $whereParams = $whereParamCount > 0
                ? array_slice($params, -$whereParamCount)
                : [];

            self::$recording = true;
            $stmt = $pdo->prepare("SELECT * FROM {$parsed['table']} WHERE" . $whereClause);
            $stmt->execute($whereParams);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            self::$recording = false;

            return $rows ?: [];
        } catch (Throwable $e) {
            self::$recording = false;
            return [];
        }
    }

    // ---------------- 内部 ----------------

    /**
     * SQL先頭から操作種別とテーブル名を抽出。書き込みでなければ null。
     */
    private static function parse(string $sql): ?array
    {
        $s = ltrim($sql);
        if (preg_match('/^INSERT\s+(?:IGNORE\s+)?INTO\s+`?(\w+)`?/i', $s, $m)) {
            return ['op' => 'INSERT', 'table' => $m[1]];
        }
        if (preg_match('/^UPDATE\s+`?(\w+)`?/i', $s, $m)) {
            return ['op' => 'UPDATE', 'table' => $m[1]];
        }
        if (preg_match('/^DELETE\s+FROM\s+`?(\w+)`?/i', $s, $m)) {
            return ['op' => 'DELETE', 'table' => $m[1]];
        }
        return null; // SELECT / REPLACE / その他は対象外
    }

    private static function opToMethod(string $op): string
    {
        switch ($op) {
            case 'INSERT': return 'POST';
            case 'UPDATE': return 'PUT';
            case 'DELETE': return 'DELETE';
            default:       return $op;
        }
    }

    /**
     * 差分を構築する。
     *   - INSERT: after（挿入値）を全カラム記録。target_id は insertId。
     *   - UPDATE: before 各行 vs after（before に SET を適用）で変化カラムのみ記録。
     *   - DELETE: before 各行を「削除された内容」として記録。
     *
     * @return array{target_id:?int, changes:?array}
     */
    private static function buildDiff(string $op, string $sql, array $params, array $beforeRows, ?int $insertId, PDO $pdo, string $table): array
    {
        if ($op === 'INSERT') {
            $cols = self::parseInsertColumns($sql);
            $values = [];
            if ($cols && count($cols) <= count($params)) {
                foreach ($cols as $i => $c) {
                    $values[$c] = self::redact($c, $params[$i] ?? null);
                }
            }
            return ['target_id' => $insertId, 'changes' => $values ?: null];
        }

        if ($op === 'DELETE') {
            // before行をそのまま「削除内容」として記録（複数行なら配列）
            $rows = array_map([self::class, 'redactRow'], $beforeRows);
            $targetId = isset($beforeRows[0]['id']) ? (int)$beforeRows[0]['id'] : null;
            return ['target_id' => $targetId, 'changes' => $rows ? ['_deleted' => $rows] : null];
        }

        // UPDATE: SET句のカラムと、afterの値（=SETパラメータ）を取り、before と比較
        $setMap = self::parseUpdateSet($sql, $params); // [col => afterValue]
        $changes = [];
        $targetId = isset($beforeRows[0]['id']) ? (int)$beforeRows[0]['id'] : null;

        if ($beforeRows) {
            // 代表として先頭行を比較対象にする（複数行更新は稀・先頭で代表）
            $before = $beforeRows[0];
            foreach ($setMap as $col => $afterVal) {
                $beforeVal = $before[$col] ?? null;
                if ((string)$beforeVal === (string)$afterVal) {
                    continue; // 変化なし
                }

                // JSONカラム（しおりの items 等）は、丸ごと before/after を出すと
                // 巨大なJSONが並んで「何を変えたか分からない」ため、JSON内のフィールド
                // 単位で差分を取り、変わったフィールドだけを記録する。
                $jsonDiff = self::jsonFieldDiff($beforeVal, $afterVal);
                if ($jsonDiff !== null) {
                    $changes[$col] = ['_json' => $jsonDiff];
                    continue;
                }

                $changes[$col] = [
                    'before' => self::redact($col, $beforeVal),
                    'after'  => self::redact($col, $afterVal),
                ];
            }
            // 複数行更新だった場合は件数も残す
            if (count($beforeRows) > 1) {
                $changes['_rows'] = count($beforeRows);
            }
        } else {
            // before が取れない（WHERE無し等）場合は after のみ
            foreach ($setMap as $col => $afterVal) {
                $changes[$col] = ['after' => self::redact($col, $afterVal)];
            }
        }

        return ['target_id' => $targetId, 'changes' => $changes ?: null];
    }

    /**
     * INSERT のカラムリストを抽出。`INSERT INTO t (a, b, c) VALUES ...`
     */
    private static function parseInsertColumns(string $sql): array
    {
        if (preg_match('/^\s*INSERT\s+(?:IGNORE\s+)?INTO\s+`?\w+`?\s*\(([^)]+)\)/i', $sql, $m)) {
            return array_map(function ($c) {
                return trim($c, " `\t\r\n");
            }, explode(',', $m[1]));
        }
        return [];
    }

    /**
     * UPDATE の SET句から [カラム => after値] を作る。
     * `UPDATE t SET a = ?, b = ? WHERE ...` のSET部プレースホルダを前から params に割り当てる。
     * 非プレースホルダ（NOW() 等）はスキップ。
     */
    private static function parseUpdateSet(string $sql, array $params): array
    {
        if (!preg_match('/\bSET\b(.*?)(?:\bWHERE\b|$)/is', $sql, $m)) {
            return [];
        }
        $setClause = $m[1];
        // カンマ区切りの代入（関数内カンマは想定しない単純ケース）
        $assigns = preg_split('/,(?![^(]*\))/', $setClause);

        $map = [];
        $pi = 0; // SET句で消費したプレースホルダ位置
        foreach ($assigns as $a) {
            if (!preg_match('/`?(\w+)`?\s*=\s*(.+)/s', trim($a), $mm)) continue;
            $col = $mm[1];
            $rhs = trim($mm[2]);
            if ($rhs === '?') {
                $map[$col] = $params[$pi] ?? null;
                $pi++;
            } else {
                // NOW(), 定数, 式などは値そのものを記録（プレースホルダ消費なし）
                // ただし '?' を含む式はパラメータを消費するので位置をずらす
                $pi += substr_count($rhs, '?');
            }
        }
        return $map;
    }

    private static function redact(string $column, $value)
    {
        if (in_array($column, self::REDACT_COLUMNS, true)) {
            return '[REDACTED]';
        }
        return $value;
    }

    private static function redactRow(array $row): array
    {
        foreach ($row as $k => $v) {
            $row[$k] = self::redact($k, $v);
        }
        return $row;
    }

    /**
     * JSONカラムの before/after を、JSON内のフィールド単位で差分化する。
     *
     * しおりの items のように複数の情報を1カラムにJSONで格納している場合、
     * 1フィールド（例: 会場）だけ変えても「カラム全体が変わった」と判定され、
     * 巨大なJSONが before/after に並んでしまう。これを防ぐため、両方が
     * JSONオブジェクトのときだけフィールド単位で比較し、変わったフィールドのみ返す。
     *
     * @return array|null フィールド差分 [field => [before,after]]。
     *                     どちらかがJSONでない・配列(リスト)・変化なしのときは null（=通常表示にフォールバック）
     */
    private static function jsonFieldDiff($beforeVal, $afterVal): ?array
    {
        if (!is_string($beforeVal) || !is_string($afterVal)) return null;
        $b = json_decode($beforeVal, true);
        $a = json_decode($afterVal, true);
        // 連想配列（JSONオブジェクト）同士のときのみ対象。数値配列や非JSONは対象外。
        if (!is_array($a) || !is_array($b)) return null;
        if ($a === array_values($a) || $b === array_values($b)) return null; // リストは対象外

        $diff = [];
        $keys = array_unique(array_merge(array_keys($b), array_keys($a)));
        foreach ($keys as $k) {
            $bv = $b[$k] ?? null;
            $av = $a[$k] ?? null;
            // 配列値（持ち物リスト・スケジュール等）はスカラ化して比較
            $bs = is_scalar($bv) || $bv === null ? (string)$bv : json_encode($bv, JSON_UNESCAPED_UNICODE);
            $as = is_scalar($av) || $av === null ? (string)$av : json_encode($av, JSON_UNESCAPED_UNICODE);
            if ($bs === $as) continue;
            $diff[(string)$k] = [
                'before' => $bv,
                'after'  => $av,
            ];
        }
        return $diff ?: null; // 差分が無ければ null（実質変化なし）
    }

    /**
     * 人間向けの操作ラベル。
     *
     * 中間テーブル（権限・メンバー所属など）は「追加＝付与／削除＝剥奪」のように
     * 意味のある日本語にする。それ以外は「<対象>を<作成|更新|削除>」。
     */
    private static function actionLabel(string $op, string $table, array $diff): string
    {
        $method  = self::opToMethod($op);
        $changes = $diff['changes'] ?? null;

        // --- 中間テーブルの特別扱い（行追加/削除に業務的意味がある） ---
        // 幹部権限: 付与 / 剥奪（対象の権限名を出す）
        if ($table === 'admin_permissions') {
            if ($op === 'INSERT' && is_array($changes) && isset($changes['permission_key'])) {
                return '権限を付与：' . AdminActivityLog::permissionKeyLabel((string)$changes['permission_key']);
            }
            if ($op === 'DELETE' && isset($changes['_deleted'][0]['permission_key'])) {
                return '権限を剥奪：' . AdminActivityLog::permissionKeyLabel((string)$changes['_deleted'][0]['permission_key']);
            }
        }

        $opLabel = $method === 'POST' ? '作成' : ($method === 'PUT' ? '更新' : '削除');
        $tableLabel = AdminActivityLog::tableLabel($table);

        // 更新時、変更カラムが1つなら「<対象>の<項目>を更新」と具体化
        if ($op === 'UPDATE' && is_array($changes)) {
            $cols = array_values(array_filter(array_keys($changes), function ($c) {
                return $c !== '_rows' && !AdminActivityLog::isHiddenColumn($c);
            }));
            if (count($cols) === 1) {
                return "{$tableLabel}の" . AdminActivityLog::columnLabel($cols[0]) . 'を更新';
            }
            if (count($cols) > 1) {
                return "{$tableLabel}を更新（" . count($cols) . '項目）';
            }
        }

        return "{$tableLabel}を{$opLabel}";
    }

    /**
     * 権限の一括差し替えを「1操作=1行」で記録する。
     *
     * AdminUser::setPermissions() から呼ばれる。before/after の集合差分から
     * 付与（granted）・剥奪（revoked）を求め、変化があった場合のみ記録する。
     *
     * @param int      $adminUserId 対象の幹部アカウントID
     * @param string[] $before      変更前の権限キー
     * @param string[] $after       変更後の権限キー
     * @param string   $targetName  対象者の表示名（誰の権限を変更したか）
     */
    public static function logPermissionChange(int $adminUserId, array $before, array $after, string $targetName = ''): void
    {
        if (self::$recording) return;

        try {
            if (!class_exists('Auth') || !Auth::isAdminContext()) return;

            $granted = array_values(array_diff($after, $before)); // 追加された権限
            $revoked = array_values(array_diff($before, $after)); // 外された権限

            if (!$granted && !$revoked) return; // 実質変更なし

            // 人間向けラベル化
            $gLabels = array_map([AdminActivityLog::class, 'permissionKeyLabel'], $granted);
            $rLabels = array_map([AdminActivityLog::class, 'permissionKeyLabel'], $revoked);

            // 「誰の権限を」を先頭に出す（対象者名が取れた場合）
            $who = $targetName !== '' ? $targetName . ' の' : '';

            $parts = [];
            if ($gLabels) $parts[] = '付与：' . implode('、', $gLabels);
            if ($rLabels) $parts[] = '剥奪：' . implode('、', $rLabels);
            $label = $who . '幹部権限を変更（' . implode(' / ', $parts) . '）';

            $changes = [
                'target'  => $targetName,
                'granted' => $gLabels,
                'revoked' => $rLabels,
            ];

            self::$recording = true;
            (new AdminActivityLog())->log([
                'admin_user_id' => $_SESSION['admin_id']        ?? null,
                'admin_email'   => $_SESSION['admin_email']      ?? null,
                'admin_name'    => $_SESSION['admin_name']       ?? null,
                'login_type'    => $_SESSION['admin_login_type'] ?? null,
                'method'        => 'PUT',
                'path'          => $_SERVER['REQUEST_URI'] ?? '',
                'feature'       => 'admin',
                'action_label'  => $label,
                'target_table'  => 'admin_permissions',
                'target_id'     => $adminUserId,
                'changes_json'  => json_encode($changes, JSON_UNESCAPED_UNICODE),
                'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
            self::$recording = false;
            self::$lastAuto = null; // 明示記録なので自動集約の連鎖を断つ
        } catch (Throwable $e) {
            self::$recording = false;
            error_log('AuditLogger::logPermissionChange failed: ' . $e->getMessage());
        }
    }
}
