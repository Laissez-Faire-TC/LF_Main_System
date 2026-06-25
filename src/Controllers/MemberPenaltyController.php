<?php
/**
 * 会員ペナルティ点 コントローラー（Admin限定）
 *   画面: /member-penalties
 *   API : /api/member-penalties/{id}            会員のペナルティ内訳
 *         /api/member-penalties/{id}/adjust     手動の加点・減点
 *
 * 「入金期限の遅れ」を検知してペナルティ点を集計する。
 * 自動分（合宿費・遠征費・物販の期限超過）は画面を開くたびに再集計し、
 * 手動分（Admin の加点・減点）は member_penalty_adjustments に蓄積する。
 *
 * 閲覧・操作は全権Adminのみ（活動ログ・幹部権限管理と同じ扱い）。
 */
class MemberPenaltyController
{
    private MemberPenalty $model;

    public function __construct()
    {
        Auth::requireAuth();
        if (!Auth::isAdmin()) {
            if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
                Response::error('ペナルティ点管理はAdminのみが利用できます', 403, 'FORBIDDEN');
            } else {
                http_response_code(403);
                echo '<!DOCTYPE html><meta charset="utf-8"><div style="max-width:480px;margin:4rem auto;font-family:sans-serif;text-align:center">'
                   . '<h2>アクセス権限がありません</h2><p>ペナルティ点管理はAdminのみが利用できます。</p>'
                   . '<p><a href="/dashboard">ダッシュボードに戻る</a></p></div>';
                exit;
            }
        }
        $this->model = new MemberPenalty();
    }

    /**
     * 管理画面
     */
    public function indexPage(array $params): void
    {
        $appConfig = require CONFIG_PATH . '/app.php';
        $appName   = $appConfig['name'];

        $penaltyConfig = require CONFIG_PATH . '/penalty.php';
        $rows          = $this->model->summaryAll();

        ob_start();
        include VIEWS_PATH . '/admin/member_penalties.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/main.php';
    }

    /**
     * 1会員の内訳（期限超過明細＋手動調整履歴）を返す JSON。
     */
    public function detail(array $params): void
    {
        $memberId = (int)($params['id'] ?? 0);
        if ($memberId <= 0) {
            Response::error('会員が指定されていません', 400, 'VALIDATION_ERROR');
            return;
        }

        $member = (new Member())->find($memberId);
        if (!$member) {
            Response::error('会員が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        Response::success([
            'member' => [
                'id'         => (int)$member['id'],
                'name'       => $member['name_kanji'] ?? '',
                'student_id' => $member['student_id'] ?? null,
            ],
            'detail' => $this->model->detail($memberId),
        ]);
    }

    /**
     * 手動の加点・減点。points は正で加点・負で減点（取り消し）。
     */
    public function adjust(array $params): void
    {
        $memberId = (int)($params['id'] ?? 0);
        $points   = (int)Request::get('points');
        $reason   = trim((string)(Request::get('reason') ?? ''));

        if ($memberId <= 0) {
            Response::error('会員が指定されていません', 400, 'VALIDATION_ERROR');
            return;
        }
        if ($points === 0) {
            Response::error('0以外の点数を指定してください', 400, 'VALIDATION_ERROR');
            return;
        }
        if ($reason === '') {
            Response::error('調整理由を入力してください', 400, 'VALIDATION_ERROR');
            return;
        }

        $member = (new Member())->find($memberId);
        if (!$member) {
            Response::error('会員が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        try {
            $this->model->addAdjustment($memberId, $points, $reason);
            Response::success([], 'ペナルティ点を調整しました');
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 400, 'ERROR');
        }
    }
}
