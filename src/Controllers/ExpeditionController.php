<?php
/**
 * 遠征コントローラー
 */
class ExpeditionController
{
    public function __construct()
    {
        Auth::requirePermission('expeditions');
    }

    // ==================== 一覧・詳細・CRUD ====================

    /**
     * 遠征一覧取得
     * GET /api/expeditions
     */
    public function index(): void
    {
        $expeditions = Expedition::findAll();
        Response::success($expeditions);
    }

    /**
     * 遠征詳細取得
     * GET /api/expeditions/{id}
     */
    public function show(array $params): void
    {
        $expedition = Expedition::findById($params['id']);
        if (!$expedition) Response::error('遠征が見つかりません', 404);
        $expedition['participants'] = ExpeditionParticipant::findByExpedition($params['id']);
        $expedition['cars']        = ExpeditionCar::findByExpedition($params['id']);
        $expedition['teams']       = ExpeditionTeam::findByExpedition($params['id']);
        Response::success($expedition);
    }

    /**
     * 遠征作成
     * POST /api/expeditions
     */
    public function store(): void
    {
        Request::validate(['name' => 'required', 'start_date' => 'required', 'end_date' => 'required']);
        $data       = Request::only(['name', 'start_date', 'end_date', 'deadline', 'base_fee', 'pre_night_fee', 'lunch_fee', 'capacity_male', 'capacity_female']);
        $expedition = Expedition::create($data);
        Response::success($expedition, 201);
    }

    /**
     * 遠征更新
     * PUT /api/expeditions/{id}
     */
    public function update(array $params): void
    {
        $data       = Request::only(['name', 'start_date', 'end_date', 'deadline', 'application_start', 'base_fee', 'pre_night_fee', 'lunch_fee', 'capacity_male', 'capacity_female', 'expense_deadline', 'subsidy']);
        $expedition = Expedition::update($params['id'], $data);
        Response::success($expedition);
    }

    /**
     * 遠征削除
     * DELETE /api/expeditions/{id}
     */
    public function destroy(array $params): void
    {
        Expedition::delete($params['id']);
        Response::success(['message' => '遠征を削除しました']);
    }

    // ==================== 参加者管理 ====================

    /**
     * 参加者一覧取得
     * GET /api/expeditions/{id}/participants
     */
    public function getParticipants(array $params): void
    {
        $participants = ExpeditionParticipant::findByExpedition($params['id']);
        Response::success($participants);
    }

    /**
     * 参加者追加
     * POST /api/expeditions/{id}/participants
     */
    public function addParticipant(array $params): void
    {
        $data        = Request::json();
        $participant = ExpeditionParticipant::add($params['id'], $data['member_id']);
        Response::success($participant, 201);
    }

    /**
     * 参加者更新
     * PUT /api/expeditions/{id}/participants/{pid}
     */
    public function updateParticipant(array $params): void
    {
        $data        = Request::only([
            'pre_night', 'lunch', 'status',
            'is_joining_car', 'driver_type', 'timescar_number', 'can_book_car', 'friday_last_class',
        ]);
        $participant = ExpeditionParticipant::update($params['pid'], $data);
        Response::success($participant);
    }

    /**
     * 参加者削除
     * DELETE /api/expeditions/{id}/participants/{pid}
     */
    public function removeParticipant(array $params): void
    {
        ExpeditionParticipant::remove($params['pid']);
        Response::success(['message' => '参加者を削除しました']);
    }

    // ==================== 車割管理 ====================

    /**
     * 車一覧取得
     * GET /api/expeditions/{id}/cars
     */
    public function getCars(array $params): void
    {
        $cars = ExpeditionCar::findByExpedition($params['id']);
        Response::success($cars);
    }

    /**
     * 車追加
     * POST /api/expeditions/{id}/cars
     */
    public function addCar(array $params): void
    {
        $data = Request::only(['name', 'capacity', 'rental_fee', 'highway_fee', 'trip_type']);
        $car  = ExpeditionCar::create($params['id'], $data);
        Response::success($car, 201);
    }

    /**
     * 車更新
     * PUT /api/expeditions/{id}/cars/{cid}
     */
    public function updateCar(array $params): void
    {
        $data = Request::only(['name', 'capacity', 'rental_fee', 'highway_fee', 'sort_order']);
        $car  = ExpeditionCar::update($params['cid'], $data);
        Response::success($car);
    }

