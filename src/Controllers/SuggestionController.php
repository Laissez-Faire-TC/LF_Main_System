<?php
/**
 * 目安箱コントローラー
 *   - 幹部側（要認証 Auth）: 管理画面・カテゴリ管理・全投稿閲覧・返信
 *   - 会員側（会員ログイン）: 投稿・自分の投稿/全体公開の閲覧・返信
 */
class SuggestionController
{
    private const MEMBER_SESSION_KEY = 'member_authenticated';

    // ==================== 幹部側 ====================

    /**
     * 目安箱 管理ページ
     * GET /suggestions
     */
    public function adminIndex(array $params): void
    {
        Auth::requirePermission('suggestions');

        $categoryId = isset($_GET['category']) && $_GET['category'] !== ''
            ? (int)$_GET['category'] : null;

        $categories  = SuggestionCategory::findAll(false);
        $suggestions = Suggestion::findAllForAdmin($categoryId);

        $this->renderAdmin('suggestions/index', [
            'categories'   => $categories,
            'suggestions'  => $suggestions,
            'selectedCat'  => $categoryId,
        ]);
    }

    /**
     * 目安箱 投稿詳細ページ（幹部）
     * GET /suggestions/{id}
     */
    public function adminShow(array $params): void
    {
        Auth::requirePermission('suggestions');

        $id         = (int)$params['id'];
        $suggestion = Suggestion::find($id);
        if (!$suggestion) {
            http_response_code(404);
            $this->renderAdmin('errors/404', []);
            return;
        }

        $replies     = SuggestionReply::findBySuggestion($id);
        $replyTree   = SuggestionReply::buildTree($replies);

        $this->renderAdmin('suggestions/show', [
            'suggestion' => $suggestion,
            'replyTree'  => $replyTree,
            'isAdmin'    => true,
        ]);
    }

    // ---------- カテゴリ管理 API（幹部） ----------

    public function listCategories(array $params): void
    {
        Auth::requirePermission('suggestions');
        Response::success(['categories' => SuggestionCategory::findAll(false)]);
    }

    public function storeCategory(array $params): void
    {
        Auth::requirePermission('suggestions');
        $name = trim(Request::get('name') ?? '');
        if ($name === '') {
            Response::error('カテゴリ名を入力してください', 400, 'VALIDATION_ERROR');
            return;
        }
        $cat = SuggestionCategory::create([
            'name'       => $name,
            'sort_order' => (int)(Request::get('sort_order') ?? 0),
        ]);
        Response::success(['category' => $cat], 'カテゴリを追加しました');
    }

    public function updateCategory(array $params): void
    {
        Auth::requirePermission('suggestions');
        $id  = (int)$params['id'];
        if (!SuggestionCategory::find($id)) {
            Response::error('カテゴリが見つかりません', 404, 'NOT_FOUND');
            return;
        }
        $data = [];
        foreach (['name', 'sort_order', 'is_active'] as $f) {
            if (Request::has($f)) $data[$f] = Request::get($f);
        }
        if (isset($data['name']) && trim((string)$data['name']) === '') {
            Response::error('カテゴリ名を入力してください', 400, 'VALIDATION_ERROR');
            return;
        }
        $cat = SuggestionCategory::update($id, $data);
        Response::success(['category' => $cat], 'カテゴリを更新しました');
    }

    public function destroyCategory(array $params): void
    {
        Auth::requirePermission('suggestions');
        $id = (int)$params['id'];
        SuggestionCategory::delete($id);
        Response::success([], 'カテゴリを削除しました');
    }

    // ---------- 投稿操作 API（幹部） ----------

    public function setStatus(array $params): void
    {
        Auth::requirePermission('suggestions');
        $id     = (int)$params['id'];
        $status = Request::get('status') ?? 'open';
        if (!Suggestion::find($id)) {
            Response::error('投稿が見つかりません', 404, 'NOT_FOUND');
            return;
        }
        Suggestion::setStatus($id, $status);
        Response::success([], '状態を更新しました');
    }

    public function destroy(array $params): void
    {
        Auth::requirePermission('suggestions');
        $id = (int)$params['id'];
        Suggestion::delete($id);
        Response::success([], '投稿を削除しました');
    }

    /**
     * 幹部の返信
     * POST /api/suggestions/{id}/replies
     */
    public function adminReply(array $params): void
    {
        Auth::requirePermission('suggestions');
        $suggestionId = (int)$params['id'];
        $suggestion   = Suggestion::find($suggestionId);
        if (!$suggestion) {
            Response::error('投稿が見つかりません', 404, 'NOT_FOUND');
            return;
        }
        $body = trim(Request::get('body') ?? '');
        if ($body === '') {
            Response::error('返信内容を入力してください', 400, 'VALIDATION_ERROR');
            return;
        }
        $parentId = Request::get('parent_reply_id');
        $reply = SuggestionReply::create([
            'suggestion_id'   => $suggestionId,
            'parent_reply_id' => $parentId,
            'author_type'     => 'admin',
            'author_name'     => '幹部',
            'body'            => $body,
        ]);
        Response::success(['reply' => $reply], '返信しました');
    }

    // ==================== 会員側 ====================

