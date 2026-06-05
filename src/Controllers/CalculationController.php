<?php
/**
 * 計算コントローラー
 */
class CalculationController
{
    public function __construct()
    {
        Auth::requirePermission('camps');
    }

    /**
     * 費用計算
     */
    public function calculate(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
        }

        try {
            // 計算前に施設料金を自動修正
            $this->autoFixFacilityFees($campId, $camp);

            $calculationService = new CalculationService();
            $result = $calculationService->calculate($campId);

            Response::success($result);

        } catch (Exception $e) {
            Response::error('計算に失敗しました: ' . $e->getMessage(), 500, 'CALCULATION_ERROR');
        }
    }

    /**
     * 施設料金を自動修正
     * facility_feeが0または未設定のスロットを、単価から計算して更新
     */
    private function autoFixFacilityFees(int $campId, array $camp): void
    {
        $db = Database::getInstance();
        $timeSlotModel = new TimeSlot();
        $slots = $timeSlotModel->getByCampId($campId);

        foreach ($slots as $slot) {
            $needsUpdate = false;
            $newFee = 0;

            // テニスコート: facility_feeが0または未設定で、court_fee_per_unitが設定されている場合
            if ($slot['activity_type'] === 'tennis' &&
                ($slot['facility_fee'] === null || $slot['facility_fee'] == 0) &&
                !empty($camp['court_fee_per_unit'])) {
                $courtCount = $slot['court_count'] ?? 1;
                $newFee = $camp['court_fee_per_unit'] * $courtCount;
                $needsUpdate = true;
            }

            // 体育館: facility_feeが0または未設定で、gym_fee_per_unitが設定されている場合
            if ($slot['activity_type'] === 'gym' &&
                ($slot['facility_fee'] === null || $slot['facility_fee'] == 0) &&
                !empty($camp['gym_fee_per_unit'])) {
                $newFee = $camp['gym_fee_per_unit'];
                $needsUpdate = true;
            }

            // 宴会場: facility_feeが0または未設定で、banquet_fee_per_personが設定されている場合
            if ($slot['activity_type'] === 'banquet' &&
                ($slot['facility_fee'] === null || $slot['facility_fee'] == 0) &&
                !empty($camp['banquet_fee_per_person'])) {
                $newFee = $camp['banquet_fee_per_person'];
                $needsUpdate = true;
            }

            if ($needsUpdate && $newFee > 0) {
                $timeSlotModel->update($slot['id'], ['facility_fee' => $newFee]);
            }
        }
    }

    /**
     * 途中参加・途中抜けスケジュール表
     */
    public function partialSchedule(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
        }

        try {
            $calculationService = new CalculationService();
            $result = $calculationService->generatePartialParticipationSchedule($campId);

            Response::success($result);

        } catch (Exception $e) {
            Response::error('スケジュール生成に失敗しました: ' . $e->getMessage(), 500, 'SCHEDULE_ERROR');
        }
    }
}
