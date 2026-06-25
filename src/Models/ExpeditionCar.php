<?php
/**
 * 遠征車両モデル
 */
class ExpeditionCar
{
    /**
     * 遠征IDに紐づく全車両を取得（乗車メンバー・立替者付き）
     */
    public static function findByExpedition(int $expedition_id): array
    {
        $db = Database::getInstance();
        $cars = $db->fetchAll(
            "SELECT * FROM expedition_cars WHERE expedition_id = ? ORDER BY sort_order",
            [$expedition_id]
        );

        foreach ($cars as &$car) {
            // 乗車メンバーを取得
            $car['car_members'] = $db->fetchAll(
                "SELECT ecm.*, m.name_kanji, m.name_kana,
                        ep.friday_last_class, ep.timescar_number
                 FROM expedition_car_members ecm
                 JOIN members m ON m.id = ecm.member_id
                 LEFT JOIN expedition_participants ep
                        ON ep.member_id = ecm.member_id AND ep.expedition_id = ?
                 WHERE ecm.car_id = ?
                 ORDER BY ecm.sort_order",
                [$expedition_id, $car['id']]
            );
            // 立替者を取得
            $car['car_payers'] = $db->fetchAll(
                "SELECT ecp.*, m.name_kanji
                 FROM expedition_car_payers ecp
                 JOIN members m ON m.id = ecp.member_id
                 WHERE ecp.car_id = ?",
                [$car['id']]
            );
        }

        return $cars;
    }

    /**
     * 車両を新規作成して作成行を返す
     */
    public static function create(int $expedition_id, array $data): ?array
    {
        $db        = Database::getInstance();
        $tripType  = in_array($data['trip_type'] ?? '', ['outbound','return','both']) ? $data['trip_type'] : 'both';
        $depClass  = ($tripType === 'outbound' && isset($data['departure_class']) && $data['departure_class'] !== '')
                     ? (int)$data['departure_class'] : null;

        $id = $db->insert(
            "INSERT INTO expedition_cars (expedition_id, name, capacity, rental_fee, highway_fee, trip_type, departure_class)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $expedition_id,
                $data['name'] ?? '',
                $data['capacity'] ?? 6,
                $data['rental_fee'] ?? 0,
                $data['highway_fee'] ?? 0,
                $tripType,
                $depClass,
            ]
        );

