<?php
/**
 * 年度管理コントローラー
 */

if (!class_exists('AcademicYearController')) {

class AcademicYearController
{
    private AcademicYear $model;

    public function __construct()
    {
        Auth::requirePermission('academic');
        $this->model = new AcademicYear();
    }

    /**
     * 年度一覧取得
     */
    public function index(array $params): void
    {
        try {
            $years = $this->model->getAll();

            Response::success([
                'years' => $years,
            ]);
        } catch (Exception $e) {
            Response::error('年度一覧の取得に失敗しました: ' . $e->getMessage(), 500, 'FETCH_ERROR');
        }
    }

    /**
     * 現在年度取得
     */
    public function getCurrent(array $params): void
    {
        try {
            $currentYear = $this->model->getCurrent();

            if (!$currentYear) {
                Response::error('現在年度が設定されていません', 404, 'NOT_FOUND');
                return;
            }

            Response::success($currentYear);
        } catch (Exception $e) {
            Response::error('現在年度の取得に失敗しました: ' . $e->getMessage(), 500, 'FETCH_ERROR');
        }
    }

    /**
     * 年度作成
     */
    public function store(array $params): void
    {
        $year = (int)Request::get('year');
        $startDate = Request::get('start_date');
        $endDate = Request::get('end_date');
        $isCurrent = (int)Request::get('is_current', 0);
        $enrollmentOpen = (int)Request::get('enrollment_open', 0);

        // バリデーション
        if (!$year || $year < 2020 || $year > 2100) {
            Response::error('年度は2020～2100の範囲で指定してください', 400, 'VALIDATION_ERROR');
            return;
        }

        if ($this->model->exists($year)) {
            Response::error('この年度は既に存在します', 400, 'DUPLICATE_ERROR');
            return;
        }

        try {
            $id = $this->model->create([
                'year' => $year,
                'start_date' => $startDate ?: "{$year}-04-01",
                'end_date' => $endDate ?: ($year + 1) . "-03-31",
                'is_current' => $isCurrent,
                'enrollment_open' => $enrollmentOpen,
            ]);

            Response::success([
                'id' => $id,
                'message' => "{$year}年度を作成しました",
            ]);
        } catch (Exception $e) {
            Response::error('年度の作成に失敗しました: ' . $e->getMessage(), 500, 'CREATE_ERROR');
        }
    }

    /**
     * 次年度を自動作成
     */
    public function createNext(array $params): void
    {
        try {
            $id = $this->model->createNextYear();

            Response::success([
                'id' => $id,
                'message' => '次年度を作成しました',
            ]);
        } catch (Exception $e) {
            Response::error('次年度の作成に失敗しました: ' . $e->getMessage(), 500, 'CREATE_ERROR');
        }
    }

    /**
     * 現在年度を切り替え
     */
    public function setCurrent(array $params): void
    {
        $year = (int)Request::get('year');

        if (!$year) {
            Response::error('年度を指定してください', 400, 'VALIDATION_ERROR');
            return;
        }

        if (!$this->model->exists($year)) {
            Response::error('指定された年度が存在しません', 404, 'NOT_FOUND');
            return;
        }

        try {
            $this->model->setCurrentYear($year);

            Response::success([
                'message' => "{$year}年度を現在年度に設定しました",
            ]);
        } catch (Exception $e) {
            Response::error('現在年度の設定に失敗しました: ' . $e->getMessage(), 500, 'UPDATE_ERROR');
        }
    }

    /**
     * 入会受付の開始/停止
     */
    public function setEnrollmentOpen(array $params): void
    {
        $year = (int)Request::get('year');
        $open = (bool)Request::get('open');

        if (!$year) {
            Response::error('年度を指定してください', 400, 'VALIDATION_ERROR');
            return;
        }

        try {
            $this->model->setEnrollmentOpen($year, $open);

            Response::success([
                'message' => $open ? '入会受付を開始しました' : '入会受付を停止しました',
            ]);
        } catch (Exception $e) {
            Response::error('入会受付の設定に失敗しました: ' . $e->getMessage(), 500, 'UPDATE_ERROR');
        }
    }

    /**
     * 新規入会フォームの受付開始/停止（期限付き）
     * POST /api/academic-years/set-enroll-open
     * body: { year, open: true/false, enrollment_deadline: "YYYY-MM-DD"|"" }
     */
    public function setEnrollOpen(array $params): void
    {
        $year     = (int)Request::get('year');
        $open     = filter_var(Request::get('open'), FILTER_VALIDATE_BOOLEAN);
        $deadline = Request::get('enrollment_deadline');

        if (!$year) {
            Response::error('年度を指定してください', 400, 'VALIDATION_ERROR');
            return;
        }
        $row = $this->model->findByYear($year);
        if (!$row) {
            Response::error('指定された年度が存在しません', 404, 'NOT_FOUND');
            return;
        }

        try {
            $this->model->setEnrollOpen($year, $open);

            $updateData = [
                'enrollment_deadline' => ($deadline !== '' && $deadline !== null) ? $deadline : null,
            ];
            $this->model->update((int)$row['id'], $updateData);

            Response::success([], $open ? '新規入会の受付を開始しました' : '新規入会の受付を停止しました');
        } catch (Exception $e) {
            Response::error('設定に失敗しました: ' . $e->getMessage(), 500, 'UPDATE_ERROR');
        }
    }

    /**
     * 継続入会フォームの受付開始/停止（期限付き）
     * POST /api/academic-years/set-renew-open
     * body: { year, open: true/false, renew_deadline: "YYYY-MM-DD"|"" }
     */
    public function setRenewOpen(array $params): void
    {
        $year     = (int)Request::get('year');
        $open     = filter_var(Request::get('open'), FILTER_VALIDATE_BOOLEAN);
        $deadline = Request::get('renew_deadline');

        if (!$year) {
            Response::error('年度を指定してください', 400, 'VALIDATION_ERROR');
            return;
        }
        $row = $this->model->findByYear($year);
        if (!$row) {
            Response::error('指定された年度が存在しません', 404, 'NOT_FOUND');
            return;
        }

        try {
            $this->model->setRenewOpen($year, $open);

            $updateData = [
                'renew_deadline' => ($deadline !== '' && $deadline !== null) ? $deadline : null,
            ];
            $this->model->update((int)$row['id'], $updateData);

            Response::success([], $open ? '継続入会の受付を開始しました' : '継続入会の受付を停止しました');
        } catch (Exception $e) {
            Response::error('設定に失敗しました: ' . $e->getMessage(), 500, 'UPDATE_ERROR');
        }
    }

    /**
     * @deprecated setEnrollOpen / setRenewOpen を使用してください（後方互換）
     */
    public function setEnrollmentDeadline(array $params): void
    {
        // 旧API互換：両方まとめて設定
        $year           = (int)Request::get('year');
        $enrollDeadline = Request::get('enrollment_deadline');
        $renewDeadline  = Request::get('renew_deadline');

        if (!$year) {
            Response::error('年度を指定してください', 400, 'VALIDATION_ERROR');
            return;
        }
        $row = $this->model->findByYear($year);
        if (!$row) {
            Response::error('指定された年度が存在しません', 404, 'NOT_FOUND');
            return;
        }

        try {
            $updateData = [
                'enrollment_deadline' => ($enrollDeadline !== '' && $enrollDeadline !== null) ? $enrollDeadline : null,
                'renew_deadline'      => ($renewDeadline  !== '' && $renewDeadline  !== null) ? $renewDeadline  : null,
            ];
            if ($updateData['enrollment_deadline']) {
                $this->model->setEnrollOpen($year, true);
                $updateData['enroll_open'] = 1;
            }
            if ($updateData['renew_deadline']) {
                $this->model->setRenewOpen($year, true);
                $updateData['renew_open'] = 1;
            }
            $this->model->update((int)$row['id'], $updateData);
            Response::success([], '入会受付を設定しました');
        } catch (Exception $e) {
            Response::error('入会受付の設定に失敗しました: ' . $e->getMessage(), 500, 'UPDATE_ERROR');
        }
    }

    /**
     * 年度管理ページ表示
     */
    public function indexPage(array $params): void
    {
        $this->render('academic-years/index');
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
