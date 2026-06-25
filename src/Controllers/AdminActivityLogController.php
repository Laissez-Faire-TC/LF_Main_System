<?php
/**
 * 幹部活動ログ コントローラー（Admin限定）
 *   画面: /admin-activity-logs
 *   API : /api/admin-activity-logs
 *
 * 幹部システムでの幹部の操作履歴（誰が・いつ・どこで・何を）を閲覧する。
 * 記録は Router::dispatch() → Auth::recordActivity() で自動的に行われる。
 */
class AdminActivityLogController
{
    private AdminActivityLog $model;

    public function __construct()
    {
        Auth::requireAuth();
        // 活動ログは全権Adminのみ閲覧可能
        if (!Auth::isAdmin()) {
            if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
                Response::error('活動ログはAdminのみが閲覧できます', 403, 'FORBIDDEN');
            } else {
                http_response_code(403);
                echo '<!DOCTYPE html><meta charset="utf-8"><div style="max-width:480px;margin:4rem auto;font-family:sans-serif;text-align:center">'
                   . '<h2>アクセス権限がありません</h2><p>活動ログはAdminのみが閲覧できます。</p>'
                   . '<p><a href="/dashboard">ダッシュボードに戻る</a></p></div>';
                exit;
            }
        }
        $this->model = new AdminActivityLog();
    }

    /**
     * 管理画面
     */
    public function indexPage(array $params): void
    {
        $appConfig = require CONFIG_PATH . '/app.php';
        $appName   = $appConfig['name'];

        $filters = $this->readFilters();
        $perPage = 100;
        $page    = max(1, (int)(Request::get('page') ?? 1));
        $offset  = ($page - 1) * $perPage;

        $logs    = $this->model->search($filters, $perPage, $offset);
        $total   = $this->model->count($filters);
        $actors  = $this->model->distinctActors();
        $totalPages = max(1, (int)ceil($total / $perPage));

        // 操作者の表示名マップ（admin_user_id => 名前）。
        // ログ記録時に admin_name が空でも、現在の幹部マスタから名前を補完するため。
        $nameMap = [];
        foreach ((new AdminUser())->allWithPermissions() as $u) {
            if (!empty($u['name'])) {
                $nameMap[(int)$u['id']] = $u['name'];
            }
        }

        ob_start();
        include VIEWS_PATH . '/admin/activity_logs.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/main.php';
    }

    /**
     * 一覧取得 API（JSON）
     */
    public function index(array $params): void
    {
        $filters = $this->readFilters();
        $perPage = min(500, max(1, (int)(Request::get('per_page') ?? 100)));
        $page    = max(1, (int)(Request::get('page') ?? 1));
        $offset  = ($page - 1) * $perPage;

        Response::success([
            'logs'  => $this->model->search($filters, $perPage, $offset),
            'total' => $this->model->count($filters),
            'page'  => $page,
        ]);
    }

    /**
     * クエリからフィルタを読む。
     */
    private function readFilters(): array
    {
        return [
            'feature'       => trim((string)(Request::get('feature') ?? '')) ?: null,
            'admin_user_id' => (Request::get('admin_user_id') ?? '') !== '' ? (int)Request::get('admin_user_id') : null,
            'keyword'       => trim((string)(Request::get('keyword') ?? '')) ?: null,
            'from'          => trim((string)(Request::get('from') ?? '')) ?: null,
            'to'            => trim((string)(Request::get('to') ?? '')) ?: null,
        ];
    }
}
