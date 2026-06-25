<?php
/**
 * 遠征チームモデル
 */
class ExpeditionTeam
{
    /**
     * 遠征IDに紐づくチーム一覧を取得（メンバー情報を含む）
     */
    public static function findByExpedition(int $expedition_id): array
    {
        $db = Database::getInstance();
        $teams = $db->fetchAll(
            "SELECT * FROM expedition_teams WHERE expedition_id = ? ORDER BY sort_order",
            [$expedition_id]
        );

        foreach ($teams as &$team) {
            $team['members'] = $db->fetchAll(
                "SELECT etm.*, m.name_kanji, m.name_kana, m.gender, m.grade, m.enrollment_year
                 FROM expedition_team_members etm
                 JOIN members m ON m.id = etm.member_id
                 WHERE etm.team_id = ?
                 ORDER BY etm.sort_order",
                [$team['id']]
            );
        }

        return $teams;
    }

    /**
     * チームを新規作成して作成した行を返す
     */
    public static function create(int $expedition_id, string $name): ?array
    {
        $db = Database::getInstance();
        $id = $db->insert(
            "INSERT INTO expedition_teams (expedition_id, name, sort_order) VALUES (?, ?, 0)",
            [$expedition_id, $name]
        );

        return $db->fetch("SELECT * FROM expedition_teams WHERE id = ?", [$id]);
    }

    /**
     * チーム情報を更新して更新後の行を返す
     */
    public static function update(int $id, array $data): ?array
    {
        $db = Database::getInstance();
        $allowedFields = ['name', 'sort_order'];
        $fields = [];
        $values = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return $db->fetch("SELECT * FROM expedition_teams WHERE id = ?", [$id]);
        }

        $values[] = $id;
        $db->execute(
            "UPDATE expedition_teams SET " . implode(', ', $fields) . " WHERE id = ?",
            $values
        );

        return $db->fetch("SELECT * FROM expedition_teams WHERE id = ?", [$id]);
    }

    /**
     * チームを削除（メンバー情報も含めてトランザクション内で削除）
     */
    public static function delete(int $id): bool
    {
        $db = Database::getInstance();

        // メンバー＋チーム本体のDELETEは「チームを削除」の1操作。1行にまとめる。
        $run = function () use ($db, $id) {
            $db->beginTransaction();
            try {
                $db->execute("DELETE FROM expedition_team_members WHERE team_id = ?", [$id]);
                $result = $db->execute("DELETE FROM expedition_teams WHERE id = ?", [$id]) > 0;
                $db->commit();
                return $result;
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
        };

        if (class_exists('AuditLogger')) {
            return (bool)AuditLogger::group([
                'feature'      => 'expeditions',
                'method'       => 'DELETE',
                'target_table' => 'expedition_teams',
                'target_id'    => $id,
                'action_label' => 'チームを削除（メンバー含む）',
            ], $run);
        }
        return $run();
    }
}
