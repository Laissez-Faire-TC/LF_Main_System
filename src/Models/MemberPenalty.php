<?php
/**
 * 会員ペナルティ点モデル（Admin限定機能）
 *
 * 「入金期限の遅れ」を検知してペナルティ点を集計する。
 *
 * 合計点 = 自動分 + 手動分
 *   - 自動分: 合宿費(camp_collections) / 遠征費(expedition_collections) / 物販(merchandise_orders)
 *             それぞれで「入金期限を過ぎても未入金・未提出」の件数 × config/penalty.php の点数。
 *             永続化せず、呼ばれるたびに集計する（cron 不要・数字がズレない）。
 *   - 手動分: member_penalty_adjustments テーブルの points の合計（Admin による加点・減点）。
 */

if (!class_exists('MemberPenalty')) {

class MemberPenalty
{
    private Database $db;
    private array $config;

    public function __construct()
    {
        $this->db     = Database::getInstance();
        $this->config = require CONFIG_PATH . '/penalty.php';
    }

    /** 種別ごとの「遅延1日あたりの点数」 */
    private function pointsPerDay(string $kind): int
    {
        return (int)($this->config['points_per_day'][$kind] ?? 0);
    }

    private function graceDays(): int
    {
        return (int)($this->config['grace_days'] ?? 0);
    }

    /**
     * 全会員のペナルティ点サマリを返す（管理画面一覧用）。
     *
     * @return array<int, array{
     *   member_id:int, name:string, name_kana:string, student_id:?string, status:?string,
     *   camp_overdue:int, expedition_overdue:int, merchandise_overdue:int,
     *   auto_points:int, manual_points:int, total_points:int
     * }>  total_points 降順（同点は name_kana 昇順）
     */
    public function summaryAll(): array
    {
        $grace = $this->graceDays();

        // 会員ごとの「未入金」分の遅延日数を種別ごとに集計（snapshot 済みは除外）
        $campDays  = $this->overdueDays('camp', $grace);
        $expDays   = $this->overdueDays('expedition', $grace);
        $merDays   = $this->overdueDays('merchandise', $grace);
        // 入金確認済みで確定した snapshot の点数（種別ごと member_id => points 合計）
        $snap      = $this->snapshotTotals();
        $manual    = $this->manualTotals();

        $members = $this->db->fetchAll(
            "SELECT id, name_kanji, name_kana, student_id, status FROM members ORDER BY name_kana ASC"
        );

        $rows = [];
        foreach ($members as $m) {
            $mid = (int)$m['id'];
            // 未入金分の遅延日数
            $campD = (int)($campDays[$mid] ?? 0);
            $expD  = (int)($expDays[$mid]  ?? 0);
            $merD  = (int)($merDays[$mid]  ?? 0);

            // 未入金分の点数（遅延日数 × 種別の1日あたり点）
            $autoLive = $campD * $this->pointsPerDay('camp')
                      + $expD  * $this->pointsPerDay('expedition')
                      + $merD  * $this->pointsPerDay('merchandise');
            // 入金確認済みで確定した点数
            $autoSnap = (int)($snap[$mid] ?? 0);
            $auto     = $autoLive + $autoSnap;
            $manualPts = (int)($manual[$mid] ?? 0);

            $rows[] = [
                'member_id'           => $mid,
                'name'                => (string)$m['name_kanji'],
                'name_kana'           => (string)($m['name_kana'] ?? ''),
                'student_id'          => $m['student_id'] ?? null,
                'status'              => $m['status'] ?? null,
                'camp_overdue'        => $campD,   // 未入金の遅延日数（合計）
                'expedition_overdue'  => $expD,
                'merchandise_overdue' => $merD,
                'auto_points'         => $auto,
                'manual_points'       => $manualPts,
                'total_points'        => $auto + $manualPts,
            ];
        }

        // 合計点の高い順（＝悪い順）に並べる。同点はカナ順。
        usort($rows, function ($a, $b) {
            if ($a['total_points'] !== $b['total_points']) {
                return $b['total_points'] <=> $a['total_points'];
            }
            return strcmp($a['name_kana'], $b['name_kana']);
        });

        return $rows;
    }

    /**
     * 1会員のペナルティ内訳（詳細表示用）。
     * 期限超過の明細（どの集金で遅れたか）と手動調整履歴を返す。
     */
    public function detail(int $memberId): array
    {
        $grace = $this->graceDays();

        return [
            'overdue_items' => [
                'camp'        => $this->overdueItems('camp', $memberId, $grace),
                'expedition'  => $this->overdueItems('expedition', $memberId, $grace),
                'merchandise' => $this->overdueItems('merchandise', $memberId, $grace),
            ],
            // 入金確認済みで確定した遅延（永続化分）
            'confirmed_items' => $this->snapshotItems($memberId),
            'adjustments'     => $this->adjustments($memberId),
        ];
    }

    /**
     * 手動調整を1件追加する。加点は正・取り消し/減点は負。
     * 監査ログに記録する（AuditLogger::group で1行）。
     */
    public function addAdjustment(int $memberId, int $points, string $reason): int
    {
        $adminId   = $_SESSION['admin_id']   ?? null;
        $adminName = $_SESSION['admin_name'] ?? null;

        // 監査ログの対象名（誰のペナルティか）。members テーブルの氏名は name_kanji のため
        // AuditLogger の自動解決（name 列前提）に頼らず明示的に渡す。
        $member     = $this->db->fetch("SELECT name_kanji FROM members WHERE id = ?", [$memberId]);
        $targetName = $member['name_kanji'] ?? '';

        return AuditLogger::group([
            'feature'      => 'penalty',
            'method'       => 'POST',
            'action_label' => ($points >= 0 ? 'ペナルティ点を加点' : 'ペナルティ点を減点')
                            . "（{$points}点：{$reason}）",
            'target_table' => 'members',
            'target_name'  => $targetName,
            'target_id'    => $memberId,
            'changes'      => ['points' => $points, 'reason' => $reason],
        ], function () use ($memberId, $points, $reason, $adminId, $adminName) {
            return $this->db->insert(
                "INSERT INTO member_penalty_adjustments (member_id, points, reason, admin_user_id, admin_name)
                 VALUES (?, ?, ?, ?, ?)",
                [$memberId, $points, $reason, $adminId, $adminName]
            );
        });
    }

    // ---------------- 内部: 集計クエリ ----------------

    /**
     * 種別ごとの「期限超過かつ未入金」遅延日数の合計を member_id => 日数 で返す。
     * 入金確認済みで snapshot に記録された分は除外する（二重計上防止）。
     */
    private function overdueDays(string $kind, int $grace): array
    {
        $sql = $this->overdueBaseSql($kind, false, $grace);
        $rows = $this->db->fetchAll($sql);
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['member_id']] = (int)$r['days'];
        }
        return $out;
    }

    /**
     * 1会員の期限超過明細（タイトル・期限・金額・遅延日数）を返す。
     */
    private function overdueItems(string $kind, int $memberId, int $grace): array
    {
        $sql = $this->overdueBaseSql($kind, true, $grace);
        return $this->db->fetchAll($sql, [$memberId]);
    }

    /**
     * 種別ごとの「期限超過かつ未入金」を抽出する SQL を組み立てる。
     *
     * @param bool $detail  true なら1会員の明細用（WHERE member_id = ? を付与・タイトル等を SELECT）。
     *                      false なら全会員集計用（member_id, COUNT(*) cnt を GROUP BY member_id）。
     *
     * 「未入金」の判定（提出フラグ・確認フラグの分離は機能ごとに異なる）:
     *   - 合宿  : camp_collection_items.submitted = 0 かつ admin_confirmed = 0
     *   - 遠征  : expedition_collection_items.paid = 0 かつ submitted = 0
     *   - 物販  : merchandise_orders.payment_status = 'unpaid' かつ payment_submitted = 0
     */
    private function overdueBaseSql(string $kind, bool $detail, int $grace): string
    {
        // 期限超過の条件: 期限 + 猶予日数 < 今日（DATE比較）
        $past = "DATE_ADD(%s, INTERVAL {$grace} DAY) < CURDATE()";
        // 遅延日数: 今日 -（期限 + 猶予日数）。最低1日。
        $daysExpr = function (string $deadlineCol): string {
            return "GREATEST(DATEDIFF(CURDATE(), DATE_ADD({$deadlineCol}, INTERVAL {$this->graceDays()} DAY)), 0)";
        };
        // 入金確認済みで snapshot 登録済みの集金は除外する SQL 断片
        $notSnapshotted = fn(string $idCol, string $k) =>
            "{$idCol} NOT IN (SELECT source_key FROM member_penalty_overdue_snapshots WHERE kind = '{$k}')";

        switch ($kind) {
            case 'camp':
                $deadlinePast = sprintf($past, 'cc.deadline');
                $days = $daysExpr('cc.deadline');
                $where = "cc.deadline IS NOT NULL
                          AND {$deadlinePast}
                          AND ci.submitted = 0
                          AND ci.admin_confirmed = 0
                          AND " . $notSnapshotted('ci.id', 'camp');
                if ($detail) {
                    return "SELECT c.name AS title, cc.deadline AS deadline,
                                   COALESCE(ci.custom_amount, cc.default_amount) AS amount,
                                   {$days} AS days_late
                            FROM camp_collection_items ci
                            JOIN camp_collections cc ON cc.id = ci.collection_id
                            JOIN camps c ON c.id = cc.camp_id
                            WHERE ci.member_id = ? AND {$where}
                            ORDER BY cc.deadline ASC";
                }
                return "SELECT ci.member_id AS member_id, COALESCE(SUM({$days}), 0) AS days
                        FROM camp_collection_items ci
                        JOIN camp_collections cc ON cc.id = ci.collection_id
                        WHERE {$where}
                        GROUP BY ci.member_id";

            case 'expedition':
                $deadlinePast = sprintf($past, 'ec.deadline');
                $days = $daysExpr('ec.deadline');
                $where = "ec.deadline IS NOT NULL
                          AND {$deadlinePast}
                          AND ei.paid = 0
                          AND ei.submitted = 0
                          AND " . $notSnapshotted('ei.id', 'expedition');
                if ($detail) {
                    return "SELECT CONCAT(e.name, '（第', ec.round, '回）') AS title,
                                   ec.deadline AS deadline, ei.amount AS amount,
                                   {$days} AS days_late
                            FROM expedition_collection_items ei
                            JOIN expedition_collections ec ON ec.id = ei.collection_id
                            JOIN expeditions e ON e.id = ec.expedition_id
                            WHERE ei.member_id = ? AND {$where}
                            ORDER BY ec.deadline ASC";
                }
                return "SELECT ei.member_id AS member_id, COALESCE(SUM({$days}), 0) AS days
                        FROM expedition_collection_items ei
                        JOIN expedition_collections ec ON ec.id = ei.collection_id
                        WHERE {$where}
                        GROUP BY ei.member_id";

            case 'merchandise':
                // 期限は商品マスタ(merchandise.payment_deadline)に持つ。注文は明細(order_items)で
                // 複数商品を含みうるため、注文に含まれる商品の最も早い期限を注文の期限とみなす。
                // 期限が設定された商品を1つも含まない注文は対象外（dl IS NULL で除外）。
                $deadlinePast = sprintf($past, 'dl.deadline');
                $days = $daysExpr('dl.deadline');
                $orderJoin =
                    "merchandise_orders o
                     JOIN (
                         SELECT moi.order_id, MIN(m.payment_deadline) AS deadline
                         FROM merchandise_order_items moi
                         JOIN merchandise m ON m.id = moi.merchandise_id
                         WHERE m.payment_deadline IS NOT NULL
                         GROUP BY moi.order_id
                     ) dl ON dl.order_id = o.id";
                $where = "o.member_id IS NOT NULL
                          AND dl.deadline IS NOT NULL
                          AND {$deadlinePast}
                          AND o.payment_status = 'unpaid'
                          AND o.payment_submitted = 0
                          AND " . $notSnapshotted('o.id', 'merchandise');
                if ($detail) {
                    return "SELECT CONCAT('物販注文 #', o.id) AS title,
                                   dl.deadline AS deadline, o.total_amount AS amount,
                                   {$days} AS days_late
                            FROM {$orderJoin}
                            WHERE o.member_id = ? AND {$where}
                            ORDER BY dl.deadline ASC";
                }
                return "SELECT o.member_id AS member_id, COALESCE(SUM({$days}), 0) AS days
                        FROM {$orderJoin}
                        WHERE {$where}
                        GROUP BY o.member_id";

            default:
                // 想定外の種別は空集合を返す（事故防止）
                return "SELECT NULL AS member_id, 0 AS days FROM DUAL WHERE 1 = 0";
        }
    }

    // ---------------- 内部: 確定済み snapshot ----------------

    /**
     * 会員ごとの確定済み snapshot 点数合計を member_id => 合計点 で返す。
     */
    private function snapshotTotals(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT member_id, COALESCE(SUM(points), 0) AS total
             FROM member_penalty_overdue_snapshots GROUP BY member_id"
        );
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['member_id']] = (int)$r['total'];
        }
        return $out;
    }

    /**
     * 1会員の確定済み遅延明細（新しい順）。
     */
    private function snapshotItems(int $memberId): array
    {
        return $this->db->fetchAll(
            "SELECT kind, title, deadline, days_late, points, confirmed_at
             FROM member_penalty_overdue_snapshots
             WHERE member_id = ?
             ORDER BY confirmed_at DESC",
            [$memberId]
        );
    }

    /**
     * 入金確認の操作時に呼ぶ。対象集金が期限超過していたら、その時点の遅延日数を確定し
     * snapshot に記録する（永続化）。すでに記録済み・期限内・未来期限なら何もしない。
     *
     * @param string $kind      'camp' | 'expedition' | 'merchandise'
     * @param int    $sourceKey 集金レコードのID（camp/expedition=item.id, merchandise=order.id）
     */
    public function recordSnapshot(string $kind, int $sourceKey): void
    {
        if (!in_array($kind, ['camp', 'expedition', 'merchandise'], true)) {
            return;
        }
        // すでに確定済みなら何もしない
        $exists = $this->db->fetch(
            "SELECT id FROM member_penalty_overdue_snapshots WHERE kind = ? AND source_key = ?",
            [$kind, $sourceKey]
        );
        if ($exists) {
            return;
        }

        $info = $this->fetchSourceInfo($kind, $sourceKey);
        if (!$info || $info['deadline'] === null || $info['member_id'] === null) {
            return;   // 期限が無い／会員に紐づかない集金はペナルティ対象外
        }

        $grace    = $this->graceDays();
        $daysLate = (int)$this->db->fetch(
            "SELECT GREATEST(DATEDIFF(CURDATE(), DATE_ADD(?, INTERVAL ? DAY)), 0) AS d",
            [$info['deadline'], $grace]
        )['d'];

        if ($daysLate <= 0) {
            return;   // 期限内・猶予内の入金はペナルティなし
        }

        $points = $daysLate * $this->pointsPerDay($kind);
        $this->db->insert(
            "INSERT INTO member_penalty_overdue_snapshots
                (member_id, kind, source_key, title, deadline, days_late, points)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [(int)$info['member_id'], $kind, $sourceKey, $info['title'], $info['deadline'], $daysLate, $points]
        );
    }

    /**
     * snapshot 記録用に、集金レコードから member_id / title / deadline を取得する。
     */
    private function fetchSourceInfo(string $kind, int $sourceKey): ?array
    {
        switch ($kind) {
            case 'camp':
                return $this->db->fetch(
                    "SELECT ci.member_id, c.name AS title, cc.deadline AS deadline
                     FROM camp_collection_items ci
                     JOIN camp_collections cc ON cc.id = ci.collection_id
                     JOIN camps c ON c.id = cc.camp_id
                     WHERE ci.id = ?",
                    [$sourceKey]
                );
            case 'expedition':
                return $this->db->fetch(
                    "SELECT ei.member_id, CONCAT(e.name, '（第', ec.round, '回）') AS title, ec.deadline AS deadline
                     FROM expedition_collection_items ei
                     JOIN expedition_collections ec ON ec.id = ei.collection_id
                     JOIN expeditions e ON e.id = ec.expedition_id
                     WHERE ei.id = ?",
                    [$sourceKey]
                );
            case 'merchandise':
                return $this->db->fetch(
                    "SELECT o.member_id, CONCAT('物販注文 #', o.id) AS title,
                            (SELECT MIN(m.payment_deadline)
                               FROM merchandise_order_items moi
                               JOIN merchandise m ON m.id = moi.merchandise_id
                              WHERE moi.order_id = o.id AND m.payment_deadline IS NOT NULL) AS deadline
                     FROM merchandise_orders o
                     WHERE o.id = ?",
                    [$sourceKey]
                );
            default:
                return null;
        }
    }

    /**
     * 会員ごとの手動調整合計を member_id => 合計点 で返す。
     */
    private function manualTotals(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT member_id, COALESCE(SUM(points), 0) AS total
             FROM member_penalty_adjustments GROUP BY member_id"
        );
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['member_id']] = (int)$r['total'];
        }
        return $out;
    }

    /**
     * 1会員の手動調整履歴（新しい順）。
     */
    private function adjustments(int $memberId): array
    {
        return $this->db->fetchAll(
            "SELECT id, points, reason, admin_user_id, admin_name, created_at
             FROM member_penalty_adjustments
             WHERE member_id = ?
             ORDER BY created_at DESC",
            [$memberId]
        );
    }
}

}
