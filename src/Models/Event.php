<?php
/**
 * 企画モデル
 */
class Event
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 新歓フォームの「学科」選択肢（指定の固定順）
     */
    public static function shinkanDepartments(): array
    {
        return [
            '表現工学科', '情報理工学科', '情報通信学科', '機械科学・航空宇宙学科',
            '電子物理システム学科', '応用数理学科', '数学科',
            'Major in Mathematical Sciences', 'Mathematical Sciences',
            '社会環境工学科', '環境資源工学科', '経営システム工学科', '総合機械工学科',
            '建築学科', '化学・生命化学科', '応用化学科', '物理学科', '応用物理学科',
            '生命医科学科', '電気・情報生命学科',
        ];
    }

    /**
     * OBOGフォームの「代」選択肢（1〜上限）。
     * 上限は 2026-09-30 まで 11、以降は毎年 10/1 を境に +1。
     */
    public static function obogGenerations(): array
    {
        $base     = 11;
        $baseYear = 2025;   // 2026/10/1（effectiveYear=2026）で初めて +1
        $year  = (int)date('Y');
        $month = (int)date('n');
        $effectiveYear = ($month >= 10) ? $year : $year - 1;
        $max = $base + max(0, $effectiveYear - $baseYear);
        return array_map('strval', range(1, $max));
    }

    /**
     * 全件取得（申込数・キャンセル待ち数付き）
     */
    public function all(): array
    {
        return $this->db->fetchAll(
            "SELECT e.*,
                    COUNT(CASE WHEN ea.status = 'submitted'  THEN 1 END) AS application_count,
                    COUNT(CASE WHEN ea.status = 'waitlisted' THEN 1 END) AS waitlist_count
             FROM events e
             LEFT JOIN event_applications ea ON ea.event_id = e.id
             GROUP BY e.id
             ORDER BY e.event_date DESC",
            []
        );
    }

    /**
     * ID指定で取得
     */
    public function find(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT e.*,
                    COUNT(CASE WHEN ea.status = 'submitted'  THEN 1 END) AS application_count,
                    COUNT(CASE WHEN ea.status = 'waitlisted' THEN 1 END) AS waitlist_count
             FROM events e
             LEFT JOIN event_applications ea ON ea.event_id = e.id
             WHERE e.id = ?
             GROUP BY e.id",
            [$id]
        );
    }

    /**
     * 公開中の企画を申込状況付きで取得（会員ホーム用）
     * ・is_active = 1
     * ・deadline が NULL または今日以降
     */
    public function getActiveWithMemberStatus(int $memberId): array
    {
        return $this->db->fetchAll(
            "SELECT e.*,
                    COUNT(CASE WHEN ea.status = 'submitted'  THEN 1 END) AS application_count,
                    COUNT(CASE WHEN ea.status = 'waitlisted' THEN 1 END) AS waitlist_count,
                    my_app.status AS my_status,
                    (SELECT COUNT(*) FROM event_applications
                     WHERE event_id = e.id AND status = 'waitlisted'
                       AND created_at <= my_app.created_at) AS my_waitlist_position
             FROM events e
             LEFT JOIN event_applications ea ON ea.event_id = e.id
             LEFT JOIN event_applications my_app
                    ON my_app.event_id = e.id AND my_app.member_id = ?
             WHERE e.is_active = 1
               AND (e.deadline IS NULL OR e.deadline >= CURDATE())
             GROUP BY e.id
             ORDER BY e.event_date ASC",
            [$memberId]
        );
    }

    /**
     * 新規作成
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO events
                    (title, event_date, event_time, description, location, participation_fee, capacity, deadline, allow_waitlist, allow_guest, guest_type, include_guests_in_calc, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        return $this->db->insert($sql, [
            $data['title'],
            $data['event_date'],
            $data['event_time']   ?? null,
            $data['description']  ?? null,
            $data['location']     ?? null,
            $data['participation_fee'] ?? 0,
            isset($data['capacity']) && $data['capacity'] !== '' ? (int)$data['capacity'] : null,
            isset($data['deadline']) && $data['deadline'] !== '' ? $data['deadline'] : null,
            $data['allow_waitlist'] ?? 0,
            $data['allow_guest'] ?? 0,
            !empty($data['guest_type']) && in_array($data['guest_type'], ['shinkan', 'obog'], true) ? $data['guest_type'] : null,
            $data['include_guests_in_calc'] ?? 1,
            $data['is_active'] ?? 0,
        ]);
    }

    /**
     * 更新
     */
    public function update(int $id, array $data): bool
    {
        $allowed = ['title', 'event_date', 'event_time', 'description', 'location',
                    'participation_fee', 'capacity', 'deadline', 'allow_waitlist',
                    'allow_guest', 'guest_type', 'include_guests_in_calc', 'is_active'];
        $fields = [];
        $values = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                if ($field === 'capacity' || $field === 'deadline') {
                    $values[] = ($data[$field] !== '' && $data[$field] !== null) ? $data[$field] : null;
                    if ($field === 'capacity' && $values[count($values)-1] !== null) {
                        $values[count($values)-1] = (int)$values[count($values)-1];
                    }
                } elseif ($field === 'guest_type') {
                    $values[] = in_array($data[$field], ['shinkan', 'obog'], true) ? $data[$field] : null;
                } else {
                    $values[] = $data[$field];
                }
            }
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        return $this->db->execute(
            "UPDATE events SET " . implode(', ', $fields) . " WHERE id = ?",
            $values
        ) >= 0;
    }

    /**
     * 削除（CASCADE で applications/expenses も削除）
     */
    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM events WHERE id = ?", [$id]) > 0;
    }
}