    /**
     * 車削除
     * DELETE /api/expeditions/{id}/cars/{cid}
     */
    public function removeCar(array $params): void
    {
        ExpeditionCar::delete($params['cid']);
        Response::success(['message' => '車を削除しました']);
    }

    /**
     * 乗員追加
     * POST /api/expeditions/{id}/cars/{cid}/members
     */
    public function addCarMember(array $params): void
    {
        $data   = Request::json();
        $member = ExpeditionCarMember::add($params['cid'], $data['member_id'], $data['role'] ?? 'passenger');
        Response::success($member, 201);
    }

    /**
     * 乗員更新
     * PUT /api/expeditions/{id}/cars/{cid}/members/{mid}
     */
    public function updateCarMember(array $params): void
    {
        $data   = Request::only(['role', 'is_excluded', 'sort_order']);
        $member = ExpeditionCarMember::update($params['mid'], $data);
        Response::success($member);
    }

    /**
     * 乗員削除
     * DELETE /api/expeditions/{id}/cars/{cid}/members/{mid}
     */
    public function removeCarMember(array $params): void
    {
        ExpeditionCarMember::remove($params['mid']);
        Response::success(['message' => '乗員を削除しました']);
    }

    /**
     * 立替者追加
     * POST /api/expeditions/{id}/cars/{cid}/payers
     */
    public function addCarPayer(array $params): void
    {
        $data  = Request::json();
        $payer = ExpeditionCarPayer::add($params['cid'], $data['member_id'], $data['amount'] ?? 0);
        Response::success($payer, 201);
    }

    /**
     * 立替者削除
     * DELETE /api/expeditions/{id}/cars/{cid}/payers/{pid}
     */
    public function removeCarPayer(array $params): void
    {
        ExpeditionCarPayer::remove($params['pid']);
        Response::success(['message' => '立替者を削除しました']);
    }

    /**
     * 車代清算計算
     * GET /api/expeditions/{id}/cars/settlement
     */
    public function getSettlement(array $params): void
    {
        $settlement = ExpeditionCar::calculateSettlement($params['id']);
        Response::success($settlement);
    }

    /**
     * 往路の車割を自動作成
     * POST /api/expeditions/{id}/cars/auto-assign
     */
    public function autoAssignCars(array $params): void
    {
        Auth::requirePermission('expeditions');
        $body            = Request::json();
        $capacities      = $body['capacities']   ?? [];
        $soloBookers     = is_array($body['solo_bookers'] ?? null)     ? array_map('intval', $body['solo_bookers'])     : [];
        $selectedBookers = is_array($body['selected_bookers'] ?? null) ? array_map('intval', $body['selected_bookers']) : [];
        // 乗員の固定指定: { member_id: car_owner_member_id, ... }（人 → 乗せる車のドライバー）
        $pinnedMembers   = [];
        if (is_array($body['pinned_members'] ?? null)) {
            foreach ($body['pinned_members'] as $memberId => $carOwnerId) {
                $pinnedMembers[(int)$memberId] = (int)$carOwnerId;
            }
        }
        $result          = ExpeditionCar::autoAssignOutbound((int)$params['id'], $capacities, $soloBookers, $selectedBookers, $pinnedMembers);
        Response::success($result);
    }

    /**
     * 参加者全員の理想的な下車主要駅を解決して返す
     * POST /api/expeditions/{id}/cars/resolve-stations
     * 戻り値: { member_id: "高田馬場"|"新宿"|"渋谷"|"東京"|null, ... }
     *   null = 住所未登録またはジオコーディング失敗
     */
    public function resolveParticipantStations(array $params): void
    {
        Auth::requirePermission('expeditions');
        $participants = ExpeditionParticipant::findByExpedition((int)$params['id']);
        $result       = [];

        foreach ($participants as $p) {
            if (empty($p['address'])) {
                $result[$p['member_id']] = null;
                continue;
            }
            $coords = StationResolverService::geocodeAddress($p['address']);
            if (!$coords) {
                $result[$p['member_id']] = null;
                continue;
            }
            $result[$p['member_id']] = StationResolverService::resolveAllByLatLng($coords['lat'], $coords['lng']);
        }

        Response::success($result);
    }

