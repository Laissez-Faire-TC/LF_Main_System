<?php
/**
 * PDFアップロードコントローラー
 */

class PdfUploadController
{
    private PdfParserService $parserService;

    public function __construct()
    {
        Auth::requirePermission('camps');
        $this->parserService = new PdfParserService();
    }

    /**
     * PDFアップロード画面を表示
     */
    public function index(): void
    {
        // 認証チェック
        if (!Auth::check()) {
            Response::redirect('/login');
            return;
        }

        require VIEWS_PATH . '/pdf/upload.php';
    }

    /**
     * PDFをアップロードして解析
     */
    public function upload(): void
    {
        // 認証チェック
        if (!Auth::check()) {
            Response::json(['error' => '未認証'], 401);
            return;
        }

        try {
            // ファイルアップロードの検証
            if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('ファイルのアップロードに失敗しました');
            }

            $file = $_FILES['pdf'];

            // ファイルタイプチェック
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if ($mimeType !== 'application/pdf') {
                throw new Exception('PDFファイルのみアップロード可能です');
            }

            // ファイルサイズチェック（10MB以下）
            if ($file['size'] > 10 * 1024 * 1024) {
                throw new Exception('ファイルサイズが大きすぎます（最大10MB）');
            }

            // 一時ディレクトリに保存
            $uploadDir = BASE_PATH . '/uploads/temp/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filename = uniqid('pdf_', true) . '.pdf';
            $filepath = $uploadDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('ファイルの保存に失敗しました');
            }

            // PDFを解析
            $parsedData = $this->parserService->parse($filepath);

            // バリデーション
            $errors = $this->parserService->validate($parsedData);

            // セッションに解析結果を保存
            $_SESSION['pdf_parsed_data'] = $parsedData;
            $_SESSION['pdf_temp_file'] = $filepath;
            $_SESSION['pdf_original_name'] = $file['name'];

