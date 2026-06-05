<?php
/**
 * 参加者モデル
 */
class Participant
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
            "SELECT * FROM participants WHERE camp_id = ? ORDER BY name",
            [$campId]
        );
    }

    /**
     * 指定タイムスロット（time_slot）に在席している参加者IDの配列を返す。
     * participant_slots に依存せず、各参加者の join/leave から直接判定する
     * （コマを後から追加した場合でも正しくカウントできる）。
     */
    public function getAttendingParticipantIds(int $timeSlotId): array
    {
        $slot = $this->db->fetch("SELECT * FROM time_slots WHERE id = ?", [$timeSlotId]);
        if (!$slot) {
            return [];
        }

        $participants = $this->db->fetchAll(
            "SELECT * FROM participants WHERE camp_id = ?",
            [$slot['camp_id']]
        );

        $ids = [];
        foreach ($participants as $p) {
            if ($this->checkSlotAttendance($p, $slot)) {
                $ids[] = (int)$p['id'];
            }
        }
        return $ids;
    }

    /**
     * ID指定で取得
     */
    public function find(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM participants WHERE id = ?",
            [$id]
        );
    }

    /**
     * 新規作成
     */
    /**
     * 申し込み時点で3年生がOB/OGに該当するか判定し、該当すれば grade=0 に変換する
     * 10月以降は3年生をOB/OG扱い（合宿参加登録は実際の時点で行われるため月判定で十分）
     */
    private function resolveGrade(?int $grade): ?int
    {
        if ($grade !== 3) {
            return $grade;
        }
        $month = (int)date('n');
        return ($month >= 10) ? 0 : 3;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO participants (
            camp_id, name, grade, gender, allergy, join_day, join_timing, leave_day, leave_timing,
            use_outbound_bus, use_return_bus, use_rental_car
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // 合宿の総日数を取得（leave_timingのデフォルト値判定に使用）
        $camp = (new Camp())->find($data['camp_id']);
        $totalDays = $camp ? $camp['nights'] + 1 : 4;

        // join_dayのデフォルト値
        $joinDay = $data['join_day'] ?? 1;
        $leaveDay = $data['leave_day'] ?? $totalDays;

        // join_timingのデフォルト値（1日目は往路バス、それ以外は午前イベント）
        $defaultJoinTiming = ($joinDay === 1) ? 'outbound_bus' : 'morning';
        $joinTiming = $data['join_timing'] ?? $defaultJoinTiming;

        // leave_timingのデフォルト値（最終日は復路バス、それ以外は昼食後）
        $defaultLeaveTiming = ($leaveDay === $totalDays) ? 'return_bus' : 'after_lunch';
        $leaveTiming = $data['leave_timing'] ?? $defaultLeaveTiming;

        // 申し込み時点でOB/OGに該当する場合はgrade=0に確定
        $grade = isset($data['grade']) ? $this->resolveGrade((int)$data['grade']) : null;

        $participantId = $this->db->insert($sql, [
            $data['camp_id'],
            $data['name'],
            $grade,
            $data['gender'] ?? null,
            $data['allergy'] ?? null,
            $joinDay,
            $joinTiming,
            $leaveDay,
            $leaveTiming,
            $data['use_outbound_bus'] ?? 1,
            $data['use_return_bus'] ?? 1,
            $data['use_rental_car'] ?? 0,
        ]);

        // 参加スロットを自動生成
        $this->generateParticipantSlots($participantId);
        // 食事調整は CalculationService で自動計算されるため、ここでは生成しない
        // $this->generateMealAdjustments($participantId);

        return $participantId;
    }

    /**
     * 更新
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];

        $allowedFields = [
            'name', 'grade', 'gender', 'allergy', 'join_day', 'join_timing', 'leave_day', 'leave_timing',
            'use_outbound_bus', 'use_return_bus', 'use_rental_car',
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                // 申し込み時点でOB/OGに該当する場合はgrade=0に確定
                $values[] = ($field === 'grade' && $data[$field] !== null)
                    ? $this->resolveGrade((int)$data[$field])
                    : $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = "UPDATE participants SET " . implode(', ', $fields) . " WHERE id = ?";

        $result = $this->db->execute($sql, $values) > 0;

        // 参加スロットを再生成
        if ($result) {
            $this->generateParticipantSlots($id);
            // 食事調整は CalculationService で自動計算されるため、ここでは生成しない
            // $this->generateMealAdjustments($id);
        }

        return $result;
    }

    /**
     * 削除
     */
    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM participants WHERE id = ?", [$id]) > 0;
    }

    /**
     * 参加スロットを自動生成
     */
    private function generateParticipantSlots(int $participantId): void
    {
        $participant = $this->find($participantId);
        if (!$participant) {
            return;
        }

        // 既存のスロットを削除
        $this->db->execute(
            "DELETE FROM participant_slots WHERE participant_id = ?",
            [$participantId]
        );

        // タイムスロットを取得
        $timeSlotModel = new TimeSlot();
        $slots = $timeSlotModel->getByCampId($participant['camp_id']);

        foreach ($slots as $slot) {
            $isAttending = $this->checkSlotAttendance($participant, $slot);

            $this->db->execute(
                "INSERT INTO participant_slots (participant_id, time_slot_id, is_attending)
                 VALUES (?, ?, ?)",
                [$participantId, $slot['id'], $isAttending ? 1 : 0]
            );
        }
    }

    /**
     * スロットへの参加チェック
     */
    private function checkSlotAttendance(array $participant, array $slot): bool
    {
        $joinDay = $participant['join_day'];
        $leaveDay = $participant['leave_day'];
        $joinTiming = $participant['join_timing'];
        $leaveTiming = $participant['leave_timing'];
        $slotDay = $slot['day_number'];
        $slotType = $slot['slot_type'];

        // 参加期間外は不参加
        if ($slotDay < $joinDay || $slotDay > $leaveDay) {
            return false;
        }

        // 参加開始日のチェック
        if ($slotDay === $joinDay) {
            $joinOrder = $this->getTimingOrder($joinTiming);
            $slotOrder = $this->getSlotOrder($slotType);

            if ($slotOrder < $joinOrder) {
                return false;
            }
        }

        // 離脱日のチェック
        if ($slotDay === $leaveDay) {
            $leaveOrder = $this->getLeaveTimingOrder($leaveTiming);
            $slotOrder = $this->getSlotOrder($slotType);

            if ($slotOrder > $leaveOrder) {
                return false;
            }
        }

        // 往路バス
        if ($slotType === 'outbound' && !$participant['use_outbound_bus']) {
            return false;
        }

        // 復路バス
        if ($slotType === 'return' && !$participant['use_return_bus']) {
            return false;
        }

        return true;
    }

    /**
     * 参加タイミングの順序
     */
    private function getTimingOrder(string $timing): int
    {
        $order = [
            'outbound_bus' => 0,  // 往路バスから（1日目のみ）
            'morning' => 1,
            'lunch' => 2,
            'afternoon' => 3,
            'dinner' => 4,
            'night' => 5,
        ];
        return $order[$timing] ?? 0;
    }

    /**
     * 離脱タイミングの順序
     */
    private function getLeaveTimingOrder(string $timing): int
    {
        $order = [
            'morning' => 0,
            'after_breakfast' => 1,
            'lunch' => 2,
            'after_lunch' => 3,
            'afternoon' => 4,
            'dinner' => 5,
            'night' => 6,
        ];
        return $order[$timing] ?? 3;
    }

    /**
     * スロットタイプの順序
     */
    private function getSlotOrder(string $slotType): int
    {
        $order = [
            'outbound' => 1,
            'morning' => 2,
            'afternoon' => 3,
            'banquet' => 4,
            'return' => 3, // 午後と同等
        ];
        return $order[$slotType] ?? 2;
    }

    /**
     * 食事調整を自動生成
     */
    private function generateMealAdjustments(int $participantId): void
    {
        $participant = $this->find($participantId);
        if (!$participant) {
            return;
        }

        // 既存の調整を削除
        $this->db->execute(
            "DELETE FROM meal_adjustments WHERE participant_id = ?",
            [$participantId]
        );

        $camp = (new Camp())->find($participant['camp_id']);
        if (!$camp) {
            return;
        }

        $joinDay = $participant['join_day'];
        $joinTiming = $participant['join_timing'];
        $leaveDay = $participant['leave_day'];
        $leaveTiming = $participant['leave_timing'];
        $days = $camp['nights'] + 1;

        // 参加日の食事調整
        if ($joinTiming === 'lunch') {
            // 昼食から参加 → 昼食追加
            $this->addMealAdjustment($participantId, $joinDay, 'lunch', 'add');
        } elseif ($joinTiming === 'night') {
            // 夕食後から参加 → 夕食欠食
            $this->addMealAdjustment($participantId, $joinDay, 'dinner', 'remove');
        }

        // 参加日より前の日の食事は欠食（1日目の昼食は除く）
        for ($day = 1; $day < $joinDay; $day++) {
            if ($day > 1 || $camp['first_day_lunch_included']) {
                // 1日目以外、または1日目昼食が対象の場合
            }
            // 全ての食事を欠食
            $this->addMealAdjustment($participantId, $day, 'dinner', 'remove');
            if ($day < $days) {
                $this->addMealAdjustment($participantId, $day + 1, 'breakfast', 'remove');
                $this->addMealAdjustment($participantId, $day + 1, 'lunch', 'remove');
            }
        }

        // 離脱日の食事調整
        if ($leaveTiming === 'morning' || $leaveTiming === 'after_breakfast') {
            // 朝食後に離脱 → 昼食欠食
            $this->addMealAdjustment($participantId, $leaveDay, 'lunch', 'remove');
        }

        // 離脱日より後の日の食事は欠食
        for ($day = $leaveDay + 1; $day <= $days; $day++) {
            $this->addMealAdjustment($participantId, $day, 'breakfast', 'remove');
            $this->addMealAdjustment($participantId, $day, 'lunch', 'remove');
            if ($day < $days) {
                $this->addMealAdjustment($participantId, $day, 'dinner', 'remove');
            }
        }
    }

    /**
     * 食事調整レコード追加
     */
    private function addMealAdjustment(int $participantId, int $dayNumber, string $mealType, string $adjustmentType): void
    {
        $this->db->execute(
            "INSERT IGNORE INTO meal_adjustments (participant_id, day_number, meal_type, adjustment_type)
             VALUES (?, ?, ?, ?)",
            [$participantId, $dayNumber, $mealType, $adjustmentType]
        );
    }

    /**
     * 参加者の参加スロット取得
     */
    public function getParticipantSlots(int $participantId): array
    {
        return $this->db->fetchAll(
            "SELECT ps.*, ts.day_number, ts.slot_type, ts.activity_type, ts.facility_fee, ts.description
             FROM participant_slots ps
             JOIN time_slots ts ON ps.time_slot_id = ts.id
             WHERE ps.participant_id = ?
             ORDER BY ts.day_number, FIELD(ts.slot_type, 'outbound', 'morning', 'afternoon', 'banquet', 'return')",
            [$participantId]
        );
    }

    /**
     * 参加者の食事調整取得
     */
    public function getMealAdjustments(int $participantId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM meal_adjustments WHERE participant_id = ? ORDER BY day_number, meal_type",
            [$participantId]
        );
    }

    /**
     * 参加者数をカウント（合宿ID指定）
     */
    public function countByCampId(int $campId): int
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM participants WHERE camp_id = ?",
            [$campId]
        );
        return $result['count'] ?? 0;
    }

    /**
     * CSVから一括登録
     *
     * 対応形式:
     * 1. 名前,学年性別 (例: 山田太郎,1男)
     * 2. 名前,性別,学年 (例: 山田太郎,男,2)
     */
    public function bulkCreateFromCsv(int $campId, array $rows): array
    {
        $camp = (new Camp())->find($campId);
        if (!$camp) {
            return ['success' => 0, 'errors' => ['合宿が見つかりません']];
        }

        $days = $camp['nights'] + 1;
        $success = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $lineNum = $index + 1;

            // 空行スキップ
            if (empty($row[0])) {
                continue;
            }

            $name = trim($row[0]);
            $grade = null;
            $gender = null;

            // 形式を判定: 3列なら「名前,性別,学年」形式
            if (isset($row[2]) && !empty(trim($row[2]))) {
                // 形式2: 名前,性別,学年
                $genderStr = trim($row[1]);
                $gradeStr = mb_convert_kana(trim($row[2]), 'n'); // 全角数字を半角に

                // 性別をパース
                if (in_array($genderStr, ['男', '男性', 'male', 'M', 'm'])) {
                    $gender = 'male';
                } elseif (in_array($genderStr, ['女', '女性', 'female', 'F', 'f'])) {
                    $gender = 'female';
                }

                // 学年をパース
                if (preg_match('/^([1-4])年?$/', $gradeStr, $matches)) {
                    $grade = (int)$matches[1];
                } elseif (strtoupper($gradeStr) === 'OB' || strtoupper($gradeStr) === 'OG') {
                    $grade = 0;
                    // OB/OGの場合、性別も設定（性別列が空の場合のみ）
                    if ($gender === null) {
                        $gender = strtoupper($gradeStr) === 'OB' ? 'male' : 'female';
                    }
                } elseif (is_numeric($gradeStr) && (int)$gradeStr >= 0 && (int)$gradeStr <= 4) {
                    $grade = (int)$gradeStr;
                }
            } else {
                // 形式1: 名前,学年性別 (従来形式)
                $gradeGender = isset($row[1]) ? trim($row[1]) : '';

                // 学年・性別をパース (1男、1女、2男、2女、3男、3女、OB、OG、OBOG)
                // 全角数字を半角に変換
                $gradeGender = mb_convert_kana($gradeGender, 'n');

                if (preg_match('/^([1-4])([男女])$/u', $gradeGender, $matches)) {
                    $grade = (int)$matches[1];
                    $gender = $matches[2] === '男' ? 'male' : 'female';
                } elseif (preg_match('/^([1-4])年([男女])$/u', $gradeGender, $matches)) {
                    // 「1年男」「2年女」などの形式にも対応
                    $grade = (int)$matches[1];
                    $gender = $matches[2] === '男' ? 'male' : 'female';
                } elseif (strtoupper($gradeGender) === 'OB') {
                    $grade = 0;
                    $gender = 'male';
                } elseif (strtoupper($gradeGender) === 'OG') {
                    $grade = 0;
                    $gender = 'female';
                } elseif (strtoupper($gradeGender) === 'OBOG') {
                    $grade = 0;
                    $gender = null;
                }
            }

            try {
                $this->create([
                    'camp_id' => $campId,
                    'name' => $name,
                    'grade' => $grade,
                    'gender' => $gender,
                    'join_day' => 1,
                    'join_timing' => 'outbound_bus',  // 1日目は往路バスから
                    'leave_day' => $days,
                    'leave_timing' => 'return_bus',   // 最終日は復路バスまで
                    'use_outbound_bus' => 1,
                    'use_return_bus' => 1,
                    'use_rental_car' => 0,
                ]);
                $success++;
            } catch (Exception $e) {
                $errors[] = "{$lineNum}行目: {$name} の登録に失敗しました";
            }
        }

        return ['success' => $success, 'errors' => $errors];
    }

    /**
     * 同姓同名・同学年の参加者を検索（重複チェック用）
     *
     * @param int $campId 合宿ID
     * @param string $name 名前
     * @param int|null $grade 学年
     * @param int|null $excludeId 除外するID（編集時用）
     * @return array 該当する参加者のリスト
     */
    public function findDuplicates(int $campId, string $name, ?int $grade = null, ?int $excludeId = null): array
    {
        $sql = "SELECT * FROM participants WHERE camp_id = ? AND name = ?";
        $params = [$campId, $name];

        if ($grade !== null) {
            $sql .= " AND grade = ?";
            $params[] = $grade;
        } else {
            $sql .= " AND grade IS NULL";
        }

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * 合宿内の重複参加者を全て取得（名前+学年が同じ組み合わせ）
     *
     * @param int $campId 合宿ID
     * @return array 重複している参加者IDのリスト
     */
    public function getDuplicateIds(int $campId): array
    {
        // 名前と学年が同じ参加者が2人以上いる組み合わせを検索
        $sql = "SELECT GROUP_CONCAT(id) as ids
                FROM participants
                WHERE camp_id = ?
                GROUP BY name, COALESCE(grade, -1)
                HAVING COUNT(*) > 1";

        $results = $this->db->fetchAll($sql, [$campId]);

        $duplicateIds = [];
        foreach ($results as $row) {
            $ids = explode(',', $row['ids']);
            foreach ($ids as $id) {
                $duplicateIds[] = (int)$id;
            }
        }

        return $duplicateIds;
    }
}
