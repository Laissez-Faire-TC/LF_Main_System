<?php
/**
 * 幹部ログインセッション コントローラー（Admin限定）
 *   画面: /admin-sessions
 *   API : /api/admin-sessions
 *
 * 幹部のログイン/ログアウト/活動時間（滞在時間）・現在のオンライン状況を閲覧する。
 * 記録は Auth（login/logout/check）から admin_sessions に行われる。
 */
class AdminSessionController
{
    private AdminSession $model;

    public function __construct()
    {
        Auth::requireAuth();
        // セッション監視は全権Adminのみ閲覧可能
        if (!Auth::isAdmin()) {
            if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
                Response::error('ログイン履歴はAdminのみが閲覧できます', 403, 'FORBIDDEN');
            } else {
                http_response_code(403);
                echo '<!DOCTYPE html><meta charset="utf-8"><div style="max-width:480px;margin:4rem auto;font-family:sans-serif;text-align:center">'
                   . '<h2>アクセス権限がありません</h2><p>ログイン履歴はAdminのみが閲覧できます。</p>'
                   . '<p><a href="/dashboard">ダッシュボードに戻る</a></p></div>';
                exit;
            }
        }
        $this->model = new AdminSession();
    }

    /**
     * 管理画面
     */
    public function indexPage(array $params): void
    {
        $appConfig = require CONFIG_PATH . '/app.php';
        $appName   = $appConfig['name'];

        // 閲覧時に、放置された継続中セッションを timeout として後始末する（cron不要の簡易方式）。
        $lifetime = $appConfig['session']['lifetime'] ?? 86400;
        $this->model->closeStale((int)$lifetime);

        $filters = $this->readFilters();
        $perPage = 100;
        $page    = max(1, (int)(Request::get('page') ?? 1));
        $offset  = ($page - 1) * $perPage;

        $sessions   = $this->model->search($filters, $perPage, $offset);
        $total      = $this->model->count($filters);
        $actors     = $this->model->distinctActors();
        $online     = $this->model->online(self::ONLINE_WINDOW);
        $totalPages = max(1, (int)ceil($total / $perPage));

        // 操作者の表示名マップ（admin_user_id => 名前）の補完用
        $nameMap = [];
        foreach ((new AdminUser())->allWithPermissions() as $u) {
            if (!empty($u['name'])) {
                $nameMap[(int)$u['id']] = $u['name'];
            }
        }

        $onlineWindow = self::ONLINE_WINDOW;

        ob_start();
        include VIEWS_PATH . '/admin/sessions.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/main.php';
    }

    /**
     * 一覧取得 API（JSON）
     */
    public function index(array $params): void
    {
        $appConfig = require CONFIG_PATH . '/app.php';
        $this->model->closeStale((int)($appConfig['session']['lifetime'] ?? 86400));

        $filters = $this->readFilters();
        $perPage = min(500, max(1, (int)(Request::get('per_page') ?? 100)));
        $page    = max(1, (int)(Request::get('page') ?? 1));
        $offset  = ($page - 1) * $perPage;

        Response::success([
            'sessions' => $this->model->search($filters, $perPage, $offset),
            'online'   => $this->model->online(self::ONLINE_WINDOW),
            'total'    => $this->model->count($filters),
            'page'     => $page,
        ]);
    }

    /** 「オンライン」とみなす最終アクセスからの猶予秒数 */
    private const ONLINE_WINDOW = 300;

    private function readFilters(): array
    {
        return [
            'admin_user_id' => (Request::get('admin_user_id') ?? '') !== '' ? (int)Request::get('admin_user_id') : null,
            'login_type'    => trim((string)(Request::get('login_type') ?? '')) ?: null,
            'from'          => trim((string)(Request::get('from') ?? '')) ?: null,
            'to'            => trim((string)(Request::get('to') ?? '')) ?: null,
            'online'        => Request::get('online') ? 1 : null,
        ];
    }
}
