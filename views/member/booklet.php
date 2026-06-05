<?php
/**
 * 合宿しおり閲覧ビュー
 * $booklet, $camp, $myName, $isLoggedIn が渡される
 */

// 自分の名前が含まれるかチェックするヘルパー
function nameMatches(string $name, string $myName): bool {
    return $myName !== '' && mb_strpos($name, $myName) !== false;
}
?>

<style>
.booklet-hero {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1.75rem 1.5rem;
    margin-bottom: 1.5rem;
}
.booklet-hero .hero-label {
    font-size: .75rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #9ca3af;
    margin-bottom: .4rem;
}
.booklet-hero h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: .25rem;
}
.booklet-hero .hero-date {
    font-size: .95rem;
    color: #6b7280;
}
.booklet-section {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    margin-bottom: 1rem;
    overflow: hidden;
}
.booklet-section-header {
    padding: .65rem 1rem;
    font-weight: 600;
    font-size: .9rem;
    color: #111827;
    border-bottom: 1px solid #e5e7eb;
    border-left: 3px solid #111827;
    display: flex;
    align-items: center;
    gap: .4rem;
    background: #f9fafb;
}
.booklet-section-header.collapsible {
    cursor: pointer;
    user-select: none;
}
.booklet-section-header.collapsible:hover {
    background: #f3f4f6;
}
.collapse-chevron {
    margin-left: auto;
    font-size: .7rem;
    color: #9ca3af;
    transition: transform .2s;
}
.booklet-section-header.collapsible.collapsed .collapse-chevron {
    transform: rotate(-90deg);
}
.booklet-section-body {
    padding: 1rem;
}
.schedule-table th {
    background: #f3f4f6;
    color: #374151;
    font-weight: 600;
}
.schedule-table td, .schedule-table th {
    padding: .4rem .75rem;
    border: 1px solid #e5e7eb;
    vertical-align: middle;
}
.highlight-me {
    background: #eff6ff !important;
    font-weight: 700;
}
.team-card {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    overflow: hidden;
    height: 100%;
}
.team-card-header {
    background: #f3f4f6;
    padding: .4rem .75rem;
    font-weight: 600;
    font-size: .85rem;
    color: #374151;
}
.team-card-header.my-team {
    background: #eff6ff;
    border-left: 3px solid #3b82f6;
    color: #1d4ed8;
}
.team-member {
    padding: .25rem .75rem;
    font-size: .88rem;
    border-top: 1px solid #f3f4f6;
    color: #374151;
}
.team-member.me {
    background: #eff6ff;
    font-weight: 700;
    color: #1d4ed8;
}
.team-leader {
    background: #f0fdf4;
    font-size: .8rem;
    color: #166534;
    padding: .2rem .75rem;
}
.red-team { background: #fff1f2; }
.white-team { background: #f8fafc; }
.kohaku-header-red { background: #dc2626; color: white; font-weight: 600; }
.kohaku-header-white { background: #374151; color: white; font-weight: 600; }
.group-card {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    overflow: hidden;
    height: 100%;
}
.group-card-header {
    background: #f3f4f6;
    padding: .4rem .75rem;
    font-weight: 600;
    font-size: .85rem;
    color: #374151;
}
.group-card-header.my-group {
    background: #eff6ff;
    border-left: 3px solid #3b82f6;
    color: #1d4ed8;
}
.items-list li {
    padding: .35rem 0;
    border-bottom: 1px solid #f3f4f6;
    font-size: .93rem;
    color: #374151;
}
.items-list li.highlight { font-weight: 700; color: #b91c1c; }
.meal-table th {
    background: #f3f4f6;
    color: #374151;
    font-weight: 600;
}
.meal-table th, .meal-table td {
    padding: .4rem .6rem;
    border: 1px solid #e5e7eb;
    text-align: center;
    font-size: .88rem;
}
.room-table th {
    background: #f3f4f6;
    color: #374151;
    font-weight: 600;
    text-align: center;
}
.room-table td, .room-table th {
    padding: .35rem .6rem;
    border: 1px solid #e5e7eb;
    font-size: .88rem;
}
.match-table th, .match-table td {
    padding: .3rem .5rem;
    border: 1px solid #e5e7eb;
    font-size: .8rem;
    text-align: center;
    vertical-align: middle;
}
.match-table th { background: #f3f4f6; color: #374151; font-weight: 600; }
.match-row-highlight { background: #eff6ff; }
.nav-tabs .nav-link { color: #6b7280; font-size: .9rem; }
.nav-tabs .nav-link.active { font-weight: 600; color: #111827; }
.nav-tabs .nav-link:hover { color: #374151; }
</style>

<!-- ヘッダー -->
<div class="booklet-hero mt-3">
    <?php
        $start = $camp['start_date'] ?? null;
        $end   = $camp['end_date']   ?? null;
    ?>
    <div class="hero-label">Laissez-Faire T.C. 合宿しおり</div>
    <h2><?= htmlspecialchars($camp['name'] ?? 'CAMP') ?></h2>
    <?php if ($start && $end): ?>
    <div class="hero-date">
        <?= date('Y年n月j日', strtotime($start)) ?> &ndash; <?= date('n月j日', strtotime($end)) ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($isLoggedIn && $myName !== ''): ?>
<div class="d-flex align-items-center gap-2 mb-3 px-1" style="font-size:.85rem; color:#3b82f6;">
    <i class="bi bi-person-check-fill"></i>
    <span><?= htmlspecialchars($myName) ?> さんの所属チーム・班をハイライト表示しています</span>
</div>
<?php endif; ?>

<!-- タブナビゲーション -->
<ul class="nav nav-tabs mb-3" id="bookletTabs" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-meeting">集合</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-items">持ち物</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-schedule">日程</a></li>
    <?php if (!empty($booklet['team_battle_teams'])): ?>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-team">団体戦</a></li>
    <?php endif; ?>
    <?php if (!empty($booklet['kohaku_teams'])): ?>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-kohaku">紅白戦</a></li>
    <?php endif; ?>
    <?php foreach (($plans ?? []) as $pi => $plan): ?>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-plan-<?= $pi ?>"><?= htmlspecialchars($plan['name']) ?></a></li>
    <?php endforeach; ?>
    <?php if (!empty($booklet['room_assignments'])): ?>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-room">部屋割り</a></li>
    <?php endif; ?>
</ul>

<div class="tab-content">

<!-- ========== 集合情報 ========== -->
<div class="tab-pane fade show active" id="tab-meeting">
    <div class="booklet-section">
        <div class="booklet-section-header"><i class="bi bi-clock"></i> 集合情報</div>
        <div class="booklet-section-body">
            <div class="mb-3">
                <?php if ($start): ?>
                <div class="fs-5 fw-bold mb-1" style="color:#111827;"><?= date('n月j日（', strtotime($start)) ?><?= ['日','月','火','水','木','金','土'][date('w', strtotime($start))] ?>）<?= htmlspecialchars($booklet['meeting_time'] ?? '8:40') ?> 集合</div>
                <?php endif; ?>
                <div style="font-size:.85rem; color:#6b7280;">時間厳守でお願いします</div>
            </div>
            <div class="mb-3" style="font-size:.93rem;">
                <span class="text-muted me-1">集合場所</span>
                <span class="fw-500"><?= htmlspecialchars($booklet['meeting_place'] ?? '新宿センタービル（地上）') ?></span>
            </div>
            <?php if (!empty($booklet['meeting_note'])): ?>
            <div class="p-3 mb-2 rounded" style="background:#f9fafb; border:1px solid #e5e7eb; font-size:.88rem; color:#374151;"><?= nl2br(htmlspecialchars($booklet['meeting_note'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($booklet['return_place'])): ?>
            <div class="p-3 mb-2 rounded" style="background:#f9fafb; border:1px solid #e5e7eb; font-size:.88rem; color:#374151;">
                <i class="bi bi-arrow-return-right me-1 text-muted"></i>帰りのバスの到着場所：<?= htmlspecialchars($booklet['return_place']) ?>
            </div>
            <?php endif; ?>

            <!-- Google マップ埋め込み（新宿センタービルデフォルト） -->
            <div class="ratio ratio-16x9 mt-3" style="border-radius:8px; overflow:hidden;">
                <iframe
                    src="https://maps.google.com/maps?q=<?= urlencode($booklet['meeting_place'] ?? '新宿センタービル') ?>&output=embed"
                    style="border:0;" allowfullscreen loading="lazy">
                </iframe>
            </div>
        </div>
    </div>
</div>

<!-- ========== 持ち物 ========== -->
<div class="tab-pane fade" id="tab-items">
    <div class="booklet-section">
        <div class="booklet-section-header"><i class="bi bi-bag-check"></i> 持ち物</div>
        <div class="booklet-section-body">
            <?php if (!empty($booklet['items_to_bring'])): ?>
            <ul class="items-list list-unstyled mb-0">
                <?php foreach ($booklet['items_to_bring'] as $item): ?>
                <li class="<?= !empty($item['highlight']) ? 'highlight' : '' ?>">
                    <?= !empty($item['highlight']) ? '<i class="bi bi-exclamation-circle-fill text-danger me-1"></i>' : '・' ?>
                    <?= htmlspecialchars($item['text'] ?? '') ?>
                    <?php if (!empty($item['note'])): ?>
                    <span class="text-muted small">（<?= htmlspecialchars($item['note']) ?>）</span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p class="text-muted text-center">持ち物リストはまだ登録されていません</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========== タイムスケジュール ========== -->
<div class="tab-pane fade" id="tab-schedule">
    <?php if (!empty($booklet['schedules'])): ?>
        <?php foreach ($booklet['schedules'] as $dayIdx => $day): ?>
        <?php $colId = 'scheduleDay' . $dayIdx; ?>
        <div class="booklet-section">
            <div class="booklet-section-header collapsible"
                 data-bs-toggle="collapse"
                 data-bs-target="#<?= $colId ?>"
                 aria-expanded="true">
                <i class="bi bi-calendar3"></i>
                <?= htmlspecialchars($day['label'] ?? ($dayIdx + 1) . '日目の予定') ?>
                <span class="collapse-chevron">▼</span>
            </div>
            <div class="collapse show" id="<?= $colId ?>">
                <div class="booklet-section-body p-0">
                    <table class="table table-sm schedule-table mb-0">
                        <thead>
                            <tr><th style="width:80px;">時間</th><th>予定</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($day['rows'] ?? [] as $row): ?>
                            <tr class="<?= !empty($row['highlight']) ? 'table-warning' : '' ?>">
                                <td class="fw-bold text-nowrap"><?= htmlspecialchars($row['time'] ?? '') ?></td>
                                <td>
                                    <?= htmlspecialchars($row['activity'] ?? '') ?>
                                    <?php if (!empty($row['note'])): ?>
                                    <div class="small text-danger">（<?= htmlspecialchars($row['note']) ?>）</div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
    <div class="booklet-section">
        <div class="booklet-section-body text-muted text-center">タイムスケジュールはまだ登録されていません</div>
    </div>
    <?php endif; ?>

    <?php if (!empty($booklet['meal_duty'])): ?>
    <div class="booklet-section">
        <div class="booklet-section-header"><i class="bi bi-cup-hot"></i> 配膳当番</div>
        <div class="booklet-section-body p-0">
            <?php
            // meal_duty: [{meal:"朝", days:[{day:"2日目", group:"1男405・406号室"}]}]
            // テーブルに変換: 列=日、行=食事
            $allDays = [];
            foreach ($booklet['meal_duty'] as $md) {
                foreach ($md['days'] ?? [] as $d) {
                    if (!in_array($d['day'], $allDays)) $allDays[] = $d['day'];
                }
            }
            ?>
            <table class="table table-sm meal-table mb-0">
                <thead>
                    <tr>
                        <th></th>
                        <?php foreach ($allDays as $dayLabel): ?>
                        <th><?= htmlspecialchars($dayLabel) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($booklet['meal_duty'] as $md): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($md['meal'] ?? '') ?></td>
                        <?php
                        $dayMap = [];
                        foreach ($md['days'] ?? [] as $d) {
                            $dayMap[$d['day']] = $d['group'] ?? '';
                        }
                        foreach ($allDays as $dayLabel):
                            $g = $dayMap[$dayLabel] ?? '—';
                        ?>
                        <td class="small"><?= htmlspecialchars($g) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ========== 団体戦 ========== -->
<?php if (!empty($booklet['team_battle_teams'])): ?>
<div class="tab-pane fade" id="tab-team">
    <div class="booklet-section">
        <div class="booklet-section-header"><i class="bi bi-trophy"></i> 団体戦チーム分け</div>
        <div class="booklet-section-body">
            <?php
            // チームを4列で表示
            $teams = $booklet['team_battle_teams'];
            $cols  = 4;
            $rows  = array_chunk($teams, $cols);
            foreach ($rows as $rowTeams):
            ?>
            <div class="row g-2 mb-2">
                <?php foreach ($rowTeams as $team):
                    $isMyTeam = false;
                    foreach ($team['members'] ?? [] as $m) {
                        if (nameMatches($m['name'] ?? '', $myName)) { $isMyTeam = true; break; }
                    }
                ?>
                <div class="col-6 col-md-3">
                    <div class="team-card <?= $isMyTeam ? 'border-warning border-2' : '' ?>">
                        <div class="team-card-header <?= $isMyTeam ? 'my-team' : '' ?>">
                            <?= htmlspecialchars($team['team_name'] ?? '') ?>
                            <?= $isMyTeam ? ' <i class="bi bi-star-fill text-warning"></i>' : '' ?>
                        </div>
                        <?php foreach ($team['members'] ?? [] as $m):
                            $isMe = nameMatches($m['name'] ?? '', $myName);
                        ?>
                        <div class="team-member <?= $isMe ? 'me' : '' ?> <?= !empty($m['is_leader']) ? '' : '' ?>">
                            <?php if (!empty($m['is_leader'])): ?>
                            <span class="badge bg-primary me-1" style="font-size:.65rem;">L</span>
                            <?php endif; ?>
                            <?= htmlspecialchars($m['name'] ?? '') ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
            <?php if (!empty($booklet['team_battle_rules'])): ?>
            <div class="mt-3 pt-3 border-top">
                <h6 class="fw-bold">団体戦ルール</h6>
                <div class="small"><?= nl2br(htmlspecialchars($booklet['team_battle_rules'])) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($booklet['team_battle_schedule'])): ?>
    <div class="booklet-section">
        <div class="booklet-section-header"><i class="bi bi-calendar3"></i> 団体戦タイムスケジュール</div>
        <div class="booklet-section-body p-0">
            <table class="table table-sm schedule-table mb-0">
                <thead>
                    <tr>
                        <th>時間</th>
                        <?php
                        $maxCourts = 0;
                        foreach ($booklet['team_battle_schedule'] as $ts) {
                            $maxCourts = max($maxCourts, count($ts['courts'] ?? []));
                        }
                        for ($ci = 1; $ci <= $maxCourts; $ci++): ?>
                        <th><?= $ci ?>面</th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($booklet['team_battle_schedule'] as $ts): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($ts['time'] ?? '') ?>〜</td>
                        <?php foreach ($ts['courts'] ?? [] as $court): ?>
                        <td><?= htmlspecialchars($court) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ========== 紅白戦 ========== -->
<?php if (!empty($booklet['kohaku_teams'])): ?>
<div class="tab-pane fade" id="tab-kohaku">
    <?php
    $redMembers   = $booklet['kohaku_teams']['red']   ?? [];
    $whiteMembers = $booklet['kohaku_teams']['white'] ?? [];
    $myTeamColor  = '';
    foreach ($redMembers   as $m) { if (nameMatches($m['name'] ?? '', $myName)) { $myTeamColor = 'red';   break; } }
    foreach ($whiteMembers as $m) { if (nameMatches($m['name'] ?? '', $myName)) { $myTeamColor = 'white'; break; } }
    ?>
    <?php
    // 性別で分類
    $genderMap    = $genderMap ?? [];
    $redMales     = array_filter($redMembers,   fn($m) => ($genderMap[$m['name'] ?? ''] ?? '') !== 'female');
    $redFemales   = array_filter($redMembers,   fn($m) => ($genderMap[$m['name'] ?? ''] ?? '') === 'female');
    $whiteMales   = array_filter($whiteMembers, fn($m) => ($genderMap[$m['name'] ?? ''] ?? '') !== 'female');
    $whiteFemales = array_filter($whiteMembers, fn($m) => ($genderMap[$m['name'] ?? ''] ?? '') === 'female');
    ?>
    <div class="booklet-section">
        <div class="booklet-section-header"><i class="bi bi-flag"></i> 紅白戦チーム分け</div>
        <div class="booklet-section-body">
            <!-- 赤組 -->
            <div class="mb-3">
                <div class="kohaku-header-red text-center py-1 fw-bold rounded-top mb-0">
                    赤組 <?= $myTeamColor === 'red' ? '<i class="bi bi-star-fill text-warning ms-1"></i>' : '' ?>
                </div>
                <div class="row g-0 border border-top-0 rounded-bottom overflow-hidden">
                    <div class="col-6 border-end">
                        <div class="text-muted small fw-bold px-2 pt-1">男子</div>
                        <?php foreach ($redMales as $m):
                            $isMe = nameMatches($m['name'] ?? '', $myName); ?>
                        <div class="red-team team-member <?= $isMe ? 'me' : '' ?>"><?= htmlspecialchars($m['name'] ?? '') ?></div>
                        <?php endforeach; ?>
                        <?php if (!$redMales): ?><div class="team-member text-muted">—</div><?php endif; ?>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small fw-bold px-2 pt-1">女子</div>
                        <?php foreach ($redFemales as $m):
                            $isMe = nameMatches($m['name'] ?? '', $myName); ?>
                        <div class="red-team team-member <?= $isMe ? 'me' : '' ?>"><?= htmlspecialchars($m['name'] ?? '') ?></div>
                        <?php endforeach; ?>
                        <?php if (!$redFemales): ?><div class="team-member text-muted">—</div><?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- 白組 -->
            <div class="mb-1">
                <div class="kohaku-header-white text-center py-1 fw-bold rounded-top mb-0">
                    白組 <?= $myTeamColor === 'white' ? '<i class="bi bi-star-fill text-warning ms-1"></i>' : '' ?>
                </div>
                <div class="row g-0 border border-top-0 rounded-bottom overflow-hidden">
                    <div class="col-6 border-end">
                        <div class="text-muted small fw-bold px-2 pt-1">男子</div>
                        <?php foreach ($whiteMales as $m):
                            $isMe = nameMatches($m['name'] ?? '', $myName); ?>
                        <div class="white-team team-member <?= $isMe ? 'me' : '' ?>"><?= htmlspecialchars($m['name'] ?? '') ?></div>
                        <?php endforeach; ?>
                        <?php if (!$whiteMales): ?><div class="team-member text-muted">—</div><?php endif; ?>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small fw-bold px-2 pt-1">女子</div>
                        <?php foreach ($whiteFemales as $m):
                            $isMe = nameMatches($m['name'] ?? '', $myName); ?>
                        <div class="white-team team-member <?= $isMe ? 'me' : '' ?>"><?= htmlspecialchars($m['name'] ?? '') ?></div>
                        <?php endforeach; ?>
                        <?php if (!$whiteFemales): ?><div class="team-member text-muted">—</div><?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if (!empty($booklet['kohaku_rules'])): ?>
            <div class="mt-3 pt-3 border-top">
                <h6 class="fw-bold">紅白戦ルール</h6>
                <div class="small"><?= nl2br(htmlspecialchars($booklet['kohaku_rules'])) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($booklet['kohaku_matches'])): ?>
    <div class="booklet-section">
        <div class="booklet-section-header"><i class="bi bi-table"></i> 紅白戦対戦表</div>
        <div class="booklet-section-body p-0 overflow-auto">
            <?php foreach ($booklet['kohaku_matches'] as $round): ?>
            <div style="padding:.5rem 1rem; border-bottom:1px solid #e5e7eb; font-weight:600; font-size:.85rem; color:#374151;">
                <?= htmlspecialchars($round['round'] ?? '') ?>
            </div>
            <?php if (!empty($round['courts'])): ?>
            <table class="table table-sm match-table mb-0">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>種別</th>
                        <th colspan="2" style="color:#dc2626;">赤組</th>
                        <th colspan="2">白組</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($round['courts'] as $ci => $c):
                        $isMe = nameMatches($c['red1'] ?? '', $myName)
                             || nameMatches($c['red2'] ?? '', $myName)
                             || nameMatches($c['white1'] ?? '', $myName)
                             || nameMatches($c['white2'] ?? '', $myName);
                    ?>
                    <tr class="<?= $isMe ? 'match-row-highlight' : '' ?>">
                        <td class="text-muted"><?= $ci + 1 ?></td>
                        <td class="text-nowrap small"><?= htmlspecialchars($c['type'] ?? '') ?></td>
                        <td class="<?= nameMatches($c['red1'] ?? '', $myName) ? 'fw-bold' : '' ?>"><?= htmlspecialchars($c['red1'] ?? '') ?></td>
                        <td class="<?= nameMatches($c['red2'] ?? '', $myName) ? 'fw-bold' : '' ?>"><?= htmlspecialchars($c['red2'] ?? '') ?></td>
                        <td class="<?= nameMatches($c['white1'] ?? '', $myName) ? 'fw-bold' : '' ?>"><?= htmlspecialchars($c['white1'] ?? '') ?></td>
                        <td class="<?= nameMatches($c['white2'] ?? '', $myName) ? 'fw-bold' : '' ?>"><?= htmlspecialchars($c['white2'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ========== 企画班分け（複数企画） ========== -->
<?php foreach (($plans ?? []) as $pi => $plan): ?>
<div class="tab-pane fade" id="tab-plan-<?= $pi ?>">
    <div class="booklet-section">
        <div class="booklet-section-header"><i class="bi bi-people"></i> <?= htmlspecialchars($plan['name']) ?><?= !empty($plan['timing']) ? ' <span class="badge bg-light text-dark">' . htmlspecialchars($plan['timing']) . '</span>' : '' ?></div>
        <div class="booklet-section-body">
            <?php
            $groups = $plan['groups'];
            $cols   = 4;
            $groupRows = array_chunk($groups, $cols);
            foreach ($groupRows as $groupRow):
            ?>
            <div class="row g-2 mb-2">
                <?php foreach ($groupRow as $group):
                    $isMyGroup = false;
                    foreach ($group['members'] ?? [] as $m) {
                        if (nameMatches($m['name'] ?? '', $myName)) { $isMyGroup = true; break; }
                    }
                ?>
                <div class="col-6 col-md-3">
                    <div class="group-card <?= $isMyGroup ? 'border-warning border-2' : '' ?>">
                        <div class="group-card-header <?= $isMyGroup ? 'my-group' : '' ?>">
                            <?= htmlspecialchars($group['group_name'] ?? '') ?>
                            <?= $isMyGroup ? ' <i class="bi bi-star-fill text-warning"></i>' : '' ?>
                        </div>
                        <?php foreach ($group['members'] ?? [] as $m):
                            $isMe = nameMatches($m['name'] ?? '', $myName);
                        ?>
                        <div class="team-member <?= $isMe ? 'me' : '' ?>">
                            <?= htmlspecialchars($m['name'] ?? '') ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- ========== 部屋割り ========== -->
<?php if (!empty($booklet['room_assignments'])): ?>
<div class="tab-pane fade" id="tab-room">
    <div class="booklet-section">
        <div class="booklet-section-header"><i class="bi bi-door-open"></i> 部屋割り</div>
        <div class="booklet-section-body p-0">
            <table class="table table-sm room-table mb-0">
                <thead>
                    <tr>
                        <th>カテゴリ</th>
                        <th>部屋番号</th>
                        <th>目安人数</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($booklet['room_assignments'] as $cat): ?>
                    <?php
                    $rooms  = $cat['rooms'] ?? [];
                    $rCount = count($rooms);
                    ?>
                    <?php foreach ($rooms as $ri => $room): ?>
                    <tr>
                        <?php if ($ri === 0): ?>
                        <td rowspan="<?= $rCount ?>" class="fw-bold"><?= htmlspecialchars($cat['category'] ?? '') ?></td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($room['room_no'] ?? '') ?></td>
                        <td class="text-center"><?= htmlspecialchars($room['capacity'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (!empty($booklet['floor_plan_image'])): ?>
    <div class="booklet-section">
        <div class="booklet-section-header"><i class="bi bi-map"></i> 宿内平面図</div>
        <div class="booklet-section-body text-center">
            <img src="<?= htmlspecialchars($booklet['floor_plan_image']) ?>"
                 class="img-fluid rounded" alt="宿内平面図" style="max-height:500px;">
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

</div><!-- /.tab-content -->

<div class="mt-4 pb-3" style="font-size:.8rem; color:#9ca3af; text-align:center;">
    ご不明な点は幹事長までご連絡ください
</div>

<script>
// 折りたたみ開閉でシェブロン回転
document.querySelectorAll('.booklet-section-header.collapsible').forEach(header => {
    const targetId = header.getAttribute('data-bs-target');
    if (!targetId) return;
    const pane = document.querySelector(targetId);
    if (!pane) return;
    pane.addEventListener('hide.bs.collapse', () => header.classList.add('collapsed'));
    pane.addEventListener('show.bs.collapse', () => header.classList.remove('collapsed'));
});
</script>
