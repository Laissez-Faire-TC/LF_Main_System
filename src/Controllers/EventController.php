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
            'location', 'participation_fee', 'capacity', 'deadline', 'allow_waitlist',
            'allow_guest', 'guest_type', 'include_guests_in_calc', 'is_active',
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
            'location', 'participation_fee', 'capacity', 'deadline', 'allow_waitlist',
            'allow_guest', 'guest_type', 'include_guests_in_calc', 'is_active',
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
        $id          = (int)$params['id'];
        $appModel    = new EventApplication();
        $guestModel  = new EventGuestApplication();
        $fieldModel  = new EventGuestField();
        $memberVals  = new EventMemberFieldValue();

        // 会員申込に、会員向けカスタム項目の回答（member_values）を付与
        $submitted = $memberVals->attachValues($appModel->getByEventId($id));
        $waitlist  = $memberVals->attachValues($appModel->getWaitlistByEventId($id));

        Response::success([
            'submitted'        => $submitted,
            'waitlist'         => $waitlist,
            'guest_submitted'  => $guestModel->getByEventId($id),
            'guest_waitlist'   => $guestModel->getWaitlistByEventId($id),
            'guest_fields'     => $fieldModel->getByEventId($id, 'guest'),
            'member_fields'    => $fieldModel->getByEventId($id, 'member'),
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
        // ゲストを集計対象に含める設定なら参加確定ゲスト数を加算
        $guestCount = 0;
        if (!empty($event['include_guests_in_calc'])) {
            $guestCount     = (new EventGuestApplication())->countByEventId($id);
            $applicantCount += $guestCount;
        }
        $totalExpenses    = $expenseModel->sumByEventId($id);
        $participationFee = (int)$event['participation_fee'];
        $expenses         = $expenseModel->getByEventId($id);

        // 参加者ごとの雑費負担（割り切れない場合は切り上げ）
        $expensePerPerson = $applicantCount > 0 ? (int)ceil($totalExpenses / $applicantCount) : 0;
        $totalPerPerson   = $participationFee + $expensePerPerson;

        Response::success([
            'applicant_count'    => $applicantCount,
            'guest_count'        => $guestCount,
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

        $members = $appModel->getByEventId($id);   // team_no 含む

        // ゲストを集計対象に含める設定なら、班分け対象にゲストも追加
        $guests        = [];
        $balanceFields = [];   // 自動振り分けの基準に使えるゲスト項目（select/radio）
        if (!empty($event['include_guests_in_calc'])) {
            $fields   = (new EventGuestField())->getByEventId($id);
            $fieldMap = [];
            foreach ($fields as $f) {
                $fieldMap[(int)$f['id']] = $f['label'];
                if (in_array($f['type'], ['select', 'radio'], true)) {
                    $balanceFields[] = ['id' => (int)$f['id'], 'label' => $f['label']];
                }
            }

            foreach ((new EventGuestApplication())->getByEventId($id) as $g) {
                // カスタム項目の回答を「項目名: 値」形式の表示用文字列にまとめる
                $detailParts = [];
                foreach (($g['values'] ?? []) as $fid => $val) {
                    $label = $fieldMap[(int)$fid] ?? '';
                    if ($val !== '' && $val !== null) {
                        $detailParts[] = ($label !== '' ? $label . ': ' : '') . $val;
                    }
                }
                $guests[] = [
                    'id'         => 'g' . $g['id'],   // 会員申込IDと衝突しない合成ID
                    'guest_id'   => (int)$g['id'],
                    'is_guest'   => true,
                    'name_kanji' => $g['name'],
                    'name_kana'  => $g['name_kana'] ?? $g['name'],
                    'grade'      => null,
                    'gender'     => null,
                    'faculty'    => null,
                    'department' => implode(' / ', $detailParts),
                    'values'     => $g['values'] ?? [],   // 班分けの基準評価に使う
                    'team_no'    => $g['team_no'],
                    'promoted'   => $g['promoted'],
                ];
            }
        }

        Response::success([
            'members'        => $members,
            'guests'         => $guests,
            'balance_fields' => $balanceFields,
            'constraints'    => $constraintModel->getByEventId($id),
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

        // 'g' プレフィックス付き = ゲスト申込、それ以外 = 会員申込に振り分け
        $memberAssignments = [];
        $guestAssignments  = [];
        foreach ($assignments as $key => $teamNo) {
            if (is_string($key) && strncmp($key, 'g', 1) === 0) {
                $guestAssignments[(int)substr($key, 1)] = $teamNo;
            } else {
                $memberAssignments[(int)$key] = $teamNo;
            }
        }

        (new EventApplication())->saveTeamAssignments($id, $memberAssignments);
        if (!empty($guestAssignments)) {
            (new EventGuestApplication())->saveTeamAssignments($id, $guestAssignments);
        }

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
    // API: ゲストフォーム項目（カスタム質問）
    // ──────────────────────────────────────────

    public function getGuestFields(array $params): void
    {
        $id = (int)$params['id'];
        Response::success(['fields' => (new EventGuestField())->getByEventId($id)]);
    }

    public function storeGuestField(array $params): void
    {
        $id    = (int)$params['id'];
        $event = $this->model->find($id);
        if (!$event) {
            Response::error('企画が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $body  = Request::json();
        $label = trim($body['label'] ?? '');
        if ($label === '') {
            Response::error('項目名を入力してください', 422, 'INVALID');
            return;
        }

        $audience = in_array($body['audience'] ?? 'guest', ['member', 'guest'], true)
                  ? ($body['audience'] ?? 'guest') : 'guest';

        $fieldModel = new EventGuestField();
        $fieldModel->create($id, [
            'audience'    => $audience,
            'label'       => $label,
            'description' => $body['description'] ?? null,
            'type'        => $body['type'] ?? 'text',
            'options'     => $body['options'] ?? null,
            'is_required' => $body['is_required'] ?? 0,
            // 並び順は同じ対象の項目数を初期値にする
            'sort_order'  => $body['sort_order'] ?? count($fieldModel->getByEventId($id, $audience)),
        ]);

        Response::success(['fields' => $fieldModel->getByEventId($id)], '項目を追加しました');
    }

    /**
     * 定番のゲスト項目テンプレートを一括追加
     * 新歓・OB交流会向け（LINE名・学科・性別・代・テニス経験・備考）。
     * 氏名はフォーム固定の必須項目のためテンプレートには含めない。
     */
    public function applyGuestFieldTemplate(array $params): void
    {
        $id    = (int)$params['id'];
        $event = $this->model->find($id);
        if (!$event) {
            Response::error('企画が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        // 氏名・カナ・代/学科は本人特定の固定項目としてフォーム先頭に表示するため、
        // テンプレート（カスタム項目）には含めない。
        $template = [
            ['label' => 'LINE名',      'type' => 'text',     'is_required' => 0],
            ['label' => '性別',        'type' => 'radio',    'is_required' => 0, 'options' => ['男', '女']],
            ['label' => 'テニスの経験', 'type' => 'radio',    'is_required' => 0,
                'options' => ['初心者', '中級者', '上級者', '超上級']],
            ['label' => '備考',        'type' => 'textarea', 'is_required' => 0,
                'description' => 'アレルギー・当日の参加時間・質問など、伝えておきたいことがあれば自由にご記入ください。'],
        ];

        $fieldModel = new EventGuestField();
        // 既存項目の末尾に追加する
        $base = count($fieldModel->getByEventId($id));
        foreach ($template as $i => $field) {
            $field['sort_order'] = $base + $i;
            $fieldModel->create($id, $field);
        }

        Response::success(['fields' => $fieldModel->getByEventId($id)], 'テンプレートを追加しました');
    }

    public function updateGuestField(array $params): void
    {
        $fieldId    = (int)$params['fid'];
        $fieldModel = new EventGuestField();
        $field      = $fieldModel->find($fieldId);
        if (!$field) {
            Response::error('項目が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $body  = Request::json();
        $label = array_key_exists('label', $body) ? trim($body['label']) : $field['label'];
        if ($label === '') {
            Response::error('項目名を入力してください', 422, 'INVALID');
            return;
        }

        $fieldModel->update($fieldId, $body + ['label' => $label]);
        Response::success(['fields' => $fieldModel->getByEventId((int)$field['event_id'])], '項目を更新しました');
    }

    public function deleteGuestField(array $params): void
    {
        $fieldId    = (int)$params['fid'];
        $fieldModel = new EventGuestField();
        $field      = $fieldModel->find($fieldId);
        if (!$field) {
            Response::error('項目が見つかりません', 404, 'NOT_FOUND');
            return;
        }
        $eventId = (int)$field['event_id'];
        $fieldModel->delete($fieldId);
        Response::success(['fields' => $fieldModel->getByEventId($eventId)], '項目を削除しました');
    }

    public function reorderGuestFields(array $params): void
    {
        $id    = (int)$params['id'];
        $order = Request::get('order', []);
        if (is_array($order) && !empty($order)) {
            (new EventGuestField())->saveOrder($order);
        }
        Response::success(['fields' => (new EventGuestField())->getByEventId($id)]);
    }

    // ──────────────────────────────────────────
    // API: ゲスト申込（取消）
    // ──────────────────────────────────────────

    public function cancelGuestApplication(array $params): void
    {
        $id         = (int)$params['id'];
        $guestModel = new EventGuestApplication();
        $rec        = $guestModel->find($id);
        if (!$rec) {
            Response::error('申込が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $prevStatus = $guestModel->cancelById($id);

        // 参加確定者のキャンセルなら、ゲストのキャンセル待ちを繰り上げ
        if ($prevStatus === 'submitted') {
            $guestModel->promoteFromWaitlist((int)$rec['event_id']);
        }

        Response::success([], 'キャンセルしました');
    }

    /**
     * ゲスト申込の再提出（内容変更）を確認済みにする
     */
    public function confirmGuestResubmit(array $params): void
    {
        $id = (int)$params['id'];
        (new EventGuestApplication())->confirmResubmission($id);
        Response::success([], '確認済みにしました');
    }

    /**
     * 会員申込の回答・備考を管理者が編集
     * PUT /api/event-applications/{id}/answers   body: { note, values:{field_id:val} }
     */
    public function updateMemberAnswers(array $params): void
    {
        $id       = (int)$params['id'];
        $appModel = new EventApplication();
        $app      = $appModel->findById($id);
        if (!$app) {
            Response::error('申込が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $body = Request::json();
        $note = trim($body['note'] ?? '') ?: null;
        $appModel->updateNote($id, $note);

        // 会員向け項目の回答を保存（送られた項目のみ）
        $values = $this->sanitizeFieldValues((int)$app['event_id'], 'member', $body['values'] ?? []);
        (new EventMemberFieldValue())->save($id, $values);

        Response::success([], '回答を更新しました');
    }

    /**
     * ゲスト申込の回答・備考を管理者が編集（再提出フラグは立てない）
     * PUT /api/event-guest-applications/{id}/answers   body: { note, values:{field_id:val} }
     */
    public function updateGuestAnswers(array $params): void
    {
        $id         = (int)$params['id'];
        $guestModel = new EventGuestApplication();
        $app        = $guestModel->find($id);
        if (!$app) {
            Response::error('申込が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $body   = Request::json();
        $note   = trim($body['note'] ?? '') ?: null;
        $values = $this->sanitizeFieldValues((int)$app['event_id'], 'guest', $body['values'] ?? []);

        // updateContent: 再活性化なし・再提出フラグなし（管理者編集のため）
        $guestModel->updateContent($id, $values, $note, false, false);
        Response::success([], '回答を更新しました');
    }

    /**
     * 送られた回答を、その企画の該当 audience 項目だけに絞り正規化する。
     * @return array [field_id => value]
     */
    private function sanitizeFieldValues(int $eventId, string $audience, $input): array
    {
        $fields = (new EventGuestField())->getByEventId($eventId, $audience);
        $in     = is_array($input) ? $input : [];
        $values = [];
        foreach ($fields as $f) {
            $fid = (int)$f['id'];
            $val = $in[$fid] ?? ($in[(string)$fid] ?? '');
            if (is_array($val)) {
                $val = implode(', ', array_map('strval', $val));
            }
            $values[$fid] = trim((string)$val);
        }
        return $values;
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
