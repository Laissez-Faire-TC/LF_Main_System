<?php
/**
 * 出力コントローラー
 */
class ExportController
{
    public function __construct()
    {
        Auth::requirePermission('camps');
    }

    /**
     * PDF出力
     */
    public function pdf(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
        }

        try {
            $exportService = new ExportService();
            $exportService->generatePdf($campId);

        } catch (Exception $e) {
            Response::error('PDF出力に失敗しました: ' . $e->getMessage(), 500, 'EXPORT_ERROR');
        }
    }

    /**
     * Excel出力（CSV形式）
     */
    public function excel(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
        }

        try {
            $exportService = new ExportService();
            $exportService->generateExcel($campId);

        } catch (Exception $e) {
            Response::error('Excel出力に失敗しました: ' . $e->getMessage(), 500, 'EXPORT_ERROR');
        }
    }

    /**
     * Excel出力（xlsx形式）
     */
    public function xlsx(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
        }

        try {
            $exportService = new ExportService();
            $exportService->generateXlsx($campId);

        } catch (Exception $e) {
            Response::error('Excel出力に失敗しました: ' . $e->getMessage(), 500, 'EXPORT_ERROR');
        }
    }

    /**
     * 保険加入者名簿Excel出力（マイコム形式）
     */
    public function insuranceRoster(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
        }

        try {
            $exportService = new ExportService();
            $exportService->generateInsuranceRoster($campId);

        } catch (Exception $e) {
            Response::error('保険名簿出力に失敗しました: ' . $e->getMessage(), 500, 'EXPORT_ERROR');
        }
    }

    /**
     * 合宿参加者名簿Excel出力（コスモ形式）
     */
    public function participantRosterCosmo(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
        }

        try {
            $exportService = new ExportService();
            $exportService->generateParticipantRosterCosmo($campId);

        } catch (Exception $e) {
            Response::error('参加者名簿出力に失敗しました: ' . $e->getMessage(), 500, 'EXPORT_ERROR');
        }
    }

    /**
     * 人数報告表Excel出力（コスモ形式）
     */
    public function headcountReport(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
        }

        try {
            $exportService = new ExportService();
            $exportService->generateHeadcountReport($campId);

        } catch (Exception $e) {
            Response::error('人数報告表出力に失敗しました: ' . $e->getMessage(), 500, 'EXPORT_ERROR');
        }
    }

    /**
     * 合宿・遠征届 参加者名簿 Excel出力
     */
    public function activityMeibo(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
        }

        try {
            $exportService = new ExportService();
            $exportService->generateActivityMeibo($campId);

        } catch (Exception $e) {
            Response::error('名簿出力に失敗しました: ' . $e->getMessage(), 500, 'EXPORT_ERROR');
        }
    }

    /**
     * アレルギーリストPDF出力
     */
    public function allergyList(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
        }

        try {
            $exportService = new ExportService();
            $exportService->generateAllergyListPdf($campId);

        } catch (Exception $e) {
            Response::error('アレルギーリスト出力に失敗しました: ' . $e->getMessage(), 500, 'EXPORT_ERROR');
        }
    }

    /**
     * 人数報告書Excel出力（マイコム形式）
     */
    public function headcountReportMycom(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
        }

        try {
            $exportService = new ExportService();
            $exportService->generateHeadcountReportMycom($campId);

        } catch (Exception $e) {
            Response::error('人数報告書出力に失敗しました: ' . $e->getMessage(), 500, 'EXPORT_ERROR');
        }
    }
}
