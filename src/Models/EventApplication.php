<?php
/**
 * 企画申し込みモデル
 */
class EventApplication
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 参加確定者一覧（status = submitted）
     */
    public function getByEventId(int $eventId): array
    {
        return $this->db->fetchAll(
            "SELECT ea.*, m.name_kanji, m.name_kana, m.student_id, m.grade, m.gender,
                    m.faculty, m.department, m.line_name
             FROM event_applications ea
             JOIN members m ON m.id = ea.member_id
             WHERE ea.event_id = ? AND ea.status = 'submitted'
             ORDER BY ea.created_at ASC",
            [$eventId]
        );
    }

    /**
     * キャンセル待ち一覧（status = waitlisted、登録順）
     */
    public function getWaitlistByEventId(int $eventId): array
    {
        return $this->db->fetchAll(
            "SELECT ea.*, m.name_kanji, m.name_kana, m.student_id, m.grade, m.gender,
                    m.faculty, m.department, m.line_name
             FROM event_applications ea
             JOIN members m ON m.id = ea.member_id
             WHERE ea.event_id = ? AND ea.status = 'waitlisted'
             ORDER BY ea.created_at ASC",
            [$eventId]
        );
    }

    /**
     * 参加確定人数（submitted のみ）
     */
    public function countByEventId(int $eventId): int
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS cnt FROM event_applications WHERE event_id = ? AND status = 'submitted'",
            [$eventId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * キャンセル待ち人数
     */
    public function countWaitlistByEventId(int $eventId): int
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS cnt FROM event_applications WHERE event_id = ? AND status = 'waitlisted'",
            [$eventId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * 会員の申込状況確認
     */
    public function findByEventAndMember(int $eventId, int $memberId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM event_applications WHERE event_id = ? AND member_id = ?",
            [$eventId, $memberId]
        );
    }

    /**
     * 申し込み（UPSERT）
     * $status = 'submitted' or 'waitlisted'
     */
    public function apply(int $eventId, int $memberId, string $status = 'submitted', ?string $note = null): bool
    {
        $existing = $this->findByEventAndMember($eventId, $memberId);

        if ($existing) {
            // キャンセル後の再申し込みは created_at をリセットしてキャンセル待ち末尾に並ぶようにする
            $resetCreatedAt = ($existing['status'] === 'cancelled');
            $sql = $resetCreatedAt
                ? "UPDATE event_applications SET status = ?, note = ?, promoted = 0, created_at = NOW(), updated_at = NOW()
                   WHERE event_id = ? AND member_id = ?"
                : "UPDATE event_applications SET status = ?, note = ?, promoted = 0, updated_at = NOW()
                   WHERE event_id = ? AND member_id = ?";
            return $this->db->execute($sql, [$status, $note, $eventId, $memberId]) >= 0;
        }

        $this->db->insert(
            "INSERT INTO event_applications (event_id, member_id, status, note, promoted) VALUES (?, ?, ?, ?, 0)",
            [$eventId, $memberId, $status, $note]
        );
        return true;
    }

    /**
     * キャンセル
     * 戻り値: キャンセル前のstatus（繰り上げ判定用）
     */
    public function cancel(int $eventId, int $memberId): string
    {
        $existing = $this->findByEventAndMember($eventId, $memberId);
        $prevStatus = $existing ? $existing['status'] : 'cancelled';

        $this->db->execute(
            "UPDATE event_applications SET status = 'cancelled', updated_at = NOW()
             WHERE event_id = ? AND member_id = ?",
            [$eventId, $memberId]
        );

        return $prevStatus;
    }

    /**
     * ID指定で1件取得
     */
    public function findById(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM event_applications WHERE id = ?", [$id]);
    }

    /**
     * 備考を更新（管理者用）
     */
    public function updateNote(int $id, ?string $note): bool
    {
        return $this->db->execute(
            "UPDATE event_applications SET note = ?, updated_at = NOW() WHERE id = ?",
            [$note, $id]
        ) >= 0;
    }

    /**
     * 管理者によるキャンセル（ID指定）
     * 戻り値: キャンセル前のstatus
     */
    public function cancelById(int $id): string
    {
        $existing = $this->db->fetch(
            "SELECT * FROM event_applications WHERE id = ?", [$id]
        );
        $prevStatus = $existing ? $existing['status'] : 'cancelled';

        $this->db->execute(
            "UPDATE event_applications SET status = 'cancelled', updated_at = NOW() WHERE id = ?",
            [$id]
        );

        return $prevStatus;
    }

    /**
     * 班番号を一括保存
     * $assignments = [ application_id => team_no|null, ... ]
     * 参加確定者（submitted）のみ対象
     */
    public function saveTeamAssignments(int $eventId, array $assignments): void
    {
        // 班割りの一括保存。多数のUPDATEを「班割りを更新」の1行にまとめる。
        $run = function () use ($eventId, $assignments) {
            foreach ($assignments as $appId => $teamNo) {
                $team = ($teamNo === null || $teamNo === '') ? null : (int)$teamNo;
                $this->db->execute(
                    "UPDATE event_applications SET team_no = ?, updated_at = NOW()
                     WHERE id = ? AND event_id = ? AND status = 'submitted'",
                    [$team, (int)$appId, $eventId]
                );
            }
        };

        if (class_exists('AuditLogger')) {
            AuditLogger::group([
                'feature'      => 'events',
                'method'       => 'PUT',
                'target_table' => 'events',
                'target_id'    => $eventId,
                'action_label' => '班割りを更新',
                'changes'      => ['対象人数' => count($assignments) . '名'],
            ], $run);
            return;
        }
        $run();
    }

    /**
     * 全申込の班番号をクリア
     */
    public function clearTeamAssignments(int $eventId): void
    {
        $this->db->execute(
            "UPDATE event_applications SET team_no = NULL WHERE event_id = ?",
            [$eventId]
        );
    }

    /**
     * キャンセル待ちの先頭を繰り上げ
     * 繰り上げた場合: その申込レコードを返す。待ち無し: null
     */
    public function promoteFromWaitlist(int $eventId): ?array
    {
        $next = $this->db->fetch(
            "SELECT * FROM event_applications
             WHERE event_id = ? AND status = 'waitlisted'
             ORDER BY created_at ASC LIMIT 1",
            [$eventId]
        );

        if (!$next) {
            return null;
        }

        $this->db->execute(
            "UPDATE event_applications
             SET status = 'submitted', promoted = 1, updated_at = NOW()
             WHERE id = ?",
            [$next['id']]
        );

        return $next;
    }
}