            Response::json([
                'success' => true,
                'data' => $parsedData,
                'errors' => $errors,
                'warnings' => $this->generateWarnings($parsedData),
            ]);

        } catch (Exception $e) {
            // エラー時は一時ファイルを削除
            if (isset($filepath) && file_exists($filepath)) {
                unlink($filepath);
            }

            Response::json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 解析結果の確認・編集画面を表示
     */
    public function review(): void
    {
        // 認証チェック
        if (!Auth::check()) {
            Response::redirect('/login');
            return;
        }

        // セッションから解析結果を取得
        if (!isset($_SESSION['pdf_parsed_data'])) {
            Response::redirect('/pdf/upload');
            return;
        }

        $parsedData = $_SESSION['pdf_parsed_data'];
        $originalName = $_SESSION['pdf_original_name'] ?? 'unknown.pdf';

        require VIEWS_PATH . '/pdf/review.php';
    }

    /**
     * 解析結果を確定して合宿データに反映
     */
    public function apply(): void
    {
        // 認証チェック
        if (!Auth::check()) {
            Response::json(['error' => '未認証'], 401);
            return;
        }

        try {
            // リクエストデータを取得
            $input = Request::json();
            $data = $input['data'] ?? [];
            $campModel = new Camp();

            // 新規作成か既存更新かを判定
            if (!empty($input['create_new'])) {
                // 新規合宿作成
                $campId = $this->createNewCamp($input, $data);
                $message = '新しい合宿を作成しました';
            } else {
                // 既存合宿に反映
                if (!isset($input['camp_id'])) {
                    throw new Exception('合宿IDが指定されていません');
                }

                $campId = (int)$input['camp_id'];
                $camp = $campModel->find($campId);

                if (!$camp) {
                    throw new Exception('合宿が見つかりません');
                }

                // データベースに反映
                $this->applyToCamp($campId, $data);
                $message = 'データを合宿に反映しました';
            }

            // セッションをクリア
            unset($_SESSION['pdf_parsed_data']);
            if (isset($_SESSION['pdf_temp_file']) && file_exists($_SESSION['pdf_temp_file'])) {
                unlink($_SESSION['pdf_temp_file']);
            }
            unset($_SESSION['pdf_temp_file']);
            unset($_SESSION['pdf_original_name']);

            Response::json([
                'success' => true,
                'message' => $message,
                'redirect' => "/camps/{$campId}",
            ]);

        } catch (Exception $e) {
            Response::json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * PDFデータから新規合宿を作成
     *
     * @param array $input
     * @param array $data
     * @return int 作成された合宿ID
     * @throws Exception
     */
    private function createNewCamp(array $input, array $data): int
    {
        // 必須項目のバリデーション
        if (empty($input['camp_name'])) {
            throw new Exception('合宿名を入力してください');
        }
        if (empty($input['start_date']) || empty($input['end_date'])) {
            throw new Exception('日程を入力してください');
        }

        $campModel = new Camp();
        $timeSlotModel = new TimeSlot();

        // 合宿データを準備
        $campData = [
            'name' => $input['camp_name'],
            'start_date' => $input['start_date'],
            'end_date' => $input['end_date'],
            'nights' => (int)$input['nights'],
        ];

        // PDFから抽出した料金データをマージ
        if (isset($data['lodging_fee_per_night'])) {
            $campData['lodging_fee_per_night'] = (int)$data['lodging_fee_per_night'];
        }
        if (isset($data['hot_spring_tax'])) {
            $campData['hot_spring_tax'] = (int)$data['hot_spring_tax'];
        }
        if (isset($data['court_fee_per_unit'])) {
            $campData['court_fee_per_unit'] = (int)$data['court_fee_per_unit'];
        }
        if (isset($data['banquet_fee_per_person'])) {
            $campData['banquet_fee_per_person'] = (int)$data['banquet_fee_per_person'];
        }
        if (isset($data['bus_fee_round_trip'])) {
            $campData['bus_fee_round_trip'] = (int)$data['bus_fee_round_trip'];
        }

        // 合宿を作成
        $campId = $campModel->create($campData);

        // デフォルトのタイムスロットを生成
        $courtFeePerUnit = $campData['court_fee_per_unit'] ?? null;
        $gymFeePerUnit = null;
        $timeSlotModel->createDefaultSlots($campId, (int)$input['nights'], $courtFeePerUnit, $gymFeePerUnit);

        return $campId;
    }

    /**
     * 抽出データを合宿に反映
     *
     * @param int $campId
     * @param array $data
     * @throws Exception
     */
    private function applyToCamp(int $campId, array $data): void
    {
        $db = Database::getInstance();
        $campModel = new Camp();

        $updateData = [];

        // データタイプに応じて更新フィールドを設定
        if (isset($data['lodging_fee_per_night'])) {
            $updateData['lodging_fee_per_night'] = (int)$data['lodging_fee_per_night'];
        }
        if (isset($data['hot_spring_tax'])) {
            $updateData['hot_spring_tax'] = (int)$data['hot_spring_tax'];
        }
        if (isset($data['court_fee_per_unit'])) {
            $updateData['court_fee_per_unit'] = (int)$data['court_fee_per_unit'];
        }
        if (isset($data['banquet_fee_per_person'])) {
            $updateData['banquet_fee_per_person'] = (int)$data['banquet_fee_per_person'];
        }
        if (isset($data['bus_fee_round_trip'])) {
            $updateData['bus_fee_round_trip'] = (int)$data['bus_fee_round_trip'];
        }
        if (isset($data['bus_fee_outbound'])) {
            $updateData['bus_fee_outbound'] = (int)$data['bus_fee_outbound'];
            $updateData['bus_fee_separate'] = 1;
        }
        if (isset($data['bus_fee_return'])) {
            $updateData['bus_fee_return'] = (int)$data['bus_fee_return'];
            $updateData['bus_fee_separate'] = 1;
        }
        if (isset($data['highway_fee_outbound'])) {
            $updateData['highway_fee_outbound'] = (int)$data['highway_fee_outbound'];
        }
        if (isset($data['highway_fee_return'])) {
            $updateData['highway_fee_return'] = (int)$data['highway_fee_return'];
        }

        if (empty($updateData)) {
            throw new Exception('更新するデータがありません');
        }

        // 合宿データを更新
        $campModel->update($campId, $updateData);
    }

    /**
     * 警告メッセージを生成
     *
     * @param array $data
     * @return array
     */
    private function generateWarnings(array $data): array
    {
        $warnings = [];

        // nullフィールドの警告
        foreach ($data as $key => $value) {
            if ($value === null && $key !== 'dates' && $key !== 'facility_name' && $key !== 'type') {
                $warnings[] = "{$key}が抽出できませんでした。手動で入力してください。";
            }
        }

        return $warnings;
    }

    /**
     * セッションデータをクリア
     */
    public function cancel(): void
    {
        if (isset($_SESSION['pdf_temp_file']) && file_exists($_SESSION['pdf_temp_file'])) {
            unlink($_SESSION['pdf_temp_file']);
        }
        unset($_SESSION['pdf_parsed_data']);
        unset($_SESSION['pdf_temp_file']);
        unset($_SESSION['pdf_original_name']);

        Response::redirect('/');
    }
}
