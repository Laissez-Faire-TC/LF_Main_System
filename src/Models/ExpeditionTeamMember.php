<?php
/**
 * 遠征チームメンバーモデル
 */
class ExpeditionTeamMember
{
    /**
     * チームにメンバーを追加して作成した行を返す
     */
    public static function add(int $team_id, int $member_id): ?array
    {
        $db = Database::getInstance();
        $id = $db->insert(
            "INSERT INTO expedition_team_members (team_id, member_id) VALUES (?, ?)",
            [$team_id, $member_id]
        );

        return $db->fetch("SELECT * FROM expedition_team_members WHERE id = ?", [$id]);
    }

    /**
     * チームメンバーを削除
     */
    public static function remove(int $id): bool
    {
        return Database::getInstance()->execute(
            "DELETE FROM expedition_team_members WHERE id = ?",
            [$id]
        ) > 0;
    }

    /**
     * メンバーの並び順とチームを一括更新（チーム移動にも対応）
     * $items: [{team_id: X, members: [{id: Y, sort_order: Z}, ...]}]
     */
    public static function updateOrder(array $items): void
    {
        $db = Database::getInstance();

        // ドラッグ&ドロップによる並び替え・チーム移動。多数のUPDATEに分かれるが、
        // 個別の値は意味が薄いので「チーム分け・並び順を更新」の1行にまとめる。
        $count = 0;
        foreach ($items as $item) { $count += count($item['members'] ?? []); }

        $run = function () use ($db, $items) {
            foreach ($items as $item) {
                $team_id = $item['team_id'];
                foreach (($item['members'] ?? []) as $member) {
                    $db->execute(
                        "UPDATE expedition_team_members SET team_id = ?, sort_order = ? WHERE id = ?",
                        [$team_id, $member['sort_order'], $member['id']]
                    );
                }
            }
        };

        if (class_exists('AuditLogger')) {
            AuditLogger::group([
                'feature'      => 'expeditions',
                'method'       => 'PUT',
                'target_table' => 'expedition_teams',
                'action_label' => 'チーム分け・並び順を更新',
                'changes'      => ['対象メンバー' => $count . '名'],
            ], $run);
            return;
        }
        $run();
    }

    /**
     * メンバーを別チームへ移動
     */
    public static function moveMember(int $member_id, int $from_team_id, int $to_team_id): bool
    {
        return Database::getInstance()->execute(
            "UPDATE expedition_team_members SET team_id = ? WHERE member_id = ? AND team_id = ?",
            [$to_team_id, $member_id, $from_team_id]
        ) > 0;
    }
}
