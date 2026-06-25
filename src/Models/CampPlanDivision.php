<?php
/**
 * 企画班分けモデル
 * 1合宿に複数の企画（班分け単位）を持ち、各企画に参加者・班番号・制約を持つ。
 */
class CampPlanDivision
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── 企画（班分け単位） ──

    /** 合宿の企画一覧（開催タイミング情報付き） */
    public function getByCampId(int $campId): array
    {
        return $this->db->fetchAll(
            "SELECT d.*, ts.day_number, ts.slot_type
             FROM camp_plan_divisions d
             LEFT JOIN time_slots ts ON ts.id = d.time_slot_id
             WHERE d.camp_id = ? ORDER BY d.sort_order, d.id",
            [$campId]
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT d.*, ts.day_number, ts.slot_type
             FROM camp_plan_divisions d
             LEFT JOIN time_slots ts ON ts.id = d.time_slot_id
             WHERE d.id = ?",
            [$id]
        );
    }

    public function create(int $campId, string $name, ?int $timeSlotId = null): int
    {
        $row = $this->db->fetch(
            "SELECT COALESCE(MAX(sort_order), -1) + 1 AS next_order FROM camp_plan_divisions WHERE camp_id = ?",
            [$campId]
        );
        $order = (int)($row['next_order'] ?? 0);

        $id = $this->db->insert(
            "INSERT INTO camp_plan_divisions (camp_id, name, time_slot_id, sort_order) VALUES (?, ?, ?, ?)",
            [$campId, $name, $timeSlotId, $order]
        );
        if ($timeSlotId) {
            $this->syncMembersFromTimeSlot($id);
        }
        return $id;
    }

    public function rename(int $id, string $name): void
    {
        $this->db->execute(
            "UPDATE camp_plan_divisions SET name = ?, updated_at = NOW() WHERE id = ?",
            [$name, $id]
        );
    }

    /**
     * 開催タイミング（time_slot）を設定し、その時点の参加者で同期する。
     */
    public function setTimeSlot(int $id, ?int $timeSlotId): void
    {
        $this->db->execute(
            "UPDATE camp_plan_divisions SET time_slot_id = ?, updated_at = NOW() WHERE id = ?",
            [$timeSlotId, $id]
        );
        $this->syncMembersFromTimeSlot($id);
    }

    /**
     * 設定された time_slot に参加している人で、メンバーを同期する。
     * - time_slot 未設定なら全メンバーを削除（参加者なし）。
     * - 既存メンバーの班番号は保持。新たに該当した人を追加、外れた人を削除。
     */
    public function syncMembersFromTimeSlot(int $divisionId): void
    {
        $div = $this->db->fetch("SELECT time_slot_id FROM camp_plan_divisions WHERE id = ?", [$divisionId]);
        $timeSlotId = $div ? $div['time_slot_id'] : null;

        if (!$timeSlotId) {
            $this->db->execute("DELETE FROM camp_plan_division_members WHERE division_id = ?", [$divisionId]);
            return;
        }

        // その時点（何日目・どのコマ）に在席している参加者を、
        // 各参加者の join/leave から直接判定（時間帯・往復バスも考慮）。
        $presentIds = (new Participant())->getAttendingParticipantIds((int)$timeSlotId);

        $this->setMembers($divisionId, $presentIds);
    }

    public function delete(int $id): void
    {
        $this->db->execute("DELETE FROM camp_plan_divisions WHERE id = ?", [$id]);
    }

    // ── メンバー（参加者＋班番号） ──

    /**
     * 企画のメンバー一覧（参加者情報・学科付き）
     * 学科は participants → camp_applications → members 経由で取得。
     * 性別は participants.gender が空の場合、申込の修正値 → 会員マスタの順で補完する
     * （手動追加・取込などで participants.gender が未設定のケースで自動振り分けが
     * 性別を反映できない問題への対処）。
     */
    public function getMembers(int $divisionId): array
    {
        return $this->db->fetchAll(
            "SELECT dm.id, dm.participant_id, dm.team_no,
                    p.name, p.grade,
                    COALESCE(NULLIF(p.gender, ''), NULLIF(ca.edited_gender, ''), m.gender) AS gender,
                    m.faculty, m.department, m.name_kana
             FROM camp_plan_division_members dm
             JOIN participants p ON p.id = dm.participant_id
             LEFT JOIN camp_applications ca ON ca.participant_id = p.id
             LEFT JOIN members m ON m.id = ca.member_id
             WHERE dm.division_id = ?
             ORDER BY dm.id",
            [$divisionId]
        );
    }

    /**
     * メンバー集合を置き換える（参加者IDの配列）。
     * 既存の班番号は可能な限り保持する。
     */
    public function setMembers(int $divisionId, array $participantIds): void
    {
        $existing = $this->db->fetchAll(
            "SELECT participant_id FROM camp_plan_division_members WHERE division_id = ?",
            [$divisionId]
        );
        $existingIds = array_map(fn($r) => (int)$r['participant_id'], $existing);
        $newIds = array_map('intval', $participantIds);

        $toAdd    = array_values(array_diff($newIds, $existingIds));
        $toRemove = array_values(array_diff($existingIds, $newIds));

        $run = function () use ($divisionId, $toAdd, $toRemove) {
            foreach ($toAdd as $pid) {
                $this->db->insert(
                    "INSERT IGNORE INTO camp_plan_division_members (division_id, participant_id) VALUES (?, ?)",
                    [$divisionId, $pid]
                );
            }
            foreach ($toRemove as $pid) {
                $this->db->execute(
                    "DELETE FROM camp_plan_division_members WHERE division_id = ? AND participant_id = ?",
                    [$divisionId, $pid]
                );
            }
        };

        // 変化が無ければ記録しない
        if (!$toAdd && !$toRemove) { $run(); return; }

        if (class_exists('AuditLogger')) {
            $changes = [];
            if ($toAdd)    $changes['追加人数'] = count($toAdd) . '名';
            if ($toRemove) $changes['削除人数'] = count($toRemove) . '名';
            AuditLogger::group([
                'feature'      => 'camps',
                'method'       => 'PUT',
                'target_table' => 'camp_plan_divisions',
                'target_id'    => $divisionId,
                'action_label' => '企画の参加者を更新',
                'changes'      => $changes,
            ], $run);
            return;
        }
        $run();
    }

    /**
     * 班番号を一括保存
     * $assignments = [ division_member_id => team_no|null ]
     */
    public function saveTeamAssignments(int $divisionId, array $assignments): void
    {
        // 班割りの一括保存。多数のUPDATEを「班割りを更新」の1行にまとめる。
        $run = function () use ($divisionId, $assignments) {
            foreach ($assignments as $memberId => $teamNo) {
                $team = ($teamNo === null || $teamNo === '') ? null : (int)$teamNo;
                $this->db->execute(
                    "UPDATE camp_plan_division_members SET team_no = ?, updated_at = NOW()
                     WHERE id = ? AND division_id = ?",
                    [$team, (int)$memberId, $divisionId]
                );
            }
        };

        if (class_exists('AuditLogger')) {
            AuditLogger::group([
                'feature'      => 'camps',
                'method'       => 'PUT',
                'target_table' => 'camp_plan_divisions',
                'target_id'    => $divisionId,
                'action_label' => '企画の班割りを更新',
                'changes'      => ['対象人数' => count($assignments) . '名'],
            ], $run);
            return;
        }
        $run();
    }

    // ── 制約 ──

    public function getConstraints(int $divisionId): array
    {
        return $this->db->fetchAll(
            "SELECT c.*, pa.name AS participant_a_name, pb.name AS participant_b_name
             FROM camp_plan_division_constraints c
             JOIN participants pa ON pa.id = c.participant_a_id
             JOIN participants pb ON pb.id = c.participant_b_id
             WHERE c.division_id = ?
             ORDER BY c.id",
            [$divisionId]
        );
    }

    public function addConstraint(int $divisionId, int $a, int $b, string $type): void
    {
        if ($a === $b) return;
        if ($a > $b) { [$a, $b] = [$b, $a]; }
        $this->db->insert(
            "INSERT IGNORE INTO camp_plan_division_constraints
             (division_id, participant_a_id, participant_b_id, type) VALUES (?, ?, ?, ?)",
            [$divisionId, $a, $b, $type]
        );
    }

    public function deleteConstraint(int $id): void
    {
        $this->db->execute("DELETE FROM camp_plan_division_constraints WHERE id = ?", [$id]);
    }
}
