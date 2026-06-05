<?php
/**
 * 幹部Googleログイン コントローラー
 *   /admin/oauth/google/start    … Google認可へリダイレクト
 *   /admin/oauth/google/callback … 認可後の処理（admin_users と照合してログイン）
 *
 * ※ ログイン前から到達するため、コンストラクタで認証ガードはしない。
 */
class AdminPortalController
{
    /**
     * Googleログイン開始
     */
    public function oauthStart(array $params): void
    {
        $provider = strtolower(trim($params['provider'] ?? ''));
        if ($provider !== 'google') {
            $_SESSION['admin_oauth_error'] = 'この連携方法は現在利用できません。';
            Response::redirect('/login');
            return;
        }

        $oauthService = new OAuthService();
        if (!$oauthService->isEnabled('google')) {
            $_SESSION['admin_oauth_error'] = 'Googleログインは現在利用できません。';
            Response::redirect('/login');
            return;
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['admin_oauth_state']    = $state;
        $_SESSION['admin_oauth_provider'] = $provider;

        Response::redirect($oauthService->getAuthUrl($provider, $state, 'admin'));
    }

    /**
     * Googleログイン コールバック
     */
    public function oauthCallback(array $params): void
    {
        $provider = strtolower(trim($params['provider'] ?? ''));
        $oauthService = new OAuthService();

        $expectedState    = $_SESSION['admin_oauth_state'] ?? null;
        $expectedProvider = $_SESSION['admin_oauth_provider'] ?? null;
        $gotState = $_GET['state'] ?? null;
        $code     = $_GET['code'] ?? null;

        unset($_SESSION['admin_oauth_state'], $_SESSION['admin_oauth_provider']);

        if ($provider !== 'google' || !$oauthService->isEnabled('google')
            || !$expectedState || !$gotState || !hash_equals($expectedState, (string)$gotState)
            || $expectedProvider !== $provider || empty($code)) {
            $_SESSION['admin_oauth_error'] = '認証に失敗しました。もう一度お試しください。';
            Response::redirect('/login');
            return;
        }

        try {
            $info = $oauthService->handleCallback($provider, (string)$code, 'admin');
        } catch (Exception $e) {
            $_SESSION['admin_oauth_error'] = '認証に失敗しました：' . $e->getMessage();
            Response::redirect('/login');
            return;
        }

        $email = $info['email'] ?? null;
        $sub   = $info['provider_user_id'];
        $name  = $info['display_name'] ?? null;

        $model = new AdminUser();

        // sub で既存照合 → なければ email で照合（初回連携）
        $admin = $model->findByGoogleSub($sub);
        if (!$admin && $email) {
            $admin = $model->findByEmail($email);
        }

        // 固定Adminメールは、未登録でも自動的に幹部として受け入れる（ロックアウト防止）
        if (!$admin && Auth::isFixedAdminEmail($email)) {
            $id = $model->create($email, $name, true);
            $admin = $model->find($id);
        }

        if (!$admin) {
            $_SESSION['admin_oauth_error'] = 'このGoogleアカウントは幹部として許可されていません。管理者にお問い合わせください。';
            Response::redirect('/login');
            return;
        }

        // 固定Adminは無効化されていても締め出さない（恒久ロックアウト防止）
        if ((int)$admin['is_active'] !== 1 && !Auth::isFixedAdminEmail($admin['email'])) {
            $_SESSION['admin_oauth_error'] = 'このアカウントは無効化されています。管理者にお問い合わせください。';
            Response::redirect('/login');
            return;
        }

        // 初回ログインで google_sub を確定
        if (empty($admin['google_sub'])) {
            $model->linkGoogle((int)$admin['id'], $sub, $name);
            $admin['google_sub'] = $sub;
        }

        $model->touchLogin((int)$admin['id']);

        $perms = $model->getPermissions((int)$admin['id']);
        Auth::establishAdminSession($admin, $perms);

        Response::redirect('/dashboard');
    }
}
