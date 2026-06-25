<?php
/**
 * 遠征集金モデル
 */
class ExpeditionCollection
{
    /**
     * 遠征IDで集金一覧を取得（各集金に明細をセットして返す）
     */
    public static function findByExpedition(int $expedition_id): array
    {
        $db = Database::getInstance();
        $collections = $db->fetchAll(
            "SELECT * FROM expedition_collections WHERE expedition_id = ? ORDER BY round",
            [$expedition_id]
        );

        foreach ($collections as &$collection) {
            $collection['items'] = $db->fetchAll(
                "SELECT eci.*, m.name_kanji, m.name_kana FROM expedition_collection_items eci
                 JOIN members m ON m.id = eci.member_id
                 WHERE eci.collection_id = ? ORDER BY m.name_kana",
                [$collection['id']]
            );
        }
        unset($collection);

        return $collections;
    }

    /**
     * 指定回次の既存集金を削除（明細はCASCADEで自動削除）
     */
    public static function deleteByRound(int $expedition_id, int $round): void
    {
        $db = Database::getInstance();
        $db->execute(
            "DELETE FROM expedition_collections WHERE expedition_id = ? AND round = ?",
            [$expedition_id, $round]
        );
    }

    /**
     * 集金を新規作成（status='pending'で作成）
     */
    public static function create(int $expedition_id, int $round, string $title): ?array
    {
        $db = Database::getInstance();
        $id = $db->insert(
            "INSERT INTO expedition_collections (expedition_id, round, title, status) VALUES (?, ?, ?, 'pending')",
            [$expedition_id, $round, $title]
        );

        return $db->fetch("SELECT * FROM expedition_collections WHERE id = ?", [$id]);
    }

    /**
     * 集金を更新
     */
    public static function update(int $id, array $data): ?array
    {
        $db = Database::getInstance();
        $allowedFields = ['round', 'title', 'status'];
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
                "UPDATE expedition_collections SET " . implode(', ', $fields) . " WHERE id = ?",
                $values
            );
        }

        return $db->fetch("SELECT * FROM expedition_collections WHERE id = ?", [$id]);
    }

    /**
     * 集金をトランザクション内で削除（明細→集金の順に削除）
     */
    public static function delete(int $id): bool
    {
        $db = Database::getInstance();

        // 明細＋集金本体のDELETEは「集金を削除」の1操作。1行にまとめる。
        $run = function () use ($db, $id) {
            $db->beginTransaction();
            try {
                $db->execute("DELETE FROM expedition_collection_items WHERE collection_id = ?", [$id]);
                $result = $db->execute("DELETE FROM expedition_collections WHERE id = ?", [$id]) > 0;
                $db->commit();
                return $result;
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
        };

        if (class_exists('AuditLogger')) {
            return (bool)AuditLogger::group([
                'feature'      => 'expeditions',
                'method'       => 'DELETE',
                'target_table' => 'expedition_collections',
                'target_id'    => $id,
                'action_label' => '集金を削除（明細含む）',
            ], $run);
        }
        return $run();
    }

    /**
     * 集金明細を自動生成する
     * round=1: 参加費（base_fee + オプション）
     * round=2: 車両費精算（ExpeditionCar::calculateSettlement を使用）
     */
    public static function generateItems(int $collection_id): bool
    {
        $db = Database::getInstance();

        // 集金と遠征の料金情報を取得
        $collection = $db->fetch(
            "SELECT ec.*, e.base_fee, e.pre_night_fee, e.lunch_fee, e.subsidy
             FROM expedition_collections ec
             JOIN expeditions e ON e.id = ec.expedition_id
             WHERE ec.id = ?",
            [$collection_id]
        );

        if (!$collection) {
            return false;
        }

        $expedition_id = (int)$collection['expedition_id'];

        if ((int)$collection['round'] === 1) {
            // 第1回: 参加費を計算して明細を生成
            $participants = $db->fetchAll(
                "SELECT * FROM expedition_participants WHERE expedition_id = ?",
                [$expedition_id]
            );

            // 補助金を人数で割って一人あたりの減額を算出（端数切り捨て）
            $subsidy       = (int)($collection['subsidy'] ?? 0);
            $participantN  = count($participants);
            $subsidyPerPerson = ($participantN > 0 && $subsidy > 0)
                ? (int)floor($subsidy / $participantN)
                : 0;

            foreach ($participants as $participant) {
                $amount = (int)$collection['base_fee'];
                if ((int)$participant['pre_night'] === 1) {
                    $amount += (int)$collection['pre_night_fee'];
                }
                if ((int)$participant['lunch'] === 1) {
                    $amount += (int)$collection['lunch_fee'];
                }
                // 補助金減額（0未満にはしない）
                $amount = max(0, $amount - $subsidyPerPerson);

                $db->insert(
                    "INSERT INTO expedition_collection_items (collection_id, member_id, amount) VALUES (?, ?, ?)",
                    [$collection_id, $participant['member_id'], $amount]
                );
            }

        } elseif ((int)$collection['round'] === 2) {
            // 第2回: レンタカー費用申請テーブルから清算額を計算して明細を生成
            // 車に乗る確定参加者のみ対象（is_joining_car=1）
            $participants = $db->fetchAll(
                "SELECT ep.member_id FROM expedition_participants ep
                 WHERE ep.expedition_id = ? AND ep.status = 'confirmed' AND ep.is_joining_car = 1",
                [$expedition_id]
            );
            $memberIds = array_column($participants, 'member_id');
            $n         = count($memberIds);

            if ($n === 0) {
                return true;
            }

            // 費用申請を集計
            $expenses = $db->fetchAll(
                "SELECT member_id,
                        (rental_fee + gas_fee + highway_fee + other_fee) AS total
                 FROM expedition_car_expenses
                 WHERE expedition_id = ?",
                [$expedition_id]
            );
            $expenseMap  = [];
            $grandTotal  = 0;
            foreach ($expenses as $e) {
                $expenseMap[(int)$e['member_id']] = (int)$e['total'];
                $grandTotal += (int)$e['total'];
            }

            // 一人あたり負担額（円未満切り上げ）
            $share = (int)ceil($grandTotal / $n);

            // 参加者ごとに精算額を計算して挿入
            // amount 正 = 支払い、負 = 返金
            foreach ($memberIds as $mid) {
                $paid   = $expenseMap[(int)$mid] ?? 0;
                $amount = $share - $paid; // 支払い超過なら負（返金）、不足なら正（支払い）
                $db->insert(
                    "INSERT INTO expedition_collection_items (collection_id, member_id, amount) VALUES (?, ?, ?)",
                    [$collection_id, (int)$mid, $amount]
                );
            }
        }

        return true;
    }
}
