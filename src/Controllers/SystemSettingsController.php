<?php
/**
 * システム設定コントローラー（管理者用）
 */
class SystemSettingsController
{
    private HpSettings $settings;

    public function __construct()
    {
        Auth::requirePermission('settings');
        $this->settings = new HpSettings();
    }

    /**
     * GET /settings
     * システム設定ページ
     */
    public function indexPage(array $params): void
    {
        $this->render('settings/index', []);
    }

    /**
     * GET /api/system-settings
     * AI設定を取得
     */
    public function get(array $params): void
    {
        $aiModel     = $this->settings->get('ai_model')      ?? 'claude-haiku-4-5-20251001';
        $aiMaxTokens = $this->settings->get('ai_max_tokens') ?? '1024';
        $aiEnabled   = $this->settings->get('ai_enabled')    ?? '1';

        Response::success([
            'ai_model'      => $aiModel,
            'ai_max_tokens' => (int)$aiMaxTokens,
            'ai_enabled'    => (bool)(int)$aiEnabled,
        ]);
    }

    /**
     * PUT /api/system-settings
     * AI設定を保存
     */
    public function update(array $params): void
    {
        $model = Request::get('ai_model');
        if ($model !== null) {
            $model = trim($model);
            if (empty($model)) {
                Response::error('モデル名を入力してください', 400, 'VALIDATION_ERROR');
                return;
            }
            $this->settings->set('ai_model', $model);
        }

        $maxTokens = Request::get('ai_max_tokens');
        if ($maxTokens !== null) {
            $maxTokens = (int)$maxTokens;
            if ($maxTokens < 256 || $maxTokens > 8192) {
                Response::error('max_tokensは256〜8192の範囲で指定してください', 400, 'VALIDATION_ERROR');
                return;
            }
            $this->settings->set('ai_max_tokens', (string)$maxTokens);
        }

        $enabled = Request::get('ai_enabled');
        if ($enabled !== null) {
            $this->settings->set('ai_enabled', $enabled ? '1' : '0');
        }

        Response::success([], 'AI設定を保存しました');
    }

    private function render(string $view, array $data = []): void
    {
        extract($data);

        $config  = require CONFIG_PATH . '/app.php';
        $appName = $config['name'];

        ob_start();
        include VIEWS_PATH . '/' . $view . '.php';
        $content = ob_get_clean();

        include VIEWS_PATH . '/layouts/main.php';
    }
}
