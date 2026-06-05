<?php
/**
 * タイムスロットコントローラー
 */
class TimeSlotController
{
    private TimeSlot $model;

    public function __construct()
    {
        Auth::requirePermission('camps');
        $this->model = new TimeSlot();
    }

    /**
     * 合宿のタイムスロット取得
     */
    public function index(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
        }

        $slots = $this->model->getByCampId($campId);
        Response::success($slots);
    }

    /**
     * タイムスロット一括更新
     */
    public function update(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
        }

        $slots = Request::get('slots');

        if (!is_array($slots)) {
            Response::error('スロットデータが不正です', 400, 'VALIDATION_ERROR');
        }

        try {
            $this->model->updateByCampId($campId, $slots);

            // 参加者のスロット情報を再生成
            $participantModel = new Participant();
            $participants = $participantModel->getByCampId($campId);

            foreach ($participants as $participant) {
                $participantModel->update($participant['id'], []);
            }

            $updatedSlots = $this->model->getByCampId($campId);
            Response::success($updatedSlots, 'タイムスロットを更新しました');

        } catch (Exception $e) {
            Response::error('タイムスロットの更新に失敗しました: ' . $e->getMessage(), 500, 'UPDATE_ERROR');
        }
    }
}
