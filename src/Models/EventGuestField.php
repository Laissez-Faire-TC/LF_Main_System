<?php
/**
 * 企画ゲストフォーム項目モデル（カスタム質問）
 */
class EventGuestField
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 企画のフォーム項目一覧（並び順）
     * options は JSON をデコードして配列で返す
     */
    /**
     * 企画のフォーム項目一覧。$audience を指定すると対象（'member'/'guest'）で絞り込む。
     */
    public function getByEventId(int $eventId, ?string $audience = null): array
    {
        if ($audience !== null && in_array($audience, ['member', 'guest'], true)) {
            $rows = $this->db->fetchAll(
                "SELECT * FROM event_guest_fields WHERE event_id = ? AND audience = ?
                 ORDER BY sort_order ASC, id ASC",
                [$eventId, $audience]
            );
        } else {
            $rows = $this->db->fetchAll(
                "SELECT * FROM event_guest_fields WHERE event_id = ? ORDER BY sort_order ASC, id ASC",
                [$eventId]
            );
        }
        foreach ($rows as &$row) {
            $row['options'] = $this->decodeOptions($row['options']);
        }
        return $rows;
    }

    public function find(int $id): ?array
    {
        $row = $this->db->fetch("SELECT * FROM event_guest_fields WHERE id = ?", [$id]);
        if ($row) {
            $row['options'] = $this->decodeOptions($row['options']);
        }
        return $row;
    }

    public function create(int $eventId, array $data): int
    {
        $type    = in_array($data['type'] ?? 'text', ['text', 'textarea', 'select', 'radio'], true)
                 ? $data['type'] : 'text';
        $options = $this->normalizeOptions($type, $data['options'] ?? null);

        $description = trim($data['description'] ?? '');
        $audience    = in_array($data['audience'] ?? 'guest', ['member', 'guest'], true)
                     ? ($data['audience'] ?? 'guest') : 'guest';

        return $this->db->insert(
            "INSERT INTO event_guest_fields (event_id, audience, label, description, type, options, is_required, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $eventId,
                $audience,
                trim($data['label'] ?? ''),
                $description !== '' ? $description : null,
                $type,
                $options !== null ? json_encode($options, JSON_UNESCAPED_UNICODE) : null,
                !empty($data['is_required']) ? 1 : 0,
                (int)($data['sort_order'] ?? 0),
            ]
        );
    }

    public function update(int $id, array $data): bool
    {
        $existing = $this->find($id);
        if (!$existing) {
            return false;
        }

        $type    = in_array($data['type'] ?? $existing['type'], ['text', 'textarea', 'select', 'radio'], true)
                 ? ($data['type'] ?? $existing['type']) : 'text';
        $options = array_key_exists('options', $data)
                 ? $this->normalizeOptions($type, $data['options'])
                 : ($existing['options'] ?: null);

        if (array_key_exists('description', $data)) {
            $desc = trim((string)$data['description']);
            $description = $desc !== '' ? $desc : null;
        } else {
            $description = $existing['description'] ?? null;
        }

        return $this->db->execute(
            "UPDATE event_guest_fields
                SET label = ?, description = ?, type = ?, options = ?, is_required = ?, sort_order = ?, updated_at = NOW()
              WHERE id = ?",
            [
                trim($data['label'] ?? $existing['label']),
                $description,
                $type,
                $options !== null ? json_encode($options, JSON_UNESCAPED_UNICODE) : null,
                array_key_exists('is_required', $data) ? (!empty($data['is_required']) ? 1 : 0) : (int)$existing['is_required'],
                array_key_exists('sort_order', $data) ? (int)$data['sort_order'] : (int)$existing['sort_order'],
                $id,
            ]
        ) >= 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM event_guest_fields WHERE id = ?", [$id]) > 0;
    }

    /**
     * 並び順を一括保存 [ field_id => sort_order, ... ]
     */
    public function saveOrder(array $order): void
    {
        foreach ($order as $fieldId => $sort) {
            $this->db->execute(
                "UPDATE event_guest_fields SET sort_order = ? WHERE id = ?",
                [(int)$sort, (int)$fieldId]
            );
        }
    }

    // ── 内部ヘルパー ──

    private function decodeOptions($raw): array
    {
        if (!$raw) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * select/radio 以外は options を NULL に。配列の各要素を trim して空を除去
     */
    private function normalizeOptions(string $type, $options): ?array
    {
        if (!in_array($type, ['select', 'radio'], true)) {
            return null;
        }
        if (!is_array($options)) {
            return [];
        }
        $clean = array_values(array_filter(array_map(
            fn($o) => trim((string)$o),
            $options
        ), fn($o) => $o !== ''));
        return $clean;
    }
}
