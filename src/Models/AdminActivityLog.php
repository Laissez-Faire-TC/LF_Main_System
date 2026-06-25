<?php
/**
 * 幹部活動ログ モデル（admin_activity_logs）
 *
 * 書き込み系API（POST/PUT/DELETE）の操作を自動記録する。
 * 記録は Router::dispatch() のフックから呼ばれる（record()）。
 * 閲覧は AdminActivityLogController（Admin限定）。
 */

if (!class_exists('AdminActivityLog')) {

class AdminActivityLog
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 1件記録する。記録失敗で本処理を止めないよう例外は握りつぶす。
     *
     * @return int 記録した行のID（失敗時は0）。AuditLogger の自動集約で使う。
     */
    public function log(array $data): int
    {
        try {
            return (int)$this->db->insert(
                "INSERT INTO admin_activity_logs
                    (admin_user_id, admin_email, admin_name, login_type, method, path,
                     feature, action_label, target_table, target_id, changes_json, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['admin_user_id'] ?? null,
                    $data['admin_email']   ?? null,
                    $data['admin_name']    ?? null,
                    $data['login_type']    ?? null,
                    $data['method']        ?? '',
                    $data['path']          ?? '',
                    $data['feature']       ?? null,
                    $data['action_label']  ?? null,
                    $data['target_table']  ?? null,
                    $data['target_id']     ?? null,
                    $data['changes_json']  ?? null,
                    $data['ip_address']    ?? null,
                ]
            );
        } catch (Throwable $e) {
            // ログ記録の失敗は本処理に影響させない
            error_log('AdminActivityLog::log failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 自動集約: 既存ログ行の件数を1増やし、action_label に「（n件）」を付け直す。
     *
     * AuditLogger が「同一テーブル・同一操作の連続書き込み」を1行にまとめるために使う。
     * changes_json に件数（_count）を持たせ、action_label 末尾の「（n件）」を更新する。
     *
     * @param  int $id 集約先のログ行ID
     * @return int 更新後の件数（更新できなければ0）
     */
    public function bumpRowCount(int $id): int
    {
        try {
            $row = $this->db->fetch(
                "SELECT action_label, changes_json FROM admin_activity_logs WHERE id = ?",
                [$id]
            );
            if (!$row) return 0;

            $changes = !empty($row['changes_json']) ? json_decode($row['changes_json'], true) : [];
            if (!is_array($changes)) $changes = [];

            // 初回集約時は「現在=1件」を起点に+1（=2件）
            $count = isset($changes['_count']) ? (int)$changes['_count'] : 1;
            $count++;
            $changes['_count'] = $count;

            // ラベル末尾の「（n件）」を付け替える
            $base = preg_replace('/（\d+件）$/u', '', (string)$row['action_label']);
            $label = $base . '（' . $count . '件）';

            $this->db->execute(
                "UPDATE admin_activity_logs SET action_label = ?, changes_json = ? WHERE id = ?",
                [$label, json_encode($changes, JSON_UNESCAPED_UNICODE), $id]
            );
            return $count;
        } catch (Throwable $e) {
            error_log('AdminActivityLog::bumpRowCount failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 検索（新しい順）。フィルタ: feature / admin_user_id / keyword / from / to
     */
    public function search(array $filters = [], int $limit = 200, int $offset = 0): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['feature'])) {
            $where[] = 'feature = ?';
            $params[] = $filters['feature'];
        }
        if (!empty($filters['admin_user_id'])) {
            $where[] = 'admin_user_id = ?';
            $params[] = (int)$filters['admin_user_id'];
        }
        if (!empty($filters['keyword'])) {
            $where[] = '(admin_email LIKE ? OR admin_name LIKE ? OR action_label LIKE ? OR path LIKE ?)';
            $kw = '%' . $filters['keyword'] . '%';
            array_push($params, $kw, $kw, $kw, $kw);
        }
        if (!empty($filters['from'])) {
            $where[] = 'created_at >= ?';
            $params[] = $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $where[] = 'created_at <= ?';
            $params[] = $filters['to'] . ' 23:59:59';
        }

        $sql = "SELECT * FROM admin_activity_logs";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY created_at DESC, id DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * 件数（同フィルタ）
     */
    public function count(array $filters = []): int
    {
        $where  = [];
        $params = [];

        if (!empty($filters['feature'])) {
            $where[] = 'feature = ?';
            $params[] = $filters['feature'];
        }
        if (!empty($filters['admin_user_id'])) {
            $where[] = 'admin_user_id = ?';
            $params[] = (int)$filters['admin_user_id'];
        }
        if (!empty($filters['keyword'])) {
            $where[] = '(admin_email LIKE ? OR admin_name LIKE ? OR action_label LIKE ? OR path LIKE ?)';
            $kw = '%' . $filters['keyword'] . '%';
            array_push($params, $kw, $kw, $kw, $kw);
        }
        if (!empty($filters['from'])) {
            $where[] = 'created_at >= ?';
            $params[] = $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $where[] = 'created_at <= ?';
            $params[] = $filters['to'] . ' 23:59:59';
        }

        $sql = "SELECT COUNT(*) AS c FROM admin_activity_logs";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $row = $this->db->fetch($sql, $params);
        return (int)($row['c'] ?? 0);
    }

    /**
     * 操作者の一覧（フィルタ用ドロップダウン）。
     */
    public function distinctActors(): array
    {
        return $this->db->fetchAll(
            "SELECT admin_user_id, MAX(admin_email) AS admin_email, MAX(admin_name) AS admin_name
               FROM admin_activity_logs
              GROUP BY admin_user_id
              ORDER BY admin_email ASC"
        );
    }

    // ---------------- テーブル → 機能カテゴリ / 表示ラベル ----------------

    /**
     * テーブル名 → [機能キー(config/admin.php permissions), 日本語ラベル]。
     * 機能キーはフィルタUI（permissions）と揃える。未登録テーブルは「その他」。
     */
    private const TABLE_MAP = [
        // 会員名簿
        'members'                  => ['members', '会員名簿'],
        'member_change_notifications' => ['members', '会員名簿（変更通知）'],
        'member_oauth_identities'  => ['members', '会員連携アカウント'],
        // 合宿
        'camps'                    => ['camps', '合宿'],
        'time_slots'               => ['camps', '合宿タイムスロット'],
        'expenses'                 => ['camps', '合宿雑費'],
        'participants'             => ['camps', '合宿参加者'],
        'participant_slots'        => ['camps', '合宿参加スロット'],
        'camp_applications'        => ['camps', '合宿申込'],
        'camp_collections'         => ['camps', '合宿集金'],
        'camp_collection_items'    => ['camps', '合宿集金明細'],
        'camp_booklets'            => ['camps', '合宿しおり'],
        'camp_tennis_battles'      => ['camps', '合宿テニス班'],
        'camp_plan_divisions'      => ['camps', '合宿企画班'],
        'camp_tokens'              => ['camps', '合宿申込URL'],
        'change_requests'          => ['camps', '合宿変更申請'],
        // 遠征
        'expeditions'              => ['expeditions', '遠征'],
        'expedition_participants'  => ['expeditions', '遠征参加者'],
        'expedition_cars'          => ['expeditions', '遠征車割'],
        'expedition_car_members'   => ['expeditions', '遠征車メンバー'],
        'expedition_car_payers'    => ['expeditions', '遠征車支払者'],
        'expedition_car_expenses'  => ['expeditions', '遠征レンタカー清算'],
        'expedition_teams'         => ['expeditions', '遠征チーム'],
        'expedition_team_members'  => ['expeditions', '遠征チームメンバー'],
        'expedition_collections'   => ['expeditions', '遠征集金'],
        'expedition_collection_items' => ['expeditions', '遠征集金明細'],
        'expedition_booklets'      => ['expeditions', '遠征しおり'],
        'expedition_tokens'        => ['expeditions', '遠征申込URL'],
        // 企画
        'events'                   => ['events', '企画'],
        'event_applications'       => ['events', '企画申込'],
        'event_expenses'           => ['events', '企画費用'],
        'event_team_constraints'   => ['events', '企画班制約'],
        'event_tokens'             => ['events', '企画申込URL'],
        // 物販
        'merchandise'              => ['merchandise', '商品'],
        'merchandise_colors'       => ['merchandise', '商品カラー'],
        'merchandise_sizes'        => ['merchandise', '商品サイズ'],
        'merchandise_orders'       => ['merchandise', '物販注文'],
        'merchandise_order_items'  => ['merchandise', '物販注文明細'],
        'merchandise_tokens'       => ['merchandise', '物販URL'],
        // 目安箱
        'suggestions'              => ['suggestions', '目安箱'],
        'suggestion_replies'       => ['suggestions', '目安箱返信'],
        'suggestion_categories'    => ['suggestions', '目安箱カテゴリ'],
        // HP
        'hp_settings'              => ['hp', 'HP設定'],
        'hp_news'                  => ['hp', 'HPニュース'],
        'hp_schedules'             => ['hp', 'HPスケジュール'],
        // 入会
        'membership_fees'          => ['enrollment', '入会金'],
        'membership_fee_grades'    => ['enrollment', '入会金（学年別）'],
        'membership_fee_items'     => ['enrollment', '入会金明細'],
        // 年度
        'academic_years'           => ['academic', '年度'],
        // 設定・権限
        'settings'                 => ['settings', 'システム設定'],
        'admin_users'              => null, // 権限管理は別扱い（下で判定）
        'admin_permissions'        => null,
    ];

    /** 権限管理テーブル（feature=admin、Admin専用機能） */
    private const ADMIN_TABLES = ['admin_users', 'admin_permissions'];

    /**
     * テーブル名から機能キー（config/admin.php の permission key）を返す。
     * 未登録は null（=「その他」表示）。権限管理は 'admin'。
     */
    public static function featureFromTable(string $table): ?string
    {
        if (in_array($table, self::ADMIN_TABLES, true)) {
            return 'admin';
        }
        $entry = self::TABLE_MAP[$table] ?? null;
        return $entry[0] ?? null;
    }

    /**
     * テーブル名の日本語ラベル。
     */
    public static function tableLabel(string $table): string
    {
        if (in_array($table, self::ADMIN_TABLES, true)) {
            return $table === 'admin_permissions' ? '幹部権限' : '幹部アカウント';
        }
        $entry = self::TABLE_MAP[$table] ?? null;
        return $entry[1] ?? $table;
    }

    /**
     * 機能キーの表示ラベル（フィルタ・一覧の機能列表示用）。
     * config/admin.php の permissions に揃える。
     */
    public static function featureLabel(?string $feature): string
    {
        if ($feature === null) return 'その他';
        if ($feature === 'admin') return '権限管理';
        static $labels = null;
        if ($labels === null) {
            $config = require CONFIG_PATH . '/admin.php';
            $labels = [];
            foreach (($config['permissions'] ?? []) as $p) {
                $labels[$p['key']] = $p['label'];
            }
        }
        return $labels[$feature] ?? $feature;
    }

    /**
     * HTTPメソッドの日本語ラベル。
     */
    public static function methodLabel(string $method): string
    {
        switch (strtoupper($method)) {
            case 'POST':   return '作成';
            case 'PUT':    return '更新';
            case 'DELETE': return '削除';
            default:       return $method;
        }
    }

    // ---------------- カラム名・値の日本語化（人間向け差分表示用） ----------------

    /**
     * カラム名 → 日本語ラベル。
     * 内部ID・タイムスタンプ等の「見せても意味がない」カラムは null を返し、
     * 差分表示から除外できるようにする。
     */
    private const COLUMN_LABELS = [
        // 共通
        'name'              => '氏名',
        'name_kana'         => 'フリガナ',
        'grade'             => '学年',
        'student_id'        => '学籍番号',
        'status'            => 'ステータス',
        'phone'             => '電話番号',
        'email'             => 'メール',
        'line_name'         => 'LINE名',
        'address'           => '住所',
        'emergency_contact' => '緊急連絡先',
        'birthdate'         => '生年月日',
        'allergy'           => 'アレルギー',
        'sns_allowed'       => 'SNS投稿可否',
        'sports_registration_no' => 'コート予約番号',
        'enrollment_year'   => '入学年度',
        'academic_year'     => '年度',
        'department'        => '学部',
        'major'             => '学科',
        // 幹部・権限
        'is_admin'          => 'Admin権限',
        'is_active'         => '有効状態',
        'permission_key'    => '権限',
        // 物販
        'total_amount'      => '合計金額',
        'paid_amount'       => '入金額',
        'payment_status'    => '支払状況',
        'price'             => '価格',
        'title'             => 'タイトル',
        // しおり（expedition_booklets / camp_booklets の items JSON 内フィールド）
        'venue'             => '会場',
        'meeting_note'      => '集合に関するメモ',
        'meeting_time'      => '集合時刻',
        'meeting_place'     => '集合場所',
        'items_to_bring'    => '持ち物',
        'car_assignment'    => '車割',
        'team_assignment'   => 'チーム分け',
        'room_assignments'  => '部屋割り',
        'schedules'         => 'スケジュール',
        'notes'             => '備考',
        'published'         => '公開状態',
        // 汎用
        'amount'            => '金額',
        'note'              => '備考',
        'memo'             => 'メモ',
        'reason'            => '理由',
    ];

    /**
     * 差分表示で「見せない」カラム（内部管理用）。
     */
    private const HIDDEN_COLUMNS = [
        'id', 'created_at', 'updated_at', 'admin_user_id', 'member_id',
        'expedition_id', 'camp_id', 'event_id', 'merchandise_id', 'order_id',
        'car_id', 'team_id', 'collection_id', 'membership_fee_id', 'google_sub',
        'sort_order', 'display_order',
    ];

    /**
     * カラム名の日本語ラベルを返す。未定義はカラム名そのまま。
     */
    public static function columnLabel(string $column): string
    {
        return self::COLUMN_LABELS[$column] ?? $column;
    }

    /**
     * 差分表示で隠すカラムか。
     */
    public static function isHiddenColumn(string $column): bool
    {
        return in_array($column, self::HIDDEN_COLUMNS, true);
    }

    /**
     * カラム値を人間向けに整形する。
     *   - 権限キー（permission_key）→「会員名簿：年度」などの日本語
     *   - 真偽フラグ（is_admin/is_active/sns_allowed）→ はい/いいえ
     *   - 空 → 「（空）」
     */
    public static function formatValue(string $column, $value): string
    {
        if ($value === null || $value === '') {
            return '（空）';
        }
        if ($column === 'permission_key') {
            return self::permissionKeyLabel((string)$value);
        }
        if (in_array($column, ['is_admin', 'is_active', 'sns_allowed'], true)) {
            return ((string)$value === '1' || $value === true) ? 'はい' : 'いいえ';
        }
        if ($column === 'status') {
            $map = ['pending' => '承認待ち', 'active' => '現役', 'ob_og' => 'OB/OG', 'withdrawn' => '退会'];
            return $map[(string)$value] ?? (string)$value;
        }
        return (string)$value;
    }

    /**
     * 権限キー（members / expeditions / members.field.academic_year など）を
     * 「会員名簿」「会員名簿：年度」のような日本語に変換する。
     * config/admin.php の permissions / member_fields を参照。
     */
    public static function permissionKeyLabel(string $key): string
    {
        static $perm = null, $field = null;
        if ($perm === null) {
            $config = require CONFIG_PATH . '/admin.php';
            $perm = [];
            foreach (($config['permissions'] ?? []) as $p) {
                $perm[$p['key']] = $p['label'];
            }
            $field = [];
            foreach (($config['member_fields'] ?? []) as $f) {
                $field[$f['key']] = $f['label'];
            }
        }
        // 項目別権限: members.field.xxx → 「会員名簿：年度」
        if (isset($field[$key])) {
            return '会員名簿：' . $field[$key];
        }
        // 機能権限: members → 「会員名簿」
        if (isset($perm[$key])) {
            return $perm[$key];
        }
        return $key;
    }
}

}
