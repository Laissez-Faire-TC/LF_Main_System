<?php
/**
 * 参加者コントローラー
 */
class ParticipantController
{
    private Participant $model;

    public function __construct()
    {
        Auth::requirePermission('camps');
        $this->model = new Participant();
    }

    /**
     * 合宿の参加者一覧取得
     */
    public function index(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
        }

        $participants = $this->model->getByCampId($campId);

        // 各参加者のスロット情報と食事調整を追加
        foreach ($participants as &$participant) {
            $participant['slots'] = $this->model->getParticipantSlots($participant['id']);
            $participant['meal_adjustments'] = $this->model->getMealAdjustments($participant['id']);
        }

        // 重複参加者のIDリストを取得
        $duplicateIds = $this->model->getDuplicateIds($campId);

        Response::success([
            'participants' => $participants,
            'duplicate_ids' => $duplicateIds,
        ]);
    }

    /**
     * 参加者追加
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
            'name' => 'required|max:50',
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);
        }

        $data = Request::only([
            'name', 'grade', 'gender', 'allergy', 'join_day', 'join_timing', 'leave_day', 'leave_timing',
            'use_outbound_bus', 'use_return_bus', 'use_rental_car',
        ]);

        $data['camp_id'] = $campId;

        // leave_dayのデフォルト値
        if (!isset($data['leave_day'])) {
            $data['leave_day'] = $camp['nights'] + 1;
        }

        try {
            $id = $this->model->create($data);
            $participant = $this->model->find($id);
            $participant['slots'] = $this->model->getParticipantSlots($id);
            $participant['meal_adjustments'] = $this->model->getMealAdjustments($id);

            Response::success($participant, '参加者を追加しました');

        } catch (Exception $e) {
            Response::error('参加者の追加に失敗しました: ' . $e->getMessage(), 500, 'CREATE_ERROR');
        }
    }

    /**
     * 参加者更新
     */
    public function update(array $params): void
    {
        $id = (int)$params['id'];

        $existing = $this->model->find($id);
        if (!$existing) {
            Response::error('参加者が見つかりません', 404, 'NOT_FOUND');
        }

        $data = Request::only([
            'name', 'grade', 'gender', 'allergy', 'join_day', 'join_timing', 'leave_day', 'leave_timing',
            'use_outbound_bus', 'use_return_bus', 'use_rental_car',
        ]);

        try {
            $this->model->update($id, $data);
            $participant = $this->model->find($id);
            $participant['slots'] = $this->model->getParticipantSlots($id);
            $participant['meal_adjustments'] = $this->model->getMealAdjustments($id);

            Response::success($participant, '参加者を更新しました');

        } catch (Exception $e) {
            Response::error('参加者の更新に失敗しました: ' . $e->getMessage(), 500, 'UPDATE_ERROR');
        }
    }

    /**
     * 参加者削除
     */
    public function destroy(array $params): void
    {
        $id = (int)$params['id'];

        $existing = $this->model->find($id);
        if (!$existing) {
            Response::error('参加者が見つかりません', 404, 'NOT_FOUND');
        }

        try {
            $this->model->delete($id);
            Response::success([], '参加者を削除しました');

        } catch (Exception $e) {
            Response::error('参加者の削除に失敗しました: ' . $e->getMessage(), 500, 'DELETE_ERROR');
        }
    }

    /**
     * 参加者全員削除
     */
    public function deleteAll(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
        }

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            // 食事調整を削除
            $db->execute(
                "DELETE ma FROM meal_adjustments ma
                 INNER JOIN participants p ON ma.participant_id = p.id
                 WHERE p.camp_id = ?",
                [$campId]
            );

            // 参加者スロットを削除
            $db->execute(
                "DELETE ps FROM participant_slots ps
                 INNER JOIN participants p ON ps.participant_id = p.id
                 WHERE p.camp_id = ?",
                [$campId]
            );

            // 参加者を削除
            $deletedCount = $db->execute("DELETE FROM participants WHERE camp_id = ?", [$campId]);

            $db->commit();

            Response::success(['deleted_count' => $deletedCount], '参加者を全員削除しました');

        } catch (Exception $e) {
            $db->rollback();
            Response::error('削除に失敗しました: ' . $e->getMessage(), 500, 'DELETE_ERROR');
        }
    }

    /**
     * 重複チェック（登録前確認用）
     */
    public function checkDuplicate(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
        }

        $name = Request::get('name');
        $grade = Request::get('grade');
        $excludeId = Request::get('exclude_id');

        if (empty($name)) {
            Response::error('名前は必須です', 400, 'VALIDATION_ERROR');
        }

        $gradeInt = ($grade !== null && $grade !== '') ? (int)$grade : null;
        $excludeIdInt = ($excludeId !== null && $excludeId !== '') ? (int)$excludeId : null;

        $duplicates = $this->model->findDuplicates($campId, $name, $gradeInt, $excludeIdInt);

        Response::success([
            'has_duplicates' => count($duplicates) > 0,
            'duplicates' => $duplicates,
        ]);
    }

    /**
     * CSVインポート
     */
    public function importCsv(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
        }

        // CSVデータを取得
        $csvData = Request::get('csv_data');

        if (empty($csvData)) {
            Response::error('CSVデータが空です', 400, 'VALIDATION_ERROR');
        }

        // CSVをパース
        $rows = [];
        $lines = preg_split('/\r\n|\r|\n/', $csvData);

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $rows[] = str_getcsv($line);
        }

        if (empty($rows)) {
            Response::error('有効なデータがありません', 400, 'VALIDATION_ERROR');
        }

        try {
            $result = $this->model->bulkCreateFromCsv($campId, $rows);

            // 重複チェック
            $duplicateIds = $this->model->getDuplicateIds($campId);
            $hasDuplicates = count($duplicateIds) > 0;

            $message = "{$result['success']}名の参加者を登録しました";
            if (!empty($result['errors'])) {
                $message .= '（一部エラーあり）';
            }
            if ($hasDuplicates) {
                $message .= '（同姓同名の参加者あり）';
            }

            Response::success([
                'success_count' => $result['success'],
                'errors' => $result['errors'],
                'has_duplicates' => $hasDuplicates,
                'duplicate_count' => count($duplicateIds),
            ], $message);

        } catch (Exception $e) {
            Response::error('インポートに失敗しました: ' . $e->getMessage(), 500, 'IMPORT_ERROR');
        }
    }
}