        return $db->fetch("SELECT * FROM expedition_cars WHERE id = ?", [$id]);
    }

    /**
     * 車両情報を更新して更新後の行を返す
     */
    public static function update(int $id, array $data): ?array
    {
        $db = Database::getInstance();
        $allowedFields = ['name', 'capacity', 'rental_fee', 'highway_fee', 'sort_order', 'trip_type', 'departure_class'];
        $fields = [];
        $values = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return $db->fetch("SELECT * FROM expedition_cars WHERE id = ?", [$id]);
        }

        $values[] = $id;
        $db->execute(
            "UPDATE expedition_cars SET " . implode(', ', $fields) . " WHERE id = ?",
            $values
        );

        return $db->fetch("SELECT * FROM expedition_cars WHERE id = ?", [$id]);
    }

    /**
     * 車両を関連データごと削除（トランザクション内で実行）
     */
    public static function delete(int $id): bool
    {
        $db = Database::getInstance();

        // 立替者・乗員・車本体の3テーブルDELETEは「車を削除」の1操作。1行にまとめる。
        $run = function () use ($db, $id) {
            $db->beginTransaction();
            try {
                $db->execute("DELETE FROM expedition_car_payers WHERE car_id = ?", [$id]);
                $db->execute("DELETE FROM expedition_car_members WHERE car_id = ?", [$id]);
                $result = $db->execute("DELETE FROM expedition_cars WHERE id = ?", [$id]) > 0;
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
                'target_table' => 'expedition_cars',
                'target_id'    => $id,
                'action_label' => '車を削除（乗員・立替者含む）',
            ], $run);
        }
        return $run();
    }

    /**
     * 遠征の車両費用精算を計算する
     * 返り値: ['total_cost' => int, 'per_person' => int, 'settlement' => [...]]
     * amount が正 = 支払い、負 = 返金
     */
    public static function calculateSettlement(int $expedition_id): array
    {
        $cars = self::findByExpedition($expedition_id);

        // 総費用を計算
        $total_cost = 0;
        foreach ($cars as $car) {
            $total_cost += (int)$car['rental_fee'] + (int)$car['highway_fee'];
        }

        // is_excluded=0 の乗員数を全車合算
        $total_members = 0;
        foreach ($cars as $car) {
            foreach ($car['car_members'] as $member) {
                if ((int)$member['is_excluded'] === 0) {
                    $total_members++;
                }
            }
        }

        // 1人あたり負担額（切り上げ）
        $per_person = ($total_members > 0) ? intval(ceil($total_cost / $total_members)) : 0;

        // 立替者マップ: member_id => 立替合計額
        $payer_map = [];
        $payer_names = [];
        foreach ($cars as $car) {
            foreach ($car['car_payers'] as $payer) {
                $mid = (int)$payer['member_id'];
                $payer_map[$mid] = ($payer_map[$mid] ?? 0) + (int)$payer['amount'];
                $payer_names[$mid] = $payer['name_kanji'];
            }
        }

        // 対象乗車者マップ: member_id => name（is_excluded=0）
        $member_map = [];
        foreach ($cars as $car) {
            foreach ($car['car_members'] as $member) {
                if ((int)$member['is_excluded'] === 0) {
                    $mid = (int)$member['member_id'];
                    if (!isset($member_map[$mid])) {
                        $member_map[$mid] = $member['name_kanji'];
                    }
                }
            }
        }

        $settlement = [];

        // 立替者の精算: 返金額 = 立替額 - 1人あたり負担額
        foreach ($payer_map as $member_id => $paid_amount) {
            $settlement[] = [
                'member_id' => $member_id,
                'name'      => $payer_names[$member_id],
                'amount'    => $paid_amount - $per_person,
            ];
        }

        // 未立替の乗車者: 支払い額 = 1人あたり負担額
        foreach ($member_map as $member_id => $name) {
            if (!isset($payer_map[$member_id])) {
                $settlement[] = [
                    'member_id' => $member_id,
                    'name'      => $name,
                    'amount'    => $per_person,
                ];
            }
        }

        return [
            'total_cost' => $total_cost,
            'per_person' => $per_person,
            'settlement' => $settlement,
        ];
    }

    /**
     * 往路の車を自動作成し、乗客を割り当てる
     *
     * 動作:
     *   1. 既存の往路・両路の車を削除してリセット
     *   2. can_book_car=1 の参加者ごとに車を作成（車名: 名前+車、出発時限: その人の friday_last_class）
     *      → その人をドライバーとして登録
     *   3. 残りの参加者を、friday_last_class <= car.departure_class を満たす最も早い車に割り当て
     *      → 空席なし・対応車なしの場合は最後の車に割り当て（警告あり）
     *
     * 戻り値: ['cars' => [...], 'warnings' => [...]]
     */
    public static function autoAssignOutbound(int $expedition_id, array $capacities = [], array $soloBookers = [], array $selectedBookers = [], array $pinnedMembers = []): array
    {
        // 内部で多数の DELETE/INSERT（既存車削除→新車作成→乗車割当）に分かれるが、
        // 業務的には「往路の車割を自動作成」という1操作。1行にまとめて記録する。
        $run = fn() => self::autoAssignOutboundInner($expedition_id, $capacities, $soloBookers, $selectedBookers, $pinnedMembers);

        if (class_exists('AuditLogger')) {
            return AuditLogger::group([
                'feature'      => 'expeditions',
                'method'       => 'POST',
                'target_table' => 'expeditions',
                'target_id'    => $expedition_id,
                'action_label' => '往路の車割を自動作成',
                'resolve'      => function ($result) {
                    $cars = is_array($result) ? ($result['cars'] ?? []) : [];
                    return ['changes' => ['作成した車' => count($cars) . '台']];
                },
            ], $run);
        }
        return $run();
    }

    private static function autoAssignOutboundInner(int $expedition_id, array $capacities = [], array $soloBookers = [], array $selectedBookers = [], array $pinnedMembers = []): array
    {
        $db = Database::getInstance();

        // 既存の往路・両路の車を削除（復路専用は残す）
        $existingCars = $db->fetchAll(
            "SELECT id FROM expedition_cars WHERE expedition_id = ? AND trip_type IN ('outbound', 'both')",
            [$expedition_id]
        );
        foreach ($existingCars as $ec) {
            $db->execute("DELETE FROM expedition_car_payers  WHERE car_id = ?", [$ec['id']]);
            $db->execute("DELETE FROM expedition_car_members WHERE car_id = ?", [$ec['id']]);
            $db->execute("DELETE FROM expedition_cars        WHERE id = ?",     [$ec['id']]);
        }

        // 車に乗る参加者を全員取得（授業終了時限順）。
        // 車を予約する人（can_book_car=1）は乗車扱いが漏れていても必ず含める
        // （車を予約する＝その車に乗るため）。
        $participants = $db->fetchAll(
            "SELECT ep.*, m.name_kanji, m.name_kana
             FROM expedition_participants ep
             JOIN members m ON m.id = ep.member_id
             WHERE ep.expedition_id = ? AND (ep.is_joining_car = 1 OR ep.can_book_car = 1)
             ORDER BY COALESCE(ep.friday_last_class, 0) ASC, m.name_kana ASC",
            [$expedition_id]
        );

        if (empty($participants)) {
            return ['cars' => [], 'warnings' => ['車に乗る参加者がいません']];
        }

        // can_book_car=1 の人を抽出（授業終了時限順）
        $bookers = array_values(array_filter($participants, fn($p) => (int)($p['can_book_car'] ?? 0) === 1));

        if (empty($bookers)) {
            return ['cars' => [], 'warnings' => ['車を予約できる人が登録されていません（申し込み時に「車の予約をする」を選択した人が必要です）']];
        }

        // 画面で「使用する」と選択されたドライバーのみに絞り込む
        // （車を借りられる人全員が実際に車を出すとは限らないため）。
        // 未指定（空）の場合は後方互換として全員を対象にする。
        if (!empty($selectedBookers)) {
            $selectedIds = array_map('intval', $selectedBookers);
            $bookers = array_values(array_filter(
                $bookers,
                fn($p) => in_array((int)$p['member_id'], $selectedIds, true)
            ));
            if (empty($bookers)) {
                return ['cars' => [], 'warnings' => ['使用する車が選択されていません']];
            }
        }

        $warnings  = [];
        $cars      = []; // [['id' => ..., 'name' => ..., 'departure_class' => ..., 'capacity' => 5], ...]
        $sortOrder = 1;

        // booker ごとに車を作成してドライバー登録
        // 車の出発時限（departure_class）はドライバー自身の時限では決めない。
        // 早出のドライバーでも遅い人を乗せられるよう、乗車者を割り当て切ってから
        // 「その車に乗る人の friday_last_class の最大値」で後段で確定する。
        foreach ($bookers as $booker) {
            $carName  = $booker['name_kanji'] . '車';

            $capacity = isset($capacities[$booker['member_id']]) ? (int)$capacities[$booker['member_id']] : 6;
            $capacity = max(1, $capacity);

            $carId = $db->insert(
                "INSERT INTO expedition_cars
                 (expedition_id, name, capacity, rental_fee, highway_fee, trip_type, departure_class, sort_order)
                 VALUES (?, ?, ?, 0, 0, 'outbound', NULL, ?)",
                [$expedition_id, $carName, $capacity, $sortOrder++]
            );

            // booker をドライバーとして登録
            $driverRole = ($booker['driver_type'] === 'sub_driver') ? 'sub_driver' : 'driver';
            $db->insert(
                "INSERT INTO expedition_car_members (car_id, member_id, role, is_excluded, sort_order)
                 VALUES (?, ?, ?, 0, 0)",
                [$carId, $booker['member_id'], $driverRole]
            );

            $cars[] = [
                'id'        => $carId,
                'name'      => $carName,
                'capacity'  => $capacity,
                'booker_id' => (int)$booker['member_id'],
                // この車に乗る人の friday_last_class 最大値（ドライバー自身から初期化）
                'max_class' => (int)($booker['friday_last_class'] ?? 0),
            ];
        }

        // booker 以外のドライバー能力がある参加者（＝サブドライバー候補）を把握する。
        // サブドライバーは事前固定せず、待ち時間最適化の中で配置する（時限を考慮するため）。
        $bookerIds = array_map(fn($b) => (int)$b['member_id'], $bookers);
        // member_id => 運転可能か（driver / sub_driver の役割を持つ。booker 以外）
        $driverCapable = [];
        foreach ($participants as $p) {
            $mid = (int)$p['member_id'];
            if (in_array($mid, $bookerIds, true)) continue; // booker は自分の車のドライバー
            $driverCapable[$mid] = in_array($p['driver_type'] ?? 'none', ['driver', 'sub_driver'], true);
        }

        // booker を除いた参加者を乗客として最適配置する（サブドライバー候補も含む）。
        $preAssigned = $bookerIds;
        $unassigned  = array_values(array_filter($participants, fn($p) => !in_array((int)$p['member_id'], $preAssigned)));

        // 現在の乗車人数をメモリ上で追跡（DB問い合わせを減らす）
        $carCounts = [];
        foreach ($cars as $car) {
            $carCounts[$car['id']] = (int)$db->fetch(
                "SELECT COUNT(*) as cnt FROM expedition_car_members WHERE car_id = ?",
                [$car['id']]
            )['cnt'];
        }

        // 固定指定によって運転可能者が乗った車（carIdx => true）。運転者要件を満たすとみなす。
        $pinnedDriverCars = [];

        // 乗員の固定指定を先に処理する（指定された人を、指定された車のドライバーの車へ固定）。
        // car_owner_id（ドライバーの member_id）から対応する車を引く。
        if (!empty($pinnedMembers)) {
            $pinnedAssigned = [];
            foreach ($pinnedMembers as $memberId => $carOwnerId) {
                $memberId   = (int)$memberId;
                $carOwnerId = (int)$carOwnerId;

                // 既にドライバー/サブドライバー等で配置済みの人はスキップ
                if (in_array($memberId, $preAssigned, true)) continue;

                // 固定先の車を探す（booker_id == car_owner_id）
                $targetIdx = null;
                foreach ($cars as $idx => $c) {
                    if ((int)$c['booker_id'] === $carOwnerId) { $targetIdx = $idx; break; }
                }
                if ($targetIdx === null) continue; // 使用しない車などに指定された → 無視（通常割当へ）

                // 固定対象の参加者データを取得
                $person = null;
                foreach ($participants as $p) {
                    if ((int)$p['member_id'] === $memberId) { $person = $p; break; }
                }
                if ($person === null) continue; // 車に乗らない人など → 無視

                $car = $cars[$targetIdx];

                // 定員超過なら固定できない → 警告して通常割当に回す
                if ($carCounts[$car['id']] >= $car['capacity']) {
                    $warnings[] = "【{$person['name_kanji']}】「{$car['name']}」が満席のため固定できませんでした（自動割り当てに回します）";
                    continue;
                }

                $personClass = (int)($person['friday_last_class'] ?? 0);
                $role = 'passenger';
                if (($person['driver_type'] ?? 'none') === 'driver')     $role = 'driver';
                if (($person['driver_type'] ?? 'none') === 'sub_driver') $role = 'sub_driver';

                $db->insert(
                    "INSERT INTO expedition_car_members (car_id, member_id, role, is_excluded, sort_order)
                     VALUES (?, ?, ?, 0, ?)",
                    [$car['id'], $memberId, $role, $carCounts[$car['id']]]
                );
                $carCounts[$car['id']]++;
                $cars[$targetIdx]['max_class'] = max($cars[$targetIdx]['max_class'], $personClass);
                $pinnedAssigned[] = $memberId;

                // 固定された人が運転可能なら、その車の運転者要件を満たす
                if ($role === 'driver' || $role === 'sub_driver') {
                    $pinnedDriverCars[$targetIdx] = true;
                }
            }

            // 固定済みの人を未割当リストから除外
            if (!empty($pinnedAssigned)) {
                $unassigned = array_values(array_filter(
                    $unassigned,
                    fn($p) => !in_array((int)$p['member_id'], $pinnedAssigned, true)
                ));
            }
        }

        $lastIdx = count($cars) - 1;

        // 各車が「2人目の運転者」を必要とするか（solo 指定でなく、固定指定でも運転者が未充足）。
        // booker 自身は1人目のドライバーなので、各車は運転可能者をもう1人必要とする。
        $needsDriver = [];
        foreach ($cars as $idx => $car) {
            $isSolo = in_array($car['booker_id'], $soloBookers);
            $needsDriver[$idx] = (!$isSolo) && empty($pinnedDriverCars[$idx]);
        }

        // 残りの乗客を「全員の待ち時間（= 乗る車の出発時限 − 自分の授業終了時限）の合計」が
        // 最小になるように割り当てる。各車には下限時限（max_class）と残席があり、さらに
        // 運転者を必要とする車には運転可能者を最低1人置く制約を課す。その下で厳密最小化する。
        $opt               = self::optimizeOutboundSeating($cars, $carCounts, $unassigned, $driverCapable, $needsDriver);
        $targetIdxByMember = $opt['assign'];
        $driverByCar       = $opt['driverByCar']; // carIdx => 運転者役に選ばれた member_id

        // 運転者役に選ばれた member_id の集合
        $assignedAsDriver = [];
        foreach ($driverByCar as $mid) $assignedAsDriver[$mid] = true;

        foreach ($unassigned as $participant) {
            $personClass = (int)($participant['friday_last_class'] ?? 0);
            $memberId    = (int)$participant['member_id'];
            $targetIdx   = $targetIdxByMember[$memberId] ?? null;

            // 最適化で割り当て先が無い（全車満席など）→ 最後の車に強制割り当て
            if ($targetIdx === null) {
                $targetIdx  = $lastIdx;
                $classLabel = $personClass === 0 ? '授業なし' : "{$personClass}限終わり";
                $warnings[] = "【{$participant['name_kanji']}（{$classLabel}）】空席のある車が見つからないため「{$cars[$targetIdx]['name']}」に割り当てました";
            }

            $targetCar = $cars[$targetIdx];
            $so        = $carCounts[$targetCar['id']];

            // ロール決定: この車の2人目運転者に選ばれた人は sub_driver。
            // それ以外は driver_type に従う。
            $role = 'passenger';
            if (($participant['driver_type'] ?? 'none') === 'driver')     $role = 'driver';
            if (($participant['driver_type'] ?? 'none') === 'sub_driver') $role = 'sub_driver';
            if (!empty($assignedAsDriver[$memberId]) && $role === 'passenger') $role = 'sub_driver';

            $db->insert(
                "INSERT INTO expedition_car_members (car_id, member_id, role, is_excluded, sort_order)
                 VALUES (?, ?, ?, 0, ?)",
                [$targetCar['id'], $participant['member_id'], $role, $so]
            );
            $carCounts[$targetCar['id']]++;
            $cars[$targetIdx]['max_class'] = max($cars[$targetIdx]['max_class'], $personClass);
        }

        // 各車の出発時限を「乗車者の friday_last_class の最大値」で確定する。
        // 早出のドライバーでも遅い人を乗せた車は、その遅い時限で出発する。
        foreach ($cars as $car) {
            $db->execute(
                "UPDATE expedition_cars SET departure_class = ? WHERE id = ?",
                [(int)$car['max_class'], $car['id']]
            );
        }

        // 各車のサブドライバー不足チェック
        foreach ($cars as $car) {
            $members = $db->fetchAll(
                "SELECT role FROM expedition_car_members WHERE car_id = ?",
                [$car['id']]
            );
            // 運転できる人（driver / sub_driver のいずれか）の人数を数える。
            // ルール: 各車に運転可能者が2人以上必要（メインドライバー1人＋もう1人。
            //         2人目はメインドライバーでもサブドライバーでも可）。solo 指定車は1人で可。
            $driverCount = 0;
            foreach ($members as $m) {
                if ($m['role'] === 'driver' || $m['role'] === 'sub_driver') $driverCount++;
            }
            $depClass = (int)$car['max_class'];
            $depLabel = $depClass === 0 ? '早出' : "{$depClass}限後出発";
            $carLabel = $car['name'] . "（{$depLabel}）";

            // booker が solo 指定の場合は運転者1人でも警告しない
            $bookerIdForCar = null;
            foreach ($cars as $c) {
                if ($c['id'] === $car['id']) { $bookerIdForCar = $c['booker_id'] ?? null; break; }
            }
            $isSolo = $bookerIdForCar !== null && in_array($bookerIdForCar, $soloBookers);

            if ($driverCount === 0) {
                $warnings[] = "【{$carLabel}】ドライバーがいません";
            } elseif ($driverCount < 2 && !$isSolo) {
                $warnings[] = "【{$carLabel}】運転できる人が1人しかいません（2人目のドライバーが必要です）";
            }
        }

        return [
            'cars'     => self::findByExpedition($expedition_id),
            'warnings' => $warnings,
        ];
    }

    /**
     * 往路の残り乗客を、待ち時間（乗る車の出発時限 − 自分の授業終了時限）の
     * 「二乗和」が最小になるように各車へ割り当てる。
     *
     * 二乗和を使う理由: 単純合計だと「1人が3限待ち(=3)」と「3人が1限待ち(=3)」が同点になるが、
     * 実運用では後者の方が望ましい（1人に長時間待たせない）。二乗和なら 9 vs 3 で後者を選ぶ。
     *
     * 制約:
     *   - 各車には下限時限 floor（= max_class。ドライバー等の既乗車者の最遅時限）がある。
     *     その車の出発時限 dep は floor 以上になる。
     *   - 各車の残席（capacity − 既乗車人数）を超えて乗せられない。
     *   - 授業なし（class=0）の人は dep<=4 の車のみ可・待ちコスト0（dep>=5 の車には乗せない）。
     *   - 運転者を必要とする車（needsDriver=true）には運転可能者を最低1人乗せたい
     *     （満たせない場合は警告に委ね、待ち時間最小を優先する＝ソフト制約）。
     *
     * アルゴリズム（DP・厳密最適）:
     *   授業終了時限 class は 0〜6 の7値しか取らず、各車の出発時限 dep も {floor..6} の
     *   高々7値に限られる。乗客を class 別（さらに運転可否別）の人数だけで表現し、車を1台ずつ
     *   「dep を何にし、各 class を何人載せるか」を全列挙する DP で二乗和を最小化する。
     *   二乗和では「class 降順に1台へ詰める」貪欲が最適でないため、各車で class 別人数を
     *   全列挙する（class 値が7・車/人数とも数十程度のため実用的に解ける）。
     *
     *   運転者要件は2段階で扱う:
     *     段1) 全 needsDriver 車に運転可能者を1人置くハード制約付きで最小化を試みる。
     *     段2) 段1が解けない（運転候補・席不足）場合は運転者要件を外して最小化し、
     *          後段の警告（サブドライバー不在）に委ねる。
     *   どちらも席が足りないときは null を返し、呼び出し側が最後の車へ強制割当する。
     *
     * @return array ['assign' => member_id => 車インデックス, 'driverByCar' => 車インデックス => member_id]
     */
    private static function optimizeOutboundSeating(
        array $cars,
        array $carCounts,
        array $unassigned,
        array $driverCapable = [],
        array $needsDriver = []
    ): array {
        $m = count($cars);
        if ($m === 0 || empty($unassigned)) {
            return ['assign' => [], 'driverByCar' => []];
        }

        $maxClass = 6; // 授業終了時限の最大（0=早出 〜 6限）

        // 各車の下限時限 floor と残席 rem
        $floors = [];
        $rem    = [];
        foreach ($cars as $idx => $car) {
            $floors[$idx] = (int)$car['max_class'];
            $rem[$idx]    = max(0, (int)$car['capacity'] - (int)$carCounts[$car['id']]);
        }

        // 自由乗客を「運転可否 × class」のバケットに振り分ける。
        // bucketD = 運転可能者, bucketN = 運転不可者。各 class ごとに member_id を保持。
        $bucketD = array_fill(0, $maxClass + 1, []);
        $bucketN = array_fill(0, $maxClass + 1, []);
        foreach ($unassigned as $p) {
            $mid = (int)$p['member_id'];
            $cl  = max(0, min($maxClass, (int)($p['friday_last_class'] ?? 0)));
            if (!empty($driverCapable[$mid])) {
                $bucketD[$cl][] = $mid;
            } else {
                $bucketN[$cl][] = $mid;
            }
        }
        $cntD = array_map('count', $bucketD); // class => 運転可能者数
        $cntN = array_map('count', $bucketN); // class => 運転不可者数

        $needs = [];
        for ($j = 0; $j < $m; $j++) {
            $needs[$j] = !empty($needsDriver[$j]);
        }

        // 出発時限 dep ベクトルを枝刈り列挙し、各構成を最小費用流で解いて二乗和最小を求める。
        $res = self::solveOutboundMCMF($floors, $rem, $cntD, $cntN, $maxClass);
        if ($res === null) {
            // 席が足りず全員を割り当て切れない → 呼び出し側の強制割当に委ねる
            return ['assign' => [], 'driverByCar' => []];
        }

        // 運転者要件（needs 車に運転可能者1人）を、待ち時間を変えない範囲で後処理で満たす。
        $res = self::fixOutboundDrivers($res, $needs);

        // 車ごと・class 別の人数（aD/aN）から実際の member_id を割り当てる。
        return self::reconstructOutboundMCMF($res, $needs, $bucketD, $bucketN);
    }

    /**
     * 出発時限 dep ベクトルを枝刈り列挙し、各構成を最小費用流で解いて
     * 「待ち時間の二乗和」が最小になる配置を求める。
     *
     * 目的関数に二乗和を使う理由: 単純合計だと「1人が3限待ち(=3)」と「3人が1限待ち(=3)」が
     * 同点になるが、実運用では後者が望ましい（1人に長時間待たせない）。二乗和なら 9 vs 3 で後者を選ぶ。
     *
     * 授業なし（class=0）の特例: dep<=4 の車のみ可・待ちコスト0（dep>=5 の車には乗せない）。
     *
     * アルゴリズム:
     *   - dep[j] を固定すると各車のコストが class ごとに定数 (dep-class)^2 になり、
     *     乗客割当は最小費用流（輸送問題）で厳密に解ける（総ユニモジュラ→整数最適）。
     *   - dep の候補は「floor[j] または乗客に出現する class（>=floor[j]）」のみで最適を取り逃さない
     *     （実際に乗る最大 class を上回る dep は損なので最適解では選ばれない）。
     *   - 全 dep ベクトルを列挙し、各構成の最小費用流コストの最小を採る。
     *
     * 運転者要件はここでは扱わず、呼び出し側が fixOutboundDrivers で後処理する。
     *
     * @return array|null ['cost'=>二乗和, 'D'=>各車dep, 'aD'=>[j][v]=運転可能者人数, 'aN'=>[j][v]=運転不可者人数] または席不足で null
     */
    private static function solveOutboundMCMF(
        array $floors,
        array $rem,
        array $cntD,
        array $cntN,
        int $maxClass
    ): ?array {
        $m = count($floors);

        // 乗客に出現する class（運転可否合算）
        $present = [];
        for ($v = 0; $v <= $maxClass; $v++) {
            if (($cntD[$v] + $cntN[$v]) > 0) $present[$v] = true;
        }

        // 各車の dep 候補: floor[j] と、出現 class のうち floor[j] 以上
        $cand = [];
        for ($j = 0; $j < $m; $j++) {
            $set = [$floors[$j] => true];
            foreach ($present as $v => $_) {
                if ($v >= $floors[$j]) $set[$v] = true;
            }
            $cand[$j] = array_keys($set);
            sort($cand[$j]);
        }

        $best    = null;
        $bestRes = null;
        $D       = array_fill(0, $m, 0);

        $rec = function ($j) use (&$rec, &$best, &$bestRes, &$D, $cand, $m, $floors, $rem, $cntD, $cntN, $maxClass) {
            if ($j === $m) {
                $r = self::transportOutbound($floors, $rem, $D, $cntD, $cntN, $maxClass);
                if ($r !== null && ($best === null || $r['cost'] < $best)) {
                    $best    = $r['cost'];
                    $bestRes = ['cost' => $r['cost'], 'aD' => $r['aD'], 'aN' => $r['aN'], 'D' => $D];
                }
                return;
            }
            foreach ($cand[$j] as $d) {
                $D[$j] = $d;
                $rec($j + 1);
            }
        };
        $rec(0);

        return $bestRes;
    }

    /**
     * dep ベクトルを固定したときの輸送問題（最小費用流）を解く。
     * 全乗客を席に収められなければ null。
     *
     * @return array|null ['cost'=>二乗和, 'aD'=>[j][v]=運転可能者人数, 'aN'=>[j][v]=運転不可者人数]
     */
    private static function transportOutbound(
        array $floors,
        array $rem,
        array $D,
        array $cntD,
        array $cntN,
        int $maxClass
    ): ?array {
        $m = count($floors);
        $n = array_sum($cntD) + array_sum($cntN);
        if ($n === 0) {
            return ['cost' => 0, 'aD' => array_fill(0, $m, array_fill(0, $maxClass + 1, 0)), 'aN' => array_fill(0, $m, array_fill(0, $maxClass + 1, 0))];
        }

        // ノード番号: S=0, 運転可能class v -> 1+v, 運転不可class v -> (maxClass+2)+v, 車 j -> base+j, T
        $S    = 0;
        $offD = 1;
        $offN = $offD + ($maxClass + 1);
        $offC = $offN + ($maxClass + 1);
        $T    = $offC + $m;
        $mc   = new self();
        $g    = $mc->newFlow($T + 1);

        $edgeId = []; // "D{j}-{v}" / "N{j}-{v}" => 正辺ID

        for ($v = 0; $v <= $maxClass; $v++) {
            if ($cntD[$v] > 0) $mc->flowAddEdge($g, $S, $offD + $v, $cntD[$v], 0);
            if ($cntN[$v] > 0) $mc->flowAddEdge($g, $S, $offN + $v, $cntN[$v], 0);
        }
        for ($j = 0; $j < $m; $j++) {
            $d = $D[$j];
            for ($v = 0; $v <= $d; $v++) {
                if ($v === 0 && $d > 4) continue; // 授業なしは dep<=4 のみ
                $w = ($v === 0) ? 0 : ($d - $v) * ($d - $v);
                if ($cntD[$v] > 0) $edgeId["D{$j}-{$v}"] = $mc->flowAddEdge($g, $offD + $v, $offC + $j, $rem[$j], $w);
                if ($cntN[$v] > 0) $edgeId["N{$j}-{$v}"] = $mc->flowAddEdge($g, $offN + $v, $offC + $j, $rem[$j], $w);
            }
            $mc->flowAddEdge($g, $offC + $j, $T, $rem[$j], 0);
        }

        [$flow, $cost] = $mc->flowRun($g, $S, $T);
        if ($flow !== $n) return null; // 全員乗せられない

        $aD = array_fill(0, $m, array_fill(0, $maxClass + 1, 0));
        $aN = array_fill(0, $m, array_fill(0, $maxClass + 1, 0));
        foreach ($edgeId as $key => $id) {
            $used = $mc->flowUsed($g, $id);
            if ($used <= 0) continue;
            $type = $key[0];
            [$jj, $vv] = explode('-', substr($key, 1));
            if ($type === 'D') $aD[(int)$jj][(int)$vv] = $used;
            else               $aN[(int)$jj][(int)$vv] = $used;
        }
        return ['cost' => $cost, 'aD' => $aD, 'aN' => $aN];
    }

    /**
     * 運転者要件（needs 車に運転可能者を1人以上）を、待ち時間を変えない範囲で後処理で満たす。
     * 運転者のいない needs 車について、同じ class（=同じ待ち時間）の運転可能者を他車から融通する。
     * 融通できない車は driverWarnings に車インデックスを記録する（後段の警告に委ねる）。
     */
    private static function fixOutboundDrivers(array $res, array $needs): array
    {
        $m  = count($res['D']);
        $aD = $res['aD'];
        $aN = $res['aN'];
        $warnings = [];

        for ($j = 0; $j < $m; $j++) {
            if (empty($needs[$j])) continue;
            $hasDriver = array_sum($aD[$j]) > 0;
            $hasAny    = (array_sum($aD[$j]) + array_sum($aN[$j])) > 0;
            if ($hasDriver || !$hasAny) continue; // 既に運転者あり、または乗客0（booker のみ）

            // 車 j の運転不可者（class v）を、他車 k の同 class 運転可能者と交換（dep 不変＝待ち不変）
            $swapped = false;
            for ($v = 0; $v <= 6 && !$swapped; $v++) {
                if ($aN[$j][$v] === 0) continue;
                for ($k = 0; $k < $m; $k++) {
                    if ($k === $j || $aD[$k][$v] === 0) continue;
                    $kDrivers = array_sum($aD[$k]);
                    $kNeeds   = !empty($needs[$k]);
                    // k から運転者を1人抜いても k の要件を壊さない
                    if (($kNeeds && $kDrivers >= 2) || (!$kNeeds && $kDrivers >= 1)) {
                        $aN[$j][$v]--; $aD[$j][$v]++;
                        $aD[$k][$v]--; $aN[$k][$v]++;
                        $swapped = true;
                        break;
                    }
                }
            }
            if (!$swapped) $warnings[] = $j;
        }

        $res['aD'] = $aD;
        $res['aN'] = $aN;
        $res['driverWarnings'] = $warnings;
        return $res;
    }

    /**
     * solveOutboundMCMF / fixOutboundDrivers の結果（車ごと・class 別の人数）から、
     * class 別バケットの member_id を各車へ割り当てる。
     *
     * @return array ['assign' => member_id => 車インデックス, 'driverByCar' => 車インデックス => member_id]
     */
    private static function reconstructOutboundMCMF(array $res, array $needs, array $bucketD, array $bucketN): array
    {
        $m  = count($res['D']);
        $aD = $res['aD'];
        $aN = $res['aN'];
        $bD = $bucketD;
        $bN = $bucketN;

        $assign      = [];
        $driverByCar = [];

        for ($j = 0; $j < $m; $j++) {
            // 運転可能者を取り出す（needs 車なら最初の1人を運転者役に）
            foreach ($aD[$j] as $v => $cnt) {
                for ($t = 0; $t < $cnt; $t++) {
                    $mid = array_pop($bD[$v]);
                    if ($mid === null) continue;
                    $assign[$mid] = $j;
                    if ($needs[$j] && empty($driverByCar[$j])) {
                        $driverByCar[$j] = $mid;
                    }
                }
            }
            // 運転不可者を取り出す
            foreach ($aN[$j] as $v => $cnt) {
                for ($t = 0; $t < $cnt; $t++) {
                    $mid = array_pop($bN[$v]);
                    if ($mid === null) continue;
                    $assign[$mid] = $j;
                }
            }
        }

        return ['assign' => $assign, 'driverByCar' => $driverByCar];
    }

    // ===== 最小費用流（SSP / SPFA ベース、整数容量）=====
    // 軽量な内部ヘルパ。flow 構造体は連想配列で持ち回る。

    /** 新しいフロー構造体を生成 */
    private function newFlow(int $n): array
    {
        return ['n' => $n, 'to' => [], 'cap' => [], 'cost' => [], 'head' => array_fill(0, $n, -1), 'next' => []];
    }

    /** 有向辺 u->v（容量 cap・コスト cost）と逆辺を追加し、正辺の ID を返す */
    private function flowAddEdge(array &$g, int $u, int $v, int $cap, int $cost): int
    {
        $id = count($g['to']);
        $g['to'][]   = $v; $g['cap'][]  = $cap; $g['cost'][] = $cost; $g['next'][] = $g['head'][$u]; $g['head'][$u] = $id;
        $g['to'][]   = $u; $g['cap'][]  = 0;    $g['cost'][] = -$cost; $g['next'][] = $g['head'][$v]; $g['head'][$v] = $id + 1;
        return $id;
    }

    /** 正辺 id に流れた量（= 逆辺の残容量） */
    private function flowUsed(array $g, int $id): int
    {
        return $g['cap'][$id ^ 1];
    }

    /** s->t の最小費用最大流を求め [flow, cost] を返す（SPFA で最短コスト路を繰り返す） */
    private function flowRun(array &$g, int $s, int $t): array
    {
        $N = $g['n'];
        $flow = 0; $cost = 0;
        while (true) {
            $dist = array_fill(0, $N, PHP_INT_MAX);
            $inq  = array_fill(0, $N, false);
            $pe   = array_fill(0, $N, -1);
            $dist[$s] = 0;
            $queue = [$s]; $inq[$s] = true;
            while ($queue) {
                $u = array_shift($queue); $inq[$u] = false;
                for ($e = $g['head'][$u]; $e !== -1; $e = $g['next'][$e]) {
                    if ($g['cap'][$e] <= 0) continue;
                    $v = $g['to'][$e];
                    if ($dist[$u] !== PHP_INT_MAX && $dist[$u] + $g['cost'][$e] < $dist[$v]) {
                        $dist[$v] = $dist[$u] + $g['cost'][$e];
                        $pe[$v]   = $e;
                        if (!$inq[$v]) { $inq[$v] = true; $queue[] = $v; }
                    }
                }
            }
            if ($dist[$t] === PHP_INT_MAX) break;
            // 経路上の最小残容量
            $f = PHP_INT_MAX;
            for ($v = $t; $v !== $s; ) { $e = $pe[$v]; $f = min($f, $g['cap'][$e]); $v = $g['to'][$e ^ 1]; }
            for ($v = $t; $v !== $s; ) { $e = $pe[$v]; $g['cap'][$e] -= $f; $g['cap'][$e ^ 1] += $f; $v = $g['to'][$e ^ 1]; }
            $flow += $f;
            $cost += $f * $dist[$t];
        }
        return [$flow, $cost];
    }

    /**
     * 復路の車を自動作成し、乗客を住所ベースで割り当てる
     *
     * モード:
     *   by_station    - 参加者の住所から最適な下車主要駅を解決し、同じ駅グループを同じ車に乗せる
     *   by_driver_home - ドライバーの家の近くに住む参加者を同じ車に乗せる
     *
     * 動作:
     *   1. 既存の復路専用車を削除してリセット
     *   2. can_book_car=1 の参加者ごとに復路車を作成してドライバー登録
     *   3. 各参加者の住所をジオコーディング → 下車駅/距離で車に割り当て
     *
     * 戻り値: ['cars' => [...], 'warnings' => [...], 'station_summary' => [...]]
     */
    public static function autoAssignReturn(int $expedition_id, string $mode = 'by_station', array $capacities = [], array $preferredStations = []): array
    {
        $db        = Database::getInstance();
        $warnings  = [];
        $sortOrder = 1;

        // 既存の復路専用車を削除
        $existingCars = $db->fetchAll(
            "SELECT id FROM expedition_cars WHERE expedition_id = ? AND trip_type = 'return'",
            [$expedition_id]
        );
        foreach ($existingCars as $ec) {
            $db->execute("DELETE FROM expedition_car_payers  WHERE car_id = ?", [$ec['id']]);
            $db->execute("DELETE FROM expedition_car_members WHERE car_id = ?", [$ec['id']]);
            $db->execute("DELETE FROM expedition_cars        WHERE id = ?",     [$ec['id']]);
        }

        // 車に乗る参加者を全員取得（住所付き）。
        // 車を予約する人（can_book_car=1）は乗車扱いが漏れていても必ず含める。
        $participants = $db->fetchAll(
            "SELECT ep.*, m.name_kanji, m.name_kana, m.address
             FROM expedition_participants ep
             JOIN members m ON m.id = ep.member_id
             WHERE ep.expedition_id = ? AND (ep.is_joining_car = 1 OR ep.can_book_car = 1)
             ORDER BY m.name_kana ASC",
            [$expedition_id]
        );

        if (empty($participants)) {
            return ['cars' => [], 'warnings' => ['車に乗る参加者がいません'], 'station_summary' => []];
        }

        // ドライバー（can_book_car=1）を抽出
        $drivers = array_values(array_filter($participants, fn($p) => (int)($p['can_book_car'] ?? 0) === 1));

        if (empty($drivers)) {
            return ['cars' => [], 'warnings' => ['車を予約できる人が登録されていません'], 'station_summary' => []];
        }

        // ドライバーごとに復路車を作成し、住所をジオコーディング
        $cars = []; // [['car_id', 'car_name', 'lat', 'lng', 'drop_station', 'capacity'], ...]

        $validStations = StationResolverService::MAJOR_STATIONS;

        foreach ($drivers as $driver) {
            // 希望駅が指定されていればそれを優先、なければ住所から解決
            $mid         = $driver['member_id'];
            $coords      = null;
            $dropStation = '高田馬場'; // デフォルト

            if (!empty($preferredStations[$mid]) && in_array($preferredStations[$mid], $validStations, true)) {
                $dropStation = $preferredStations[$mid];
            } elseif (!empty($driver['address'])) {
                $coords = StationResolverService::geocodeAddress($driver['address']);
                if ($coords) {
                    $dropStation = StationResolverService::resolveDropStationByLatLng($coords['lat'], $coords['lng']);
                } else {
                    $warnings[] = "【{$driver['name_kanji']}（ドライバー）】住所のジオコーディングに失敗しました（デフォルト: 高田馬場）";
                }
            } else {
                $warnings[] = "【{$driver['name_kanji']}（ドライバー）】住所が登録されていません（デフォルト: 高田馬場）";
            }

            // 車名: "ドライバー名車（下車駅方面）"
            $carName = $driver['name_kanji'] . '車（' . $dropStation . '方面）';

            $capacity = isset($capacities[$driver['member_id']]) ? (int)$capacities[$driver['member_id']] : 6;
            $capacity = max(1, $capacity);

            $carId = $db->insert(
                "INSERT INTO expedition_cars
                 (expedition_id, name, capacity, rental_fee, highway_fee, trip_type, departure_class, sort_order)
                 VALUES (?, ?, ?, 0, 0, 'return', NULL, ?)",
                [$expedition_id, $carName, $capacity, $sortOrder++]
            );

            // ドライバーを登録
            $driverRole = ($driver['driver_type'] === 'sub_driver') ? 'sub_driver' : 'driver';
            $db->insert(
                "INSERT INTO expedition_car_members (car_id, member_id, role, is_excluded, sort_order)
                 VALUES (?, ?, ?, 0, 0)",
                [$carId, $driver['member_id'], $driverRole]
            );

            $cars[] = [
                'car_id'       => $carId,
                'car_name'     => $carName,
                'lat'          => $coords['lat'] ?? null,
                'lng'          => $coords['lng'] ?? null,
                'drop_station' => $dropStation,
                'capacity'     => $capacity,
            ];
        }

        // ドライバー以外の参加者を事前にジオコーディング
        $driverIds  = array_map(fn($d) => (int)$d['member_id'], $drivers);
        $passengers = array_values(array_filter($participants, fn($p) => !in_array((int)$p['member_id'], $driverIds)));

        foreach ($passengers as &$p) {
            $p['coords']       = null;
            $p['drop_station'] = '高田馬場';

            if (!empty($p['address'])) {
                $coords = StationResolverService::geocodeAddress($p['address']);
                if ($coords) {
                    $p['coords']       = $coords;
                    $p['drop_station'] = StationResolverService::resolveDropStationByLatLng($coords['lat'], $coords['lng']);
                } else {
                    $warnings[] = "【{$p['name_kanji']}】住所のジオコーディングに失敗しました（デフォルト: 高田馬場）";
                }
            }
        }
        unset($p);

        // 各参加者を車に割り当て
        foreach ($passengers as $passenger) {
            $targetCarId = null;

            if ($mode === 'by_station') {
                // 下車駅が一致する車（空席あり）を探す
                foreach ($cars as $car) {
                    if ($car['drop_station'] === $passenger['drop_station']) {
                        $cnt = (int)$db->fetch(
                            "SELECT COUNT(*) as cnt FROM expedition_car_members WHERE car_id = ?",
                            [$car['car_id']]
                        )['cnt'];
                        if ($cnt < $car['capacity']) {
                            $targetCarId = $car['car_id'];
                            break;
                        }
                    }
                }

                // 一致する車がない場合は最も空いている車
                if (!$targetCarId) {
                    $minCount = PHP_INT_MAX;
                    foreach ($cars as $car) {
                        $cnt = (int)$db->fetch(
                            "SELECT COUNT(*) as cnt FROM expedition_car_members WHERE car_id = ?",
                            [$car['car_id']]
                        )['cnt'];
                        if ($cnt < $car['capacity'] && $cnt < $minCount) {
                            $minCount    = $cnt;
                            $targetCarId = $car['car_id'];
                        }
                    }
                    if ($targetCarId) {
                        $warnings[] = "【{$passenger['name_kanji']}（{$passenger['drop_station']}方面）】一致する車がないため別の車に割り当てました";
                    }
                }
            } else {
                // by_driver_home: ドライバーの家に最も近い車（空席あり）を探す
                if ($passenger['coords'] !== null) {
                    $minDist = PHP_FLOAT_MAX;
                    foreach ($cars as $car) {
                        if ($car['lat'] === null) continue;
                        $cnt = (int)$db->fetch(
                            "SELECT COUNT(*) as cnt FROM expedition_car_members WHERE car_id = ?",
                            [$car['car_id']]
                        )['cnt'];
                        if ($cnt < $car['capacity']) {
                            $dist = StationResolverService::calcDistance(
                                $passenger['coords']['lat'], $passenger['coords']['lng'],
                                $car['lat'], $car['lng']
                            );
                            if ($dist < $minDist) {
                                $minDist     = $dist;
                                $targetCarId = $car['car_id'];
                            }
                        }
                    }
                }

                // 座標なし or 全ドライバー座標なし → 最も空いている車
                if (!$targetCarId) {
                    $minCount = PHP_INT_MAX;
                    foreach ($cars as $car) {
                        $cnt = (int)$db->fetch(
                            "SELECT COUNT(*) as cnt FROM expedition_car_members WHERE car_id = ?",
                            [$car['car_id']]
                        )['cnt'];
                        if ($cnt < $car['capacity'] && $cnt < $minCount) {
                            $minCount    = $cnt;
                            $targetCarId = $car['car_id'];
                        }
                    }
                }
            }

            // 全車満席 → 最後の車に強制割り当て
            if (!$targetCarId) {
                $lastCar     = end($cars);
                $targetCarId = $lastCar['car_id'];
                $warnings[]  = "【{$passenger['name_kanji']}】全車満席のため「{$lastCar['car_name']}」に強制割り当てしました";
            }

            $so = (int)$db->fetch(
                "SELECT COUNT(*) as cnt FROM expedition_car_members WHERE car_id = ?",
                [$targetCarId]
            )['cnt'];

            // driver_type に基づいてロールを決定
            $passRole = 'passenger';
            if (($passenger['driver_type'] ?? 'none') === 'driver')     $passRole = 'driver';
            if (($passenger['driver_type'] ?? 'none') === 'sub_driver') $passRole = 'sub_driver';

            $db->insert(
                "INSERT INTO expedition_car_members (car_id, member_id, role, is_excluded, sort_order)
                 VALUES (?, ?, ?, 0, ?)",
                [$targetCarId, $passenger['member_id'], $passRole, $so]
            );
        }

        // サブドライバー不足チェック
        foreach ($cars as $car) {
            $members      = $db->fetchAll(
                "SELECT role FROM expedition_car_members WHERE car_id = ?",
                [$car['car_id']]
            );
            // 運転できる人（driver / sub_driver のいずれか）の人数で判定する。
            // 2人目はメインドライバーでもサブドライバーでも可。
            $driverCount = 0;
            foreach ($members as $m) {
                if ($m['role'] === 'driver' || $m['role'] === 'sub_driver') $driverCount++;
            }
            if ($driverCount === 0) {
                $warnings[] = "【{$car['car_name']}】ドライバーがいません";
            } elseif ($driverCount < 2) {
                $warnings[] = "【{$car['car_name']}】運転できる人が1人しかいません（2人目のドライバーが必要です）";
            }
        }

        // 下車駅サマリー（car_id => drop_station）
        $stationSummary = [];
        foreach ($cars as $car) {
            $stationSummary[$car['car_id']] = $car['drop_station'];
        }

        return [
            'cars'            => self::findByExpedition($expedition_id),
            'warnings'        => $warnings,
            'station_summary' => $stationSummary,
        ];
    }
}
