<?php
/**
 * 幹部・権限管理コントローラー（Admin限定）
 *   画面: /admin-users
 *   API : /api/admin-users ...
 */
class AdminUserController
{
    private AdminUser $model;

    public function __construct()
    {
        Auth::requireAuth();
        // 全権Adminのみ操作可能
        if (!Auth::isAdmin()) {
            if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
                Response::error('権限管理はAdminのみが操作できます', 403, 'FORBIDDEN');
            } else {
                http_response_code(403);
                echo '<!DOCTYPE html><meta charset="utf-8"><div style="max-width:480px;margin:4rem auto;font-family:sans-serif;text-align:center">'
                   . '<h2>アクセス権限がありません</h2><p>幹部・権限管理はAdminのみが利用できます。</p>'
                   . '<p><a href="/dashboard">ダッシュボードに戻る</a></p></div>';
                exit;
            }
        }
        $this->model = new AdminUser();
    }

    /**
     * 管理画面
     */
    public function indexPage(array $params): void
    {
        $config      = require CONFIG_PATH . '/admin.php';
        $appConfig   = require CONFIG_PATH . '/app.php';
        $appName     = $appConfig['name'];

        $users        = $this->model->allWithPermissions();
        $permDefs     = $config['permissions'] ?? [];
        $fieldDefs    = $config['member_fields'] ?? [];
        $fixedEmails  = array_map('strtolower', $config['admin_emails'] ?? []);

        // 各ユーザーに「固定Adminか（降格・削除不可）」フラグを付加
        foreach ($users as &$u) {
            $u['is_fixed'] = in_array(strtolower($u['email']), $fixedEmails, true);
        }
        unset($u);

        ob_start();
        include VIEWS_PATH . '/admin/admin_users.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/main.php';
    }

    /**
     * 一覧取得 API
     */
    public function index(array $params): void
    {
        Response::success($this->model->allWithPermissions());
    }

    /**
     * 幹部追加（メール招待）
     */
    public function store(array $params): void
    {
        $email   = trim((string)Request::get('email'));
        $name    = trim((string)(Request::get('name') ?? ''));
        $isAdmin = (bool)Request::get('is_admin');
        $perms   = Request::get('permissions') ?? [];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('有効なメールアドレスを入力してください', 400, 'VALIDATION_ERROR');
            return;
        }
        if ($this->model->findByEmail($email)) {
            Response::error('このメールアドレスは既に登録されています', 400, 'DUPLICATE');
            return;
        }

        $id = $this->model->create($email, $name !== '' ? $name : null, $isAdmin);
        if (is_array($perms)) {
            $this->model->setPermissions($id, $this->filterPermissions($perms));
        }

        Response::success(['id' => $id], '幹部を追加しました');
    }

    /**
     * 幹部更新（名前・Admin・有効/無効・権限）
     */
    public function update(array $params): void
    {
        $id   = (int)$params['id'];
        $user = $this->model->find($id);
        if (!$user) {
            Response::error('対象が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $isFixed = Auth::isFixedAdminEmail($user['email']);

        $name     = trim((string)(Request::get('name') ?? ''));
        $isAdmin  = (bool)Request::get('is_admin');
        $isActive = (bool)Request::get('is_active');
        $perms    = Request::get('permissions') ?? [];

        // 固定Adminは常に全権・有効を強制（降格・無効化を防ぐ）
        if ($isFixed) {
            $isAdmin  = true;
            $isActive = true;
        }

        // 自分自身の降格・無効化を防ぐ（最後の管理者を失わないため）
        if ((int)($_SESSION['admin_id'] ?? 0) === $id) {
            if (!$isAdmin || !$isActive) {
                Response::error('自分自身のAdmin権限の解除・無効化はできません', 400, 'FORBIDDEN');
                return;
            }
        }

        $this->model->updateProfile($id, $name !== '' ? $name : null, $isAdmin, $isActive);
        if (is_array($perms)) {
            $this->model->setPermissions($id, $this->filterPermissions($perms));
        }

        Response::success([], '更新しました');
    }

    /**
     * 幹部削除
     */
    public function destroy(array $params): void
    {
        $id   = (int)$params['id'];
        $user = $this->model->find($id);
        if (!$user) {
            Response::error('対象が見つかりません', 404, 'NOT_FOUND');
            return;
        }
        if (Auth::isFixedAdminEmail($user['email'])) {
            Response::error('固定Adminは削除できません', 400, 'FORBIDDEN');
            return;
        }
        // 自分自身は削除させない（ロックアウト防止）
        if ((int)($_SESSION['admin_id'] ?? 0) === $id) {
            Response::error('自分自身は削除できません', 400, 'FORBIDDEN');
            return;
        }

        $this->model->delete($id);
        Response::success([], '削除しました');
    }

    /**
     * 権限キーを正準リスト（config/admin.php）に限定する。
     * 想定外の文字列キーが admin_permissions に入るのを防ぐ。
     * 将来タブ単位（例: expeditions.cars）を許す場合は、親キーで始まる子キーも許可。
     */
    private function filterPermissions(array $keys): array
    {
        $config  = require CONFIG_PATH . '/admin.php';
        $allowed = array_merge(
            array_column($config['permissions'] ?? [], 'key'),
            array_column($config['member_fields'] ?? [], 'key')
        );

        return array_values(array_filter($keys, function ($k) use ($allowed) {
            $k = trim((string)$k);
            if ($k === '') return false;
            foreach ($allowed as $a) {
                if ($k === $a) return true;
                if (strpos($k, $a . '.') === 0) return true; // 子キー（タブ）を許可
            }
            return false;
        }));
    }
}