    /**
     * 会員 目安箱トップ（一覧＋投稿フォーム）
     * GET /member/suggestions
     */
    public function memberIndex(array $params): void
    {
        if (!$this->checkMemberAuth()) {
            Response::redirect('/member/login?return=' . urlencode('/member/suggestions'));
            return;
        }

        $memberId   = (int)($_SESSION['member_id'] ?? 0);
        $categoryId = isset($_GET['category']) && $_GET['category'] !== ''
            ? (int)$_GET['category'] : null;

        $categories  = SuggestionCategory::findAll(true);
        $suggestions = Suggestion::findAllForMember($memberId, $categoryId);

        $this->renderMember('member/suggestions', [
            'memberName'  => $_SESSION['member_name'] ?? '',
            'memberId'    => $memberId,
            'categories'  => $categories,
            'suggestions' => $suggestions,
            'selectedCat' => $categoryId,
        ]);
    }

    /**
     * 会員 投稿詳細（返信閲覧・返信）
     * GET /member/suggestions/{id}
     */
    public function memberShow(array $params): void
    {
        if (!$this->checkMemberAuth()) {
            Response::redirect('/member/login?return=' . urlencode('/member/suggestions'));
            return;
        }

        $memberId   = (int)($_SESSION['member_id'] ?? 0);
        $id         = (int)$params['id'];
        $suggestion = Suggestion::find($id);

        if (!$suggestion || !Suggestion::canMemberView($suggestion, $memberId)) {
            http_response_code(404);
            $this->renderMember('errors/404', ['memberName' => $_SESSION['member_name'] ?? '']);
            return;
        }

        $replies   = SuggestionReply::findBySuggestion($id);
        $replyTree = SuggestionReply::buildTree($replies);
        $isOwner   = (int)$suggestion['member_id'] === $memberId;

        $this->renderMember('member/suggestion_show', [
            'memberName' => $_SESSION['member_name'] ?? '',
            'memberId'   => $memberId,
            'suggestion' => $suggestion,
            'replyTree'  => $replyTree,
            'isOwner'    => $isOwner,
            'isAdmin'    => false,
        ]);
    }

    /**
     * 会員 投稿送信 API
     * POST /api/member/suggestions
     */
    public function memberStore(array $params): void
    {
        if (!$this->checkMemberAuth()) {
            Response::error('ログインが必要です', 401, 'UNAUTHORIZED');
            return;
        }

        $memberId = (int)($_SESSION['member_id'] ?? 0);
        $title    = trim(Request::get('title') ?? '');
        $body     = trim(Request::get('body') ?? '');
        $visibility = Request::get('visibility') === 'public' ? 'public' : 'private';
        $categoryId = Request::get('category_id');

        if ($title === '') {
            Response::error('題名を入力してください', 400, 'VALIDATION_ERROR');
            return;
        }
        if ($body === '') {
            Response::error('内容を入力してください', 400, 'VALIDATION_ERROR');
            return;
        }

        $suggestion = Suggestion::create([
            'member_id'   => $memberId,
            'author_name' => $_SESSION['member_name'] ?? null,
            'category_id' => $categoryId,
            'title'       => $title,
            'body'        => $body,
            'visibility'  => $visibility,
        ]);

        Response::success(['suggestion' => $suggestion], '送信しました');
    }

    /**
     * 会員の返信 API
     * POST /api/member/suggestions/{id}/replies
     */
    public function memberReply(array $params): void
    {
        if (!$this->checkMemberAuth()) {
            Response::error('ログインが必要です', 401, 'UNAUTHORIZED');
            return;
        }

        $memberId     = (int)($_SESSION['member_id'] ?? 0);
        $suggestionId = (int)$params['id'];
        $suggestion   = Suggestion::find($suggestionId);

        if (!$suggestion || !Suggestion::canMemberView($suggestion, $memberId)) {
            Response::error('投稿が見つかりません', 404, 'NOT_FOUND');
            return;
        }
        // 会員が返信できるのは閲覧できる投稿（全体公開 or 自分の投稿）
        $body = trim(Request::get('body') ?? '');
        if ($body === '') {
            Response::error('返信内容を入力してください', 400, 'VALIDATION_ERROR');
            return;
        }

        $reply = SuggestionReply::create([
            'suggestion_id'    => $suggestionId,
            'parent_reply_id'  => Request::get('parent_reply_id'),
            'author_type'      => 'member',
            'author_member_id' => $memberId,
            'author_name'      => $_SESSION['member_name'] ?? null,
            'body'             => $body,
        ]);
        Response::success(['reply' => $reply], '返信しました');
    }

    // ==================== ヘルパー ====================

    private function checkMemberAuth(): bool
    {
        if (!isset($_SESSION[self::MEMBER_SESSION_KEY])) {
            return false;
        }
        if (isset($_SESSION['member_login_time'])) {
            if (time() - $_SESSION['member_login_time'] > 86400) {
                unset($_SESSION[self::MEMBER_SESSION_KEY]);
                return false;
            }
        }
        return true;
    }

    private function renderAdmin(string $view, array $data = []): void
    {
        extract($data);
        $config  = require CONFIG_PATH . '/app.php';
        $appName = $config['name'];
        ob_start();
        include VIEWS_PATH . '/' . $view . '.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/main.php';
    }

    private function renderMember(string $view, array $data = []): void
    {
        extract($data);
        $config  = require CONFIG_PATH . '/app.php';
        $appName = $config['name'];
        ob_start();
        include VIEWS_PATH . '/' . $view . '.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/member.php';
    }
}
