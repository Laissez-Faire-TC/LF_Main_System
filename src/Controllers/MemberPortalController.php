<?php
/**
 * 会員ポータルコントローラー（一般会員向け）
 */
class MemberPortalController
{
    private const SESSION_KEY = 'member_authenticated';

    /**
     * 会員ログインページ
     */
    public function loginPage(array $params): void
    {
        $returnTo = $this->safeReturnTo($_GET['return'] ?? null);

        if ($this->checkAuth()) {
            Response::redirect($returnTo ?: '/member/home');
            return;
        }

        // OAuthコールバックからのエラー/通知メッセージ（あれば表示）
        $oauthError = $_SESSION['oauth_flash_error'] ?? null;
        unset($_SESSION['oauth_flash_error']);

        $oauthService = new OAuthService();
        $this->render('member/login', [
            'returnTo'      => $returnTo,
            'googleEnabled' => $oauthService->isEnabled('google'),
            'lineEnabled'   => $oauthService->isEnabled('line'),
            'oauthError'    => $oauthError,
        ]);
    }

    /**
     * 会員ログイン処理（API）
     */
    public function login(array $params): void
    {
        $studentId = trim(Request::get('student_id') ?? '');

        // 正規化：全角→半角、小文字→大文字
        $studentId = mb_convert_kana($studentId, 'a');
        $studentId = strtoupper($studentId);

        if (empty($studentId)) {
            Response::error('学籍番号を入力してください', 400, 'VALIDATION_ERROR');
            return;
        }

        $memberModel = new Member();
        $member = $memberModel->findByStudentId($studentId);

        if ($member && in_array($member['status'], [Member::STATUS_ACTIVE, Member::STATUS_OB_OG])) {
            // OAuth連携済みの会員は学籍番号ログインを禁止（OAuth経由のみ許可）
            $oauthModel = new MemberOauthIdentity();
            if ($oauthModel->countByMember((int)$member['id']) > 0) {
                Response::error(
                    'この会員は外部アカウント（Google／LINE）連携が設定されています。学籍番号ではログインできません。下のボタンからログインしてください。',
                    403,
                    'OAUTH_REQUIRED'
                );
                return;
            }

            $this->establishSession($member);

            $returnTo = $this->safeReturnTo(Request::get('return'));
            Response::success(['redirect' => $returnTo ?: '/member/home'], 'ログインしました');
        } else {
            $debugMsg = '会員が見つかりません';
            if ($member) {
                $debugMsg = '会員は存在しますが、ステータスが対象外です: ' . $member['status'];
            }
            Response::error($debugMsg, 401, 'INVALID_STUDENT_ID');
        }
    }

    /**
     * オープンリダイレクト対策：自サイト内のパスのみ許可
     */
    private function safeReturnTo(?string $url): ?string
    {
        if (empty($url)) return null;
        $url = (string)$url;
        // パスのみ許可（先頭が "/" かつ "//" や "/\\" で始まらない）
        if (!preg_match('#^/(?!/|\\\\)[^\s]*$#', $url)) return null;
        return $url;
    }

    /**
     * ログインセッションを確立（学籍番号ログイン／OAuthログイン共通）
     */
    private function establishSession(array $member): void
    {
        $_SESSION[self::SESSION_KEY] = true;
        $_SESSION['member_id']   = $member['id'];
        $_SESSION['member_name'] = $member['name_kanji'];
        $_SESSION['member_login_time'] = time();
    }

    // ==================== OAuth2.0 連携・ログイン ====================

    /**
     * OAuth開始（ログイン用・連携用 兼用）
     * GET /member/oauth/{provider}/start
     *   - 未ログイン     → ログイン目的。コールバックで会員を引き当ててログイン
     *   - ログイン中     → 連携目的。コールバックで現在の会員に紐付け
     */
    public function oauthStart(array $params): void
    {
        $provider     = $this->normalizeProvider($params['provider'] ?? '');
        $oauthService = new OAuthService();

        if (!$provider || !$oauthService->isEnabled($provider)) {
            $_SESSION['oauth_flash_error'] = 'この連携方法は現在利用できません。';
            Response::redirect('/member/login');
            return;
        }

        // CSRF対策の state を生成しセッションに保存
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state']    = $state;
        $_SESSION['oauth_provider'] = $provider;
        // ログイン目的か連携目的かを記録
        $_SESSION['oauth_mode']     = $this->checkAuth() ? 'link' : 'login';
        // ログイン後の戻り先
        $_SESSION['oauth_return']   = $this->safeReturnTo($_GET['return'] ?? null);

        Response::redirect($oauthService->getAuthUrl($provider, $state));
    }

