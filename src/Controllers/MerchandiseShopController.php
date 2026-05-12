<?php
/**
 * 物販ショップコントローラー（会員ポータル＋公開URL）
 */
class MerchandiseShopController
{
    private const MEMBER_SESSION_KEY = 'member_authenticated';

    /**
     * 会員ショップページ
     * GET /member/store
     */
    public function memberShop(array $params): void
    {
        if (!$this->checkMemberAuth()) {
            Response::redirect('/member/login?return=' . urlencode('/member/store'));
            return;
        }

        $items = Merchandise::findAvailable();

        $memberId = (int)($_SESSION['member_id'] ?? 0);
        $myOrders = Database::getInstance()->fetchAll(
            "SELECT o.* FROM merchandise_orders o
             WHERE o.member_id = ?
             ORDER BY o.created_at DESC
             LIMIT 20",
            [$memberId]
        );
        foreach ($myOrders as &$o) {
            $o['items'] = MerchandiseOrder::getItems((int)$o['id']);
        }

        $this->render('shop/member', [
            'memberName' => $_SESSION['member_name'] ?? '',
            'items'      => $items,
            'myOrders'   => $myOrders,
            'mode'       => 'member',
        ]);
    }

    /**
     * 暫定購入ショップページ（DB未登録の入会予定者向け）
     * GET /store/pending
     */
    public function pendingShop(array $params): void
    {
        // ログイン済みなら通常会員ショップへ
        if ($this->checkMemberAuth()) {
            Response::redirect('/member/store');
            return;
        }

        $items = Merchandise::findAvailable();

        $this->render('shop/pending', [
            'memberName' => '',
            'items'      => $items,
            'token'      => null,
            'mode'       => 'pending',
        ]);
    }

