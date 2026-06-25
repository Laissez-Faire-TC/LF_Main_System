<?php
/**
 * タイムスロットモデル
 */
class TimeSlot
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 合宿IDで取得
     */
    public function getByCampId(int $campId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM time_slots WHERE camp_id = ? ORDER BY day_number,
             FIELD(slot_type, 'outbound', 'morning', 'afternoon', 'banquet', 'return')",
            [$campId]
        );
    }

    /**
     * ID指定で取得
     */
    public function find(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM time_slots WHERE id = ?",
            [$id]
        );
    }

    /**
     * 新規作成
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO time_slots (
            camp_id, day_number, slot_type, activity_type, facility_fee, court_count, description
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        return $this->db->insert($sql, [
            $data['camp_id'],
            $data['day_number'],
            $data['slot_type'],
            $data['activity_type'] ?? null,
            $data['facility_fee'] ?? null,
            $data['court_count'] ?? 1,
            $data['description'] ?? null,
        ]);
    }

    /**
     * 更新
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];

        $allowedFields = ['activity_type', 'facility_fee', 'court_count', 'description'];

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
        $sql = "UPDATE time_slots SET " . implode(', ', $fields) . " WHERE id = ?";

        return $this->db->execute($sql, $values) > 0;
    }

    /**
     * 削除
     */
    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM time_slots WHERE id = ?", [$id]) > 0;
    }

    /**
     * 合宿のタイムスロットを一括更新
     */
    public function updateByCampId(int $campId, array $slots): void
    {
        // 内部は「全削除→全件再INSERT」の差し替え。スロットを1つ足しただけでも全件が
        // 入れ替わるため、個別の作成ログを出すと「やっていない作成」が並んで誤解を招く。
        // before/after を比較して「追加/削除したスロット」を1行にまとめて記録する。
        $before = $this->getByCampId($campId);

        $run = function () use ($campId, $slots) {
            // 既存のスロットを削除
            $this->db->execute("DELETE FROM time_slots WHERE camp_id = ?", [$campId]);

            // 新しいスロットを挿入
            foreach ($slots as $slot) {
                $slot['camp_id'] = $campId;
                $this->create($slot);
            }
        };

        if (class_exists('AuditLogger')) {
            $diff = self::diffSlots($before, $slots);
            AuditLogger::group([
                'feature'      => 'camps',
                'method'       => 'PUT',
                'target_table' => 'camps',
                'target_id'    => $campId,
                'action_label' => 'タイムスケジュールを更新',
                'changes'      => $diff,
            ], $run);
            return;
        }
        $run();
    }

    /** slot_type の日本語ラベル */
    private const SLOT_TYPE_LABELS = [
        'outbound'  => '往路',
        'morning'   => '午前',
        'afternoon' => '午後',
        'banquet'   => '宴会',
        'return'    => '復路',
    ];

    /**
     * スロット1件を「3日目の宴会」のような人間向けの名前にする。
     * description があれば添える（例: 「3日目の宴会（懇親会）」）。
     */
    private static function slotName(array $slot): string
    {
        $day  = (int)($slot['day_number'] ?? 0);
        $type = (string)($slot['slot_type'] ?? '');
        $typeLabel = self::SLOT_TYPE_LABELS[$type] ?? $type;
        $name = ($day > 0 ? "{$day}日目の" : '') . $typeLabel;
        $desc = trim((string)($slot['description'] ?? ''));
        if ($desc !== '' && $desc !== $typeLabel) {
            $name .= "（{$desc}）";
        }
        return $name;
    }

    /**
     * スロット集合の内容キー。安定IDが無いので内容で同一性を判定する。
     */
    private static function slotKey(array $slot): string
    {
        return implode('|', [
            (int)($slot['day_number'] ?? 0),
            (string)($slot['slot_type'] ?? ''),
            (string)($slot['activity_type'] ?? ''),
            (string)($slot['description'] ?? ''),
            (string)($slot['facility_fee'] ?? ''),
            (string)($slot['court_count'] ?? ''),
        ]);
    }

    /**
     * before（保存前）と after（保存内容）を比較し、追加/削除されたスロット名を返す。
     * 件数の増減も添える。変化が無ければ件数のみ。
     *
     * @return array changes_json 用の配列
     */
    private static function diffSlots(array $before, array $after): array
    {
        $beforeKeys = [];
        foreach ($before as $s) { $beforeKeys[self::slotKey($s)] = $s; }
        $afterKeys = [];
        foreach ($after as $s)  { $afterKeys[self::slotKey($s)]  = $s; }

        $added = [];
        foreach ($afterKeys as $k => $s) {
            if (!isset($beforeKeys[$k])) $added[] = self::slotName($s);
        }
        $removed = [];
        foreach ($beforeKeys as $k => $s) {
            if (!isset($afterKeys[$k])) $removed[] = self::slotName($s);
        }

        $changes = ['スロット数' => count($before) . '件 → ' . count($after) . '件'];
        if ($added)   $changes['追加'] = $added;
        if ($removed) $changes['削除'] = $removed;
        // 件数も内容も変化なし（再保存のみ）の場合は、その旨を明示する
        if (!$added && !$removed && count($before) === count($after)) {
            $changes['内容'] = '変更なし（再保存）';
        }
        return $changes;
    }

    /**
     * デフォルトのタイムスロットを生成
     * @param int $campId 合宿ID
     * @param int $nights 泊数
     * @param int|null $courtFeePerUnit コート単価（テニス用）
     * @param int|null $gymFeePerUnit 体育館単価
     */
    public function createDefaultSlots(int $campId, int $nights, ?int $courtFeePerUnit = null, ?int $gymFeePerUnit = null): void
    {
        $days = $nights + 1;
        // デフォルトのコート数は1
        $defaultCourtCount = 1;
        // テニスのfacility_feeを計算（コート単価 × コート数）
        $tennisFee = $courtFeePerUnit ? $courtFeePerUnit * $defaultCourtCount : 0;

        for ($day = 1; $day <= $days; $day++) {
            // 1日目は往路
            if ($day === 1) {
                $this->create([
                    'camp_id' => $campId,
                    'day_number' => $day,
                    'slot_type' => 'outbound',
                    'activity_type' => 'bus',
                    'description' => 'バス移動（往路）',
                ]);
            }

            // 最終日以外は午前スロット
            if ($day > 1) {
                $this->create([
                    'camp_id' => $campId,
                    'day_number' => $day,
                    'slot_type' => 'morning',
                    'activity_type' => 'tennis',
                    'facility_fee' => $tennisFee,
                    'court_count' => $defaultCourtCount,
                    'description' => 'テニスコート',
                ]);
            }

            // 午後スロット（最終日は除く）
            if ($day < $days) {
                $this->create([
                    'camp_id' => $campId,
                    'day_number' => $day,
                    'slot_type' => 'afternoon',
                    'activity_type' => 'tennis',
                    'facility_fee' => $tennisFee,
                    'court_count' => $defaultCourtCount,
                    'description' => 'テニスコート',
                ]);
            }

            // 最終日は復路
            if ($day === $days) {
                $this->create([
                    'camp_id' => $campId,
                    'day_number' => $day,
                    'slot_type' => 'return',
                    'activity_type' => 'bus',
                    'description' => 'バス移動（復路）',
                ]);
            }
        }
    }

    /**
     * 合宿複製用
     */
    public function duplicateForCamp(int $originalCampId, int $newCampId): void
    {
        $slots = $this->getByCampId($originalCampId);

        foreach ($slots as $slot) {
            unset($slot['id']);
            $slot['camp_id'] = $newCampId;
            $this->create($slot);
        }
    }
}