    /**
     * OAuthコールバック
     * GET /member/oauth/{provider}/callback
     */
    public function oauthCallback(array $params): void
    {
        $provider     = $this->normalizeProvider($params['provider'] ?? '');
        $oauthService = new OAuthService();

        $mode     = $_SESSION['oauth_mode'] ?? 'login';
        $returnTo = $this->safeReturnTo($_SESSION['oauth_return'] ?? null);

        // state / provider 照合（CSRF対策）
        $expectedState    = $_SESSION['oauth_state'] ?? null;
        $expectedProvider = $_SESSION['oauth_provider'] ?? null;
        $gotState         = $_GET['state'] ?? null;
        $code             = $_GET['code'] ?? null;

        // 使い捨て：照合後はクリア
        unset($_SESSION['oauth_state'], $_SESSION['oauth_provider'], $_SESSION['oauth_mode'], $_SESSION['oauth_return']);

        $failRedirect = ($mode === 'link') ? '/member/profile' : '/member/login';

        if (!$provider || !$oauthService->isEnabled($provider)
            || !$expectedState || !$gotState || !hash_equals($expectedState, (string)$gotState)
            || $expectedProvider !== $provider || empty($code)) {
            $_SESSION['oauth_flash_error'] = '認証に失敗しました。お手数ですがもう一度お試しください。';
            Response::redirect($failRedirect);
            return;
        }

        try {
            $info = $oauthService->handleCallback($provider, (string)$code);
        } catch (Exception $e) {
            $_SESSION['oauth_flash_error'] = '認証に失敗しました：' . $e->getMessage();
            Response::redirect($failRedirect);
            return;
        }

        $oauthModel = new MemberOauthIdentity();

        if ($mode === 'link') {
            $this->oauthLink($provider, $info, $oauthModel, $returnTo);
        } else {
            $this->oauthLogin($provider, $info, $oauthModel, $returnTo);
        }
    }

    /**
     * OAuthログイン処理（連携済みの会員を引き当ててログイン）
     */
    private function oauthLogin(string $provider, array $info, MemberOauthIdentity $oauthModel, ?string $returnTo): void
    {
        $identity = $oauthModel->findByProviderUser($provider, $info['provider_user_id']);

        if (!$identity) {
            $_SESSION['oauth_flash_error'] =
                'この外部アカウントは会員と連携されていません。学籍番号でログイン後、登録情報の変更画面から連携してください。';
            Response::redirect('/member/login');
            return;
        }

        $memberModel = new Member();
        $member      = $memberModel->find((int)$identity['member_id']);

        if (!$member || !in_array($member['status'], [Member::STATUS_ACTIVE, Member::STATUS_OB_OG])) {
            $_SESSION['oauth_flash_error'] = 'この会員アカウントは現在ログインできません。幹部にお問い合わせください。';
            Response::redirect('/member/login');
            return;
        }

        $this->establishSession($member);
        Response::redirect($returnTo ?: '/member/home');
    }

    /**
     * OAuth連携処理（ログイン中の会員に外部アカウントを紐付け）
     */
    private function oauthLink(string $provider, array $info, MemberOauthIdentity $oauthModel, ?string $returnTo): void
    {
        $memberId = (int)($_SESSION['member_id'] ?? 0);
        if ($memberId <= 0) {
            $_SESSION['oauth_flash_error'] = 'セッションが切れました。再度ログインしてください。';
            Response::redirect('/member/login');
            return;
        }

        // この外部アカウントが既に他の会員に紐付いていないか
        $existing = $oauthModel->findByProviderUser($provider, $info['provider_user_id']);
        if ($existing) {
            if ((int)$existing['member_id'] === $memberId) {
                Response::redirect('/member/profile?linked=' . $provider);
            } else {
                $_SESSION['oauth_flash_error'] = 'この外部アカウントは既に別の会員に連携されています。';
                Response::redirect('/member/profile');
            }
            return;
        }

        // 同一会員が同プロバイダを二重連携しないように
        if ($oauthModel->findByMemberAndProvider($memberId, $provider)) {
            Response::redirect('/member/profile?linked=' . $provider);
            return;
        }

        $oauthModel->create($memberId, $provider, $info['provider_user_id'], $info['email'] ?? null, $info['display_name'] ?? null);
        Response::redirect('/member/profile?linked=' . $provider);
    }

    /**
     * OAuth連携の解除（会員本人）
     * POST /api/member/oauth/{provider}/unlink
     */
    public function oauthUnlink(array $params): void
    {
        if (!$this->checkAuth()) {
            Response::error('ログインが必要です', 401, 'UNAUTHORIZED');
            return;
        }

        $provider = $this->normalizeProvider($params['provider'] ?? '');
        if (!$provider) {
            Response::error('不正なプロバイダです', 400, 'VALIDATION_ERROR');
            return;
        }

        $memberId   = (int)($_SESSION['member_id'] ?? 0);
        $oauthModel = new MemberOauthIdentity();

        if (!$oauthModel->findByMemberAndProvider($memberId, $provider)) {
            Response::error('この連携は設定されていません', 404, 'NOT_FOUND');
            return;
        }

        $oauthModel->deleteByMemberAndProvider($memberId, $provider);
        Response::success([], '連携を解除しました');
    }

