<?php
/**
 * 会員名簿コントローラー
 */

if (!class_exists('MemberController')) {

class MemberController
{
    private Member $model;
    private StudentIdParserService $parser;

    public function __construct()
    {
        Auth::requirePermission('members');
        $this->model = new Member();
        $this->parser = new StudentIdParserService();
    }

    /**
     * 会員一覧ページ表示
     */
    public function indexPage(array $params): void
    {
        $this->render('members/index');
    }

    /**
     * 入会申請一覧ページ表示
     */
    public function pendingPage(array $params): void
    {
        $this->render('members/pending');
    }

    /**
     * 会員一覧API（JSON）
     */
    public function index(array $params): void
    {
        // 検索・フィルタパラメータ
        $search = Request::get('search', '');
        $academicYear = Request::get('academic_year');
        $page = (int)Request::get('page', 1);
        $perPage = (int)Request::get('per_page', 20);

        // 複数選択可能フィルタ（配列 or 文字列を正規化）
        $normalizeArray = function($val): array {
            if ($val === null) return [];
            $arr = is_array($val) ? $val : [$val];
            return array_values(array_filter($arr, fn($v) => $v !== ''));
        };

        // フィルタ条件を構築
        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }
        $status = $normalizeArray(Request::get('status'));
        if (!empty($status)) $filters['status'] = $status;

        $grade = $normalizeArray(Request::get('grade'));
        if (!empty($grade)) $filters['grade'] = $grade;

        $faculty = $normalizeArray(Request::get('faculty'));
        if (!empty($faculty)) $filters['faculty'] = $faculty;

        $gender = $normalizeArray(Request::get('gender'));
        if (!empty($gender)) $filters['gender'] = $gender;

        $department = $normalizeArray(Request::get('department'));
        if (!empty($department)) $filters['department'] = $department;

        if ($academicYear !== null && $academicYear !== '') {
            $filters['academic_year'] = (int)$academicYear;
        }

        // ページネーション
        $offset = ($page - 1) * $perPage;

        try {
            $members = $this->model->search($filters, $perPage, $offset);
            $total = $this->model->countSearch($filters);

            Response::success([
                'members' => $members,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage),
                ],
            ]);
        } catch (Exception $e) {
            Response::error('会員一覧の取得に失敗しました: ' . $e->getMessage(), 500, 'FETCH_ERROR');
        }
    }

    /**
     * 会員詳細取得
     */
    public function show(array $params): void
    {
        $id = (int)$params['id'];

        $member = $this->model->find($id);
        if (!$member) {
            Response::error('会員が見つかりません', 404, 'NOT_FOUND');
        }

        // OAuth連携状況を付加（幹部による救済解除の判断用）
        $oauthModel = new MemberOauthIdentity();
        $member['oauth_providers'] = array_map(
            fn($row) => $row['provider'],
            $oauthModel->findByMember($id)
        );

        Response::success($member);
    }

    /**
     * 幹部によるOAuth連携の強制解除（救済用）
     * DELETE /api/members/{id}/oauth
     *   連携をすべて解除し、その会員が学籍番号ログインに戻れるようにする。
     */
    public function unlinkOauth(array $params): void
    {
        $id     = (int)$params['id'];
        $member = $this->model->find($id);
        if (!$member) {
            Response::error('会員が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $oauthModel = new MemberOauthIdentity();
        $deleted    = $oauthModel->deleteAllByMember($id);

        Response::success(['deleted' => $deleted], "{$deleted}件の連携を解除しました。学籍番号ログインが可能になりました。");
    }

    /**
     * 会員新規作成
     */
    public function store(array $params): void
    {
        $data = Request::only([
            'name_kanji', 'name_kana', 'gender', 'grade', 'faculty', 'department',
            'student_id', 'phone', 'address', 'emergency_contact', 'birthdate',
            'allergy', 'line_name', 'sns_allowed', 'sports_registration_no', 'email',
            'status', 'enrollment_year', 'academic_year',
        ]);

        // 学籍番号が指定されている場合、重複チェック
        if (!empty($data['student_id'])) {
            // ログインと同じ正規化（全角→半角、小文字→大文字）
            $data['student_id'] = strtoupper(mb_convert_kana($data['student_id'], 'a'));

            $existing = $this->model->findByStudentId($data['student_id']);
            if ($existing) {
                Response::error('この学籍番号は既に登録されています', 400, 'DUPLICATE_STUDENT_ID');
            }

            // 学籍番号から入学年度を自動設定
            $parsed = $this->parser->parse($data['student_id']);
            if ($parsed['is_valid'] && $parsed['enrollment_year']) {
                $data['enrollment_year'] = $parsed['enrollment_year'];
            }
        }

        // メールアドレスが指定されている場合、重複チェック
        if (!empty($data['email'])) {
            $existing = $this->model->findByEmail($data['email']);
            if ($existing) {
                Response::error('このメールアドレスは既に登録されています', 400, 'DUPLICATE_EMAIL');
            }
        }

        // academic_year が未指定の場合、入会受付中の年度か現在年度を自動設定
        if (empty($data['academic_year'])) {
            $academicYearModel = new AcademicYear();
            $openYear = $academicYearModel->getEnrollmentOpenYear();
            if ($openYear) {
                $data['academic_year'] = $openYear['year'];
            } else {
                $current = $academicYearModel->getCurrent();
                $data['academic_year'] = $current ? $current['year'] : (int)date('Y');
            }
        }

        try {
            $id = $this->model->create($data);
            $member = $this->model->find($id);

            // 物販: この学籍番号で暫定購入された注文を自動紐付け
            if (!empty($data['student_id']) && class_exists('MerchandiseOrder')) {
                MerchandiseOrder::matchByStudentId($data['student_id'], $id);
            }

            Response::success($member, '会員を登録しました');
        } catch (Exception $e) {
            Response::error('会員の登録に失敗しました: ' . $e->getMessage(), 500, 'CREATE_ERROR');
        }
    }

    /**
     * 会員更新
     */
    public function update(array $params): void
    {
        $id = (int)$params['id'];

        $existing = $this->model->find($id);
        if (!$existing) {
            Response::error('会員が見つかりません', 404, 'NOT_FOUND');
        }

        $data = Request::only([
            'name_kanji', 'name_kana', 'gender', 'grade', 'faculty', 'department',
            'student_id', 'phone', 'address', 'emergency_contact', 'birthdate',
            'allergy', 'line_name', 'sns_allowed', 'sports_registration_no', 'email',
            'status', 'department_not_set', 'enrollment_year',
        ]);

        // 学籍番号の重複チェック（自身を除く）
        if (!empty($data['student_id'])) {
            // ログインと同じ正規化（全角→半角、小文字→大文字）
            $data['student_id'] = strtoupper(mb_convert_kana($data['student_id'], 'a'));

            $duplicateByStudentId = $this->model->findByStudentId($data['student_id']);
            if ($duplicateByStudentId && $duplicateByStudentId['id'] !== $id) {
                Response::error('この学籍番号は既に登録されています', 400, 'DUPLICATE_STUDENT_ID');
            }

            // 学籍番号から入学年度を自動更新
            $parsed = $this->parser->parse($data['student_id']);
            if ($parsed['is_valid'] && $parsed['enrollment_year']) {
                $data['enrollment_year'] = $parsed['enrollment_year'];
            }
        }

        // メールアドレスの重複チェック（自身を除く）
        if (!empty($data['email'])) {
            $duplicateByEmail = $this->model->findByEmail($data['email']);
            if ($duplicateByEmail && $duplicateByEmail['id'] !== $id) {
                Response::error('このメールアドレスは既に登録されています', 400, 'DUPLICATE_EMAIL');
            }
        }

        try {
            $this->model->update($id, $data);
            $member = $this->model->find($id);
            Response::success($member, '会員情報を更新しました');
        } catch (Exception $e) {
            Response::error('会員の更新に失敗しました: ' . $e->getMessage(), 500, 'UPDATE_ERROR');
        }
    }

    /**
     * 会員削除
     */
    public function destroy(array $params): void
    {
        $id = (int)$params['id'];

        $existing = $this->model->find($id);
        if (!$existing) {
            Response::error('会員が見つかりません', 404, 'NOT_FOUND');
        }

        try {
            $this->model->delete($id);
            Response::success([], '会員を削除しました');
        } catch (Exception $e) {
            Response::error('会員の削除に失敗しました: ' . $e->getMessage(), 500, 'DELETE_ERROR');
        }
    }

    /**
     * 新規入会者一覧（直近60日以内にactiveで登録された会員）
     */
    public function pending(array $params): void
    {
        try {
            $members = $this->model->getRecentlyJoined();
            Response::success([
                'members' => $members,
                'count' => count($members),
            ]);
        } catch (Exception $e) {
            Response::error('新規入会者の取得に失敗しました: ' . $e->getMessage(), 500, 'FETCH_ERROR');
        }
    }

    /**
     * 新規入会者件数のみ取得
     */
    public function pendingCount(array $params): void
    {
        try {
            $members = $this->model->getRecentlyJoined();
            Response::success(['count' => count($members)]);
        } catch (Exception $e) {
            Response::error('新規入会者件数の取得に失敗しました: ' . $e->getMessage(), 500, 'FETCH_ERROR');
        }
    }

    /**
     * 会員承認
     */
    public function approve(array $params): void
    {
        $id = (int)$params['id'];

        $member = $this->model->find($id);
        if (!$member) {
            Response::error('会員が見つかりません', 404, 'NOT_FOUND');
        }

        if ($member['status'] !== Member::STATUS_PENDING) {
            Response::error('この会員は承認待ち状態ではありません', 400, 'INVALID_STATUS');
        }

        try {
            $this->model->updateStatus($id, Member::STATUS_ACTIVE);
            $member = $this->model->find($id);
            Response::success($member, '会員を承認しました');
        } catch (Exception $e) {
            Response::error('承認処理に失敗しました: ' . $e->getMessage(), 500, 'APPROVE_ERROR');
        }
    }

    /**
     * 会員却下
     */
    public function reject(array $params): void
    {
        $id = (int)$params['id'];

        $member = $this->model->find($id);
        if (!$member) {
            Response::error('会員が見つかりません', 404, 'NOT_FOUND');
        }

        if ($member['status'] !== Member::STATUS_PENDING) {
            Response::error('この会員は承認待ち状態ではありません', 400, 'INVALID_STATUS');
        }

        try {
            // 却下された会員は削除
            $this->model->delete($id);
            Response::success([], '会員を却下しました');
        } catch (Exception $e) {
            Response::error('却下処理に失敗しました: ' . $e->getMessage(), 500, 'REJECT_ERROR');
        }
    }

    /**
     * 会員名簿Excel出力
     */
    public function exportExcel(array $params): void
    {
        $filters = [];
        $search = Request::get('search', '');
        if (!empty($search)) $filters['search'] = $search;

        $normalizeArray = function($val): array {
            if ($val === null) return [];
            $arr = is_array($val) ? $val : [$val];
            return array_values(array_filter($arr, fn($v) => $v !== ''));
        };

        $grade = $normalizeArray(Request::get('grade'));
        if (!empty($grade)) $filters['grade'] = $grade;

        $faculty = $normalizeArray(Request::get('faculty'));
        if (!empty($faculty)) $filters['faculty'] = $faculty;

        $gender = $normalizeArray(Request::get('gender'));
        if (!empty($gender)) $filters['gender'] = $gender;

        $status = $normalizeArray(Request::get('status'));
        if (!empty($status)) $filters['status'] = $status;

        $academicYear = Request::get('academic_year');
        if ($academicYear !== null && $academicYear !== '') $filters['academic_year'] = (int)$academicYear;

        $department = $normalizeArray(Request::get('department'));
        if (!empty($department)) $filters['department'] = $department;

        $allColumns = [
            'name_kanji'             => '氏名（漢字）',
            'name_kana'              => '氏名（カナ）',
            'gender'                 => '性別',
            'grade'                  => '学年',
            'faculty'                => '学部',
            'department'             => '学科',
            'student_id'             => '学籍番号',
            'phone'                  => '電話番号',
            'email'                  => 'メールアドレス',
            'line_name'              => 'LINE名',
            'status'                 => 'ステータス',
            'enrollment_year'        => '入学年度',
            'academic_year'          => '年度',
            'birthdate'              => '生年月日',
            'allergy'                => 'アレルギー',
            'emergency_contact'      => '緊急連絡先',
            'address'                => '住所',
            'sns_allowed'            => 'SNS投稿可否',
            'sports_registration_no' => '都営登録番号',
        ];

        $requestedCols = Request::get('columns');
        if ($requestedCols) {
            $keys = explode(',', $requestedCols);
            $columns = [];
            foreach ($keys as $k) {
                if (isset($allColumns[$k])) $columns[$k] = $allColumns[$k];
            }
        } else {
            $columns = $allColumns;
        }

        try {
            $members = $this->model->search($filters, 10000, 0);

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('会員名簿');

            $statusLabels = ['active' => '現役', 'pending' => '承認待ち', 'ob_og' => 'OB/OG', 'withdrawn' => '退会'];
            $genderLabels = ['male' => '男性', 'female' => '女性'];

            $colKeys = array_keys($columns);
            foreach ($colKeys as $colIdx => $key) {
                $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . '1';
                $sheet->setCellValue($cell, $columns[$key]);
                $sheet->getStyle($cell)->getFont()->setBold(true);
                $sheet->getStyle($cell)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('4472C4');
                $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
            }

            foreach ($members as $rowIdx => $m) {
                $row = $rowIdx + 2;
                foreach ($colKeys as $colIdx => $key) {
                    $cellAddr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . $row;
                    $val = match ($key) {
                        'gender'      => $genderLabels[$m['gender']] ?? $m['gender'],
                        'status'      => $statusLabels[$m['status']] ?? $m['status'],
                        'sns_allowed' => ($m['sns_allowed'] ? '可' : '不可'),
                        default       => $m[$key] ?? '',
                    };
                    $sheet->setCellValueExplicit($cellAddr, (string)($val ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }

            foreach (range(1, count($columns)) as $col) {
                $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            }

            $sheet->setAutoFilter('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($columns)) . '1');

            $yearLabel = isset($filters['academic_year']) ? $filters['academic_year'] . '年度_' : '';
            $filename = '会員名簿_' . $yearLabel . date('Ymd') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($filename));
            header('Cache-Control: max-age=0');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (Exception $e) {
            Response::error('Excel出力に失敗しました: ' . $e->getMessage(), 500, 'EXPORT_ERROR');
        }
    }

    /**
     * 学籍番号解析API
     *
     * 学籍番号から入学年度・学部などの情報を解析
     */
    public function parseStudentId(array $params): void
    {
        $studentId = Request::get('student_id', '');

        if (empty($studentId)) {
            Response::error('学籍番号を指定してください', 400, 'VALIDATION_ERROR');
        }

        try {
            $parsed = $this->parser->parse($studentId);
            Response::success($parsed);
        } catch (Exception $e) {
            Response::error('学籍番号の解析に失敗しました: ' . $e->getMessage(), 400, 'PARSE_ERROR');
        }
    }

    /**
     * 会員インポート
     */
    public function import(array $params): void
    {
        // 出力バッファを開始（PHP Notice/Warning が JSON に混入するのを防ぐ）
        ob_start();

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Response::error('ファイルのアップロードに失敗しました', 400, 'FILE_UPLOAD_ERROR');
        }

        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
            Response::error('対応していないファイル形式です（CSV, Excel のみ対応）', 400, 'INVALID_FILE_TYPE');
        }

        try {
            // ファイル読み込みとパース（簡易実装）
            $members = $this->parseImportFile($file['tmp_name'], $ext);

            // パース結果が空の場合
            if (empty($members)) {
                Response::error('CSVファイルからデータを読み取れませんでした。フォーマットを確認してください。', 400, 'PARSE_ERROR');
                return;
            }

            // academic_year が未設定の行に、入会受付中の年度 or 現在年度を自動設定
            $academicYearModel = new AcademicYear();
            $openYear = $academicYearModel->getEnrollmentOpenYear();
            $currentYear = $openYear
                ? $openYear['year']
                : ($academicYearModel->getCurrent()['year'] ?? (int)date('Y'));

            foreach ($members as &$m) {
                if (empty($m['academic_year'])) {
                    $m['academic_year'] = $currentYear;
                }
            }
            unset($m);

            // 一括インポート実行
            $result = $this->model->bulkImport($members);

            // エラーがある場合
            if (!empty($result['errors'])) {
                $errorMsg = implode(', ', $result['errors']);
                Response::error("インポート中にエラーが発生しました: {$errorMsg}", 500, 'IMPORT_ERROR');
                return;
            }

            // 物販: 暫定購入注文の自動マッチング
            if (class_exists('MerchandiseOrder')) {
                MerchandiseOrder::matchAllPending();
            }

            Response::success($result, "{$result['imported']}名を新規登録、{$result['updated']}名を更新しました");
        } catch (Exception $e) {
            Response::error('インポートに失敗しました: ' . $e->getMessage(), 500, 'IMPORT_ERROR');
        }
    }

    /**
     * インポートファイルをパース
     *
     * @param string $filepath ファイルパス
     * @param string $ext 拡張子
     * @return array 会員データの配列
     */
    private function parseImportFile(string $filepath, string $ext): array
    {
        $members = [];

        if ($ext === 'csv') {
            // 文字コードを自動検出してUTF-8に変換
            $content = file_get_contents($filepath);
            $encoding = mb_detect_encoding($content, ['UTF-8', 'SJIS', 'SJIS-win', 'EUC-JP', 'ASCII'], true);

            if ($encoding && $encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                // 一時ファイルに書き出し
                $tmpFile = tempnam(sys_get_temp_dir(), 'csv_');
                file_put_contents($tmpFile, $content);
                $filepath = $tmpFile;
            }

            // CSVファイルのパース
            $handle = fopen($filepath, 'r');
            if (!$handle) {
                throw new Exception('ファイルを開けませんでした');
            }

            $lineNum = 0;
            $headers = null;

            // データ行を読み込み
            while (($row = fgetcsv($handle)) !== false) {
                $lineNum++;

                // 1行目をヘッダーとして扱う（データに「名前（漢字）」等の文字列が含まれる場合）
                if ($lineNum === 1) {
                    // ヘッダー行かどうか判定（「名前」「学籍番号」等の文字列が含まれるか）
                    $firstCell = $row[0] ?? '';
                    if (strpos($firstCell, '名前') !== false || strpos($firstCell, 'カナ') !== false) {
                        $headers = $row;
                        continue; // ヘッダー行はスキップ
                    }
                }

                // 空行をスキップ
                if (empty(array_filter($row))) {
                    continue;
                }

                // 最低限必要な列数チェック（緩和: 最低10列）
                if (count($row) < 10) {
                    continue;
                }

                // 学籍番号から入学年度・学科/学系を自動判定
                $enrollmentYear = null;
                $autoDepartment = null;
                if (!empty($row[6])) {
                    $parsed = $this->parser->parse($row[6]);
                    if ($parsed['is_valid'] && $parsed['enrollment_year']) {
                        $enrollmentYear = $parsed['enrollment_year'];
                    }
                    if ($parsed['is_valid'] && !empty($parsed['department'])) {
                        $autoDepartment = $parsed['department'];
                    }
                }

                // 性別の正規化
                $genderRaw = trim($row[2]);
                $gender = 'male'; // デフォルト
                if (in_array($genderRaw, ['女性', '女'])) {
                    $gender = 'female';
                }

                // 学年の正規化
                $gradeRaw = trim($row[3]);
                $grade = $gradeRaw;
                $status = Member::STATUS_ACTIVE; // デフォルトは現役

                if ($gradeRaw === 'M1') {
                    $grade = 'M1';
                    $status = Member::STATUS_OB_OG; // M1は大学院生なのでOB/OG扱い
                } elseif ($gradeRaw === 'M2') {
                    $grade = 'M2';
                    $status = Member::STATUS_OB_OG; // M2は大学院生なのでOB/OG扱い
                } elseif ($gradeRaw === 'OB' || $gradeRaw === 'OG') {
                    $grade = $gradeRaw;
                    $status = Member::STATUS_OB_OG;
                } else {
                    // 学籍番号から入学年度が判明している場合は学年を自動計算（CSV値より優先）
                    if ($enrollmentYear) {
                        $grade = (string)$this->parser->calculateGrade($enrollmentYear);
                    } else {
                        // 学籍番号が無い・無効な場合はCSVの値を使用（「1年」「2年」等 → 数字のみに変換）
                        $grade = str_replace('年', '', $gradeRaw);
                    }
                }

                // 入学年度から自動的にOB/OG判定（学部生のみ）
                // 3年生の10月以降はOB/OG、M1/M2は既にOB/OG扱い
                if ($enrollmentYear && $status === Member::STATUS_ACTIVE &&
                    $grade !== 'OB' && $grade !== 'OG' && $grade !== 'M1' && $grade !== 'M2') {
                    $currentYear = (int)date('Y');
                    $currentMonth = (int)date('n');

                    // 4月以降は新年度として扱う
                    $academicYear = $currentMonth >= 4 ? $currentYear : $currentYear - 1;

                    // 引退年度を計算（3年生の10月 = 入学から2年6ヶ月後）
                    $retirementAcademicYear = $enrollmentYear + 2; // 2023年入学 → 2025年度の3年生で10月に引退

                    // 3年生の10月以降はOB/OG
                    if ($academicYear > $retirementAcademicYear ||
                        ($academicYear === $retirementAcademicYear && $currentMonth >= 10)) {
                        $status = Member::STATUS_OB_OG;
                        $grade = $gender === 'male' ? 'OB' : 'OG';
                    }
                }

                $members[] = [
                    'name_kanji' => trim($row[0]),
                    'name_kana' => trim($row[1]),
                    'gender' => $gender,
                    'grade' => $grade,
                    'faculty' => trim($row[4]),
                    'department' => $autoDepartment ?? $this->parser->normalizeGakukei(trim($row[5])),
                    'student_id' => trim($row[6]),
                    'phone' => trim($row[7]),
                    'address' => trim($row[8]),
                    'emergency_contact' => trim($row[9]),
                    'birthdate' => isset($row[10]) && trim($row[10]) ? trim($row[10]) : null,
                    'allergy' => isset($row[11]) && trim($row[11]) ? trim($row[11]) : null,
                    'line_name' => isset($row[12]) && trim($row[12]) ? trim($row[12]) : '',
                    'sns_allowed' => $this->parseSnsAllowed($row[13] ?? ''),
                    'sports_registration_no' => $this->validateSportsRegistrationNo($row[14] ?? ''),
                    'email' => isset($row[15]) && trim($row[15]) ? trim($row[15]) : null,
                    'status' => $status,
                    'enrollment_year' => $enrollmentYear,
                ];
            }

            fclose($handle);

            // 一時ファイルを削除
            if (isset($tmpFile) && file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }

        // TODO: Excel形式の対応（PhpSpreadsheetライブラリが必要）

        return $members;
    }

    /**
     * SNS投稿可否の値をパース
     *
     * @param string $value CSV の値
     * @return int 1 (許可) or 0 (不許可)
     */
    private function parseSnsAllowed(string $value): int
    {
        $value = trim($value);

        // 許可を示す値
        $allowedValues = ['はい', '可', '○', 'OK', 'Yes', 'yes', 'Y', 'y', '1'];
        if (in_array($value, $allowedValues)) {
            return 1;
        }

        // 不許可を示す値
        $deniedValues = ['いいえ', '不可', '×', 'NG', 'No', 'no', 'N', 'n', '0'];
        if (in_array($value, $deniedValues)) {
            return 0;
        }

        // 空欄の場合はデフォルト（許可）
        return empty($value) ? 1 : 0;
    }

    /**
     * 都営登録番号のバリデーション
     * 8桁の半角数字のみ有効、それ以外はNULL
     *
     * @param string $value CSV の値
     * @return string|null 有効な場合は値、無効な場合はnull
     */
    private function validateSportsRegistrationNo(string $value): ?string
    {
        $value = trim($value);

        // 空欄の場合
        if (empty($value)) {
            return null;
        }

        // 半角数字8桁のみ許可
        if (preg_match('/^\d{8}$/', $value)) {
            return $value;
        }

        // 全角数字を半角に変換して再チェック
        $valueHankaku = mb_convert_kana($value, 'n');
        if (preg_match('/^\d{8}$/', $valueHankaku)) {
            return $valueHankaku;
        }

        // 8桁でない場合やその他の文字が含まれる場合はnull
        return null;
    }

    /**
     * ビューのレンダリング
     */
    private function render(string $view, array $data = []): void
    {
        extract($data);

        $config = require CONFIG_PATH . '/app.php';
        $appName = $config['name'];

        ob_start();
        include VIEWS_PATH . '/' . $view . '.php';
        $content = ob_get_clean();

        include VIEWS_PATH . '/layouts/main.php';
    }
}

}
