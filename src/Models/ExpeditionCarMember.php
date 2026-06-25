<?php
/**
 * 遠征車両乗車メンバーモデル
 */
class ExpeditionCarMember
{
    /**
     * 乗車メンバーを追加して作成行を返す
     */
    public static function add(int $car_id, int $member_id, string $role = 'passenger'): ?array
    {
        $db = Database::getInstance();
        $id = $db->insert(
            "INSERT INTO expedition_car_members (car_id, member_id, role) VALUES (?, ?, ?)",
            [$car_id, $member_id, $role]
        );

        return $db->fetch("SELECT * FROM expedition_car_members WHERE id = ?", [$id]);
    }

    /**
     * 乗車メンバー情報を更新して更新後の行を返す
     */
    public static function update(int $id, array $data): ?array
    {
        $db = Database::getInstance();
        $allowedFields = ['role', 'is_excluded', 'sort_order'];
        $fields = [];
        $values = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return $db->fetch("SELECT * FROM expedition_car_members WHERE id = ?", [$id]);
        }

        $values[] = $id;
        $db->execute(
            "UPDATE expedition_car_members SET " . implode(', ', $fields) . " WHERE id = ?",
            $values
        );

        return $db->fetch("SELECT * FROM expedition_car_members WHERE id = ?", [$id]);
    }

    /**
     * 乗車メンバーを削除
     */
    public static function remove(int $id): bool
    {
        return Database::getInstance()->execute(
            "DELETE FROM expedition_car_members WHERE id = ?",
            [$id]
        ) > 0;
    }

    /**
     * 並び順を一括更新
     */
    public static function updateOrder(array $items): void
    {
        $db = Database::getInstance();

        // 乗車メンバーの並び替え。多数のUPDATEを「乗車順を更新」の1行にまとめる。
        $run = function () use ($db, $items) {
            foreach ($items as $item) {
                $db->execute(
                    "UPDATE expedition_car_members SET sort_order = ? WHERE id = ?",
                    [$item['sort_order'], $item['id']]
                );
            }
        };

        if (class_exists('AuditLogger')) {
            AuditLogger::group([
                'feature'      => 'expeditions',
                'method'       => 'PUT',
                'target_table' => 'expedition_cars',
                'action_label' => '乗車順を更新',
                'changes'      => ['対象メンバー' => count($items) . '名'],
            ], $run);
            return;
        }
        $run();
    }
}