    /**
     * プロバイダ名の正規化（許可リスト方式）
     */
    private function normalizeProvider(string $provider): ?string
    {
        $provider = strtolower(trim($provider));
        return in_array($provider, [MemberOauthIdentity::PROVIDER_GOOGLE, MemberOauthIdentity::PROVIDER_LINE], true)
            ? $provider : null;
    }

    /**
     * 会員ホームページ
     */
    public function home(array $params): void
    {
        if (!$this->checkAuth()) {
            Response::redirect('/member/login');
            return;
        }

        $campTokenModel = new CampToken();
        $activeCamps = $campTokenModel->getActiveCampsWithTokens();

        $memberId     = (int)($_SESSION['member_id'] ?? 0);
        $eventModel   = new Event();
        $activeEvents = $eventModel->getActiveWithMemberStatus($memberId);

        $collItemModel      = new CampCollectionItem();
        $pendingCollections = $collItemModel->getPendingByMemberId($memberId);

        // 遠征振込確認フォーム（未提出）
        $pendingExpeditionCollections = ExpeditionCollectionItem::getPendingByMemberId($memberId);

        $feeItemModel      = new MembershipFeeItem();
        $pendingFees       = $feeItemModel->getPendingByMemberId($memberId);

        // 継続入会受付状態チェック
        $ayModel       = new AcademicYear();
        $renewOpenYear = $ayModel->getRenewOpenYear();
        $renewOpen     = false;
        $alreadyRenewed = false;
        if ($renewOpenYear && (empty($renewOpenYear['renew_deadline']) || $renewOpenYear['renew_deadline'] >= date('Y-m-d'))) {
            $renewOpen = true;
            // ログイン中の会員が新年度に既に登録済みか確認
            $memberModel = new Member();
            $currentMember = $memberModel->find($memberId);
            if ($currentMember) {
                $existing = $memberModel->findByStudentIdAndYear($currentMember['student_id'], (int)$renewOpenYear['year']);
                $alreadyRenewed = ($existing !== null);
            }
        }

        // しおりが公開されている合宿のうち、ログイン会員が申し込み済みのものだけ表示
        $bookletModel   = new CampBooklet();
        $allBookletCamps = $bookletModel->getPublicCamps();
        $appModel       = new CampApplication();
        $bookletCamps   = array_values(array_filter($allBookletCamps, function($bc) use ($memberId, $appModel) {
            return $appModel->findByCampAndMember((int)$bc['id'], $memberId) !== null;
        }));

        // 受付中の遠征（有効なトークンがある遠征）
        $activeExpeditions = [];
        foreach (ExpeditionToken::getActiveExpeditionsWithTokens() as $et) {
            $participants   = ExpeditionParticipant::findByExpedition((int)$et['id']);
            $alreadyApplied = false;
            foreach ($participants as $p) {
                if ((int)$p['member_id'] === $memberId) {
                    $alreadyApplied = true;
                    break;
                }
            }
            $activeExpeditions[] = array_merge($et, ['already_applied' => $alreadyApplied]);
        }

        // 費用申請受付中の遠征（参加確定 & 申請期限内）
        $expenseExpeditions = Database::getInstance()->fetchAll(
            "SELECT e.id, e.name, e.start_date, e.end_date, e.expense_deadline
             FROM expeditions e
             JOIN expedition_participants ep ON ep.expedition_id = e.id
             WHERE ep.member_id = ?
               AND ep.status = 'confirmed'
               AND e.expense_deadline IS NOT NULL
               AND e.expense_deadline >= CURDATE()
             ORDER BY e.expense_deadline ASC",
            [$memberId]
        );
        foreach ($expenseExpeditions as &$ex) {
            $ex['my_expense'] = ExpeditionCarExpense::findByMemberAndExpedition($memberId, (int)$ex['id']);
        }
        unset($ex);

        // 公開済み遠征しおり（自分が参加者の遠征のみ）
        $expeditionBooklets = Database::getInstance()->fetchAll(
            "SELECT e.id, e.name, e.start_date, e.end_date, eb.public_token
             FROM expeditions e
             JOIN expedition_booklets eb ON eb.expedition_id = e.id
             JOIN expedition_participants ep ON ep.expedition_id = e.id
             WHERE eb.published = 1
               AND ep.member_id = ?
               AND ep.status = 'confirmed'
             ORDER BY e.start_date DESC",
            [$memberId]
        );

        $this->render('member/home', [
            'activeCamps'         => $activeCamps,
            'activeEvents'        => $activeEvents,
            'memberName'          => $_SESSION['member_name'] ?? '',
            'memberId'            => $memberId,
            'pendingCollections'  => $pendingCollections,
            'pendingFees'         => $pendingFees,
            'renewOpen'           => $renewOpen,
            'alreadyRenewed'      => $alreadyRenewed,
            'renewYear'           => $renewOpenYear['year'] ?? null,
            'bookletCamps'        => $bookletCamps,
            'activeExpeditions'            => $activeExpeditions,
            'expenseExpeditions'           => $expenseExpeditions,
            'expeditionBooklets'           => $expeditionBooklets,
            'pendingExpeditionCollections' => $pendingExpeditionCollections,
        ]);
    }

