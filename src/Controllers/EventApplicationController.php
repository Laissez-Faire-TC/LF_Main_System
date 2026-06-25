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
        $allowGuest = !empty($event['allow_guest']);   // 非会員申込を許可するか

        // Google / LINE 連携ログイン（設定済みなら申し込みフォームにもボタンを出す）
        $oauthService = new OAuthService();
        $googleEnabled = $oauthService->isEnabled('google');
        $lineEnabled   = $oauthService->isEnabled('line');

        $pageTitle = htmlspecialchars($event['title'] ?? '企画') . ' - 申し込み';
        $appName   = '企画申し込み';
        ob_start();
        include VIEWS_PATH . '/events/apply.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/public.php';
    }

    // ==================== 非会員（ゲスト）申込フォーム ====================

    /**
     * GET /apply/event/{token}/guest
     * 本人特定ステップ＋カスタム項目を含むゲスト用フォームを表示
     */
    public function guestForm(array $params): void
    {
        $token     = $params['token'];
        $tokenData = EventToken::findByToken($token);

        if (!$this->isTokenValid($tokenData)) {
            $this->showError('無効な申し込みURLです。URLが正しいか、有効期限内かをご確認ください。');
            return;
        }

        $event = (new Event())->find((int)$tokenData['event_id']);
        if (!$event || empty($event['allow_guest'])) {
            $this->showError('この企画では会員以外の方の申し込みは受け付けていません。');
            return;
        }

        $guestType = $event['guest_type'] ?? null;           // 'shinkan' / 'obog' / null
        $fields    = (new EventGuestField())->getByEventId((int)$event['id']);
        // 本人特定の固定項目（新歓=学科 / OBOG=代）の選択肢
        $matchOptions = $guestType === 'shinkan'
            ? Event::shinkanDepartments()
            : ($guestType === 'obog' ? Event::obogGenerations() : []);

        $pageTitle = htmlspecialchars($event['title'] ?? '企画') . ' - 申し込み（会員以外の方）';
        $appName   = '企画申し込み';
        ob_start();
        include VIEWS_PATH . '/events/apply_guest.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/public.php';
    }

    /**
     * POST /api/apply/event/{token}/guest/lookup
     * 氏名・カナ・代/学科で本人を照合し、この企画の申込があれば返す
     */
    public function guestLookup(array $params): void
    {
        $ctx = $this->guestContext($params);
        if (!$ctx) return;
        [$event, $guestType] = $ctx;

        $keys = $this->guestKeys();
        if (!$keys) return;

        $person = (new GuestPerson())->findByKeys($guestType, $keys['name'], $keys['name_kana'], $keys['match_key']);
        if (!$person) {
            Response::success(['matched' => false]);
            return;
        }

        // 人物は一致。ただし「申込済み」判定はこの企画に限る
        $app = (new EventGuestApplication())->findByEventAndPerson((int)$event['id'], (int)$person['id']);
        if (!$app || $app['status'] === 'cancelled') {
            Response::success(['matched' => false, 'known_person' => true]);
            return;
        }

        Response::success([
            'matched'     => true,
            'application' => [
                'id'      => (int)$app['id'],
                'status'  => $app['status'],
                'note'    => $app['note'],
                'values'  => $app['values'],
            ],
        ]);
    }

    /**
     * POST /api/apply/event/{token}/guest
     * 新規ゲスト申込（本人を find-or-create）
     */
    public function guestApply(array $params): void
    {
        $ctx = $this->guestContext($params, true);
        if (!$ctx) return;
        [$event, $guestType] = $ctx;

        $keys = $this->guestKeys();
        if (!$keys) return;

        $values = $this->collectGuestValues((int)$event['id']);
        if ($values === null) return;   // 必須エラーは collectGuestValues 側で応答済み

        $personModel = new GuestPerson();
        $personId    = $personModel->findOrCreate($guestType, $keys['name'], $keys['name_kana'], $keys['match_key']);

        $guestModel = new EventGuestApplication();
        $existing   = $guestModel->findByEventAndPerson((int)$event['id'], $personId);
        if ($existing && in_array($existing['status'], ['submitted', 'waitlisted'], true)) {
            Response::error('すでにこの企画に申し込み済みです', 400, 'ALREADY_APPLIED');
            return;
        }

        $note = trim(Request::json()['note'] ?? '') ?: null;

        // キャンセル後の再申込（同企画×同人物のレコードが残っている）は内容更新で復活
        if ($existing && $existing['status'] === 'cancelled') {
            $status = $guestModel->updateContent((int)$existing['id'], $values, $note, true);
            // 定員超過なら待ち扱いに切り替える必要があるが、復活は基本submitted。
            Response::success(['status' => $status], '申し込みが完了しました');
            return;
        }

        // 定員チェック（会員＋ゲスト合算）
        $capacity  = $event['capacity'] !== null ? (int)$event['capacity'] : null;
        $confirmed = (new EventApplication())->countByEventId((int)$event['id'])
                   + $guestModel->countByEventId((int)$event['id']);
        $isFull    = $capacity !== null && $confirmed >= $capacity;

        if ($isFull) {
            if (!empty($event['allow_waitlist'])) {
                $guestModel->create((int)$event['id'], $keys['name'], 'waitlisted', $values, $note, $personId, $keys['name_kana']);
                Response::success(['status' => 'waitlisted'], 'キャンセル待ちに登録しました');
            } else {
                Response::error('定員に達しているため申し込みできません', 400, 'CAPACITY_FULL');
            }
            return;
        }

        $guestModel->create((int)$event['id'], $keys['name'], 'submitted', $values, $note, $personId, $keys['name_kana']);
        Response::success(['status' => 'submitted'], '申し込みが完了しました');
    }

    /**
     * POST /api/apply/event/{token}/guest/update
     * 本人照合のうえ、この企画の申込内容を変更
     */
    public function guestUpdate(array $params): void
    {
        $ctx = $this->guestContext($params, true);
        if (!$ctx) return;
        [$event, $guestType] = $ctx;

        $keys = $this->guestKeys();
        if (!$keys) return;

        $app = $this->resolveOwnApplication($event, $guestType, $keys);
        if (!$app) return;   // エラー応答済み

        if (!in_array($app['status'], ['submitted', 'waitlisted'], true)) {
            Response::error('変更できる申し込みがありません', 400, 'NOT_APPLIED');
            return;
        }

        $values = $this->collectGuestValues((int)$event['id']);
        if ($values === null) return;

        $note = trim(Request::json()['note'] ?? '') ?: null;
        (new EventGuestApplication())->updateContent((int)$app['id'], $values, $note, false, true);
        Response::success(['status' => $app['status']], '申し込み内容を変更しました');
    }

    /**
     * POST /api/apply/event/{token}/guest/cancel
     * 本人照合のうえ、この企画の申込をキャンセル
     */
    public function guestCancel(array $params): void
    {
        $ctx = $this->guestContext($params);   // キャンセルは期限後も許可
        if (!$ctx) return;
        [$event, $guestType] = $ctx;

        $keys = $this->guestKeys();
        if (!$keys) return;

        $app = $this->resolveOwnApplication($event, $guestType, $keys);
        if (!$app) return;

        $guestModel = new EventGuestApplication();
        $prev = $guestModel->cancelById((int)$app['id']);
        if ($prev === 'submitted') {
            $guestModel->promoteFromWaitlist((int)$event['id']);
        }
        Response::success([], '申し込みをキャンセルしました');
    }

    // ── ゲスト処理の内部ヘルパー ──

    /**
     * トークン検証＋企画取得＋ゲスト許可チェック。
     * $checkDeadline=true なら期限切れも弾く。戻り値 [$event, $guestType] / 失敗時 null（応答済み）
     */
    private function guestContext(array $params, bool $checkDeadline = false): ?array
    {
        $tokenData = EventToken::findByToken($params['token']);
        if (!$this->isTokenValid($tokenData)) {
            Response::error('無効な申し込みURLです', 400, 'INVALID_TOKEN');
            return null;
        }
        $event = (new Event())->find((int)$tokenData['event_id']);
        if (!$event) {
            Response::error('企画が見つかりません', 404, 'NOT_FOUND');
            return null;
        }
        if (empty($event['allow_guest'])) {
            Response::error('この企画では会員以外の方の申し込みは受け付けていません', 400, 'GUEST_NOT_ALLOWED');
            return null;
        }
        if ($checkDeadline && $event['deadline'] !== null && $event['deadline'] < date('Y-m-d')) {
            Response::error('申し込み期限を過ぎています', 400, 'DEADLINE_PASSED');
            return null;
        }
        return [$event, $event['guest_type'] ?? null];
    }

    /**
     * 本人特定キー（氏名・カナ・代/学科）を取得・検証。失敗時 null（応答済み）
     */
    private function guestKeys(): ?array
    {
        $body     = Request::json();
        $name     = $this->normalizeName($body['name'] ?? '');
        $nameKana = $this->normalizeName($body['name_kana'] ?? '');
        $matchKey = trim($body['match_key'] ?? '');

        if ($name === '' || $nameKana === '' || $matchKey === '') {
            Response::error('氏名・カナ氏名・代/学科をすべて入力してください', 422, 'INVALID');
            return null;
        }
        return ['name' => $name, 'name_kana' => $nameKana, 'match_key' => $matchKey];
    }

    /**
     * 氏名の姓名間スペースを全角に矯正（半角/全角の連続スペースを全角1つにまとめ、前後を除去）。
     * クライアント側と同一ルール。人物同定の表記ゆれを防ぐ。
     */
    private function normalizeName(string $v): string
    {
        // 半角/全角スペース・タブ等の連続を全角スペース1つに
        $v = preg_replace('/[\s\x{3000}]+/u', '　', $v);
        // 先頭・末尾の全角スペースを除去
        return preg_replace('/^　+|　+$/u', '', $v);
    }

    /**
     * カスタム項目の回答を収集・必須チェック。失敗時 null（応答済み）
     */
    private function collectGuestValues(int $eventId): ?array
    {
        $fields      = (new EventGuestField())->getByEventId($eventId);
        $inputValues = is_array(Request::json()['values'] ?? null) ? Request::json()['values'] : [];
        $values      = [];
        foreach ($fields as $f) {
            $fid = (int)$f['id'];
            $val = $inputValues[$fid] ?? ($inputValues[(string)$fid] ?? '');
            if (is_array($val)) {
                $val = implode(', ', array_map('strval', $val));
            }
            $val = trim((string)$val);
            if (!empty($f['is_required']) && $val === '') {
                Response::error("「{$f['label']}」を入力してください", 422, 'REQUIRED');
                return null;
            }
            $values[$fid] = $val;
        }
        return $values;
    }

    /**
     * 照合キーから人物→この企画の自分の申込を解決。無ければエラー応答して null
     */
    private function resolveOwnApplication(array $event, ?string $guestType, array $keys): ?array
    {
        $person = (new GuestPerson())->findByKeys($guestType, $keys['name'], $keys['name_kana'], $keys['match_key']);
        if (!$person) {
            Response::error('申し込み情報が見つかりませんでした', 404, 'NOT_FOUND');
            return null;
        }
        $app = (new EventGuestApplication())->findByEventAndPerson((int)$event['id'], (int)$person['id']);
        if (!$app) {
            Response::error('この企画への申し込みが見つかりませんでした', 404, 'NOT_FOUND');
            return null;
        }
        return $app;
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

        // 会員向けカスタム質問項目
        $memberFields = (new EventGuestField())->getByEventId((int)$event['id'], 'member');

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

        // 会員向けカスタム項目の必須チェック＋回答収集
        $memberFields = (new EventGuestField())->getByEventId((int)$event['id'], 'member');
        $inputValues  = is_array($body['values'] ?? null) ? $body['values'] : [];
        $values       = [];
        foreach ($memberFields as $f) {
            $fid = (int)$f['id'];
            $val = $inputValues[$fid] ?? ($inputValues[(string)$fid] ?? '');
            if (is_array($val)) {
                $val = implode(', ', array_map('strval', $val));
            }
            $val = trim((string)$val);
            if (!empty($f['is_required']) && $val === '') {
                Response::error("「{$f['label']}」を入力してください", 422, 'REQUIRED');
                return;
            }
            $values[$fid] = $val;
        }

        $saveValues = function () use ($appModel, $event, $memberId, $values) {
            $app = $appModel->findByEventAndMember((int)$event['id'], $memberId);
            if ($app) {
                (new EventMemberFieldValue())->save((int)$app['id'], $values);
            }
        };

        if ($isFull) {
            if ($event['allow_waitlist']) {
                $appModel->apply((int)$event['id'], $memberId, 'waitlisted', $note ?: null);
                $saveValues();
                Response::success(['status' => 'waitlisted'], 'キャンセル待ちに登録しました');
            } else {
                Response::error('定員に達しているため申し込みできません', 400, 'CAPACITY_FULL');
            }
            return;
        }

        $appModel->apply((int)$event['id'], $memberId, 'submitted', $note ?: null);
        $saveValues();
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
        $isGuest     = !empty($_GET['guest']);   // 非会員からの完了表示

        if (!$isGuest && $this->checkAuth() && $event) {
            $memberId    = (int)$_SESSION['member_id'];
            $memberModel = new Member();
            $member      = $memberModel->find($memberId);
            $appModel    = new EventApplication();
            $application = $appModel->findByEventAndMember((int)$event['id'], $memberId);
        }

        // 非会員はキャンセル待ちかどうかを query で受け取る
        if ($isGuest && !empty($_GET['status'])) {
            $application = ['status' => $_GET['status'] === 'waitlisted' ? 'waitlisted' : 'submitted'];
        }

        // 完了の種別: applied(新規) / updated(変更) / cancelled(取消) / waitlisted
        $guestAction = $isGuest ? ($_GET['action'] ?? 'submitted') : null;

        $pageTitle = '申し込み完了';
        $appName   = '企画申し込み';
        $token     = $params['token'];
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
