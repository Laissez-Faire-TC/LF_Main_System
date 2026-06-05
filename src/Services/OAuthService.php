<?php
/**
 * OAuth2.0 サービス（Google / LINE Login）
 *
 * 会員ログインの希望者向け連携で使用。
 *   - getAuthUrl(): 認可エンドポイントへのリダイレクトURLを生成（state でCSRF対策）
 *   - handleCallback(): code をトークンに交換し、プロバイダの一意ユーザー情報を返す
 *
 * 戻り値（handleCallback）:
 *   ['provider_user_id' => string, 'email' => ?string, 'display_name' => ?string]
 *
 * 認証情報は config/oauth.php から読み込む。
 */

if (!class_exists('OAuthService')) {

class OAuthService
{
    private array $config;

    public function __construct()
    {
        $this->config = require CONFIG_PATH . '/oauth.php';
    }

    /**
     * 指定プロバイダが有効（設定済み）か
     */
    public function isEnabled(string $provider): bool
    {
        if ($provider === 'google') {
            return !empty($this->config['google']['enabled'])
                && !empty($this->config['google']['client_id'])
                && !empty($this->config['google']['client_secret']);
        }
        if ($provider === 'line') {
            return !empty($this->config['line']['enabled'])
                && !empty($this->config['line']['channel_id'])
                && !empty($this->config['line']['channel_secret']);
        }
        return false;
    }

    /**
     * コールバックURL（プロバイダのコンソール登録値と一致させる）
     *
     * @param string $context 'member'（会員）または 'admin'（幹部）
     */
    public function redirectUri(string $provider, string $context = 'member'): string
    {
        $base = rtrim($this->config['base_url'] ?? '', '/');
        $prefix = ($context === 'admin') ? 'admin' : 'member';
        return $base . '/index.php?route=' . $prefix . '/oauth/' . $provider . '/callback';
    }

    /**
     * 認可エンドポイントへのリダイレクトURLを生成。
     * $state は呼び出し側でセッションに保存し、コールバックで照合すること。
     *
     * @param string $context 'member' or 'admin'（redirect_uri を切り替える）
     */
    public function getAuthUrl(string $provider, string $state, string $context = 'member'): string
    {
        if ($provider === 'google') {
            $params = [
                'client_id'     => $this->config['google']['client_id'],
                'redirect_uri'  => $this->redirectUri('google', $context),
                'response_type' => 'code',
                'scope'         => 'openid email profile',
                'state'         => $state,
                'access_type'   => 'online',
                'prompt'        => 'select_account',
            ];
            return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
        }

        if ($provider === 'line') {
            $params = [
                'response_type' => 'code',
                'client_id'     => $this->config['line']['channel_id'],
                'redirect_uri'  => $this->redirectUri('line'),
                'state'         => $state,
                'scope'         => 'openid profile email',
            ];
            return 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query($params);
        }

        throw new Exception('未対応のプロバイダです: ' . $provider);
    }

    /**
     * コールバック処理：code をトークン交換し、ユーザー識別情報を返す。
     *
     * @return array{provider_user_id:string, email:?string, display_name:?string}
     * @throws Exception 認証失敗時
     */
    public function handleCallback(string $provider, string $code, string $context = 'member'): array
    {
        if ($provider === 'google') {
            return $this->handleGoogle($code, $context);
        }
        if ($provider === 'line') {
            return $this->handleLine($code, $context);
        }
        throw new Exception('未対応のプロバイダです: ' . $provider);
    }

    // ---------------- Google ----------------

    private function handleGoogle(string $code, string $context = 'member'): array
    {
        $token = $this->postForm('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => $this->config['google']['client_id'],
            'client_secret' => $this->config['google']['client_secret'],
            'redirect_uri'  => $this->redirectUri('google', $context),
            'grant_type'    => 'authorization_code',
        ]);

        if (empty($token['access_token'])) {
            throw new Exception('Googleのトークン取得に失敗しました');
        }

        // userinfo から不変の sub を取得
        $info = $this->getJson(
            'https://openidconnect.googleapis.com/v1/userinfo',
            ['Authorization: Bearer ' . $token['access_token']]
        );

        if (empty($info['sub'])) {
            throw new Exception('Googleのユーザー情報取得に失敗しました');
        }

        return [
            'provider_user_id' => (string)$info['sub'],
            'email'            => $info['email'] ?? null,
            'display_name'     => $info['name'] ?? null,
        ];
    }

    // ---------------- LINE ----------------

    private function handleLine(string $code, string $context = 'member'): array
    {
        $token = $this->postForm('https://api.line.me/oauth2/v2.1/token', [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $this->redirectUri('line', $context),
            'client_id'     => $this->config['line']['channel_id'],
            'client_secret' => $this->config['line']['channel_secret'],
        ]);

        if (empty($token['access_token'])) {
            throw new Exception('LINEのトークン取得に失敗しました');
        }

        // userId（不変）はプロフィールAPIから取得
        $profile = $this->getJson(
            'https://api.line.me/v2/profile',
            ['Authorization: Bearer ' . $token['access_token']]
        );

        if (empty($profile['userId'])) {
            throw new Exception('LINEのユーザー情報取得に失敗しました');
        }

        // email は id_token（JWT）の payload に含まれる場合がある（emailスコープ承認時）
        $email = null;
        if (!empty($token['id_token'])) {
            $email = $this->emailFromIdToken($token['id_token']);
        }

        return [
            'provider_user_id' => (string)$profile['userId'],
            'email'            => $email,
            'display_name'     => $profile['displayName'] ?? null,
        ];
    }

    /**
     * id_token（JWT）の payload から email を緩く取り出す（署名検証は行わない＝参考情報のため）。
     */
    private function emailFromIdToken(string $idToken): ?string
    {
        $parts = explode('.', $idToken);
        if (count($parts) < 2) return null;
        $payload = base64_decode(strtr($parts[1], '-_', '+/'));
        if ($payload === false) return null;
        $data = json_decode($payload, true);
        return is_array($data) ? ($data['email'] ?? null) : null;
    }

    // ---------------- HTTP helpers ----------------

    private function postForm(string $url, array $fields): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $res  = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($res === false) {
            throw new Exception('通信エラー: ' . $err);
        }
        $data = json_decode($res, true);
        return is_array($data) ? $data : [];
    }

    private function getJson(string $url, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($res === false) {
            throw new Exception('通信エラー: ' . $err);
        }
        $data = json_decode($res, true);
        return is_array($data) ? $data : [];
    }
}

}
