<?php
/**
 * 入会金管理コントローラー（管理者用）
 */
class MembershipFeeController
{
    private MembershipFee $feeModel;
    private MembershipFeeItem $itemModel;

    public function __construct()
    {
        Auth::requirePermission('enrollment');
        $this->feeModel  = new MembershipFee();
        $this->itemModel = new MembershipFeeItem();
    }

    /**
     * GET /membership-fees
     * 入会金管理ページ
     */
    public function index(array $params): void
    {
        $fees = $this->feeModel->getAll();

        $this->render('membership_fees/index', [
            'fees' => $fees,
        ]);
    }

    /**
     * GET /api/membership-fees
     * 入会金設定一覧を返す
     */
    public function list(array $params): void
    {
        $fees = $this->feeModel->getAll();
        Response::success($fees);
    }

    /**
     * GET /api/membership-fees/{id}
     * 入会金設定詳細＋アイテム一覧を返す
     */
    public function get(array $params): void
    {
        $id  = (int)$params['id'];
        $fee = $this->feeModel->findById($id);

        if (!$fee) {
            Response::error('入会金設定が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $grades      = $this->feeModel->getGrades($id);
        $allItems    = $this->itemModel->getByFeeId($id);
        $submitted   = array_values(array_filter($allItems, fn($i) => (int)$i['submitted'] === 1));
        $unsubmitted = array_values(array_filter($allItems, fn($i) => (int)$i['submitted'] === 0));

        Response::success([
            'fee'         => $fee,
            'grades'      => $grades,
            'submitted'   => $submitted,
            'unsubmitted' => $unsubmitted,
        ]);
    }

    /**
     * POST /api/membership-fees
     * 入会金設定を新規作成する
     */
    public function store(array $params): void
    {
        $errors = Request::validate([
            'academic_year' => 'required|integer',
            'name'          => 'required',
            'deadline'      => 'required|date',
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        // 学年別金額を取得（任意）
        $gradesInput = Request::get('grades');
        $grades = [];
        if (is_array($gradesInput)) {
            foreach ($gradesInput as $grade => $amount) {
                $grades[$grade] = (int)$amount;
            }
        }

        $targetType = Request::get('target_type', 'both');
        if (!in_array($targetType, ['new', 'renew', 'both'])) {
            $targetType = 'both';
        }

        $id = $this->feeModel->create([
            'academic_year' => (int)Request::get('academic_year'),
            'name'          => Request::get('name'),
            'deadline'      => Request::get('deadline'),
            'target_type'   => $targetType,
            'grades'        => $grades,
        ]);

        $fee    = $this->feeModel->findById($id);
        $grades = $this->feeModel->getGrades($id);

        Response::success([
            'fee'    => $fee,
            'grades' => $grades,
        ], '入会金設定を作成しました');
    }

    /**
     * PUT /api/membership-fees/{id}
     * 入会金設定を更新する
     */
    public function update(array $params): void
    {
        $id  = (int)$params['id'];
        $fee = $this->feeModel->findById($id);

        if (!$fee) {
            Response::error('入会金設定が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $data = [];

        $name = Request::get('name');
        if ($name !== null) {
            $data['name'] = $name;
        }

        $deadline = Request::get('deadline');
        if ($deadline !== null) {
            $data['deadline'] = $deadline;
        }

        $isActive = Request::get('is_active');
        if ($isActive !== null) {
            $data['is_active'] = (int)$isActive;
        }

        $targetType = Request::get('target_type');
        if ($targetType !== null && in_array($targetType, ['new', 'renew', 'both'])) {
            $data['target_type'] = $targetType;
        }

        $gradesInput = Request::get('grades');
        if (is_array($gradesInput)) {
            $grades = [];
            foreach ($gradesInput as $grade => $amount) {
                $grades[$grade] = (int)$amount;
            }
            $data['grades'] = $grades;
        }

        $this->feeModel->update($id, $data);

        $updatedFee    = $this->feeModel->findById($id);
        $updatedGrades = $this->feeModel->getGrades($id);

        Response::success([
            'fee'    => $updatedFee,
            'grades' => $updatedGrades,
        ], '更新しました');
    }

    /**
     * DELETE /api/membership-fees/{id}
     * 入会金設定を削除する
     */
    public function destroy(array $params): void
    {
        $id  = (int)$params['id'];
        $fee = $this->feeModel->findById($id);

        if (!$fee) {
            Response::error('入会金設定が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $this->feeModel->delete($id);
        Response::success([], '削除しました');
    }

    /**
     * PUT /api/membership-fee-items/{id}
     * アイテムの custom_amount を更新する
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
     * POST /api/membership-fee-items/{id}/confirm
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

    /**
     * ビューのレンダリング
     */
    private function render(string $view, array $data = []): void
    {
        extract($data);

        $config  = require CONFIG_PATH . '/app.php';
        $appName = $config['name'];

        ob_start();
        include VIEWS_PATH . '/' . $view . '.php';
        $content = ob_get_clean();

        include VIEWS_PATH . '/layouts/main.php';
    }
}