    /**
     * 暫定購入の注文確定 API
     * POST /api/store/pending/checkout
     */
    public function pendingCheckout(array $params): void
    {
        $body = Request::json();
        $cart = is_array($body['cart'] ?? null) ? $body['cart'] : [];
        if (empty($cart)) {
            Response::error('カートが空です', 400, 'EMPTY_CART');
            return;
        }

        $studentId = trim($body['student_id'] ?? '');
        $name      = trim($body['name']       ?? '');
        $lineName  = trim($body['line_name']  ?? '');
        $phone     = trim($body['phone']      ?? '');

        // 正規化（学籍番号: 全角→半角、小文字→大文字）
        $studentId = strtoupper(mb_convert_kana($studentId, 'a'));

        if ($studentId === '') {
            Response::error('学籍番号を入力してください', 400, 'VALIDATION_ERROR');
            return;
        }
        if ($name === '') {
            Response::error('氏名を入力してください', 400, 'VALIDATION_ERROR');
            return;
        }
        if ($lineName === '' && $phone === '') {
            Response::error('LINE名または電話番号のいずれかを入力してください', 400, 'VALIDATION_ERROR');
            return;
        }

        // 既存会員の学籍番号と一致したら member_id に紐付け、それ以外は pending として保存
        $member  = (new Member())->findByStudentId($studentId);
        $isMember = $member && in_array($member['status'], [Member::STATUS_ACTIVE, Member::STATUS_OB_OG]);

        $buyer = [
            'member_id'          => $isMember ? (int)$member['id'] : null,
            'pending_student_id' => $isMember ? null : $studentId,
            'name'               => $name,
            'kana'               => null,
            'pending_line_name'  => $lineName ?: null,
            'pending_phone'      => $phone    ?: null,
            'contact'            => $lineName ?: $phone,
            'notes'              => $body['notes'] ?? null,
        ];

        try {
            $order = MerchandiseOrder::createOrUpdate($cart, $buyer);
            Response::success(array_merge($order, ['matched' => $isMember]));
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400, 'CHECKOUT_ERROR');
        }
    }

    /**
     * 公開ショップページ（トークン経由・会員ログイン必須）
     * GET /store/{token}
     */
    public function publicShop(array $params): void
    {
        // 未ログインなら会員ログインへリダイレクト（戻り先付き）
        if (!$this->checkMemberAuth()) {
            $returnTo = '/store/' . urlencode($params['token']);
            Response::redirect('/member/login?return=' . urlencode($returnTo));
            return;
        }

        $token = MerchandiseToken::findByToken($params['token']);
        if (!$token) {
            http_response_code(404);
            $this->renderError('URLが無効です');
            return;
        }
        if (!empty($token['expires_at']) && strtotime($token['expires_at']) < time()) {
            $this->renderError('このURLは有効期限を過ぎています');
            return;
        }

        $items    = Merchandise::findAvailable();
        $memberId = (int)($_SESSION['member_id'] ?? 0);
        $myOrders = Database::getInstance()->fetchAll(
            "SELECT * FROM merchandise_orders
             WHERE member_id = ?
             ORDER BY created_at DESC
             LIMIT 20",
            [$memberId]
        );
        foreach ($myOrders as &$o) {
            $o['items'] = MerchandiseOrder::getItems((int)$o['id']);
        }

        $this->render('shop/public', [
            'memberName' => $_SESSION['member_name'] ?? '',
            'items'      => $items,
            'myOrders'   => $myOrders,
            'token'      => $token['token'],
            'mode'       => 'member',
        ]);
    }

    /**
     * 注文確定 API（会員ポータル）
     * POST /api/member/store/checkout
     */
    public function memberCheckout(array $params): void
    {
        if (!$this->checkMemberAuth()) {
            Response::error('ログインが必要です', 401, 'UNAUTHORIZED');
            return;
        }

        $body = Request::json();
        $cart = is_array($body['cart'] ?? null) ? $body['cart'] : [];
        if (empty($cart)) {
            Response::error('カートが空です', 400, 'EMPTY_CART');
            return;
        }

        $memberId = (int)($_SESSION['member_id'] ?? 0);
        $member   = Database::getInstance()->fetch(
            "SELECT id, name_kanji, name_kana, line_name, phone FROM members WHERE id = ?",
            [$memberId]
        );

        $buyer = [
            'member_id' => $memberId,
            'name'      => $member['name_kanji'] ?? ($body['buyer_name'] ?? ''),
            'kana'      => $member['name_kana']  ?? null,
            'contact'   => $body['contact']      ?? ($member['line_name'] ?? $member['phone'] ?? null),
            'notes'     => $body['notes']        ?? null,
        ];

        try {
            $order = MerchandiseOrder::createOrUpdate($cart, $buyer);
            Response::success($order);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400, 'CHECKOUT_ERROR');
        }
    }

    /**
     * 注文確定 API（公開URL・会員ログイン必須）
     * POST /api/store/{token}/checkout
     */
    public function publicCheckout(array $params): void
    {
        if (!$this->checkMemberAuth()) {
            Response::error('ログインが必要です', 401, 'UNAUTHORIZED');
            return;
        }

        $token = MerchandiseToken::findByToken($params['token']);
        if (!$token) {
            Response::error('URLが無効です', 404, 'INVALID_TOKEN');
            return;
        }
        if (!empty($token['expires_at']) && strtotime($token['expires_at']) < time()) {
            Response::error('このURLは有効期限を過ぎています', 400, 'TOKEN_EXPIRED');
            return;
        }

        $body = Request::json();
        $cart = is_array($body['cart'] ?? null) ? $body['cart'] : [];
        if (empty($cart)) {
            Response::error('カートが空です', 400, 'EMPTY_CART');
            return;
        }

        $memberId = (int)($_SESSION['member_id'] ?? 0);
        $member   = Database::getInstance()->fetch(
            "SELECT id, name_kanji, name_kana, line_name, phone FROM members WHERE id = ?",
            [$memberId]
        );
        if (!$member) {
            Response::error('会員情報が見つかりません', 404, 'MEMBER_NOT_FOUND');
            return;
        }

        $buyer = [
            'member_id' => $memberId,
            'name'      => $member['name_kanji'] ?? '',
            'kana'      => $member['name_kana']  ?? null,
            'contact'   => $member['line_name'] ?? $member['phone'] ?? null,
            'notes'     => $body['notes'] ?? null,
        ];

        try {
            $order = MerchandiseOrder::createOrUpdate($cart, $buyer);
            Response::success($order);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400, 'CHECKOUT_ERROR');
        }
    }

    /**
     * 会員による振込完了報告 API
     * POST /api/member/store/orders/{id}/submit-payment
     */
    public function submitPayment(array $params): void
    {
        if (!$this->checkMemberAuth()) {
            Response::error('ログインが必要です', 401, 'UNAUTHORIZED');
            return;
        }

        $memberId = (int)($_SESSION['member_id'] ?? 0);
        $orderId  = (int)($params['id'] ?? 0);
        if ($memberId <= 0 || $orderId <= 0) {
            Response::error('不正なリクエストです', 400, 'INVALID_REQUEST');
            return;
        }

        $ok = MerchandiseOrder::submitPayment($orderId, $memberId);
        if (!$ok) {
            Response::error('この注文は報告できません（支払済み・他会員の注文・既に報告済みのいずれか）', 400, 'SUBMIT_NOT_ALLOWED');
            return;
        }
        Response::success(MerchandiseOrder::findById($orderId));
    }

    private function checkMemberAuth(): bool
    {
        return !empty($_SESSION[self::MEMBER_SESSION_KEY]);
    }

    private function render(string $view, array $data = []): void
    {
        extract($data);
        $config  = require CONFIG_PATH . '/app.php';
        $appName = $config['name'];

        ob_start();
        include VIEWS_PATH . '/' . $view . '.php';
        $content = ob_get_clean();

        // 公開・暫定モードは public layout、会員モードは member layout
        if (in_array(($mode ?? ''), ['public', 'pending'])) {
            include VIEWS_PATH . '/layouts/public.php';
        } else {
            include VIEWS_PATH . '/layouts/member.php';
        }
    }

    private function renderError(string $message): void
    {
        $content = '<div class="alert alert-danger m-4">' . htmlspecialchars($message) . '</div>';
        include VIEWS_PATH . '/layouts/public.php';
    }
}