    /**
     * 企画申し込み（API）
     */
    public function applyEvent(array $params): void
    {
        if (!$this->checkAuth()) {
            Response::error('ログインが必要です', 401, 'UNAUTHORIZED');
            return;
        }

        $eventId  = (int)$params['id'];
        $memberId = (int)($_SESSION['member_id'] ?? 0);

        $eventModel = new Event();
        $event      = $eventModel->find($eventId);

        if (!$event || !$event['is_active']) {
            Response::error('企画が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        // 締め切りチェック
        if ($event['deadline'] !== null && $event['deadline'] < date('Y-m-d')) {
            Response::error('申込期限を過ぎています', 400, 'DEADLINE_PASSED');
            return;
        }

        $appModel = new EventApplication();
        $existing = $appModel->findByEventAndMember($eventId, $memberId);

        // すでに参加確定 or キャンセル待ちなら何もしない
        if ($existing && in_array($existing['status'], ['submitted', 'waitlisted'])) {
            Response::error('すでに申し込み済みです', 400, 'ALREADY_APPLIED');
            return;
        }

        // 定員チェック
        $isFull = $event['capacity'] !== null
               && (int)$event['application_count'] >= (int)$event['capacity'];

        if ($isFull) {
            if ($event['allow_waitlist']) {
                $note = Request::get('note') ?? null;
        $appModel->apply($eventId, $memberId, 'waitlisted', $note);
                Response::success(['status' => 'waitlisted'], 'キャンセル待ちに登録しました');
            } else {
                Response::error('定員に達しているため申し込みできません', 400, 'CAPACITY_FULL');
            }
            return;
        }

        $note = Request::get('note') ?? null;
        $appModel->apply($eventId, $memberId, 'submitted', $note);
        Response::success(['status' => 'submitted'], '申し込みが完了しました');
    }

    /**
     * 企画申し込みキャンセル（API）
     */
    public function cancelEvent(array $params): void
    {
        if (!$this->checkAuth()) {
            Response::error('ログインが必要です', 401, 'UNAUTHORIZED');
            return;
        }

        $eventId  = (int)$params['id'];
        $memberId = (int)($_SESSION['member_id'] ?? 0);

        $appModel   = new EventApplication();
        $prevStatus = $appModel->cancel($eventId, $memberId);

        // 参加確定者がキャンセルした場合のみ繰り上げ
        if ($prevStatus === 'submitted') {
            $appModel->promoteFromWaitlist($eventId);
        }

        Response::success([], 'キャンセルしました');
    }

    /**
     * 未提出の集金一覧を返す API
     * GET /api/member/collections
     */
    public function myCollections(array $params): void
    {
        if (!$this->checkAuth()) {
            Response::error('ログインが必要です', 401, 'UNAUTHORIZED');
            return;
        }

        $memberId  = (int)($_SESSION['member_id'] ?? 0);
        $itemModel = new CampCollectionItem();
        Response::success($itemModel->getPendingByMemberId($memberId));
    }

    /**
     * 集金フォームページ
     * GET /member/collection/{id}  (id = collection_id)
     */
    public function collectionForm(array $params): void
    {
        if (!$this->checkAuth()) {
            Response::redirect('/member/login');
            return;
        }

        $collectionId    = (int)$params['id'];
        $memberId        = (int)($_SESSION['member_id'] ?? 0);
        $itemModel       = new CampCollectionItem();
        $collectionModel = new CampCollection();

        $item = $itemModel->findByMemberAndCollection($memberId, $collectionId);
        if (!$item) {
            http_response_code(404);
            $this->render('error', ['message' => '集金情報が見つかりません']);
            return;
        }

        $collection = $collectionModel->findById($collectionId);
        if (!$collection) {
            http_response_code(404);
            $this->render('error', ['message' => '集金情報が見つかりません']);
            return;
        }

        $campModel = new Camp();
        $camp      = $campModel->find((int)$collection['camp_id']);

        $this->render('member/collection', [
            'item'       => $item,
            'collection' => $collection,
            'camp'       => $camp,
            'memberName' => $_SESSION['member_name'] ?? '',
            'memberId'   => $memberId,
        ]);
    }

    /**
     * 集金提出 API
     * POST /api/member/collection-items/{id}/submit
     */
    public function submitCollection(array $params): void
    {
        if (!$this->checkAuth()) {
            Response::error('ログインが必要です', 401, 'UNAUTHORIZED');
            return;
        }

        $itemId    = (int)$params['id'];
        $memberId  = (int)($_SESSION['member_id'] ?? 0);
        $itemModel = new CampCollectionItem();

        $item = $itemModel->find($itemId);
        if (!$item || (int)$item['member_id'] !== $memberId) {
            Response::error('Not found', 404, 'NOT_FOUND');
            return;
        }

        if ((int)$item['submitted'] === 1) {
            Response::error('すでに提出済みです', 400, 'ALREADY_SUBMITTED');
            return;
        }

        $transferred = Request::get('transferred');
        if (!$transferred || $transferred === '0' || $transferred === 'false') {
            Response::error('振り込み完了のチェックが必要です', 400, 'VALIDATION_ERROR');
            return;
        }

        // 期限超過チェック：期限が過ぎている場合は late_reason が必須
        $collectionModel = new CampCollection();
        $collection      = $collectionModel->findById((int)$item['collection_id']);
        $lateReason      = Request::get('late_reason') ?? null;

        if ($collection && $collection['deadline'] < date('Y-m-d')) {
            if (empty($lateReason)) {
                Response::error('入金遅れの理由を入力してください', 400, 'VALIDATION_ERROR');
                return;
            }
        }

        $itemModel->submit($itemId, $lateReason ?: null);
        Response::success([], '提出しました');
    }

    /**
     * 未提出の入会金一覧を返す API
     * GET /api/member/membership-fees
     */
    public function myFees(array $params): void
    {
        if (!$this->checkAuth()) {
            Response::error('ログインが必要です', 401, 'UNAUTHORIZED');
            return;
        }

        $memberId  = (int)($_SESSION['member_id'] ?? 0);
        $feeItemModel = new MembershipFeeItem();
        Response::success($feeItemModel->getPendingByMemberId($memberId));
    }

    /**
     * 入会金支払い確認フォームページ
     * GET /member/membership-fee/{id}  (id = membership_fee_id)
     */
    public function membershipFeeForm(array $params): void
    {
        if (!$this->checkAuth()) {
            Response::redirect('/member/login');
            return;
        }

        $feeId    = (int)$params['id'];
        $memberId = (int)($_SESSION['member_id'] ?? 0);

        $feeItemModel = new MembershipFeeItem();
        $feeModel     = new MembershipFee();

        $item = $feeItemModel->findByMemberAndFee($memberId, $feeId);
        if (!$item) {
            http_response_code(404);
            $this->render('error', ['message' => '入会金情報が見つかりません']);
            return;
        }

        $fee = $feeModel->findById($feeId);
        if (!$fee) {
            http_response_code(404);
            $this->render('error', ['message' => '入会金情報が見つかりません']);
            return;
        }

        $grades = $feeModel->getGrades($feeId);

        // 会員の学年に対応する金額を取得
        $memberModel = new Member();
        $member      = $memberModel->find($memberId);
        $memberGrade = $member['grade'] ?? '';
        $gradeAmount = $grades[$memberGrade] ?? null;

        $effectiveAmount = $item['custom_amount'] ?? $gradeAmount;

        $this->render('member/membership_fee', [
            'item'            => $item,
            'fee'             => $fee,
            'grades'          => $grades,
            'memberGrade'     => $memberGrade,
            'effectiveAmount' => $effectiveAmount,
            'memberName'      => $_SESSION['member_name'] ?? '',
            'memberId'        => $memberId,
        ]);
    }

    /**
     * 入会金支払い提出 API
     * POST /api/member/membership-fee-items/{id}/submit
     */
    public function submitFee(array $params): void
    {
        if (!$this->checkAuth()) {
            Response::error('ログインが必要です', 401, 'UNAUTHORIZED');
            return;
        }

        $itemId       = (int)$params['id'];
        $memberId     = (int)($_SESSION['member_id'] ?? 0);
        $feeItemModel = new MembershipFeeItem();

        $item = $feeItemModel->find($itemId);
        if (!$item || (int)$item['member_id'] !== $memberId) {
            Response::error('Not found', 404, 'NOT_FOUND');
            return;
        }

        if ((int)$item['submitted'] === 1) {
            Response::error('すでに提出済みです', 400, 'ALREADY_SUBMITTED');
            return;
        }

        $transferred = Request::get('transferred');
        if (!$transferred || $transferred === '0' || $transferred === 'false') {
            Response::error('振り込み完了のチェックが必要です', 400, 'VALIDATION_ERROR');
            return;
        }

        // 期限超過チェック
        $feeModel  = new MembershipFee();
        $fee       = $feeModel->findById((int)$item['membership_fee_id']);
        $lateReason = Request::get('late_reason') ?? null;

        if ($fee && $fee['deadline'] < date('Y-m-d')) {
            if (empty($lateReason)) {
                Response::error('入金遅れの理由を入力してください', 400, 'VALIDATION_ERROR');
                return;
            }
        }

        $feeItemModel->submit($itemId, $lateReason ?: null);
        Response::success([], '提出しました');
    }

    /**
     * プロフィール編集ページ
     * GET /member/profile
     */
    public function profilePage(array $params): void
    {
        if (!$this->checkAuth()) {
            Response::redirect('/member/login');
            return;
        }

        $memberId    = (int)($_SESSION['member_id'] ?? 0);
        $memberModel = new Member();
        $member      = $memberModel->find($memberId);

        if (!$member) {
            http_response_code(404);
            $this->render('error', ['message' => '会員情報が見つかりません']);
            return;
        }

        $oauthModel    = new MemberOauthIdentity();
        $oauthService  = new OAuthService();
        $linkedRows    = $oauthModel->findByMember($memberId);
        $linkedMap     = [];
        foreach ($linkedRows as $row) {
            $linkedMap[$row['provider']] = $row;
        }

        $this->render('member/profile', [
            'member'        => $member,
            'memberName'    => $_SESSION['member_name'] ?? '',
            'memberId'      => $memberId,
            'success'       => $_GET['success'] ?? null,
            'linkedMap'     => $linkedMap,
            'googleEnabled' => $oauthService->isEnabled('google'),
            'lineEnabled'   => $oauthService->isEnabled('line'),
            'justLinked'    => $_GET['linked'] ?? null,
            'oauthError'    => (function() {
                $e = $_SESSION['oauth_flash_error'] ?? null;
                unset($_SESSION['oauth_flash_error']);
                return $e;
            })(),
        ]);
    }

    /**
     * プロフィール更新 API
     * PUT /api/member/profile
     */
    public function updateProfile(array $params): void
    {
        if (!$this->checkAuth()) {
            Response::error('ログインが必要です', 401, 'UNAUTHORIZED');
            return;
        }

        $memberId    = (int)($_SESSION['member_id'] ?? 0);
        $memberModel = new Member();
        $member      = $memberModel->find($memberId);

        if (!$member) {
            Response::error('会員情報が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $editableFields = ['phone', 'address', 'emergency_contact', 'email', 'allergy', 'line_name', 'sns_allowed'];

        // 入力された項目のみ更新対象とする（空欄項目は既存値を維持）
        $newData = [];
        foreach ($editableFields as $field) {
            $val = Request::get($field);
            if ($val === null) {
                continue;
            }
            if ($field === 'sns_allowed') {
                if ($val === '' || $val === null) {
                    continue;
                }
                $newData[$field] = (int)(bool)$val;
            } else {
                $trimmed = trim((string)$val);
                if ($trimmed === '') {
                    continue;
                }
                $newData[$field] = $trimmed;
            }
        }

        if (empty($newData)) {
            Response::error('変更したい項目を入力してください', 400, 'VALIDATION_ERROR');
            return;
        }

        // 変更箇所を記録
        $changes = [];
        foreach ($newData as $field => $newVal) {
            $oldVal = $member[$field] ?? null;
            if ($field === 'sns_allowed') {
                $oldVal = (int)$oldVal;
                $newVal = (int)$newVal;
            }
            if ((string)$oldVal !== (string)$newVal) {
                $changes[$field] = ['before' => $oldVal, 'after' => $newVal];
            }
        }

        if (empty($changes)) {
            Response::success([], '変更はありませんでした');
            return;
        }

        $memberModel->update($memberId, $newData);

        // ダッシュボードに通知を記録
        $notifModel = new MemberChangeNotification();
        $notifModel->create($memberId, $member['name_kanji'], $member['student_id'], $changes);

        Response::success([], '情報を更新しました');
    }

    /**
     * 合宿しおり表示（会員ログイン済み）
     * GET /member/camp/{id}/booklet
     */
    public function booklet(array $params): void
    {
        if (!$this->checkAuth()) {
            Response::redirect('/member/login');
            return;
        }

        $campId   = (int)$params['id'];
        $memberId = (int)($_SESSION['member_id'] ?? 0);

        $campModel    = new Camp();
        $bookletModel = new CampBooklet();

        $camp    = $campModel->find($campId);
        $booklet = $bookletModel->findByCampId($campId);

        if (!$camp || !$booklet || !(int)$booklet['is_public']) {
            http_response_code(404);
            $this->render('error', ['message' => 'しおりが見つかりません']);
            return;
        }

        // 参加者チェック：申し込み済みでない会員は閲覧不可
        $appModel = new CampApplication();
        if ($appModel->findByCampAndMember($campId, $memberId) === null) {
            http_response_code(403);
            $this->render('error', ['message' => 'このしおりは合宿参加者のみ閲覧できます']);
            return;
        }

        $memberModel = new Member();
        $member      = $memberModel->find($memberId);
        $myName      = $member ? $member['name_kanji'] : '';

        $participantModel = new Participant();
        $genderMap = [];
        foreach ($participantModel->getByCampId($campId) as $p) {
            $genderMap[$p['name']] = $p['gender'];
        }

        $booklet = $this->mergeTennisData($campId, $booklet);
        $plans   = $this->buildPlanDivisions($campId);

        $this->render('member/booklet', [
            'camp'       => $camp,
            'booklet'    => $booklet,
            'plans'      => $plans,
            'memberName' => $_SESSION['member_name'] ?? '',
            'myName'     => $myName,
            'isLoggedIn' => true,
            'genderMap'  => $genderMap,
        ]);
    }

    /**
     * しおりにテニス班分け（団体戦/紅白戦/対戦表）データを合流させる。
     * これらは camp_tennis_battles テーブルへ移行済み。
     */
    private function mergeTennisData(int $campId, array $booklet): array
    {
        $tennis = (new CampTennisBattle())->findByCampId($campId);
        $booklet['team_battle_teams'] = $tennis['team_battle_teams'] ?? [];
        $booklet['team_battle_rules'] = $tennis['team_battle_rules'] ?? '';
        $booklet['kohaku_teams']      = $tennis['kohaku_teams']      ?? ['red' => [], 'white' => []];
        $booklet['kohaku_rules']      = $tennis['kohaku_rules']      ?? '';
        $booklet['kohaku_matches']    = $tennis['kohaku_matches']    ?? [];
        // 紅白戦チームが空（赤白ともに0名）なら非表示にするため空配列化
        $kt = $booklet['kohaku_teams'];
        if (empty($kt['red']) && empty($kt['white'])) {
            $booklet['kohaku_teams'] = [];
        }
        return $booklet;
    }

    /**
     * 企画班分けを「企画名→班→メンバー」の表示用構造で取得。
     * 班番号でグルーピングし、未割り当ては除外。
     */
    private function buildPlanDivisions(int $campId): array
    {
        $model     = new CampPlanDivision();
        $divisions = $model->getByCampId($campId);
        $result    = [];

        $slotLabels = ['outbound' => '往路', 'morning' => '午前', 'afternoon' => '午後', 'banquet' => '宴会', 'return' => '復路'];

        foreach ($divisions as $div) {
            $members = $model->getMembers((int)$div['id']);
            $groups  = [];
            foreach ($members as $m) {
                if ($m['team_no'] === null) continue;
                $no = (int)$m['team_no'];
                $groups[$no] = $groups[$no] ?? ['group_name' => $no . '班', 'members' => []];
                $groups[$no]['members'][] = ['name' => $m['name']];
            }
            if (empty($groups)) continue;   // 班割り未実施の企画は表示しない
            ksort($groups);

            $timing = '';
            if (!empty($div['day_number'])) {
                $timing = $div['day_number'] . '日目 ' . ($slotLabels[$div['slot_type']] ?? '');
            }

            $result[] = [
                'name'   => $div['name'],
                'timing' => $timing,
                'groups' => array_values($groups),
            ];
        }
        return $result;
    }

    /**
     * 合宿しおり公開URL表示（ログイン不要）
     * GET /booklet/{token}
     */
    public function bookletPublic(array $params): void
    {
        $token        = $params['token'];
        $bookletModel = new CampBooklet();
        $booklet      = $bookletModel->findByToken($token);

        if (!$booklet) {
            http_response_code(404);
            $this->render('error', ['message' => 'しおりが見つかりません']);
            return;
        }

        $campModel = new Camp();
        $camp      = $campModel->find((int)$booklet['camp_id']);

        $myName     = '';
        $isLoggedIn = false;
        if ($this->checkAuth()) {
            $memberId    = (int)($_SESSION['member_id'] ?? 0);
            $memberModel = new Member();
            $member      = $memberModel->find($memberId);
            $myName      = $member ? $member['name_kanji'] : '';
            $isLoggedIn  = true;
        }

        $participantModel = new Participant();
        $genderMap = [];
        foreach ($participantModel->getByCampId((int)$booklet['camp_id']) as $p) {
            $genderMap[$p['name']] = $p['gender'];
        }

        $bookletCampId = (int)$booklet['camp_id'];
        $booklet = $this->mergeTennisData($bookletCampId, $booklet);
        $plans   = $this->buildPlanDivisions($bookletCampId);

        $this->render('member/booklet', [
            'camp'       => $camp,
            'booklet'    => $booklet,
            'plans'      => $plans,
            'memberName' => $_SESSION['member_name'] ?? '',
            'myName'     => $myName,
            'isLoggedIn' => $isLoggedIn,
            'genderMap'  => $genderMap,
        ]);
    }

    /**
     * デバッグ用（確認後削除）
     */
    public function debugMember(array $params): void
    {
        $studentId = strtoupper(trim($_GET['student_id'] ?? '1Y23F158-5'));
        $memberModel = new Member();
        $member = $memberModel->findByStudentId($studentId);
        header('Content-Type: application/json');
        echo json_encode([
            'searched_id' => $studentId,
            'found' => $member ? true : false,
            'status' => $member['status'] ?? null,
            'STATUS_ACTIVE' => Member::STATUS_ACTIVE,
            'STATUS_OB_OG' => Member::STATUS_OB_OG,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * 会員ログアウト処理（API）
     */
    public function logout(array $params): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        unset($_SESSION['member_id']);
        unset($_SESSION['member_name']);
        unset($_SESSION['member_login_time']);
        Response::success([], 'ログアウトしました');
    }

    /**
     * 認証チェック
     */
    private function checkAuth(): bool
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return false;
        }
        // セッション有効期限：24時間
        if (isset($_SESSION['member_login_time'])) {
            if (time() - $_SESSION['member_login_time'] > 86400) {
                unset($_SESSION[self::SESSION_KEY]);
                return false;
            }
        }
        return true;
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

        include VIEWS_PATH . '/layouts/member.php';
    }

    // ==================== 遠征集金 ====================

    /**
     * 遠征集金フォームページ
     * GET /member/expedition-collection/{id}  (id = collection_id)
     */
    public function expeditionCollectionForm(array $params): void
    {
        if (!$this->checkAuth()) {
            Response::redirect('/member/login');
            return;
        }

        $collectionId = (int)$params['id'];
        $memberId     = (int)($_SESSION['member_id'] ?? 0);

        $item = ExpeditionCollectionItem::findByMemberAndCollection($memberId, $collectionId);
        if (!$item) {
            http_response_code(404);
            $this->render('error', ['message' => '集金情報が見つかりません']);
            return;
        }

        $collection = Database::getInstance()->fetch(
            "SELECT ec.*, e.name AS expedition_name, e.start_date, e.end_date
             FROM expedition_collections ec
             JOIN expeditions e ON e.id = ec.expedition_id
             WHERE ec.id = ?",
            [$collectionId]
        );
        if (!$collection) {
            http_response_code(404);
            $this->render('error', ['message' => '集金情報が見つかりません']);
            return;
        }

        $this->render('member/expedition_collection', [
            'item'        => $item,
            'collection'  => $collection,
            'memberName'  => $_SESSION['member_name'] ?? '',
            'memberId'    => $memberId,
        ]);
    }

    /**
     * 遠征集金 提出 API
     * POST /api/member/expedition-collection-items/{id}/submit
     */
    public function submitExpeditionCollection(array $params): void
    {
        if (!$this->checkAuth()) {
            Response::error('ログインが必要です', 401, 'UNAUTHORIZED');
            return;
        }

        $itemId   = (int)$params['id'];
        $memberId = (int)($_SESSION['member_id'] ?? 0);

        $item = ExpeditionCollectionItem::find($itemId);
        if (!$item || (int)$item['member_id'] !== $memberId) {
            Response::error('Not found', 404, 'NOT_FOUND');
            return;
        }

        if ((int)$item['submitted'] === 1) {
            Response::error('すでに提出済みです', 400, 'ALREADY_SUBMITTED');
            return;
        }

        $transferred = Request::get('transferred');
        if (!$transferred || $transferred === '0' || $transferred === 'false') {
            Response::error('振り込み完了のチェックが必要です', 400, 'VALIDATION_ERROR');
            return;
        }

        // 期限超過時は遅延理由が必須
        $collection = Database::getInstance()->fetch(
            "SELECT deadline FROM expedition_collections WHERE id = ?",
            [$item['collection_id']]
        );
        $lateReason = Request::get('late_reason') ?? null;

        if ($collection && !empty($collection['deadline']) && $collection['deadline'] < date('Y-m-d')) {
            if (empty($lateReason)) {
                Response::error('入金遅れの理由を入力してください', 400, 'VALIDATION_ERROR');
                return;
            }
        }

        ExpeditionCollectionItem::submit($itemId, $lateReason ?: null);
        Response::success([], '提出しました');
    }
}
