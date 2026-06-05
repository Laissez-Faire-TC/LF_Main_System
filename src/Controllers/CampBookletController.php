<?php
/**
 * 合宿しおりコントローラー（管理者用）
 */
class CampBookletController
{
    private CampBooklet $model;

    public function __construct()
    {
        Auth::requirePermission('camps');
        $this->model = new CampBooklet();
    }

    /**
     * しおり取得
     * GET /api/camps/{id}/booklet
     */
    public function show(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        if (!$campModel->find($campId)) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $booklet = $this->model->findByCampId($campId);

        if (!$booklet) {
            Response::success([
                'camp_id'              => $campId,
                'meeting_time'         => '8:40',
                'meeting_place'        => '新宿センタービル（地上）',
                'meeting_note'         => null,
                'return_place'         => null,
                'items_to_bring'       => [],
                'schedules'            => [],
                'team_battle_teams'    => [],
                'team_battle_rules'    => '',
                'team_battle_schedule' => [],
                'kohaku_teams'         => ['red' => [], 'white' => []],
                'kohaku_rules'         => '',
                'kohaku_matches'       => [],
                'night_rec_groups'     => [],
                'room_assignments'     => [],
                'floor_plan_image'     => null,
                'meal_duty'            => [],
                'is_public'            => 0,
                'public_token'         => null,
            ]);
            return;
        }

        Response::success($booklet);
    }

    /**
     * 日程設定（time_slots）から初期スケジュールを生成して返す
     * GET /api/camps/{id}/booklet/import-schedule
     */
    public function importSchedule(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        $camp      = $campModel->find($campId);
        if (!$camp) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $timeSlotModel = new TimeSlot();
        $slots         = $timeSlotModel->getByCampId($campId);

        // slot_type → 日本語ラベル
        $slotLabels = [
            'outbound'  => 'バス移動（往路）',
            'morning'   => '午前練習',
            'afternoon' => '午後練習',
            'banquet'   => '宴会',
            'return'    => 'バス移動（復路）',
        ];

        // day_number ごとにグループ化
        $days = [];
        foreach ($slots as $slot) {
            $day = (int)$slot['day_number'];
            if (!isset($days[$day])) {
                $days[$day] = [];
            }
            $days[$day][] = $slot;
        }

        $startDate = $camp['start_date'];
        $nights    = (int)$camp['nights'];
        $totalDays = $nights + 1;

        $weekDayJa = ['日', '月', '火', '水', '木', '金', '土'];

        $schedules = [];
        for ($d = 1; $d <= $totalDays; $d++) {
            $dateStr = date('n月j日', strtotime($startDate . ' +' . ($d - 1) . ' days'));
            $dow     = (int)date('w', strtotime($startDate . ' +' . ($d - 1) . ' days'));
            $label   = "{$d}日目の予定（{$dateStr}（{$weekDayJa[$dow]}））";

            $rows = [];

            // 1日目固定の集合行
            if ($d === 1) {
                $rows[] = ['time' => '8:40',  'activity' => '集合（新宿センタービル地上）', 'note' => ''];
                $rows[] = ['time' => '9:00',  'activity' => '出発', 'note' => ''];
            }

            // タイムスロットから行を生成
            $daySlots = $days[$d] ?? [];
            foreach ($daySlots as $slot) {
                $desc = $slot['description'] ?: ($slotLabels[$slot['slot_type']] ?? $slot['slot_type']);
                $isGym = ($slot['activity_type'] ?? '') === 'gym';
                switch ($slot['slot_type']) {
                    case 'outbound':
                        $rows[] = ['time' => '12:00', 'activity' => '現地到着・昼食・準備', 'note' => ''];
                        $rows[] = ['time' => '13:00', 'activity' => $isGym ? '体育館企画' : '練習', 'note' => ''];
                        break;
                    case 'morning':
                        if ($d > 1) {
                            $rows[] = ['time' => '7:00',  'activity' => '朝練（自由）', 'note' => ''];
                            $rows[] = ['time' => '7:45',  'activity' => '朝食準備', 'note' => '（配膳当番：）'];
                            $rows[] = ['time' => '8:00',  'activity' => '朝食', 'note' => ''];
                            $rows[] = ['time' => '9:00',  'activity' => $isGym ? '体育館企画' : '練習', 'note' => ''];
                        }
                        break;
                    case 'afternoon':
                        $rows[] = ['time' => '12:15', 'activity' => '昼食準備', 'note' => '（配膳当番：）'];
                        $rows[] = ['time' => '12:30', 'activity' => '昼食', 'note' => ''];
                        $rows[] = ['time' => '13:30', 'activity' => $isGym ? '体育館企画' : '練習', 'note' => ''];
                        $rows[] = ['time' => '17:00', 'activity' => $isGym ? 'お風呂' : '自主練またはお風呂', 'note' => ''];
                        $rows[] = ['time' => '17:45', 'activity' => '夕食準備', 'note' => '（配膳当番：）'];
                        $rows[] = ['time' => '18:00', 'activity' => '夕食・お風呂など', 'note' => ''];
                        break;
                    case 'return':
                        $rows[] = ['time' => '13:45', 'activity' => 'バス乗車', 'note' => ''];
                        $rows[] = ['time' => '14:00', 'activity' => '出発', 'note' => ''];
                        $rows[] = ['time' => '17:00', 'activity' => '帰着', 'note' => ''];
                        break;
                    case 'banquet':
                        $rows[] = ['time' => '18:00', 'activity' => '宴会', 'note' => ''];
                        break;
                }
            }

            // 重複排除（同timeで同activityが連続した場合）
            $unique = [];
            foreach ($rows as $r) {
                $key = $r['time'] . '|' . $r['activity'];
                if (!isset($unique[$key])) {
                    $unique[$key] = $r;
                }
            }

            $schedules[] = [
                'label' => $label,
                'rows'  => array_values($unique),
            ];
        }

        Response::success(['schedules' => $schedules]);
    }

