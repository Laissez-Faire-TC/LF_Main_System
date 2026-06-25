<?php
/**
 * 遠征集金明細モデル
 */
class ExpeditionCollectionItem
{
    /**
     * 集金IDで明細一覧を取得（フリガナ順）
     */
    public static function findByCollection(int $collection_id): array
    {
        return Database::getInstance()->fetchAll(
            "SELECT eci.*, m.name_kanji, m.name_kana
             FROM expedition_collection_items eci
             JOIN members m ON m.id = eci.member_id
             WHERE eci.collection_id = ? ORDER BY m.name_kana",
            [$collection_id]
        );
    }

    /**
     * IDで1件取得
     */
    public static function find(int $id): ?array
    {
        return Database::getInstance()->fetch(
            "SELECT * FROM expedition_collection_items WHERE id = ?",
            [$id]
        );
    }

    /**
     * 会員IDと集金IDで明細を1件取得
     */
    public static function findByMemberAndCollection(int $memberId, int $collectionId): ?array
    {
        return Database::getInstance()->fetch(
            "SELECT * FROM expedition_collection_items
             WHERE member_id = ? AND collection_id = ?",
            [$memberId, $collectionId]
        );
    }

    /**
     * 会員の未提出遠征集金一覧を取得（会員ホーム表示用）
     * submitted=0 の集金明細を返す
     */
    public static function getPendingByMemberId(int $memberId): array
    {
        return Database::getInstance()->fetchAll(
            "SELECT eci.id         AS item_id,
                    eci.collection_id,
                    eci.amount,
                    eci.submitted,
                    ec.round,
                    ec.title       AS collection_title,
                    ec.deadline,
                    e.id           AS expedition_id,
                    e.name         AS expedition_name,
                    e.start_date,
                    e.end_date
             FROM expedition_collection_items eci
             JOIN expedition_collections ec ON ec.id = eci.collection_id
             JOIN expeditions e             ON e.id  = ec.expedition_id
             WHERE eci.member_id  = ?
               AND eci.submitted  = 0
             ORDER BY ec.deadline ASC, e.start_date ASC",
            [$memberId]
        );
    }

    /**
     * 会員提出処理（submitted=1, submitted_at=NOW()）
     */
    public static function submit(int $id, ?string $lateReason): void
    {
        // 提出（入金報告）時点で期限超過していたら遅延を確定記録（ペナルティ永続化）
        if (class_exists('MemberPenalty')) {
            (new MemberPenalty())->recordSnapshot('expedition', $id);
        }
        Database::getInstance()->execute(
            "UPDATE expedition_collection_items
             SET submitted = 1, submitted_at = NOW(), late_reason = ?
             WHERE id = ?",
            [$lateReason, $id]
        );
    }

    /**
     * 明細を更新（paid / memo / amount を対象フィールドとする）
     */
    public static function update(int $id, array $data): ?array
    {
        $db = Database::getInstance();
        $allowedFields = ['paid', 'memo', 'amount'];
        $fields = [];
        $values = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        // 入金確認（paid=1）の場合、期限超過していたら遅延を確定記録（ペナルティ永続化）
        if (array_key_exists('paid', $data) && (int)$data['paid'] === 1 && class_exists('MemberPenalty')) {
            (new MemberPenalty())->recordSnapshot('expedition', $id);
        }

        if (!empty($fields)) {
            $values[] = $id;
            $db->execute(
                "UPDATE expedition_collection_items SET " . implode(', ', $fields) . " WHERE id = ?",
                $values
            );
        }

        return $db->fetch("SELECT * FROM expedition_collection_items WHERE id = ?", [$id]);
    }
}
