<?php
/**
 * 会員（入会者）申込のカスタム項目回答モデル
 *
 * audience='member' の event_guest_fields に対する、会員申込（event_applications）ごとの回答。
 */
class EventMemberFieldValue
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 1申込分の回答を保存（既存は入れ替え）。$values = [ field_id => value, ... ]
     */
    public function save(int $applicationId, array $values): void
    {
        $this->db->execute(
            "DELETE FROM event_member_field_values WHERE application_id = ?",
            [$applicationId]
        );
        foreach ($values as $fieldId => $value) {
            $val = is_array($value) ? implode(', ', $value) : (string)$value;
            if ($val === '') {
                continue;
            }
            $this->db->insert(
                "INSERT INTO event_member_field_values (application_id, field_id, value)
                 VALUES (?, ?, ?)",
                [$applicationId, (int)$fieldId, $val]
            );
        }
    }

    /**
     * 1申込分の回答 [ field_id => value ]
     */
    public function getByApplication(int $applicationId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT field_id, value FROM event_member_field_values WHERE application_id = ?",
            [$applicationId]
        );
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['field_id']] = $r['value'];
        }
        return $map;
    }

    /**
     * 申込配列それぞれに values（field_id => value）を付与する。
     * $apps の各要素は 'id'（event_applications.id）を持つ前提。
     */
    public function attachValues(array $apps): array
    {
        if (empty($apps)) {
            return [];
        }
        $ids = array_map(fn($a) => (int)$a['id'], $apps);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->db->fetchAll(
            "SELECT application_id, field_id, value
               FROM event_member_field_values
              WHERE application_id IN ($placeholders)",
            $ids
        );
        $byApp = [];
        foreach ($rows as $r) {
            $byApp[(int)$r['application_id']][(int)$r['field_id']] = $r['value'];
        }
        foreach ($apps as &$a) {
            $a['member_values'] = $byApp[(int)$a['id']] ?? [];
        }
        return $apps;
    }
}