    /**
     * 復路の車割を住所ベースで自動作成
     * POST /api/expeditions/{id}/cars/auto-assign-return
     * ボディ: { "mode": "by_station" | "by_driver_home", "capacities": { member_id: capacity, ... }, "preferred_stations": { member_id: "高田馬場"|"新宿"|"渋谷"|"東京", ... } }
     */
    public function autoAssignReturnCars(array $params): void
    {
        Auth::requirePermission('expeditions');
        $body              = Request::json();
        $mode              = in_array($body['mode'] ?? '', ['by_station', 'by_driver_home'])
                             ? $body['mode'] : 'by_station';
        $capacities        = is_array($body['capacities'] ?? null)         ? $body['capacities']         : [];
        $preferredStations = is_array($body['preferred_stations'] ?? null) ? $body['preferred_stations'] : [];
        $result            = ExpeditionCar::autoAssignReturn((int)$params['id'], $mode, $capacities, $preferredStations);
        Response::success($result);
    }

    // ==================== チーム管理 ====================

    /**
     * チーム一覧取得
     * GET /api/expeditions/{id}/teams
     */
    public function getTeams(array $params): void
    {
        $expedition_id = (int)$params['id'];
        $teams         = ExpeditionTeam::findByExpedition($expedition_id);

        // チームに割り当て済みの member_id を収集
        $assignedMemberIds = [];
        foreach ($teams as $team) {
            foreach ($team['members'] as $m) {
                $assignedMemberIds[] = (int)$m['member_id'];
            }
        }

        // 未割り当て参加者（どのチームにも属していない）
        $allParticipants = ExpeditionParticipant::findByExpedition($expedition_id);
        $unassigned = array_values(array_filter($allParticipants, function ($p) use ($assignedMemberIds) {
            return !in_array((int)$p['member_id'], $assignedMemberIds);
        }));

        Response::success(['unassigned' => $unassigned, 'teams' => $teams]);
    }

    /**
     * チーム追加
     * POST /api/expeditions/{id}/teams
     */
    public function addTeam(array $params): void
    {
        $data = Request::json();
        $team = ExpeditionTeam::create($params['id'], $data['name']);
        Response::success($team, 201);
    }

    /**
     * チーム更新
     * PUT /api/expeditions/{id}/teams/{tid}
     */
    public function updateTeam(array $params): void
    {
        $data = Request::only(['name', 'sort_order']);
        $team = ExpeditionTeam::update($params['tid'], $data);
        Response::success($team);
    }

    /**
     * チーム削除
     * DELETE /api/expeditions/{id}/teams/{tid}
     */
    public function removeTeam(array $params): void
    {
        ExpeditionTeam::delete($params['tid']);
        Response::success(['message' => 'チームを削除しました']);
    }

    /**
     * チームメンバー追加
     * POST /api/expeditions/{id}/teams/{tid}/members
     */
    public function addTeamMember(array $params): void
    {
        $data   = Request::json();
        $member = ExpeditionTeamMember::add($params['tid'], $data['member_id']);
        Response::success($member, 201);
    }

    /**
     * チームメンバー削除
     * DELETE /api/expeditions/{id}/teams/{tid}/members/{mid}
     */
    public function removeTeamMember(array $params): void
    {
        ExpeditionTeamMember::remove($params['mid']);
        Response::success(['message' => 'メンバーを削除しました']);
    }

    /**
     * チーム並び順更新
     * PUT /api/expeditions/{id}/teams/order
     */
    public function updateTeamOrder(array $params): void
    {
        $data = Request::json();
        ExpeditionTeamMember::updateOrder($data);
        Response::success(['message' => '並び順を更新しました']);
    }

    // ==================== 集金管理 ====================

    /**
     * 集金一覧取得
     * GET /api/expeditions/{id}/collection
     */
    public function getCollection(array $params): void
    {
        $collections = ExpeditionCollection::findByExpedition($params['id']);
        Response::success($collections);
    }

