<?php
/**
 * 雑費コントローラー
 */
class ExpenseController
{
    private Expense $model;

    public function __construct()
    {
        Auth::requirePermission('camps');
        $this->model = new Expense();
    }

    /**
     * 合宿の雑費一覧取得
     */
    public function index(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
        }

        $expenses = $this->model->getByCampId($campId);
        Response::success($expenses);
    }

    /**
     * 雑費追加
     */
    public function store(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
        }

        $errors = Request::validate([
            'name' => 'required|max:100',
            'amount' => 'required|integer',
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);
        }

        $data = Request::only([
            'name', 'amount', 'target_type', 'target_day', 'target_slot', 'payer_id',
        ]);

        $data['camp_id'] = $campId;

        try {
            $id = $this->model->create($data);
            $expenses = $this->model->getByCampId($campId);
            $expense = array_filter($expenses, fn($e) => $e['id'] == $id);
            $expense = reset($expense);
            Response::success($expense, '雑費を追加しました');

        } catch (Exception $e) {
            Response::error('雑費の追加に失敗しました: ' . $e->getMessage(), 500, 'CREATE_ERROR');
        }
    }

    /**
     * 雑費更新
     */
    public function update(array $params): void
    {
        $id = (int)$params['id'];

        $existing = $this->model->find($id);
        if (!$existing) {
            Response::error('雑費が見つかりません', 404, 'NOT_FOUND');
        }

        $data = Request::only([
            'name', 'amount', 'target_type', 'target_day', 'target_slot', 'payer_id',
        ]);

        try {
            $this->model->update($id, $data);
            $expenses = $this->model->getByCampId($existing['camp_id']);
            $expense = array_filter($expenses, fn($e) => $e['id'] == $id);
            $expense = reset($expense);
            Response::success($expense, '雑費を更新しました');

        } catch (Exception $e) {
            Response::error('雑費の更新に失敗しました: ' . $e->getMessage(), 500, 'UPDATE_ERROR');
        }
    }

    /**
     * 雑費削除
     */
    public function destroy(array $params): void
    {
        $id = (int)$params['id'];

        $existing = $this->model->find($id);
        if (!$existing) {
            Response::error('雑費が見つかりません', 404, 'NOT_FOUND');
        }

        try {
            $this->model->delete($id);
            Response::success([], '雑費を削除しました');

        } catch (Exception $e) {
            Response::error('雑費の削除に失敗しました: ' . $e->getMessage(), 500, 'DELETE_ERROR');
        }
    }
}
