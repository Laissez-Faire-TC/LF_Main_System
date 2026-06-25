<?php
/**
 * HP スケジュールモデル
 */
class HpSchedule
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM hp_schedule ORDER BY sort_order ASC'
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM hp_schedule WHERE id = ?', [$id]) ?: null;
    }

    public function findByKey(string $key): ?array
    {
        return $this->db->fetch('SELECT * FROM hp_schedule WHERE month_key = ?', [$key]) ?: null;
    }

    /**
     * スケジュールをキー付き連想配列で返す（公開API用）
     */
    public function allAsMap(): array
    {
        $rows = $this->all();
        $map = [];
        foreach ($rows as $row) {
            $row['images'] = json_decode($row['images'] ?? '[]', true) ?: [];
            $map[$row['month_key']] = $row;
        }
        return $map;
    }

    public function update(int $id, array $data): void
    {
        $allowed = ['month_label', 'title', 'text_html', 'extra_html', 'images', 'type'];
        $filtered = array_intersect_key($data, array_flip($allowed));

        if (isset($filtered['images']) && is_array($filtered['images'])) {
            $filtered['images'] = json_encode($filtered['images'], JSON_UNESCAPED_UNICODE);
        }

        if (!empty($filtered)) {
            $this->db->execute(
                'UPDATE hp_schedule SET ' . implode(', ', array_map(fn($k) => "$k = ?", array_keys($filtered))) . ' WHERE id = ?',
                [...array_values($filtered), $id]
            );
        }
    }
}
