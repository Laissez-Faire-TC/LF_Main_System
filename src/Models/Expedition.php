<?php
/**
 * 遠征モデル
 */
class Expedition
{
    /**
     * 全件取得（参加者数付き）
     */
    public static function findAll(): array
    {
        return Database::getInstance()->fetchAll(
            "SELECT e.*, COUNT(ep.id) as participant_count
             FROM expeditions e
             LEFT JOIN expedition_participants ep ON ep.expedition_id = e.id
             GROUP BY e.id
             ORDER BY e.start_date DESC"
        );
    }

    /**
     * ID指定で取得
     */
    public static function findById(int $id): ?array
    {
        return Database::getInstance()->fetch(
            "SELECT * FROM expeditions WHERE id = ?",
            [$id]
        );
    }

    /**
     * 新規作成
     */
    public static function create(array $data): ?array
    {
        $sql = "INSERT INTO expeditions (
            name, start_date, end_date, deadline, application_start, base_fee, pre_night_fee, lunch_fee,
            capacity_male, capacity_female, expense_deadline, subsidy
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $id = Database::getInstance()->insert($sql, [
            $data['name'],
            $data['start_date'],
            $data['end_date'],
            $data['deadline']           ?? null,
            $data['application_start']  ?? null,
            $data['base_fee']           ?? 0,
            $data['pre_night_fee']      ?? 0,
            $data['lunch_fee']          ?? 0,
            isset($data['capacity_male'])   && $data['capacity_male']   !== '' ? (int)$data['capacity_male']   : null,
            isset($data['capacity_female']) && $data['capacity_female'] !== '' ? (int)$data['capacity_female'] : null,
            $data['expense_deadline']   ?? null,
            $data['subsidy']            ?? 0,
        ]);

        return self::findById($id);
    }

    /**
     * 更新
     */
    public static function update(int $id, array $data): ?array
    {
        $fields = [];
        $values = [];

        // 更新可能なフィールド
        $allowedFields = [
            'name', 'start_date', 'end_date', 'deadline', 'application_start',
            'base_fee', 'pre_night_fee', 'lunch_fee',
            'capacity_male', 'capacity_female', 'expense_deadline', 'subsidy',
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                // 定員は空文字→NULL
                if (in_array($field, ['capacity_male', 'capacity_female'])) {
                    $values[] = ($data[$field] !== '' && $data[$field] !== null) ? (int)$data[$field] : null;
                } else {
                    $values[] = $data[$field];
                }
            }
        }

        if (empty($fields)) {
            return self::findById($id);
        }

        $values[] = $id;
        $sql = "UPDATE expeditions SET " . implode(', ', $fields) . " WHERE id = ?";
        Database::getInstance()->execute($sql, $values);

        return self::findById($id);
    }

    /**
     * 削除（関連テーブルをトランザクション内で順次削除）
     */
    public static function delete(int $id): bool
    {
        // 削除前に遠征名を控える（削除後は引けないため、ログの対象名に使う）
        $exp = self::findById($id);
        $expName = $exp['name'] ?? '';

        // 内部は関連テーブルへの多数のDELETEに分かれるが、業務的には「遠征を削除」の1操作。
        // 異なるテーブルへのDELETEなので自動集約は効かない。group() で1行にまとめる。
        $run = fn() => self::deleteInner($id);

        if (class_exists('AuditLogger')) {
            return (bool)AuditLogger::group([
                'feature'      => 'expeditions',
                'method'       => 'DELETE',
                'target_table' => 'expeditions',
                'target_id'    => $id,
                'target_name'  => $expName,
                'action_label' => '遠征を削除（関連データ含む）',
            ], $run);
        }
        return $run();
    }

    /**
     * 削除の実体（関連テーブルをトランザクション内で順次削除）
     */
    private static function deleteInner(int $id): bool
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // 1. 集金明細を削除
            $db->execute(
                "DELETE ec FROM expedition_collection_items ec
                 INNER JOIN expedition_collections ecl ON ec.collection_id = ecl.id
                 WHERE ecl.expedition_id = ?",
                [$id]
            );
            // 2. 集金を削除
            $db->execute("DELETE FROM expedition_collections WHERE expedition_id = ?", [$id]);
            // 3. チームメンバーを削除
            $db->execute(
                "DELETE etm FROM expedition_team_members etm
                 INNER JOIN expedition_teams et ON etm.team_id = et.id
                 WHERE et.expedition_id = ?",
                [$id]
            );
            // 4. チームを削除
            $db->execute("DELETE FROM expedition_teams WHERE expedition_id = ?", [$id]);
            // 5. 立替者を削除
            $db->execute(
                "DELETE ecp FROM expedition_car_payers ecp
                 INNER JOIN expedition_cars ec ON ecp.car_id = ec.id
                 WHERE ec.expedition_id = ?",
                [$id]
            );
            // 6. 乗員を削除
            $db->execute(
                "DELETE ecm FROM expedition_car_members ecm
                 INNER JOIN expedition_cars ec ON ecm.car_id = ec.id
                 WHERE ec.expedition_id = ?",
                [$id]
            );
            // 7. 車両を削除
            $db->execute("DELETE FROM expedition_cars WHERE expedition_id = ?", [$id]);
            // 8. 参加者を削除
            $db->execute("DELETE FROM expedition_participants WHERE expedition_id = ?", [$id]);
            // 9. トークンを削除
            $db->execute("DELETE FROM expedition_tokens WHERE expedition_id = ?", [$id]);
            // 10. しおりを削除
            $db->execute("DELETE FROM expedition_booklets WHERE expedition_id = ?", [$id]);
            // 11. 遠征本体を削除
            $result = $db->execute("DELETE FROM expeditions WHERE id = ?", [$id]) > 0;

            $db->commit();
            return $result;

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
}
