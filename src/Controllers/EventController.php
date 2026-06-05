<?php
/**
 * 企画コントローラー（管理者用）
 */
class EventController
{
    private Event $model;
    private Database $db;

    public function __construct()
    {
        Auth::requirePermission('events');
        $this->model = new Event();
        $this->db    = Database::getInstance();
    }

    // ──────────────────────────────────────────
    // API
    // ──────────────────────────────────────────

    /**
     * 一覧取得
     */
    public function index(array $params): void
    {
        Response::success($this->model->all());
    }

    /**
     * 詳細取得
     */
    public function show(array $params): void
    {
        $id    = (int)$params['id'];
        $event = $this->model->find($id);

        if (!$event) {
            Response::error('企画が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $appModel     = new EventApplication();
        $expenseModel = new EventExpense();

        $event['applications'] = $appModel->getByEventId($id);
        $event['expenses']     = $expenseModel->getByEventId($id);

        Response::success($event);
    }

    /**
     * 新規作成
     */
    public function store(array $params): void
    {
        $errors = Request::validate([
            'title'      => 'required|max:255',
            'event_date' => 'required|date',
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        $data = Request::only([
            'title', 'event_date', 'event_time', 'description',
            'location', 'participation_fee', 'capacity', 'deadline', 'allow_waitlist', 'is_active',
        ]);

        $id = $this->model->create($data);
        $event = $this->model->find($id);

        Response::success($event, '企画を作成しました');
    }

    /**
     * 更新
     */
    public function update(array $params): void
    {
        $id    = (int)$params['id'];
        $event = $this->model->find($id);

        if (!$event) {
            Response::error('企画が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $data = Request::only([
            'title', 'event_date', 'event_time', 'description',
            'location', 'participation_fee', 'capacity', 'deadline', 'allow_waitlist', 'is_active',
        ]);

        $this->model->update($id, $data);
        Response::success($this->model->find($id), '企画を更新しました');
    }

    /**
     * 削除
     */
    public function destroy(array $params): void
    {
        $id    = (int)$params['id'];
        $event = $this->model->find($id);

        if (!$event) {
            Response::error('企画が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $this->model->delete($id);
        Response::success([], '企画を削除しました');
    }

    /**
     * 公開/非公開切り替え
     */
    public function toggleActive(array $params): void
    {
        $id    = (int)$params['id'];
        $event = $this->model->find($id);

        if (!$event) {
            Response::error('企画が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $newState = $event['is_active'] ? 0 : 1;
        $this->model->update($id, ['is_active' => $newState]);
        Response::success(['is_active' => $newState], $newState ? '会員ページに公開しました' : '非公開にしました');
    }

    /**
     * 申込一覧取得（参加確定＋キャンセル待ち）
     */
    public function getApplications(array $params): void
    {
        $id       = (int)$params['id'];
        $appModel = new EventApplication();
        Response::success([
            'submitted' => $appModel->getByEventId($id),
            'waitlist'  => $appModel->getWaitlistByEventId($id),
        ]);
    }

    /**
     * 申込キャンセル（管理者）。submitted キャンセル時はキャンセル待ちを繰り上げ
     */
    public function cancelApplication(array $params): void
    {
        $id       = (int)$params['id'];
        $appModel = new EventApplication();

        // レコードからevent_idを取得
        $rec = $this->db->fetch(
            "SELECT * FROM event_applications WHERE id = ?", [$id]
        );
        if (!$rec) {
            Response::error('申込が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $prevStatus = $appModel->cancelById($id);

        if ($prevStatus === 'submitted') {
            $appModel->promoteFromWaitlist((int)$rec['event_id']);
        }

        Response::success([], 'キャンセルしました');
    }

    /**
     * 雑費追加
     */
    public function storeExpense(array $params): void
    {
        $eventId = (int)$params['id'];
        $event   = $this->model->find($eventId);

        if (!$event) {
            Response::error('企画が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $errors = Request::validate([
            'name'   => 'required',
            'amount' => 'required|integer',
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        $expenseModel = new EventExpense();
        $data = Request::only(['name', 'amount']);
        $data['event_id'] = $eventId;

        $newId   = $expenseModel->create($data);
        $expense = $expenseModel->find($newId);

        Response::success($expense, '雑費を追加しました');
    }

    /**
     * 雑費更新
     */
    public function updateExpense(array $params): void
    {
        $id           = (int)$params['id'];
        $expenseModel = new EventExpense();
        $expense      = $expenseModel->find($id);

        if (!$expense) {
            Response::error('雑費が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $data = Request::only(['name', 'amount']);
        $expenseModel->update($id, $data);
        Response::success($expenseModel->find($id), '更新しました');
    }

    /**
     * 雑費削除
     */
    public function destroyExpense(array $params): void
    {
        $id           = (int)$params['id'];
        $expenseModel = new EventExpense();

        if (!$expenseModel->find($id)) {
            Response::error('雑費が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $expenseModel->delete($id);
        Response::success([], '削除しました');
    }

    /**
     * 費用計算
     */
    public function calculate(array $params): void
    {
        $id    = (int)$params['id'];
        $event = $this->model->find($id);

        if (!$event) {
            Response::error('企画が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $appModel     = new EventApplication();
        $expenseModel = new EventExpense();

        $applicantCount   = (int)$event['application_count'];
        $totalExpenses    = $expenseModel->sumByEventId($id);
        $participationFee = (int)$event['participation_fee'];
        $expenses         = $expenseModel->getByEventId($id);

        // 参加者ごとの雑費負担（割り切れない場合は切り上げ）
        $expensePerPerson = $applicantCount > 0 ? (int)ceil($totalExpenses / $applicantCount) : 0;
        $totalPerPerson   = $participationFee + $expensePerPerson;

        Response::success([
            'applicant_count'    => $applicantCount,
            'participation_fee'  => $participationFee,
            'total_expenses'     => $totalExpenses,
            'expense_per_person' => $expensePerPerson,
            'total_per_person'   => $totalPerPerson,
            'expenses'           => $expenses,
        ]);
    }

    // ──────────────────────────────────────────
    // API: 班決め（グループ分け）
    // ──────────────────────────────────────────

    /**
     * 班決め情報取得（参加確定者＋制約）
     */
    public function getTeams(array $params): void
    {
        $id          = (int)$params['id'];
        $event       = $this->model->find($id);
        if (!$event) {
            Response::error('企画が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $appModel        = new EventApplication();
        $constraintModel = new EventTeamConstraint();

        Response::success([
            'members'     => $appModel->getByEventId($id),   // team_no 含む
            'constraints' => $constraintModel->getByEventId($id),
        ]);
    }

    /**
     * 班割り当てを保存
     * body: { assignments: { application_id: team_no|null, ... } }
     */
    public function saveTeams(array $params): void
    {
        $id    = (int)$params['id'];
        $event = $this->model->find($id);
        if (!$event) {
            Response::error('企画が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $assignments = Request::get('assignments', []);
        if (!is_array($assignments)) {
            $assignments = [];
        }

        $appModel = new EventApplication();
        $appModel->saveTeamAssignments($id, $assignments);

        Response::success([], '班割りを保存しました');
    }

    /**
     * 制約を追加（絶対一緒 / 絶対別）
     * body: { member_a_id, member_b_id, type }
     */
    public function addTeamConstraint(array $params): void
    {
        $id    = (int)$params['id'];
        $event = $this->model->find($id);
        if (!$event) {
            Response::error('企画が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $memberA = (int)Request::get('member_a_id');
        $memberB = (int)Request::get('member_b_id');
        $type    = Request::get('type');

        if ($memberA <= 0 || $memberB <= 0 || $memberA === $memberB) {
            Response::error('2名のメンバーを選択してください', 422, 'INVALID');
            return;
        }
        if (!in_array($type, ['together', 'apart'], true)) {
            Response::error('種別が不正です', 422, 'INVALID');
            return;
        }

        $constraintModel = new EventTeamConstraint();
        $constraintModel->add($id, $memberA, $memberB, $type);

        Response::success(['constraints' => $constraintModel->getByEventId($id)], '制約を追加しました');
    }

    /**
     * 制約を削除
     */
    public function deleteTeamConstraint(array $params): void
    {
        $constraintId = (int)$params['cid'];
        $constraintModel = new EventTeamConstraint();
        $constraintModel->delete($constraintId);
        Response::success([], '制約を削除しました');
    }

    // ── API: 申込URLトークン ──

    public function getToken(array $params): void
    {
        $id    = (int)$params['id'];
        $token = EventToken::findByEvent($id);
        Response::success(['token' => $token]);
    }

    public function generateToken(array $params): void
    {
        $id    = (int)$params['id'];
        $event = $this->model->find($id);
        if (!$event) {
            Response::error('企画が見つかりません', 404, 'NOT_FOUND');
            return;
        }
        $token = EventToken::generate($id);
        Response::success(['token' => $token]);
    }

    public function deleteToken(array $params): void
    {
        EventToken::delete((int)$params['id']);
        Response::success([]);
    }

    // ──────────────────────────────────────────
    // ページレンダリング
    // ──────────────────────────────────────────

    /**
     * 企画一覧ページ
     */
    public function indexPage(array $params): void
    {
        $this->render('events/index');
    }

    /**
     * 企画詳細ページ
     */
    public function detailPage(array $params): void
    {
        $id    = (int)$params['id'];
        $event = $this->model->find($id);

        if (!$event) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $this->render('events/detail', ['eventId' => $id, 'event' => $event]);
    }

    // ──────────────────────────────────────────
    // 内部
    // ──────────────────────────────────────────

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
