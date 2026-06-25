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
            self::openSession('password', null, null, null);
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
        $_SESSION['admin_name']       = $adminUser['name'] ?? null;
        $_SESSION['admin_is_admin']   = self::isFixedAdminEmail($adminUser['email']) || (int)$adminUser['is_admin'] === 1;
        $_SESSION['admin_perms']      = array_values($perms);
        $_SESSION['admin_login_type'] = 'google';
        self::openSession(
            'google',
            (int)$adminUser['id'],
            $adminUser['email'] ?? null,
            $adminUser['name'] ?? null
        );
    }

    /**
     * ログインセッション監視（admin_sessions）の行を開始する。
     * セッションに行IDと最終touch時刻を保持。記録失敗はログインを止めない。
     */
    private static function openSession(string $loginType, ?int $adminId, ?string $email, ?string $name): void
    {
        if (!class_exists('AdminSession')) return;
        try {
            // 再ログイン等で前のセッション行が継続中なら閉じてから開く
            if (!empty($_SESSION['admin_session_id'])) {
                (new AdminSession())->close((int)$_SESSION['admin_session_id'], 'logout');
            }
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $sid = (new AdminSession())->open([
                'admin_user_id' => $adminId,
                'admin_email'   => $email,
                'admin_name'    => $name,
                'login_type'    => $loginType,
                'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent'    => $ua !== null ? mb_substr($ua, 0, 255) : null,
            ]);
            $_SESSION['admin_session_id'] = $sid;
            $_SESSION['admin_session_touch'] = time();
        } catch (Throwable $e) {
            error_log('Auth::openSession failed: ' . $e->getMessage());
        }
    }

    /**
     * ログアウト
     */
    public static function logout(): void
    {
        self::closeSession('logout');
        unset(
            $_SESSION[self::SESSION_KEY],
            $_SESSION['login_time'],
            $_SESSION['admin_id'],
            $_SESSION['admin_email'],
            $_SESSION['admin_name'],
            $_SESSION['admin_is_admin'],
            $_SESSION['admin_perms'],
            $_SESSION['admin_login_type'],
            $_SESSION['admin_session_id'],
            $_SESSION['admin_session_touch']
        );
    }

    /**
     * セッション監視行を終了させる（ログアウト/切れ）。
     *
     * @param string $reason 'logout' / 'timeout'
     */
    private static function closeSession(string $reason): void
    {
        if (!class_exists('AdminSession')) return;
        $sid = $_SESSION['admin_session_id'] ?? 0;
        if ((int)$sid <= 0) return;
        try {
            (new AdminSession())->close((int)$sid, $reason);
        } catch (Throwable $e) {
            error_log('Auth::closeSession failed: ' . $e->getMessage());
        }
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
                self::closeSession('timeout');
                self::logout();
                return false;
            }
        }

        // 最終アクセス時刻を更新（毎リクエストだとDB負荷になるため60秒間隔にスロットリング）
        self::touchSession();

        return true;
    }

    /**
     * セッション監視の最終アクセス時刻を更新する（スロットリング付き）。
     * check() から呼ばれる。前回更新から60秒未満なら何もしない。
     */
    private static function touchSession(): void
    {
        if (!class_exists('AdminSession')) return;
        $sid = $_SESSION['admin_session_id'] ?? 0;
        if ((int)$sid <= 0) return;

        $now  = time();
        $last = $_SESSION['admin_session_touch'] ?? 0;
        if ($now - $last < 60) return; // 60秒以内は更新しない

        $_SESSION['admin_session_touch'] = $now;
        try {
            (new AdminSession())->touch((int)$sid);
        } catch (Throwable $e) {
            error_log('Auth::touchSession failed: ' . $e->getMessage());
        }
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
     * 幹部セッションでログイン中か（会員セッション・未ログインと区別する）。
     * 会員ポータル／公開フォームでは個人情報マスキングを適用しないために使う。
     */
    public static function isAdminContext(): bool
    {
        return self::check() && isset($_SESSION['admin_login_type']);
    }

    /**
     * 会員の指定カラムを閲覧できるか（個人情報マスキング判定）。
     *
     * 設計（明示許可制／ホワイトリスト）:
     *   - Admin（全権）は常に全項目を閲覧可。
     *   - それ以外は members.field.<column> を「明示的に」保有する項目のみ閲覧可。
     *   - `members`（名簿アクセス権）を持っていても、個人情報項目は自動では見えない。
     *     ＝ members は「名簿を開ける」権限であって「全項目を見る」権限ではない。
     *
     * 注意: can() の前方一致は使わない（members が全 field を開けてしまうため）。
     */
    public static function canViewMemberField(string $column): bool
    {
        if (!self::check()) return false;
        if (!empty($_SESSION['admin_is_admin'])) return true; // Admin は常に全項目可

        $perms = $_SESSION['admin_perms'] ?? [];
        return in_array('members.field.' . $column, $perms, true);
    }

    /**
     * 会員データ（1件 or 複数件）から、現在の幹部が閲覧できない個人情報項目を除去する。
     * 幹部セッションでない場合（会員ポータル・公開フォーム・未ログイン）は何もしない。
     *
     * @param array $data 連想配列1件、または連想配列の配列
     * @return array マスキング後のデータ
     */
    public static function maskMemberData(array $data): array
    {
        // 幹部以外（会員本人・公開）は対象外。Adminは全項目可なので除去不要。
        if (!self::isAdminContext() || !empty($_SESSION['admin_is_admin'])) {
            return $data;
        }

        $config = require CONFIG_PATH . '/admin.php';
        $fields = $config['member_fields'] ?? [];

        // 閲覧不可なカラムを列挙（明示許可制：canViewMemberField で判定）
        // ※ can() の前方一致を使うと members 保持者が全項目通ってしまうため使わない。
        $hidden = [];
        foreach ($fields as $f) {
            if (!self::canViewMemberField($f['column'])) {
                $hidden[] = $f['column'];
            }
        }
        if (empty($hidden)) {
            return $data;
        }

        $strip = function (array $row) use ($hidden): array {
            foreach ($hidden as $col) {
                unset($row[$col]);
            }
            return $row;
        };

        // 配列の配列か単一行かを判定（単一行は最初の要素が配列でない）
        if ($data === [] || !is_array(reset($data))) {
            return $strip($data);
        }
        return array_map($strip, $data);
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
