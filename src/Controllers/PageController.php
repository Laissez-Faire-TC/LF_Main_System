<?php
/**
 * ページコントローラー（HTML表示用）
 */
class PageController
{
    /**
     * トップページ（ダッシュボード）
     */
    public function index(array $params): void
    {
        if (!Auth::check()) {
            Response::redirect('/login');
            return;
        }

        $campTokenModel = new CampToken();
        $activeCamps = $campTokenModel->getActiveCampsWithTokens();

        $notifModel = new MemberChangeNotification();
        $changeNotifications = $notifModel->getUnread();

        // ゲスト申込の再提出（内容変更）通知
        $guestResubmissions = (new EventGuestApplication())->getUnconfirmedResubmissions();

        $this->render('dashboard', [
            'activeCamps'         => $activeCamps,
            'changeNotifications' => $changeNotifications,
            'guestResubmissions'  => $guestResubmissions,
        ]);
    }

    /**
     * ログインページ
     */
    public function login(array $params): void
    {
        if (Auth::check()) {
            Response::redirect('/dashboard');
        }

        $oauthService = new OAuthService();
        $oauthError   = $_SESSION['admin_oauth_error'] ?? null;
        unset($_SESSION['admin_oauth_error']);

        $this->render('auth/login', [
            'googleEnabled' => $oauthService->isEnabled('google'),
            'oauthError'    => $oauthError,
        ]);
    }

    /**
     * 合宿一覧ページ
     */
    public function camps(array $params): void
    {
        Auth::requirePermission('camps');
        $this->render('camps/index');
    }

    /**
     * 合宿詳細ページ
     */
    public function campDetail(array $params): void
    {
        Auth::requirePermission('camps');
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $this->render('camps/detail', ['campId' => $campId, 'camp' => $camp]);
    }

    /**
     * 遠征一覧ページ
     */
    public function expeditions(array $params): void
    {
        Auth::requirePermission('expeditions');
        $this->render('expeditions/index');
    }

    /**
     * 遠征詳細ページ
     */
    public function expeditionDetail(array $params): void
    {
        Auth::requirePermission('expeditions');
        $this->render('expeditions/detail', ['id' => $params['id']]);
    }

    /**
     * 計算結果ページ
     */
    public function result(array $params): void
    {
        Auth::requirePermission('camps');
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $this->render('results/show', ['campId' => $campId, 'camp' => $camp]);
    }

    /**
     * 途中参加・途中抜けスケジュール表ページ
     */
    public function partialSchedule(array $params): void
    {
        Auth::requirePermission('camps');
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp = $campModel->find($campId);

        if (!$camp) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $this->render('results/partial-schedule', ['campId' => $campId, 'camp' => $camp]);
    }

    /**
     * 会員変更通知を既読にする API
     * POST /api/member-change-notifications/{id}/read
     */
    public function dismissChangeNotification(array $params): void
    {
        if (!Auth::check()) {
            Response::error('ログインが必要です', 401, 'UNAUTHORIZED');
            return;
        }

        $notifModel = new MemberChangeNotification();
        $notifModel->markAsRead((int)$params['id']);
        Response::success([]);
    }

    /**
     * 使い方ガイドページ
     */
    public function guide(array $params): void
    {
        Auth::requireAuth();
        $this->render('guide');
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
