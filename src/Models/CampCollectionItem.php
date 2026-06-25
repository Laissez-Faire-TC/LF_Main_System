<?php
/**
 * 合宿集金アイテムモデル
 */
class CampCollectionItem
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * collection_id に紐づく全アイテムを会員情報付きで取得（カナ順）
     */
    public function getByCollectionId(int $collectionId): array
    {
        return $this->db->fetchAll(
            "SELECT ci.*,
                    m.name_kanji, m.name_kana, m.grade, m.gender
             FROM camp_collection_items ci
             JOIN members m ON m.id = ci.member_id
             WHERE ci.collection_id = ?
             ORDER BY m.name_kana ASC",
            [$collectionId]
        );
    }

    /**
     * ID 指定で取得
     */
    public function find(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM camp_collection_items WHERE id = ?",
            [$id]
        );
    }

    /**
     * 会員ID と collection_id で取得
     */
    public function findByMemberAndCollection(int $memberId, int $collectionId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM camp_collection_items WHERE member_id = ? AND collection_id = ?",
            [$memberId, $collectionId]
        );
    }

    /**
     * 未提出の集金アイテムを会員ID で取得（集金・合宿情報付き）
     */
    public function getPendingByMemberId(int $memberId): array
    {
        return $this->db->fetchAll(
            "SELECT ci.*, cc.default_amount, cc.deadline, cc.id as collection_id, c.name as camp_name
             FROM camp_collection_items ci
             JOIN camp_collections cc ON cc.id = ci.collection_id
             JOIN camps c ON c.id = cc.camp_id
             WHERE ci.member_id = ? AND ci.submitted = 0 AND cc.is_active = 1
             ORDER BY cc.deadline ASC",
            [$memberId]
        );
    }

    /**
     * custom_amount / admin_confirmed を更新する
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];

        $allowed = ['custom_amount', 'admin_confirmed'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        // 入金確認（admin_confirmed=1）の場合、期限超過していたら遅延を確定記録（ペナルティ永続化）
        if (array_key_exists('admin_confirmed', $data) && (int)$data['admin_confirmed'] === 1
            && class_exists('MemberPenalty')) {
            (new MemberPenalty())->recordSnapshot('camp', $id);
        }

        $values[] = $id;
        return $this->db->execute(
            "UPDATE camp_collection_items SET " . implode(', ', $fields) . " WHERE id = ?",
            $values
        ) > 0;
    }

    /**
     * 提出済みにする（submitted=1, submitted_at=NOW(), late_reason を設定）
     */
    public function submit(int $id, ?string $lateReason): bool
    {
        // 提出（入金報告）時点で期限超過していたら遅延を確定記録（ペナルティ永続化）
        if (class_exists('MemberPenalty')) {
            (new MemberPenalty())->recordSnapshot('camp', $id);
        }
        return $this->db->execute(
            "UPDATE camp_collection_items
             SET submitted = 1, submitted_at = NOW(), late_reason = ?
             WHERE id = ?",
            [$lateReason, $id]
        ) > 0;
    }
}
