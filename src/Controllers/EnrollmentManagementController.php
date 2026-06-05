<?php
/**
 * 入会管理コントローラー（管理者用）
 * 入会フォームURL管理・入会金管理・新規入会者リストを統合
 */
class EnrollmentManagementController
{
    public function __construct()
    {
        Auth::requirePermission('enrollment');
    }

    /**
     * GET /enrollment-management
     * 入会管理ページ
     */
    public function index(array $params): void
    {
        $this->render('enrollment-management/index', []);
    }

    /**
     * ビューのレンダリング
     */
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
