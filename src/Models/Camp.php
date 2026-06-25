<?php
/**
 * 合宿モデル
 */
class Camp
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 全件取得
     */
    public function all(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM camps ORDER BY start_date DESC"
        );
    }

    /**
     * ID指定で取得
     */
    public function find(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM camps WHERE id = ?",
            [$id]
        );
    }

    /**
     * 新規作成
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO camps (
            name, start_date, end_date, nights,
            lodging_fee_per_night, hot_spring_tax,
            breakfast_add_price, breakfast_remove_price,
            lunch_add_price, lunch_remove_price,
            dinner_add_price, dinner_remove_price,
            insurance_fee, court_fee_per_unit, gym_fee_per_unit, banquet_fee_per_person, first_day_lunch_included,
            bus_fee_round_trip, bus_fee_separate,
            bus_fee_outbound, bus_fee_return,
            highway_fee_outbound, highway_fee_return,
            use_rental_car, rental_car_fee, rental_car_highway_fee, rental_car_capacity
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        return $this->db->insert($sql, [
            $data['name'],
            $data['start_date'],
            $data['end_date'],
            $data['nights'],
            $data['lodging_fee_per_night'] ?? 0,
            $data['hot_spring_tax'] ?? 0,
            $data['breakfast_add_price'] ?? 0,
            $data['breakfast_remove_price'] ?? 0,
            $data['lunch_add_price'] ?? 0,
            $data['lunch_remove_price'] ?? 0,
            $data['dinner_add_price'] ?? 0,
            $data['dinner_remove_price'] ?? 0,
            $data['insurance_fee'] ?? 0,
            $data['court_fee_per_unit'] ?? null,
            $data['gym_fee_per_unit'] ?? null,
            $data['banquet_fee_per_person'] ?? null,
            $data['first_day_lunch_included'] ?? 0,
            $data['bus_fee_round_trip'] ?? null,
            $data['bus_fee_separate'] ?? 0,
            $data['bus_fee_outbound'] ?? null,
            $data['bus_fee_return'] ?? null,
            $data['highway_fee_outbound'] ?? null,
            $data['highway_fee_return'] ?? null,
            $data['use_rental_car'] ?? 0,
            $data['rental_car_fee'] ?? null,
            $data['rental_car_highway_fee'] ?? null,
            $data['rental_car_capacity'] ?? null,
        ]);
    }

    /**
     * 更新
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];

        $allowedFields = [
            'name', 'start_date', 'end_date', 'nights',
            'lodging_fee_per_night', 'hot_spring_tax',
            'breakfast_add_price', 'breakfast_remove_price',
            'lunch_add_price', 'lunch_remove_price',
            'dinner_add_price', 'dinner_remove_price',
            'insurance_fee', 'court_fee_per_unit', 'gym_fee_per_unit', 'banquet_fee_per_person', 'first_day_lunch_included',
            'bus_fee_round_trip', 'bus_fee_separate',
            'bus_fee_outbound', 'bus_fee_return',
            'highway_fee_outbound', 'highway_fee_return',
            'use_rental_car', 'rental_car_fee', 'rental_car_highway_fee', 'rental_car_capacity',
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = "UPDATE camps SET " . implode(', ', $fields) . " WHERE id = ?";

        return $this->db->execute($sql, $values) > 0;
    }

    /**
     * 削除（関連データも含めて削除）
     */
    public function delete(int $id): bool
    {
        // 削除前に合宿名を控える（削除後は引けないため、ログの対象名に使う）
        $camp = $this->find($id);
        $campName = $camp['name'] ?? '';

        // 内部は関連テーブルへの複数DELETEに分かれるが、業務的には「合宿を削除」の1操作。
        // 異なるテーブルへのDELETEなので自動集約は効かない。group() で1行にまとめる。
        $run = fn() => $this->deleteInner($id);

        if (class_exists('AuditLogger')) {
            return (bool)AuditLogger::group([
                'feature'      => 'camps',
                'method'       => 'DELETE',
                'target_table' => 'camps',
                'target_id'    => $id,
                'target_name'  => $campName,
                'action_label' => '合宿を削除（関連データ含む）',
            ], $run);
        }
        return $run();
    }

    /**
     * 削除の実体（関連データも含めて削除）
     */
    private function deleteInner(int $id): bool
    {
        $this->db->beginTransaction();

        try {
            // 参加者に紐づくデータを削除
            // 1. 食事調整を削除
            $this->db->execute(
                "DELETE ma FROM meal_adjustments ma
                 INNER JOIN participants p ON ma.participant_id = p.id
                 WHERE p.camp_id = ?",
                [$id]
            );

            // 2. 参加者スロットを削除
            $this->db->execute(
                "DELETE ps FROM participant_slots ps
                 INNER JOIN participants p ON ps.participant_id = p.id
                 WHERE p.camp_id = ?",
                [$id]
            );

            // 3. 参加者を削除
            $this->db->execute("DELETE FROM participants WHERE camp_id = ?", [$id]);

            // 4. タイムスロットを削除
            $this->db->execute("DELETE FROM time_slots WHERE camp_id = ?", [$id]);

            // 5. 雑費を削除
            $this->db->execute("DELETE FROM expenses WHERE camp_id = ?", [$id]);

            // 6. 申し込みを削除
            $this->db->execute("DELETE FROM camp_applications WHERE camp_id = ?", [$id]);

            // 7. 合宿を削除
            $result = $this->db->execute("DELETE FROM camps WHERE id = ?", [$id]) > 0;

            $this->db->commit();
            return $result;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * 複製
     */
    public function duplicate(int $id, array $overrideData = []): ?int
    {
        $original = $this->find($id);
        if (!$original) {
            return null;
        }

        // IDと日時を除外
        unset($original['id'], $original['created_at'], $original['updated_at']);

        // 名前に「(コピー)」を追加
        $original['name'] = $original['name'] . '（コピー）';

        // オーバーライドデータをマージ
        $newData = array_merge($original, $overrideData);

        $this->db->beginTransaction();

        try {
            // 合宿を複製
            $newCampId = $this->create($newData);

            // タイムスロットを複製
            $timeSlotModel = new TimeSlot();
            $timeSlotModel->duplicateForCamp($id, $newCampId);

            // 雑費を複製
            $expenseModel = new Expense();
            $expenseModel->duplicateForCamp($id, $newCampId);

            $this->db->commit();
            return $newCampId;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
