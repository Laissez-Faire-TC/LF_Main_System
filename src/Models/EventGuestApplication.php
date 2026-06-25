<?php
/**
 * 企画ゲスト（非会員）申込モデル
 *
 * 会員用 event_applications とは別テーブル。氏名のみ固定で、
 * その他の属性はカスタム項目（event_guest_fields / event_guest_field_values）で持つ。
 */
class EventGuestApplication
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 参加確定ゲスト一覧（カスタム項目の回答付き）
     */
    public function getByEventId(int $eventId): array
    {
        return $this->attachValues($this->db->fetchAll(
            "SELECT ga.*, gp.match_key, gp.person_type
               FROM event_guest_applications ga
               LEFT JOIN guest_persons gp ON gp.id = ga.guest_person_id
              WHERE ga.event_id = ? AND ga.status = 'submitted'
              ORDER BY ga.created_at ASC",
            [$eventId]
        ));
    }

    /**
     * キャンセル待ちゲスト一覧
     */
    public function getWaitlistByEventId(int $eventId): array
    {
        return $this->attachValues($this->db->fetchAll(
            "SELECT ga.*, gp.match_key, gp.person_type
               FROM event_guest_applications ga
               LEFT JOIN guest_persons gp ON gp.id = ga.guest_person_id
              WHERE ga.event_id = ? AND ga.status = 'waitlisted'
              ORDER BY ga.created_at ASC",
            [$eventId]
        ));
    }

    public function countByEventId(int $eventId): int
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS cnt FROM event_guest_applications WHERE event_id = ? AND status = 'submitted'",
            [$eventId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    public function countWaitlistByEventId(int $eventId): int
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS cnt FROM event_guest_applications WHERE event_id = ? AND status = 'waitlisted'",
            [$eventId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    public function find(int $id): ?array
    {
        $row = $this->db->fetch("SELECT * FROM event_guest_applications WHERE id = ?", [$id]);
        if (!$row) {
            return null;
        }
        $row['values'] = $this->getValues($id);
        return $row;
    }

    /**
     * 企画 × 人物 で申込を検索（カスタム項目の回答付き）。無ければ null
     */
    public function findByEventAndPerson(int $eventId, int $guestPersonId): ?array
    {
        $row = $this->db->fetch(
            "SELECT * FROM event_guest_applications WHERE event_id = ? AND guest_person_id = ?",
            [$eventId, $guestPersonId]
        );
        if (!$row) {
            return null;
        }
        $row['values'] = $this->getValues((int)$row['id']);
        return $row;
    }

    /**
     * ゲスト申込を作成（カスタム項目の回答も保存）
     * $values = [ field_id => value, ... ]
     * 戻り値: 新規 guest_application の ID
     */
    public function create(
        int $eventId, string $name, string $status, array $values = [],
        ?string $note = null, ?int $guestPersonId = null, ?string $nameKana = null
    ): int {
        $this->db->beginTransaction();
        try {
            $id = $this->db->insert(
                "INSERT INTO event_guest_applications
                    (event_id, guest_person_id, name, name_kana, status, note, promoted)
                 VALUES (?, ?, ?, ?, ?, ?, 0)",
                [$eventId, $guestPersonId, $name, $nameKana, $status, $note]
            );

            $this->saveValues($id, $values);

            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * 申込内容（カスタム項目の回答・備考）を更新。氏名/カナ/代/学科などの同定キーは変更しない。
     * cancelled だった場合は submitted に戻す（再申込）。戻り値: 更新後の status
     */
    public function updateContent(int $id, array $values = [], ?string $note = null, bool $reactivate = false, bool $markResubmit = false): string
    {
        $this->db->beginTransaction();
        try {
            $existing = $this->db->fetch("SELECT * FROM event_guest_applications WHERE id = ?", [$id]);
            $status   = $existing['status'] ?? 'submitted';

            if ($reactivate && $status === 'cancelled') {
                $status = 'submitted';
                $this->db->execute(
                    "UPDATE event_guest_applications
                        SET status = 'submitted', promoted = 0, note = ?, created_at = NOW(), updated_at = NOW()
                      WHERE id = ?",
                    [$note, $id]
                );
            } else {
                // 内容変更（再提出）の場合は未確認フラグ resubmitted_at を立てる
                $sql = $markResubmit
                    ? "UPDATE event_guest_applications SET note = ?, resubmitted_at = NOW(), updated_at = NOW() WHERE id = ?"
                    : "UPDATE event_guest_applications SET note = ?, updated_at = NOW() WHERE id = ?";
                $this->db->execute($sql, [$note, $id]);
            }

            // 既存の回答を入れ替え
            $this->db->execute("DELETE FROM event_guest_field_values WHERE guest_application_id = ?", [$id]);
            $this->saveValues($id, $values);

            $this->db->commit();
            return $status;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * キャンセル（ID指定）。戻り値: キャンセル前の status
     */
    public function cancelById(int $id): string
    {
        $existing = $this->db->fetch("SELECT * FROM event_guest_applications WHERE id = ?", [$id]);
        $prev = $existing ? $existing['status'] : 'cancelled';

        $this->db->execute(
            "UPDATE event_guest_applications SET status = 'cancelled', updated_at = NOW() WHERE id = ?",
            [$id]
        );
        return $prev;
    }

    /**
     * キャンセル待ちの先頭を繰り上げ。繰り上げたレコードを返す（無ければ null）
     */
    public function promoteFromWaitlist(int $eventId): ?array
    {
        $next = $this->db->fetch(
            "SELECT * FROM event_guest_applications
              WHERE event_id = ? AND status = 'waitlisted'
              ORDER BY created_at ASC LIMIT 1",
            [$eventId]
        );
        if (!$next) {
            return null;
        }
        $this->db->execute(
            "UPDATE event_guest_applications
                SET status = 'submitted', promoted = 1, updated_at = NOW()
              WHERE id = ?",
            [$next['id']]
        );
        return $next;
    }

    /**
     * 班番号を一括保存 [ guest_application_id => team_no|null, ... ]
     * 参加確定（submitted）のみ対象
     */
    public function saveTeamAssignments(int $eventId, array $assignments): void
    {
        foreach ($assignments as $id => $teamNo) {
            $team = ($teamNo === null || $teamNo === '') ? null : (int)$teamNo;
            $this->db->execute(
                "UPDATE event_guest_applications SET team_no = ?, updated_at = NOW()
                  WHERE id = ? AND event_id = ? AND status = 'submitted'",
                [$team, (int)$id, $eventId]
            );
        }
    }

    public function clearTeamAssignments(int $eventId): void
    {
        $this->db->execute(
            "UPDATE event_guest_applications SET team_no = NULL WHERE event_id = ?",
            [$eventId]
        );
    }

    // ── 再提出（内容変更）通知 ──

    /**
     * 未確認の再提出（内容変更）一覧。企画名付き・新しい順。ダッシュボード用。
     */
    public function getUnconfirmedResubmissions(): array
    {
        return $this->db->fetchAll(
            "SELECT ga.id, ga.event_id, ga.name, ga.resubmitted_at, e.title AS event_title
               FROM event_guest_applications ga
               JOIN events e ON e.id = ga.event_id
              WHERE ga.resubmitted_at IS NOT NULL
                AND ga.status IN ('submitted', 'waitlisted')
              ORDER BY ga.resubmitted_at DESC"
        );
    }

    /**
     * ある企画の未確認再提出 ID 一覧（企画ページのバッジ表示用）
     */
    public function getResubmittedIdsByEvent(int $eventId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT id FROM event_guest_applications
              WHERE event_id = ? AND resubmitted_at IS NOT NULL",
            [$eventId]
        );
        return array_map(fn($r) => (int)$r['id'], $rows);
    }

    /**
     * 再提出を確認済み（既読）にする
     */
    public function confirmResubmission(int $id): bool
    {
        return $this->db->execute(
            "UPDATE event_guest_applications SET resubmitted_at = NULL WHERE id = ?",
            [$id]
        ) >= 0;
    }

    // ── 内部ヘルパー ──

    /**
     * カスタム項目の回答を保存 [ field_id => value, ... ]
     */
    private function saveValues(int $guestApplicationId, array $values): void
    {
        foreach ($values as $fieldId => $value) {
            $val = is_array($value) ? implode(', ', $value) : (string)$value;
            if ($val === '') {
                continue;
            }
            $this->db->insert(
                "INSERT INTO event_guest_field_values (guest_application_id, field_id, value)
                 VALUES (?, ?, ?)",
                [$guestApplicationId, (int)$fieldId, $val]
            );
        }
    }

    /**
     * 1件分のカスタム項目回答 [ field_id => value ]
     */
    private function getValues(int $guestApplicationId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT field_id, value FROM event_guest_field_values WHERE guest_application_id = ?",
            [$guestApplicationId]
        );
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['field_id']] = $r['value'];
        }
        return $map;
    }

    /**
     * 申込配列それぞれに values（field_id => value）を付与
     */
    private function attachValues(array $apps): array
    {
        if (empty($apps)) {
            return [];
        }
        $ids = array_map(fn($a) => (int)$a['id'], $apps);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->db->fetchAll(
            "SELECT guest_application_id, field_id, value
               FROM event_guest_field_values
              WHERE guest_application_id IN ($placeholders)",
            $ids
        );

        $byApp = [];
        foreach ($rows as $r) {
            $byApp[(int)$r['guest_application_id']][(int)$r['field_id']] = $r['value'];
        }
        foreach ($apps as &$a) {
            $a['values'] = $byApp[(int)$a['id']] ?? [];
        }
        return $apps;
    }
}
