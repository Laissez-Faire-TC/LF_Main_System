<?php
/**
 * 企画班分けコントローラー（管理者用）
 * 1合宿に複数の企画（班分け単位）を持ち、各企画で自動振り分け・制約・班割りを管理。
 */
class CampPlanController
{
    private CampPlanDivision $model;

    public function __construct()
    {
        Auth::requirePermission('camps');
        $this->model = new CampPlanDivision();
    }

    private function assertCamp(int $campId): bool
    {
        $campModel = new Camp();
        if (!$campModel->find($campId)) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
            return false;
        }
        return true;
    }

    private function assertDivision(int $divisionId): ?array
    {
        $division = $this->model->find($divisionId);
        if (!$division) {
            Response::error('企画が見つかりません', 404, 'NOT_FOUND');
            return null;
        }
        return $division;
    }

    // ── 企画一覧・参加者 ──

    /**
     * 企画一覧＋参加者
     * GET /api/camps/{id}/plans
     */
    public function index(array $params): void
    {
        $campId = (int)$params['id'];
        if (!$this->assertCamp($campId)) return;

        Response::success([
            'divisions'    => $this->model->getByCampId($campId),
            'participants' => CampBookletController::loadEnrichedParticipants($campId),
            'time_slots'   => (new TimeSlot())->getByCampId($campId),
        ]);
    }

    /**
     * 企画作成
     * POST /api/camps/{id}/plans  body: { name, time_slot_id }
     */
    public function store(array $params): void
    {
        $campId = (int)$params['id'];
        if (!$this->assertCamp($campId)) return;

        $name = trim((string)Request::get('name', ''));
        if ($name === '') $name = '新しい企画';

        $timeSlotId = Request::get('time_slot_id');
        $timeSlotId = ($timeSlotId === null || $timeSlotId === '') ? null : (int)$timeSlotId;

        $id = $this->model->create($campId, $name, $timeSlotId);
        Response::success($this->model->find($id), '企画を作成しました');
    }

    /**
     * 企画名・開催タイミング変更
     * PUT /api/plans/{id}  body: { name?, time_slot_id? }
     * time_slot_id を変更するとその時点の参加者で自動同期する。
     */
    public function update(array $params): void
    {
        $id = (int)$params['id'];
        if (!$this->assertDivision($id)) return;

        if (Request::has('name')) {
            $name = trim((string)Request::get('name', ''));
            if ($name === '') {
                Response::error('企画名を入力してください', 422, 'INVALID');
                return;
            }
            $this->model->rename($id, $name);
        }

        if (Request::has('time_slot_id')) {
            $timeSlotId = Request::get('time_slot_id');
            $timeSlotId = ($timeSlotId === null || $timeSlotId === '') ? null : (int)$timeSlotId;
            $this->model->setTimeSlot($id, $timeSlotId);   // 参加者を自動同期
        }

        Response::success([
            'division' => $this->model->find($id),
            'members'  => $this->model->getMembers($id),
        ], '企画を更新しました');
    }

    /**
     * 企画削除
     * DELETE /api/plans/{id}
     */
    public function destroy(array $params): void
    {
        $id = (int)$params['id'];
        if (!$this->assertDivision($id)) return;

        $this->model->delete($id);
        Response::success([], '企画を削除しました');
    }

    // ── 班分け詳細（メンバー＋制約） ──

    /**
     * 企画の班分け詳細（メンバー＋制約）
     * GET /api/plans/{id}
     */
    public function show(array $params): void
    {
        $id = (int)$params['id'];
        $division = $this->assertDivision($id);
        if (!$division) return;

        Response::success([
            'division'     => $division,
            'members'      => $this->model->getMembers($id),
            'constraints'  => $this->model->getConstraints($id),
            'participants' => CampBookletController::loadEnrichedParticipants((int)$division['camp_id']),
        ]);
    }

    /**
     * 開催タイミングの参加者で再同期する（参加者情報が更新された場合に使用）
     * POST /api/plans/{id}/resync
     */
    public function resyncMembers(array $params): void
    {
        $id = (int)$params['id'];
        if (!$this->assertDivision($id)) return;

        $this->model->syncMembersFromTimeSlot($id);
        Response::success(['members' => $this->model->getMembers($id)], '参加者を最新の状態に更新しました');
    }

    /**
     * 班割り保存
     * POST /api/plans/{id}/teams  body: { assignments: { division_member_id: team_no|null } }
     */
    public function saveTeams(array $params): void
    {
        $id = (int)$params['id'];
        if (!$this->assertDivision($id)) return;

        $assignments = Request::get('assignments', []);
        if (!is_array($assignments)) $assignments = [];

        $this->model->saveTeamAssignments($id, $assignments);
        Response::success([], '班割りを保存しました');
    }

    // ── 制約 ──

    /**
     * 制約追加
     * POST /api/plans/{id}/constraints  body: { participant_a_id, participant_b_id, type }
     */
    public function addConstraint(array $params): void
    {
        $id = (int)$params['id'];
        if (!$this->assertDivision($id)) return;

        $a    = (int)Request::get('participant_a_id');
        $b    = (int)Request::get('participant_b_id');
        $type = Request::get('type');

        if ($a <= 0 || $b <= 0 || $a === $b) {
            Response::error('2名の参加者を選択してください', 422, 'INVALID');
            return;
        }
        if (!in_array($type, ['together', 'apart'], true)) {
            Response::error('種別が不正です', 422, 'INVALID');
            return;
        }

        $this->model->addConstraint($id, $a, $b, $type);
        Response::success(['constraints' => $this->model->getConstraints($id)], '制約を追加しました');
    }

    /**
     * 制約削除
     * DELETE /api/plans/{id}/constraints/{cid}
     */
    public function deleteConstraint(array $params): void
    {
        $id  = (int)$params['id'];
        if (!$this->assertDivision($id)) return;

        $this->model->deleteConstraint((int)$params['cid']);
        Response::success([], '制約を削除しました');
    }
}
