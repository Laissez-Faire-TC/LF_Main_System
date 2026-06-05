<style>
.dashboard-card {
    transition: transform 0.15s, box-shadow 0.15s;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
}
.dashboard-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important;
    color: inherit;
}
.dashboard-icon {
    font-size: 2.5rem;
    opacity: 0.85;
}
</style>

<div class="mb-4">
    <h1 class="mb-1">ダッシュボード</h1>
    <p class="text-muted mb-0">サークル管理システム</p>
</div>

<?php
$fieldLabels = [
    'phone'             => '電話番号',
    'address'           => '住所',
    'emergency_contact' => '緊急連絡先',
    'email'             => 'メールアドレス',
    'allergy'           => 'アレルギー',
    'line_name'         => 'LINE名',
    'sns_allowed'       => 'SNS掲載同意',
];
?>

<?php if (!empty($changeNotifications)): ?>
<div class="card mb-4 border-info" id="changeNotifPanel">
    <div class="card-header bg-info bg-opacity-10 border-info d-flex justify-content-between align-items-center">
        <span><i class="bi bi-bell-fill text-info"></i> 会員による登録情報の変更通知
            <span class="badge bg-info ms-1"><?= count($changeNotifications) ?></span>
        </span>
        <button class="btn btn-outline-info btn-sm" onclick="dismissAll()">すべて既読にする</button>
    </div>
    <div class="card-body p-0" id="notifList">
        <?php foreach ($changeNotifications as $i => $notif):
            $changes = json_decode($notif['changes_json'], true) ?? [];
            $dt = date('n/j H:i', strtotime($notif['created_at']));
        ?>
        <div class="p-3 <?= $i < count($changeNotifications) - 1 ? 'border-bottom' : '' ?>" id="notif-<?= (int)$notif['id'] ?>">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="fw-semibold mb-1">
                        <a href="/members?search=<?= urlencode($notif['member_name']) ?>" class="text-decoration-none">
                            <?= htmlspecialchars($notif['member_name']) ?>
                        </a>
                        <span class="text-muted fw-normal small ms-1"><?= htmlspecialchars($notif['student_id']) ?></span>
                        <span class="text-muted fw-normal small ms-2"><?= $dt ?></span>
                    </div>
                    <div class="small text-muted">
                        <?php foreach ($changes as $field => $diff):
                            $label  = $fieldLabels[$field] ?? $field;
                            $before = $diff['before'] ?? '（未設定）';
                            $after  = $diff['after']  ?? '（未設定）';
                            if ($field === 'sns_allowed') {
                                $before = $before ? '同意する' : '同意しない';
                                $after  = $after  ? '同意する' : '同意しない';
                            }
                        ?>
                        <span class="me-3">
                            <span class="text-dark"><?= htmlspecialchars($label) ?>：</span>
                            <span class="text-danger text-decoration-line-through"><?= htmlspecialchars((string)$before) ?></span>
                            <i class="bi bi-arrow-right mx-1"></i>
                            <span class="text-success"><?= htmlspecialchars((string)$after) ?></span>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button class="btn btn-sm btn-outline-secondary ms-3 flex-shrink-0"
                        onclick="dismiss(<?= (int)$notif['id'] ?>)">既読</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
async function dismiss(id) {
    await fetch(`/api/member-change-notifications/${id}/read`, { method: 'POST' });
    const el = document.getElementById(`notif-${id}`);
    if (el) el.remove();
    updateBadge();
}

async function dismissAll() {
    const items = document.querySelectorAll('#notifList [id^="notif-"]');
    await Promise.all([...items].map(el => {
        const id = el.id.replace('notif-', '');
        return fetch(`/api/member-change-notifications/${id}/read`, { method: 'POST' });
    }));
    document.getElementById('changeNotifPanel').remove();
}

function updateBadge() {
    const panel = document.getElementById('changeNotifPanel');
    if (!panel) return;
    const remaining = panel.querySelectorAll('[id^="notif-"]').length;
    if (remaining === 0) {
        panel.remove();
    } else {
        panel.querySelector('.badge').textContent = remaining;
    }
}
</script>
<?php endif; ?>

