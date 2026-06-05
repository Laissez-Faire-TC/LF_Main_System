<?php
/**
 * 集金管理コントローラー（管理者用）
 */
class CollectionController
{
    private CampCollection $collectionModel;
    private CampCollectionItem $itemModel;

    public function __construct()
    {
        Auth::requirePermission('camps');
        $this->collectionModel = new CampCollection();
        $this->itemModel       = new CampCollectionItem();
    }

    /**
     * GET /api/camps/{id}/collection
     * 集金情報＋提出済み・未提出アイテムを返す
     */
    public function get(array $params): void
    {
        $campId     = (int)$params['id'];
        $collection = $this->collectionModel->findByCampId($campId);

        if (!$collection) {
            // 計算結果の平均負担額をフォームのデフォルト金額として提案する
            $suggestedAmount = null;
            try {
                $calculationService = new CalculationService();
                $calc = $calculationService->calculate($campId);
                if (!empty($calc['summary']['average_amount'])) {
                    $suggestedAmount = (int)$calc['summary']['average_amount'];
                }
            } catch (Exception $e) {
                // 計算失敗時はnullのまま（フォームは空欄で表示）
            }

            Response::success([
                'collection'      => null,
                'submitted'       => [],
                'unsubmitted'     => [],
                'suggested_amount' => $suggestedAmount,
            ]);
            return;
        }

        // 後から申し込んだ参加者を自動的に追加（INSERT IGNORE なので既存行は重複しない）
        $this->collectionModel->initializeItems((int)$collection['id'], $campId);

        $allItems    = $this->itemModel->getByCollectionId((int)$collection['id']);
        $submitted   = array_values(array_filter($allItems, fn($i) => (int)$i['submitted'] === 1));
        $unsubmitted = array_values(array_filter($allItems, fn($i) => (int)$i['submitted'] === 0));

        // 計算結果の平均負担額を提案値として含める
        $suggestedAmount = null;
        try {
            $calculationService = new CalculationService();
            $calc = $calculationService->calculate($campId);
            if (!empty($calc['summary']['average_amount'])) {
                $suggestedAmount = (int)$calc['summary']['average_amount'];
            }
        } catch (Exception $e) {
            // 計算失敗時はnullのまま
        }

        Response::success([
            'collection'       => $collection,
            'submitted'        => $submitted,
            'unsubmitted'      => $unsubmitted,
            'suggested_amount' => $suggestedAmount,
        ]);
    }

    /**
     * POST /api/camps/{id}/collection
     * 集金レコードを新規作成する
     */
    public function store(array $params): void
    {
        $campId = (int)$params['id'];

        $errors = Request::validate([
            'default_amount' => 'required|integer',
            'deadline'       => 'required|date',
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        // すでに存在する場合はエラー
        if ($this->collectionModel->findByCampId($campId)) {
            Response::error('この合宿の集金はすでに作成されています', 400, 'ALREADY_EXISTS');
            return;
        }

        $id = $this->collectionModel->create([
            'camp_id'        => $campId,
            'default_amount' => (int)Request::get('default_amount'),
            'deadline'       => Request::get('deadline'),
        ]);

        $collection = $this->collectionModel->findById($id);
        $allItems   = $this->itemModel->getByCollectionId($id);
        $submitted  = array_values(array_filter($allItems, fn($i) => (int)$i['submitted'] === 1));
        $unsubmitted= array_values(array_filter($allItems, fn($i) => (int)$i['submitted'] === 0));

        Response::success([
            'collection'  => $collection,
            'submitted'   => $submitted,
            'unsubmitted' => $unsubmitted,
        ], '集金を作成しました');
    }

    /**
     * PUT /api/camps/{id}/collection
     * deadline / default_amount を更新する
     */
    public function update(array $params): void
    {
        $campId     = (int)$params['id'];
        $collection = $this->collectionModel->findByCampId($campId);

        if (!$collection) {
            Response::error('集金が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $data = [];

        $amount = Request::get('default_amount');
        if ($amount !== null) {
            $data['default_amount'] = (int)$amount;
        }

        $deadline = Request::get('deadline');
        if ($deadline !== null) {
            $data['deadline'] = $deadline;
        }

        $isActive = Request::get('is_active');
        if ($isActive !== null) {
            $data['is_active'] = (int)$isActive;
        }

        $this->collectionModel->update((int)$collection['id'], $data);

        Response::success([], '更新しました');
    }

    /**
     * DELETE /api/camps/{id}/collection
     * 集金レコードを削除する
     */
    public function destroy(array $params): void
    {
        $campId     = (int)$params['id'];
        $collection = $this->collectionModel->findByCampId($campId);

        if (!$collection) {
            Response::error('集金が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $this->collectionModel->delete((int)$collection['id']);
        Response::success([], '削除しました');
    }

    /**
     * PUT /api/collection-items/{id}
     * アイテムの custom_amount を更新する（空→NULL）
     */
    public function updateItem(array $params): void
    {
        $itemId = (int)$params['id'];
        $item   = $this->itemModel->find($itemId);

        if (!$item) {
            Response::error('アイテムが見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $rawAmount    = Request::get('custom_amount');
        $customAmount = ($rawAmount === null || $rawAmount === '') ? null : (int)$rawAmount;

        $this->itemModel->update($itemId, ['custom_amount' => $customAmount]);
        Response::success([], '更新しました');
    }

    /**
     * POST /api/collection-items/{id}/confirm
     * admin_confirmed をトグルする
     */
    public function toggleConfirm(array $params): void
    {
        $itemId = (int)$params['id'];
        $item   = $this->itemModel->find($itemId);

        if (!$item) {
            Response::error('アイテムが見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $newValue = (int)$item['admin_confirmed'] === 1 ? 0 : 1;
        $this->itemModel->update($itemId, ['admin_confirmed' => $newValue]);

        Response::success(['admin_confirmed' => $newValue], '更新しました');
    }
}