    /**
     * 参加者一覧（しおり編集用）
     * GET /api/camps/{id}/booklet/participants
     */
    public function participants(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        if (!$campModel->find($campId)) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        Response::success(['participants' => self::loadEnrichedParticipants($campId)]);
    }

    /**
     * 合宿参加者を学科（学部）付きで取得。
     * 学科は participants → camp_applications → members 経由で補完。
     * テニス班分け・企画班分けタブからも利用する共通処理。
     */
    public static function loadEnrichedParticipants(int $campId): array
    {
        $db = Database::getInstance();
        $all = $db->fetchAll(
            "SELECT p.id, p.name, p.grade, p.gender,
                    m.faculty, m.department, m.name_kana
             FROM participants p
             LEFT JOIN camp_applications ca ON ca.participant_id = p.id
             LEFT JOIN members m ON m.id = ca.member_id
             WHERE p.camp_id = ?
             GROUP BY p.id",
            [$campId]
        );

        $gradeLabels = [0 => 'OB/OG', 1 => '1年', 2 => '2年', 3 => '3年', 4 => '4年', 5 => '5年'];

        $result = array_map(fn($p) => [
            'id'         => (int)$p['id'],
            'name'       => $p['name'],
            'grade'      => $p['grade'] !== null ? (int)$p['grade'] : null,
            'gender'     => $p['gender'],
            'faculty'    => $p['faculty'] ?? null,
            'department' => $p['department'] ?? null,
            'name_kana'  => $p['name_kana'] ?? null,
            'label'      => ($gradeLabels[$p['grade']] ?? '') . ($p['gender'] === 'female' ? '女' : '男') . ' ' . $p['name'],
        ], $all);

        // 学年→性別→名前 でソート
        usort($result, function($a, $b) {
            if ($a['grade'] !== $b['grade']) return ($a['grade'] ?? 99) <=> ($b['grade'] ?? 99);
            if ($a['gender'] !== $b['gender']) return strcmp($a['gender'] ?? '', $b['gender'] ?? '');
            return strcmp($a['name'], $b['name']);
        });

        return $result;
    }

    /**
     * しおり保存（新規作成 or 更新）
     * PUT /api/camps/{id}/booklet
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
            'meeting_time', 'meeting_place', 'meeting_note', 'return_place',
            'items_to_bring', 'schedules',
            'team_battle_teams', 'team_battle_rules', 'team_battle_schedule',
            'kohaku_teams', 'kohaku_rules', 'kohaku_matches',
            'night_rec_groups', 'room_assignments',
            'floor_plan_image', 'meal_duty',
            'is_public',
        ]);

        try {
            $this->model->upsert($campId, $data);
            $booklet = $this->model->findByCampId($campId);
            Response::success($booklet, 'しおりを保存しました');
        } catch (Exception $e) {
            Response::error('保存に失敗しました: ' . $e->getMessage(), 500, 'SAVE_ERROR');
        }
    }

    /**
     * 公開URL発行
     * POST /api/camps/{id}/booklet/token
     */
    public function generateToken(array $params): void
    {
        $campId = (int)$params['id'];

        $campModel = new Camp();
        if (!$campModel->find($campId)) {
            Response::error('合宿が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        try {
            $token = $this->model->generateToken($campId);

            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
            $baseUrl .= '://' . $_SERVER['HTTP_HOST'];

            // 最新のしおりデータを返す（is_public が更新されているため）
            $booklet = $this->model->findByCampId($campId);

            Response::success([
                'token'   => $token,
                'url'     => $baseUrl . '/booklet/' . $token,
                'booklet' => $booklet,
            ], '公開URLを発行しました');
        } catch (Exception $e) {
            Response::error('URL発行に失敗しました: ' . $e->getMessage(), 500, 'TOKEN_ERROR');
        }
    }
}