    /**
     * 集金生成
     * POST /api/expeditions/{id}/collection/generate
     */
    public function generateCollection(array $params): void
    {
        $body       = Request::json();
        $round      = $body['round'] ?? 1;
        $round      = intval($round);
        $title      = $round === 1 ? '遠征前集金（参加費）' : '遠征後集金（車代清算）';

        $expeditionId = (int)$params['id'];

        // 既存集金の削除＋集金作成＋参加者数分の明細生成は、業務的には「集金を作成」の
        // 1操作。内部の多数のDELETE/INSERTを1行にまとめて記録する（参加者が多いと
        // そのままでは「n件削除」「n件作成」が並んで誤解を招く）。
        $run = function () use ($expeditionId, $round, $title) {
            // 同じ回次の既存集金を削除（明細はCASCADEで自動削除）
            ExpeditionCollection::deleteByRound($expeditionId, $round);
            $collection = ExpeditionCollection::create($expeditionId, $round, $title);
            ExpeditionCollection::generateItems($collection['id']);
            // ログ用に生成した明細件数を控える
            $cnt = Database::getInstance()->fetch(
                "SELECT COUNT(*) AS c FROM expedition_collection_items WHERE collection_id = ?",
                [$collection['id']]
            );
            $collection['_item_count'] = (int)($cnt['c'] ?? 0);
            return $collection;
        };

        if (class_exists('AuditLogger')) {
            $collection = AuditLogger::group([
                'feature'      => 'expeditions',
                'method'       => 'POST',
                'target_table' => 'expeditions',
                'target_id'    => $expeditionId,
                'action_label' => $title . 'を作成',
                'resolve'      => function ($c) {
                    return ['changes' => ['集金明細' => (int)($c['_item_count'] ?? 0) . '件を生成']];
                },
            ], $run);
        } else {
            $collection = $run();
        }
        unset($collection['_item_count']);
        Response::success($collection);
    }

    /**
     * 集金期限更新
     * PUT /api/expeditions/{id}/collection/{cid}
     */
    public function updateCollection(array $params): void
    {
        $data = Request::only(['deadline']);
        $db   = Database::getInstance();
        $db->execute(
            "UPDATE expedition_collections SET deadline = ? WHERE id = ? AND expedition_id = ?",
            [$data['deadline'] ?: null, (int)$params['cid'], (int)$params['id']]
        );
        $collection = $db->fetch("SELECT * FROM expedition_collections WHERE id = ?", [(int)$params['cid']]);
        Response::success($collection);
    }

    /**
     * 集金明細更新
     * PUT /api/expeditions/{id}/collection/{cid}/items/{iid}
     */
    public function updateCollectionItem(array $params): void
    {
        $data = Request::only(['paid', 'memo', 'amount']);
        $item = ExpeditionCollectionItem::update($params['iid'], $data);
        Response::success($item);
    }

    // ==================== 申し込みURL ====================

    /**
     * 申し込みURL取得
     * GET /api/expeditions/{id}/application-url
     */
    public function getApplicationUrl(array $params): void
    {
        $expedition = Expedition::findById($params['id']);
        $token      = ExpeditionToken::findByExpedition($params['id']);

        $base = [
            'deadline'          => $expedition['deadline']          ?? null,
            'application_start' => $expedition['application_start'] ?? null,
        ];

        if (!$token) {
            Response::success(array_merge($base, ['has_token' => false]));
            return;
        }

        // expires_at の有効期限チェック
        $expiresAt = $token['expires_at'] ?? null;
        $isExpired = $expiresAt && strtotime($expiresAt) < time();
        $baseUrl   = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $applyUrl  = $baseUrl . '/apply/expedition/' . $token['token'];

        Response::success(array_merge($base, [
            'has_token' => true,
            'url'       => $applyUrl,
            'token'     => [
                'id'        => $token['id'],
                'token'     => $token['token'],
                'is_active' => $isExpired ? 0 : 1,
                'deadline'  => $expiresAt,
            ],
        ]));
    }

    /**
     * 申し込みURL生成
     * POST /api/expeditions/{id}/application-url
     */
    public function generateApplicationUrl(array $params): void
    {
        $token      = ExpeditionToken::generate($params['id']);
        $expiresAt  = $token['expires_at'] ?? null;
        $baseUrl    = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $applyUrl   = $baseUrl . '/apply/expedition/' . $token['token'];

        Response::success([
            'has_token' => true,
            'url'       => $applyUrl,
            'token'     => [
                'id'        => $token['id'],
                'token'     => $token['token'],
                'is_active' => 1,
                'deadline'  => $expiresAt,
            ],
        ]);
    }
}
