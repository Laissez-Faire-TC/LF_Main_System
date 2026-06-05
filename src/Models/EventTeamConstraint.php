<?php
/**
 * 班決め制約モデル（絶対一緒 / 絶対別）
 */
class EventTeamConstraint
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 企画IDで制約一覧を取得（両メンバーの氏名付き）
     */
    public function getByEventId(int $eventId): array
    {
        return $this->db->fetchAll(
            "SELECT c.*, ma.name_kanji AS member_a_name, mb.name_kanji AS member_b_name
             FROM event_team_constraints c
             JOIN members ma ON ma.id = c.member_a_id
             JOIN members mb ON mb.id = c.member_b_id
             WHERE c.event_id = ?
             ORDER BY c.id",
            [$eventId]
        );
    }

    /**
     * 制約を追加（重複は無視）。member_a < member_b に正規化
     */
    public function add(int $eventId, int $memberA, int $memberB, string $type): int
    {
        if ($memberA === $memberB) {
            return 0;
        }
        // ペアを正規化（小さいIDをaに）
        if ($memberA > $memberB) {
            [$memberA, $memberB] = [$memberB, $memberA];
        }

        return $this->db->insert(
            "INSERT IGNORE INTO event_team_constraints (event_id, member_a_id, member_b_id, type)
             VALUES (?, ?, ?, ?)",
            [$eventId, $memberA, $memberB, $type]
        );
    }

    /**
     * 制約を削除
     */
    public function delete(int $id): void
    {
        $this->db->execute(
            "DELETE FROM event_team_constraints WHERE id = ?",
            [$id]
        );
    }
}
