<?php
/**
 * HP ニュースモデル
 */
class HpNews
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM hp_news ORDER BY news_date DESC, id DESC'
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM hp_news WHERE id = ?', [$id]) ?: null;
    }

    public function create(array $data): int
    {
        return $this->db->insert(
            'INSERT INTO hp_news (news_date, title, description, image_path, anchor_id, is_quick_news) VALUES (?,?,?,?,?,?)',
            [
                $data['news_date']    ?? '',
                $data['title']        ?? '',
                $data['description']  ?? '',
                $data['image_path']   ?? null,
                $data['anchor_id']    ?? null,
                isset($data['is_quick_news']) ? (int)$data['is_quick_news'] : 0,
            ]
        );
    }

    public function update(int $id, array $data): void
    {
        $allowed = ['news_date', 'title', 'description', 'image_path', 'anchor_id', 'is_quick_news'];
        $filtered = array_intersect_key($data, array_flip($allowed));
        if (!empty($filtered)) {
            $this->db->execute(
                'UPDATE hp_news SET ' . implode(', ', array_map(fn($k) => "$k = ?", array_keys($filtered))) . ' WHERE id = ?',
                [...array_values($filtered), $id]
            );
        }
    }

    public function delete(int $id): void
    {
        $this->db->execute('DELETE FROM hp_news WHERE id = ?', [$id]);
    }
}
