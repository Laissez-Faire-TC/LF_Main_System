<?php
/**
 * 認証ヘルパークラス
 *
 * ログイン方式は2つ:
 *   1) 共有パスワード（従来）         … 常に全権Admin扱い（移行期間の保険）
 *   2) Googleログイン（admin_users） … 機能ごとの権限を持つ
 *
 * 権限判定:
 *   - isAdmin(): 全権（固定Adminメール or is_admin or パスワードログイン）
 *   - can($key): 機能/タブへのアクセス可否（Adminは常にtrue、前方一致）
 */
class Auth
{
    private const SESSION_KEY = 'authenticated';

    /**
     * 共有パスワードでログイン（従来方式・全権扱い）
     */
    public static function login(string $password): bool
    {
        $db = Database::getInstance();
        $setting = $db->fetch(
            "SELECT setting_value FROM settings WHERE setting_key = 'password'"
        );

        if (!$setting) {
            return false;
        }

        if (password_verify($password, $setting['setting_value'])) {
            $_SESSION[self::SESSION_KEY] = true;
            $_SESSION['login_time'] = time();
            // パスワードログインは全権・Google幹部ではない
            $_SESSION['admin_is_admin'] = true;
            $_SESSION['admin_id']    = null;
            $_SESSION['admin_email'] = null;
            $_SESSION['admin_perms'] = [];
            $_SESSION['admin_login_type'] = 'password';
            return true;
        }

        return false;
    }

    /**
     * Google幹部としてログインセッションを確立する。
     * （AdminPortalController のコールバックから呼ばれる）
     *
     * @param array $adminUser admin_users の行
     * @param array $perms     権限キー配列
     */
    public static function establishAdminSession(array $adminUser, array $perms): void
    {
        $_SESSION[self::SESSION_KEY]  = true;
        $_SESSION['login_time']       = time();
        $_SESSION['admin_id']         = (int)$adminUser['id'];
        $_SESSION['admin_email']      = $adminUser['email'];
        $_SESSION['admin_is_admin']   = self::isFixedAdminEmail($adminUser['email']) || (int)$adminUser['is_admin'] === 1;
        $_SESSION['admin_perms']      = array_values($perms);
        $_SESSION['admin_login_type'] = 'google';
    }

    /**
     * ログアウト
     */
    public static function logout(): void
    {
        unset(
            $_SESSION[self::SESSION_KEY],
            $_SESSION['login_time'],
            $_SESSION['admin_id'],
            $_SESSION['admin_email'],
            $_SESSION['admin_is_admin'],
            $_SESSION['admin_perms'],
            $_SESSION['admin_login_type']
        );
    }

    /**
     * 認証チェック
     */
    public static function check(): bool
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        // セッション有効期限チェック（24時間）
        $config = require CONFIG_PATH . '/app.php';
        $lifetime = $config['session']['lifetime'] ?? 86400;

        if (isset($_SESSION['login_time'])) {
            if (time() - $_SESSION['login_time'] > $lifetime) {
                self::logout();
                return false;
            }
        }

        return true;
    }

    /**
     * 認証必須ガード
     */
    public static function requireAuth(): void
    {
        if (!self::check()) {
            if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
                Response::unauthorized();
            } else {
                Response::redirect('/login');
            }
        }
    }

    /**
     * 全権Adminか
     */
    public static function isAdmin(): bool
    {
        return self::check() && !empty($_SESSION['admin_is_admin']);
    }

    /**
     * 指定された機能/タブ権限を持つか。
     * Adminは常にtrue。権限キーは前方一致で判定する。
     *   例: 'expeditions' を保有 → can('expeditions') も can('expeditions.cars') も true
     */
    public static function can(string $key): bool
    {
        if (!self::check()) return false;
        if (!empty($_SESSION['admin_is_admin'])) return true;

        $key   = trim($key);
        $perms = $_SESSION['admin_perms'] ?? [];
        foreach ($perms as $p) {
            // 保有キー $p が要求キー $key を包含するか（前方一致・境界考慮）
            if ($p === $key) return true;
            if (strpos($key, $p . '.') === 0) return true; // 親権限 expeditions は expeditions.cars を許可
        }
        return false;
    }

    /**
     * 権限必須ガード（requireAuth 済み前提で各コントローラーから呼ぶ）
     */
    public static function requirePermission(string $key): void
    {
        self::requireAuth();
        if (!self::can($key)) {
            if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
                Response::error('この操作の権限がありません', 403, 'FORBIDDEN');
            } else {
                http_response_code(403);
                echo '<!DOCTYPE html><meta charset="utf-8"><div style="max-width:480px;margin:4rem auto;font-family:sans-serif;text-align:center">'
                   . '<h2>アクセス権限がありません</h2><p>この機能を利用する権限が付与されていません。管理者にお問い合わせください。</p>'
                   . '<p><a href="/dashboard">ダッシュボードに戻る</a></p></div>';
                exit;
            }
        }
    }

    /**
     * 固定Adminメールか（config/admin.php）
     */
    public static function isFixedAdminEmail(?string $email): bool
    {
        if (empty($email)) return false;
        $config = require CONFIG_PATH . '/admin.php';
        $emails = array_map('strtolower', $config['admin_emails'] ?? []);
        return in_array(strtolower($email), $emails, true);
    }

    /**
     * 現在ログイン中の幹部情報（表示用）
     */
    public static function currentAdmin(): array
    {
        return [
            'id'       => $_SESSION['admin_id'] ?? null,
            'email'    => $_SESSION['admin_email'] ?? null,
            'is_admin' => !empty($_SESSION['admin_is_admin']),
            'type'     => $_SESSION['admin_login_type'] ?? null,
        ];
    }

    /**
     * パスワード設定（初期設定用）
     */
    public static function setPassword(string $password): void
    {
        $db = Database::getInstance();
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $existing = $db->fetch(
            "SELECT id FROM settings WHERE setting_key = 'password'"
        );

        if ($existing) {
            $db->execute(
                "UPDATE settings SET setting_value = ? WHERE setting_key = 'password'",
                [$hash]
            );
        } else {
            $db->execute(
                "INSERT INTO settings (setting_key, setting_value) VALUES ('password', ?)",
                [$hash]
            );
        }
    }
}
