<?php
/**
 * 遠征参加者モデル
 */
class ExpeditionParticipant
{
    /**
     * 遠征IDで参加者一覧を取得（ふりがな順）
     */
    public static function findByExpedition(int $expedition_id): array
    {
        $rows = Database::getInstance()->fetchAll(
            "SELECT ep.*, m.name_kanji, m.name_kana, m.gender, m.grade, m.allergy, m.address,
                    ep.timescar_number
             FROM expedition_participants ep
             JOIN members m ON m.id = ep.member_id
             WHERE ep.expedition_id = ?
             ORDER BY m.name_kana",
            [$expedition_id]
        );

        // 権限のない幹部からは members 由来の個人情報（address/allergy 等）を除去する。
        // 会員ポータル・公開しおりから呼ばれた場合は maskMemberData が何もしないため安全。
        return Auth::maskMemberData($rows);
    }

    /**
     * 参加者を追加してレコードを返す
     * pre_night: 前泊するか（デフォルト: 1=する）
     * lunch: 昼食を頼むか（デフォルト: 0=頼まない）
     * is_joining_car: 車に乗るか（デフォルト: 1=乗る）
     * driver_type: ドライバー種別（driver/sub_driver/none）
     * timescar_number: タイムズカーシェア利用者番号
     * can_book_car: 車の予約をするか
     * friday_last_class: 金曜授業終了時限（0=なし, 1〜6=何限まで）
     */
    public static function add(
        int $expedition_id,
        int $member_id,
        int $pre_night = 1,
        int $lunch = 0,
        string $status = 'confirmed',
        int $is_joining_car = 1,
        string $driver_type = 'none',
        string $timescar_number = '',
        int $can_book_car = 0,
        ?int $friday_last_class = null
    ): ?array {
        $db = Database::getInstance();
        $id = $db->insert(
            "INSERT INTO expedition_participants
             (expedition_id, member_id, pre_night, lunch, status,
              is_joining_car, driver_type, timescar_number, can_book_car, friday_last_class)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$expedition_id, $member_id, $pre_night, $lunch, $status,
             $is_joining_car, $driver_type, $timescar_number, $can_book_car, $friday_last_class]
        );

        return $db->fetch(
            "SELECT * FROM expedition_participants WHERE id = ?",
            [$id]
        );
    }

    /**
     * 性別・ステータス別の参加者数を取得
     * 戻り値例: ['confirmed_male' => 3, 'confirmed_female' => 2, 'waitlisted_male' => 1, 'waitlisted_female' => 0]
     */
    public static function countByGenderAndStatus(int $expedition_id): array
    {
        $rows = Database::getInstance()->fetchAll(
            "SELECT m.gender, ep.status, COUNT(ep.id) as cnt
             FROM expedition_participants ep
             JOIN members m ON m.id = ep.member_id
             WHERE ep.expedition_id = ?
             GROUP BY m.gender, ep.status",
            [$expedition_id]
        );

        $result = [
            'confirmed_male'    => 0,
            'confirmed_female'  => 0,
            'waitlisted_male'   => 0,
            'waitlisted_female' => 0,
        ];

        foreach ($rows as $row) {
            $gKey = ($row['gender'] === 'male') ? 'male' : 'female';
            $sKey = ($row['status'] === 'confirmed') ? 'confirmed' : 'waitlisted';
            $key  = "{$sKey}_{$gKey}";
            if (array_key_exists($key, $result)) {
                $result[$key] = (int)$row['cnt'];
            }
        }

        return $result;
    }

    /**
     * 参加者情報を更新して更新後レコードを返す
     */
    public static function update(int $id, array $data): ?array
    {
        $db = Database::getInstance();
        $allowedFields = ['pre_night', 'lunch', 'status', 'is_joining_car', 'driver_type', 'timescar_number', 'can_book_car', 'friday_last_class'];
        $fields = [];
        $values = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        if (!empty($fields)) {
            $values[] = $id;
            $db->execute(
                "UPDATE expedition_participants SET " . implode(', ', $fields) . " WHERE id = ?",
                $values
            );
        }

        return $db->fetch(
            "SELECT * FROM expedition_participants WHERE id = ?",
            [$id]
        );
    }

    /**
     * 参加者を削除する
     */
    public static function remove(int $id): bool
    {
        return Database::getInstance()->execute(
            "DELETE FROM expedition_participants WHERE id = ?",
            [$id]
        ) > 0;
    }
}
