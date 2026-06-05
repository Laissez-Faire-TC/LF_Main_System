<?php
/**
 * 遠征しおりコントローラー
 */
class ExpeditionBookletController
{
    /**
     * しおり取得
     * GET /api/expeditions/{id}/booklet
     */
    public function getBooklet(array $params): void
    {
        Auth::requirePermission('expeditions');

        $booklet = ExpeditionBooklet::findByExpedition((int)$params['id']);
        if (!$booklet) {
            Response::success([
                'published'       => false,
                'public_token'    => null,
                'venue'           => '',
                'meeting_note'    => '',
                'items_to_bring'  => [],
                'car_assignment'  => '',
                'team_assignment' => '',
                'room_assignments'=> [],
                'notes'           => '',
            ]);
            return;
        }

        Response::success($booklet);
    }

    /**
     * しおり保存（新規作成 or 更新）
     * POST /api/expeditions/{id}/booklet
     */
    public function saveBooklet(array $params): void
    {
        Auth::requirePermission('expeditions');

        $data    = Request::json();
        $booklet = ExpeditionBooklet::save((int)$params['id'], $data);
        Response::success($booklet);
    }

    /**
     * しおり公開
     * POST /api/expeditions/{id}/booklet/publish
     */
    public function publishBooklet(array $params): void
    {
        Auth::requirePermission('expeditions');

        $booklet = ExpeditionBooklet::publish((int)$params['id']);
        Response::success($booklet);
    }

    /**
     * 公開しおり閲覧（認証不要・会員ログイン時は強調表示あり）
     * GET /public/expedition-booklet/{token}
     */
    public function viewPublicBooklet(array $params): void
    {
        $booklet = ExpeditionBooklet::findByPublicToken($params['token']);
        if (!$booklet) Response::error('しおりが見つかりません', 404);

        // DBから実データ（チーム・車割）を取得して渡す
        $expeditionId = (int)$booklet['expedition_id'];
        $dbTeams = ExpeditionTeam::findByExpedition($expeditionId);
        $dbCars  = ExpeditionCar::findByExpedition($expeditionId);

        // 会員ポータルにログイン中なら自分の情報を取得して強調表示に使う
        $myMemberId = 0;
        $myName     = '';
        $isLoggedIn = false;
        if (
            isset($_SESSION['member_authenticated']) &&
            $_SESSION['member_authenticated'] === true &&
            (!isset($_SESSION['member_login_time']) || time() - $_SESSION['member_login_time'] <= 86400)
        ) {
            $myMemberId = (int)($_SESSION['member_id'] ?? 0);
            $myName     = $_SESSION['member_name'] ?? '';
            $isLoggedIn = true;
        }

        $this->render('expeditions/booklet_public', [
            'booklet'    => $booklet,
            'dbTeams'    => $dbTeams,
            'dbCars'     => $dbCars,
            'myMemberId' => $myMemberId,
            'myName'     => $myName,
            'isLoggedIn' => $isLoggedIn,
        ]);
    }

    /**
     * ビューのレンダリング
     */
    private function render(string $view, array $data = []): void
    {
        extract($data);

        ob_start();
        include VIEWS_PATH . '/' . $view . '.php';
        $content = ob_get_clean();

        include VIEWS_PATH . '/layouts/public.php';
    }
}