<?php if (!empty($activeCamps)): ?>
<!-- 募集中の合宿 -->
<div class="card mb-4 border-warning">
    <div class="card-header bg-warning bg-opacity-25 border-warning">
        <i class="bi bi-megaphone"></i> 募集中の合宿
    </div>
    <div class="card-body">
        <?php foreach ($activeCamps as $camp): ?>
        <div class="d-flex justify-content-between align-items-center <?= $camp !== end($activeCamps) ? 'mb-3 pb-3 border-bottom' : '' ?>">
            <div>
                <h6 class="mb-1"><?= htmlspecialchars($camp['camp_name']) ?></h6>
                <small class="text-muted">
                    <?= date('Y/n/j', strtotime($camp['start_date'])) ?> 〜 <?= date('n/j', strtotime($camp['end_date'])) ?>
                    <?php if ($camp['deadline']): ?>
                    ・締切 <?= date('n/j', strtotime($camp['deadline'])) ?>
                    <?php endif; ?>
                </small>
            </div>
            <a href="/apply/<?= htmlspecialchars($camp['token']) ?>" class="btn btn-warning btn-sm" target="_blank">
                申し込む
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- メイン機能 -->
<h6 class="text-uppercase text-muted fw-bold mb-3 small">管理機能</h6>
<div class="row g-3 mb-4">
    <?php if (Auth::can('camps')): ?>
    <div class="col-md-4 col-sm-6">
        <a href="/camps" class="dashboard-card card shadow-sm h-100 text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="dashboard-icon text-primary"><i class="bi bi-house-gear"></i></div>
                <div>
                    <h5 class="mb-1">合宿管理</h5>
                    <small class="text-muted">合宿の作成・費用計算・参加者管理</small>
                </div>
            </div>
        </a>
    </div>
    <?php endif; ?>
    <?php if (Auth::can('expeditions')): ?>
    <div class="col-md-4 col-sm-6">
        <a href="/expeditions" class="dashboard-card card shadow-sm h-100 text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="dashboard-icon text-primary"><i class="bi bi-geo-alt"></i></div>
                <div>
                    <h5 class="mb-1">遠征管理</h5>
                    <small class="text-muted">遠征の作成・車割・チーム分け・集金管理</small>
                </div>
            </div>
        </a>
    </div>
    <?php endif; ?>
    <?php if (Auth::can('events')): ?>
    <div class="col-md-4 col-sm-6">
        <a href="/events" class="dashboard-card card shadow-sm h-100 text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="dashboard-icon text-primary"><i class="bi bi-calendar-event"></i></div>
                <div>
                    <h5 class="mb-1">企画管理</h5>
                    <small class="text-muted">イベントの作成・申込管理・費用計算</small>
                </div>
            </div>
        </a>
    </div>
    <?php endif; ?>
    <?php if (Auth::can('merchandise')): ?>
    <div class="col-md-4 col-sm-6">
        <a href="/merchandise" class="dashboard-card card shadow-sm h-100 text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="dashboard-icon text-primary"><i class="bi bi-bag"></i></div>
                <div>
                    <h5 class="mb-1">物販管理</h5>
                    <small class="text-muted">Tシャツ・パーカー等の販売・注文管理</small>
                </div>
            </div>
        </a>
    </div>
    <?php endif; ?>
    <?php if (Auth::can('suggestions')): ?>
    <div class="col-md-4 col-sm-6">
        <a href="/suggestions" class="dashboard-card card shadow-sm h-100 text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="dashboard-icon text-danger"><i class="bi bi-inbox"></i></div>
                <div>
                    <h5 class="mb-1">目安箱</h5>
                    <small class="text-muted">会員からの要望・質問・相談の確認と返信</small>
                </div>
            </div>
        </a>
    </div>
    <?php endif; ?>
    <?php if (Auth::can('hp')): ?>
    <div class="col-md-4 col-sm-6">
        <a href="/hp" class="dashboard-card card shadow-sm h-100 text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="dashboard-icon text-success"><i class="bi bi-globe2"></i></div>
                <div>
                    <h5 class="mb-1">HP管理</h5>
                    <small class="text-muted">公開HPのコンテンツ編集・ニュース更新</small>
                </div>
            </div>
        </a>
    </div>
    <?php endif; ?>
    <?php if (Auth::can('members')): ?>
    <div class="col-md-4 col-sm-6">
        <a href="/members" class="dashboard-card card shadow-sm h-100 text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="dashboard-icon text-success"><i class="bi bi-people"></i></div>
                <div>
                    <h5 class="mb-1">会員名簿</h5>
                    <small class="text-muted">会員情報の管理・検索・編集</small>
                </div>
            </div>
        </a>
    </div>
    <?php endif; ?>
    <?php if (Auth::can('enrollment')): ?>
    <div class="col-md-4 col-sm-6">
        <a href="/enrollment-management" class="dashboard-card card shadow-sm h-100 text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="dashboard-icon text-warning"><i class="bi bi-person-plus"></i></div>
                <div>
                    <h5 class="mb-1">入会管理</h5>
                    <small class="text-muted">入会フォームURL・入会金管理・新規入会者リスト</small>
                </div>
            </div>
        </a>
    </div>
    <?php endif; ?>
    <?php if (Auth::can('academic')): ?>
    <div class="col-md-4 col-sm-6">
        <a href="/academic-years" class="dashboard-card card shadow-sm h-100 text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="dashboard-icon text-info"><i class="bi bi-calendar3"></i></div>
                <div>
                    <h5 class="mb-1">年度管理</h5>
                    <small class="text-muted">年度の作成・過去の名簿閲覧</small>
                </div>
            </div>
        </a>
    </div>
    <?php endif; ?>
    <?php if (Auth::can('camps')): ?>
    <div class="col-md-4 col-sm-6">
        <a href="/pdf/upload" class="dashboard-card card shadow-sm h-100 text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="dashboard-icon text-secondary"><i class="bi bi-file-pdf"></i></div>
                <div>
                    <h5 class="mb-1">PDF読み取り</h5>
                    <small class="text-muted">PDFから参加者データを取り込む</small>
                </div>
            </div>
        </a>
    </div>
    <?php endif; ?>
    <?php if (Auth::can('settings')): ?>
    <div class="col-md-4 col-sm-6">
        <a href="/settings" class="dashboard-card card shadow-sm h-100 text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="dashboard-icon text-secondary"><i class="bi bi-gear"></i></div>
                <div>
                    <h5 class="mb-1">システム設定</h5>
                    <small class="text-muted">AIモデル設定・パスワード変更</small>
                </div>
            </div>
        </a>
    </div>
    <?php endif; ?>
    <?php if (Auth::isAdmin()): ?>
    <div class="col-md-4 col-sm-6">
        <a href="/admin-users" class="dashboard-card card shadow-sm h-100 text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="dashboard-icon text-danger"><i class="bi bi-shield-lock"></i></div>
                <div>
                    <h5 class="mb-1">幹部・権限管理</h5>
                    <small class="text-muted">Googleログイン許可・幹部ごとの権限設定</small>
                </div>
            </div>
        </a>
    </div>
    <?php endif; ?>
    <div class="col-md-4 col-sm-6">
        <a href="/guide" class="dashboard-card card shadow-sm h-100 text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="dashboard-icon text-info"><i class="bi bi-question-circle"></i></div>
                <div>
                    <h5 class="mb-1">使い方ガイド</h5>
                    <small class="text-muted">各機能の操作説明</small>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- 公開フォーム -->
<h6 class="text-uppercase text-muted fw-bold mb-3 small">公開フォーム（URL共有用）</h6>
<div class="row g-3">
    <div class="col-md-4 col-sm-6">
        <a href="/enroll" class="dashboard-card card shadow-sm h-100 text-decoration-none" target="_blank">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="dashboard-icon text-success"><i class="bi bi-person-plus"></i></div>
                <div>
                    <h5 class="mb-1">新規入会フォーム</h5>
                    <small class="text-muted">新1・2年生向け入会申請</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4 col-sm-6">
        <a href="/renew" class="dashboard-card card shadow-sm h-100 text-decoration-none" target="_blank">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="dashboard-icon text-primary"><i class="bi bi-arrow-repeat"></i></div>
                <div>
                    <h5 class="mb-1">継続入会フォーム</h5>
                    <small class="text-muted">2年生以上の継続登録</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4 col-sm-6">
        <a href="/portal" class="dashboard-card card shadow-sm h-100 text-decoration-none" target="_blank">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="dashboard-icon text-warning"><i class="bi bi-grid"></i></div>
                <div>
                    <h5 class="mb-1">会員ポータル</h5>
                    <small class="text-muted">部員向け公開ページ</small>
                </div>
            </div>
        </a>
    </div>
</div>

