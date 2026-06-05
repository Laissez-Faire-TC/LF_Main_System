<?php
/**
 * 入会フォームコントローラー（公開ページ）
 */
class EnrollmentController
{
    /**
     * 入会フォーム表示
     */
    public function form(array $params): void
    {
        $ayModel = new AcademicYear();
        $openYear = $ayModel->getEnrollOpenYear();  // 新規入会専用フラグ

        $enrollmentClosed = ($openYear === null);   // 受付していない
        $deadlinePassed   = false;
        $deadline         = null;

        if ($openYear && !empty($openYear['enrollment_deadline'])) {
            $deadline       = $openYear['enrollment_deadline'];
            $deadlinePassed = $deadline < date('Y-m-d');
        }
        // 期限切れも「受付していない」扱い
        if ($deadlinePassed) {
            $enrollmentClosed = true;
        }

        $savedData = $_SESSION['enrollment_data'] ?? [];
        $this->render('enroll/form', [
            'savedData'        => $savedData,
            'enrollmentClosed' => $enrollmentClosed,
            'deadlinePassed'   => $deadlinePassed,
            'deadline'         => $deadline,
        ]);
    }

    /**
     * 入会申請確認画面表示
     */
    public function confirm(array $params): void
    {
        // POSTデータがセッションに保存されているか確認
        if (!isset($_SESSION['enrollment_data'])) {
            Response::redirect('/enroll');
            return;
        }

        $data = $_SESSION['enrollment_data'];
        $this->render('enroll/confirm', ['data' => $data]);
    }

    /**
     * 入会申請送信（確認画面からPOST）
     */
    public function submit(array $params): void
    {
        $action = Request::get('action');

        // 確認画面へ進む場合：POSTデータをバリデーションしてセッションに保存
        if ($action === 'confirm') {
            $data = [
                'name_kanji' => Request::get('name_kanji'),
                'name_kana' => Request::get('name_kana'),
                'gender' => Request::get('gender'),
                'birthdate' => Request::get('birthdate'),
                'student_id' => Request::get('student_id'),
                'faculty' => Request::get('faculty'),
                'department' => (new StudentIdParserService())->normalizeGakukei(Request::get('department')),
                'enrollment_year' => Request::get('enrollment_year'),
                'phone' => Request::get('phone'),
                'address' => Request::get('address'),
                'emergency_contact' => Request::get('emergency_contact'),
                'email' => Request::get('email'),
                'line_name' => Request::get('line_name'),
                'allergy' => Request::get('allergy'),
                'sns_allowed' => Request::get('sns_allowed', 0),
                'sports_registration_no' => Request::get('sports_registration_no'),
                'sports_registration_shared' => Request::get('sports_registration_shared', 0),
            ];

            $errors = $this->validate($data);
            if (!empty($errors)) {
                Response::json(['success' => false, 'errors' => $errors]);
                return;
            }

            // 氏名のスペースを正規化（確認画面にも正規化済みの名前を表示するため）
            if (!empty($data['name_kanji'])) {
                $data['name_kanji'] = Member::normalizeJapaneseName($data['name_kanji']);
            }
            if (!empty($data['name_kana'])) {
                $data['name_kana'] = Member::normalizeJapaneseName($data['name_kana']);
            }

            $_SESSION['enrollment_data'] = $data;
            Response::json(['success' => true, 'redirect' => '/enroll/confirm']);
            return;
        }

        // 申請送信の場合：セッションからデータを取得して登録
        if ($action === 'submit') {
            try {
                // 期限チェック（新規入会専用フラグ）
                $ayModel  = new AcademicYear();
                $openYear = $ayModel->getEnrollOpenYear();
                if (!$openYear) {
                    Response::json(['success' => false, 'error' => '現在、新規入会の受付を行っていません']);
                    return;
                }
                if (!empty($openYear['enrollment_deadline']) && $openYear['enrollment_deadline'] < date('Y-m-d')) {
                    Response::json(['success' => false, 'error' => '入会受付期限が過ぎています']);
                    return;
                }

                // セッションから取得（確認画面からの送信）
                if (!isset($_SESSION['enrollment_data'])) {
                    Response::json(['success' => false, 'error' => 'セッションが切れました。入力画面からやり直してください。']);
                    return;
                }
                $data = $_SESSION['enrollment_data'];

                // 受付中の年度をacademic_yearとしてセット
                $data['academic_year'] = $openYear['year'] ?? null;

                // 学年の計算（入学年度から現在の学年を算出）
                $parserService = new StudentIdParserService();
                $currentYear = (int)date('Y');
                $currentMonth = (int)date('n');
                $grade = $parserService->calculateGrade((int)$data['enrollment_year'], $currentYear, $currentMonth);
                $data['grade'] = (string)$grade;

                // 即座にアクティブ会員として登録
                $data['status'] = 'active';

                // 会員データを登録
                $memberModel = new Member();
                $memberId = $memberModel->create($data);

                // 物販: 暫定購入注文の自動マッチング
                if (!empty($data['student_id']) && class_exists('MerchandiseOrder')) {
                    MerchandiseOrder::matchByStudentId($data['student_id'], $memberId);
                }

                // 管理者に入会申請通知メールを送信
                $member = $memberModel->find($memberId);
                if ($member) {
                    $emailService = new EmailService();
                    $emailService->sendEnrollmentNotification($member);
                }

                // 有効な入会金設定のうち新規入会対象のものを自動でアイテムに追加
                $feeModel = new MembershipFee();
                foreach ($feeModel->getActive('new') as $fee) {
                    $feeModel->addMember((int)$fee['id'], $memberId);
                }

                // セッションクリア
                unset($_SESSION['enrollment_data']);

                Response::json([
                    'success' => true,
                    'redirect' => '/enroll/complete',
                    'message' => '入会申請を受け付けました'
                ]);

            } catch (Throwable $e) {
                Response::json([
                    'success' => false,
                    'error' => '入会申請の送信に失敗しました: ' . $e->getMessage()
                ]);
            }
            return;
        }

        Response::json([
            'success' => false,
            'error' => '不正なリクエストです'
        ]);
    }

