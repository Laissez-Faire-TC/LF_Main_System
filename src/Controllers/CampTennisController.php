<?php
/**
 * テニス班分けコントローラー（管理者用）
 * 団体戦チーム / 紅白戦チーム / 紅白戦対戦表
 */
class CampTennisController
{
    private CampTennisBattle $model;

    public function __construct()
    {
        Auth::requirePermission('camps');
        $this->model = new CampTennisBattle();
    }

    /**
     * 取得（テニス班分けデータ＋参加者）
     * GET /api/camps/{id}/tennis
     */
    public function show(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        if (!$campModel->find($campId)) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $data = $this->model->findByCampId($campId) ?? [
            'team_battle_teams' => [],
            'team_battle_rules' => '',
            'kohaku_teams'      => ['red' => [], 'white' => []],
            'kohaku_rules'      => '',
            'kohaku_matches'    => [],
        ];

        $data['participants'] = CampBookletController::loadEnrichedParticipants($campId);

        Response::success($data);
    }

    /**
     * 保存
     * PUT /api/camps/{id}/tennis
     */
    public function upsert(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        if (!$campModel->find($campId)) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $data = Request::only([
            'team_battle_teams', 'team_battle_rules',
            'kohaku_teams', 'kohaku_rules', 'kohaku_matches',
        ]);

        try {
            $this->model->upsert($campId, $data);
            $saved = $this->model->findByCampId($campId);
            Response::success($saved, 'テニス班分けを保存しました');
        } catch (Exception $e) {
            Response::error('保存に失敗しました: ' . $e->getMessage(), 500, 'SAVE_ERROR');
        }
    }
}
