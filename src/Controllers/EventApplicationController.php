<?php
/**
 * 企画申し込みコントローラー（公開・会員ログイン使用）
 *
 * フロー:
 *   GET  /apply/event/{token}          → ログインページ（未ログイン）or confirm へリダイレクト
 *   GET  /apply/event/{token}/confirm  → 情報確認・申し込みボタン
 *   POST /api/apply/event/{token}      → 申し込み処理（JSON API）
 *   GET  /apply/event/{token}/complete → 完了ページ
 */
class EventApplicationController
{
    private const SESSION_KEY = 'member_authenticated';

    // ==================== Step 1: ログイン ====================

    public function form(array $params): void
    {
        $token     = $params['token'];
        $tokenData = EventToken::findByToken($token);

        if (!$this->isTokenValid($tokenData)) {
            $this->showError('無効な申し込みURLです。URLが正しいか、有効期限内かをご確認ください。');
            return;
        }

        if ($this->checkAuth()) {
            Response::redirect("/apply/event/{$token}/confirm");
            return;
        }

        $event     = (new Event())->find((int)$tokenData['event_id']);
        $pageTitle = htmlspecialchars($event['title'] ?? '企画') . ' - 申し込み';
        $appName   = '企画申し込み';
        ob_start();
        include VIEWS_PATH . '/events/apply.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/public.php';
    }

    // ==================== Step 2: 情報確認 ====================

    public function confirm(array $params): void
    {
        $token     = $params['token'];
        $tokenData = EventToken::findByToken($token);

        if (!$this->isTokenValid($tokenData)) {
            $this->showError('無効な申し込みURLです。URLが正しいか、有効期限内かをご確認ください。');
            return;
        }

        if (!$this->checkAuth()) {
            Response::redirect("/apply/event/{$token}");
            return;
        }

        $event = (new Event())->find((int)$tokenData['event_id']);
        if (!$event) {
            $this->showError('企画が見つかりません。');
            return;
        }

        $memberId    = (int)$_SESSION['member_id'];
        $memberModel = new Member();
        $member      = $memberModel->find($memberId);

        if (!$member || !in_array($member['status'], ['active', 'ob_og'])) {
            $this->showError('会員情報が見つかりません。');
            return;
        }

        $appModel       = new EventApplication();
        $existing       = $appModel->findByEventAndMember((int)$event['id'], $memberId);
        $alreadyApplied = $existing && in_array($existing['status'], ['submitted', 'waitlisted']);
        $existingStatus = $existing['status'] ?? null;

        // 定員チェック
        $confirmedCount = $appModel->countByEventId((int)$event['id']);
        $capacity       = $event['capacity'] !== null ? (int)$event['capacity'] : null;
        $isFull         = $capacity !== null && $confirmedCount >= $capacity;
        $waitlistCount  = $appModel->countWaitlistByEventId((int)$event['id']);
        $remaining      = $capacity !== null ? max(0, $capacity - $confirmedCount) : null;

        // 期限チェック
        $isDeadlinePassed = $event['deadline'] !== null && $event['deadline'] < date('Y-m-d');

        $pageTitle = htmlspecialchars($event['title'] ?? '企画') . ' - 申し込み確認';
        $appName   = '企画申し込み';
        ob_start();
        include VIEWS_PATH . '/events/apply_confirm.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/public.php';
    }

    // ==================== Step 3: 申し込み処理（API） ====================

    public function apply(array $params): void
    {
        $token     = $params['token'];
        $tokenData = EventToken::findByToken($token);

        if (!$this->isTokenValid($tokenData)) {
            Response::error('無効な申し込みURLです', 400, 'INVALID_TOKEN');
            return;
        }

        if (!$this->checkAuth()) {
            Response::error('ログインが必要です', 401, 'UNAUTHORIZED');
            return;
        }

        $memberId = (int)$_SESSION['member_id'];
        $event    = (new Event())->find((int)$tokenData['event_id']);
        if (!$event) {
            Response::error('企画が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        // 申込期限チェック
        if ($event['deadline'] !== null && $event['deadline'] < date('Y-m-d')) {
            Response::error('申し込み期限を過ぎています', 400, 'DEADLINE_PASSED');
            return;
        }

        $appModel = new EventApplication();
        $existing = $appModel->findByEventAndMember((int)$event['id'], $memberId);

        // すでに参加確定 or キャンセル待ち
        if ($existing && in_array($existing['status'], ['submitted', 'waitlisted'])) {
            Response::error('すでに申し込み済みです', 400, 'ALREADY_APPLIED');
            return;
        }

        // 定員チェック
        $confirmedCount = $appModel->countByEventId((int)$event['id']);
        $capacity       = $event['capacity'] !== null ? (int)$event['capacity'] : null;
        $isFull         = $capacity !== null && $confirmedCount >= $capacity;

        $body = Request::json();
        $note = trim($body['note'] ?? '');

        if ($isFull) {
            if ($event['allow_waitlist']) {
                $appModel->apply((int)$event['id'], $memberId, 'waitlisted', $note ?: null);
                Response::success(['status' => 'waitlisted'], 'キャンセル待ちに登録しました');
            } else {
                Response::error('定員に達しているため申し込みできません', 400, 'CAPACITY_FULL');
            }
            return;
        }

        $appModel->apply((int)$event['id'], $memberId, 'submitted', $note ?: null);
        Response::success(['status' => 'submitted'], '申し込みが完了しました');
    }

    // ==================== Step 4: 完了 ====================

    public function complete(array $params): void
    {
        $token     = $params['token'];
        $tokenData = EventToken::findByToken($token);

        if (!$tokenData) {
            $this->showError('URLが無効です。');
            return;
        }

        $event       = (new Event())->find((int)$tokenData['event_id']);
        $member      = null;
        $application = null;

        if ($this->checkAuth()) {
            $memberId    = (int)$_SESSION['member_id'];
            $memberModel = new Member();
            $member      = $memberModel->find($memberId);
            $appModel    = new EventApplication();
            $application = $appModel->findByEventAndMember((int)$event['id'], $memberId);
        }

        $pageTitle = '申し込み完了';
        $appName   = '企画申し込み';
        ob_start();
        include VIEWS_PATH . '/events/apply_complete.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/public.php';
    }

    // ==================== ユーティリティ ====================

    private function isTokenValid(?array $tokenData): bool
    {
        if (!$tokenData) return false;
        if ($tokenData['expires_at'] && strtotime($tokenData['expires_at']) < time()) return false;
        return true;
    }

    private function checkAuth(): bool
    {
        if (!isset($_SESSION[self::SESSION_KEY])) return false;
        if (isset($_SESSION['member_login_time'])) {
            if (time() - $_SESSION['member_login_time'] > 86400) {
                unset($_SESSION[self::SESSION_KEY]);
                return false;
            }
        }
        return true;
    }

    private function showError(string $message): void
    {
        $errorMessage = $message;
        $appName      = '企画申し込み';
        $content      = '<div class="container py-5"><div class="alert alert-danger">' . htmlspecialchars($message) . '</div></div>';
        include VIEWS_PATH . '/layouts/public.php';
    }
}