    /**
     * 学籍番号解析API（認証不要・公開）
     */
    public function parseStudentId(array $params): void
    {
        $studentId = Request::get('student_id', '');

        if (empty($studentId)) {
            Response::error('学籍番号を指定してください', 400, 'VALIDATION_ERROR');
            return;
        }

        try {
            $parser = new StudentIdParserService();
            $parsed = $parser->parse($studentId);
            Response::success($parsed);
        } catch (Exception $e) {
            Response::error('学籍番号の解析に失敗しました: ' . $e->getMessage(), 400, 'PARSE_ERROR');
        }
    }

    /**
     * 入会完了画面表示
     */
    public function complete(array $params): void
    {
        $this->render('enroll/complete');
    }

    /**
     * バリデーション
     */
    private function validate(array $data): array
    {
        $errors = [];

        // 必須項目チェック
        if (empty($data['name_kanji'])) {
            $errors['name_kanji'] = '名前（漢字）は必須です';
        }
        if (empty($data['name_kana'])) {
            $errors['name_kana'] = '名前（カナ）は必須です';
        }
        if (empty($data['gender'])) {
            $errors['gender'] = '性別は必須です';
        } elseif (!in_array($data['gender'], ['male', 'female'])) {
            $errors['gender'] = '性別の値が不正です';
        }
        if (empty($data['birthdate'])) {
            $errors['birthdate'] = '生年月日は必須です';
        }
        if (empty($data['student_id'])) {
            $errors['student_id'] = '学籍番号は必須です';
        } else {
            // 学籍番号の形式チェック
            $parserService = new StudentIdParserService();
            if (!$parserService->isValidFormat($data['student_id'])) {
                $errors['student_id'] = '学籍番号の形式が正しくありません（例: 1Y25F158-5）';
            } else {
                // 重複チェック
                $memberModel = new Member();
                if ($memberModel->existsByStudentId($data['student_id'])) {
                    $errors['student_id'] = 'この学籍番号は既に登録されています';
                }
            }
        }
        if (empty($data['faculty'])) {
            $errors['faculty'] = '学部は必須です';
        }
        if (empty($data['department'])) {
            $errors['department'] = '学科/学系は必須です';
        }
        if (empty($data['enrollment_year'])) {
            $errors['enrollment_year'] = '入学年度は必須です';
        }
        if (empty($data['phone'])) {
            $errors['phone'] = '電話番号は必須です';
        } elseif (!preg_match('/^\d{2,4}-\d{2,4}-\d{4}$/', $data['phone'])) {
            $errors['phone'] = '電話番号はハイフン区切りで入力してください（例: 090-1234-5678）';
        }
        if (empty($data['address'])) {
            $errors['address'] = '住所は必須です';
        }
        if (empty($data['emergency_contact'])) {
            $errors['emergency_contact'] = '緊急連絡先は必須です';
        } elseif (!preg_match('/^\d{2,4}-\d{2,4}-\d{4}$/', $data['emergency_contact'])) {
            $errors['emergency_contact'] = '緊急連絡先はハイフン区切りで入力してください';
        }
        if (empty($data['line_name'])) {
            $errors['line_name'] = 'LINE名は必須です';
        }

        // アレルギーは任意項目のため、チェック不要

        return $errors;
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

        // 公開ページなので認証不要のレイアウト
        include VIEWS_PATH . '/layouts/public.php';
    }
}
