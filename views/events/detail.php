<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="/events" class="text-muted small text-decoration-none">← 企画一覧</a>
        <h1 class="mb-0 mt-1"><?= htmlspecialchars($event['title']) ?></h1>
    </div>
    <div>
        <button id="toggleActiveBtn" class="btn btn-outline-secondary me-2" onclick="toggleActive()">
            <?= $event['is_active'] ? '非公開にする' : '会員ページに公開する' ?>
        </button>
    </div>
</div>

<!-- タブナビゲーション -->
<ul class="nav nav-tabs mb-4" id="eventTabs">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#tabInfo">基本情報</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tabApplications">
            申込者 <span class="badge bg-secondary" id="appCountBadge"><?= $event['application_count'] ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tabTeams">班決め</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tabExpenses">雑費</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tabCalc">費用計算</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tabToken">申込URL</a>
    </li>
</ul>

<div class="tab-content">

    <!-- ── 基本情報タブ ── -->
    <div class="tab-pane fade show active" id="tabInfo">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">タイトル</label>
                        <input type="text" class="form-control" id="infoTitle" value="<?= htmlspecialchars($event['title']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">開催日</label>
                        <input type="date" class="form-control" id="infoDate" value="<?= htmlspecialchars($event['event_date']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">開始時刻</label>
                        <input type="time" class="form-control" id="infoTime" value="<?= htmlspecialchars(substr($event['event_time'] ?? '', 0, 5)) ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">場所</label>
                        <input type="text" class="form-control" id="infoLocation" value="<?= htmlspecialchars($event['location'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">概要</label>
                        <textarea class="form-control" id="infoDescription" rows="4"><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">参加費（円）</label>
                        <input type="number" class="form-control" id="infoFee" value="<?= (int)$event['participation_fee'] ?>" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">定員</label>
                        <input type="number" class="form-control" id="infoCapacity"
                               value="<?= $event['capacity'] !== null ? (int)$event['capacity'] : '' ?>"
                               placeholder="空欄 = 制限なし" min="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">申込期限</label>
                        <input type="date" class="form-control" id="infoDeadline"
                               value="<?= htmlspecialchars($event['deadline'] ?? '') ?>">
                        <div class="form-text">期限を過ぎると会員ページから非表示</div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" id="infoAllowWaitlist"
                                   <?= $event['allow_waitlist'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="infoAllowWaitlist">キャンセル待ちを受け付ける</label>
                        </div>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="infoIsActive"
                                   <?= $event['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="infoIsActive">公開中</label>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    <button class="btn btn-primary" onclick="saveInfo()">保存</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 申込者タブ ── -->
    <div class="tab-pane fade" id="tabApplications">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>申込者一覧</span>
                <span id="capacityDisplay" class="fw-semibold"></span>
            </div>
            <div class="card-body p-0">
                <div id="applicationsContainer">
                    <div class="text-center p-4 text-muted">読み込み中...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 班決めタブ ── -->
    <div class="tab-pane fade" id="tabTeams">

        <!-- 自動振り分け設定 -->
        <div class="card shadow-sm mb-3">
            <div class="card-header">班の自動振り分け</div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">班の数</label>
                        <input type="number" class="form-control" id="teamCount" value="4" min="1" max="50">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold d-block">均等にする基準</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="balanceGrade" checked>
                            <label class="form-check-label" for="balanceGrade">学年を分散</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="balanceGender" checked>
                            <label class="form-check-label" for="balanceGender">性別を分散</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="balanceFaculty">
                            <label class="form-check-label" for="balanceFaculty">学科を分散</label>
                        </div>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button class="btn btn-primary" onclick="autoAssignTeams()">自動振り分け</button>
                    </div>
                </div>
                <div class="form-text mt-2">
                    制約（絶対一緒／絶対別）を守りつつ、選択した基準が各班でばらけるように割り当てます。手動での変更も可能です。
                </div>
            </div>
        </div>

        <!-- 制約設定 -->
        <div class="card shadow-sm mb-3">
            <div class="card-header">組み合わせの制約</div>
            <div class="card-body">
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label">メンバー1</label>
                        <select class="form-select" id="constraintMemberA"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">メンバー2</label>
                        <select class="form-select" id="constraintMemberB"></select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">種別</label>
                        <select class="form-select" id="constraintType">
                            <option value="together">絶対一緒</option>
                            <option value="apart">絶対別</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-outline-primary" onclick="addConstraint()">追加</button>
                    </div>
                </div>
                <div id="constraintsContainer">
                    <div class="text-muted small">制約はまだありません</div>
                </div>
            </div>
        </div>

        <!-- 申込者一覧（班割り当て） -->
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span>申込者一覧</span>
                    <!-- 表示切替 -->
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-primary" id="teamViewBtn" onclick="setTeamView('teams')">班ビュー</button>
                        <button type="button" class="btn btn-outline-primary" id="listViewBtn" onclick="setTeamView('list')">一覧ビュー</button>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- 並べ替え（一覧ビューのみ） -->
                    <div id="teamSortControls" class="align-items-center gap-2 flex-wrap" style="display:none;">
                        <div class="input-group input-group-sm" style="width: auto;">
                            <span class="input-group-text">並べ替え</span>
                            <select class="form-select form-select-sm" id="teamSortPrimary" onchange="applyTeamSorting()" style="width: auto;">
                                <option value="team_no" selected>班番号</option>
                                <option value="name_kana">カナ順</option>
                                <option value="grade">学年</option>
                                <option value="gender">性別</option>
                                <option value="faculty">学部</option>
                            </select>
                            <button type="button" class="btn btn-outline-secondary" id="teamSortPrimaryDir" onclick="toggleTeamSortDirection('primary')" title="昇順/降順切り替え">↑</button>
                        </div>
                        <div class="input-group input-group-sm" style="width: auto;">
                            <span class="input-group-text">→</span>
                            <select class="form-select form-select-sm" id="teamSortSecondary" onchange="applyTeamSorting()" style="width: auto;">
                                <option value="">なし</option>
                                <option value="name_kana" selected>カナ順</option>
                                <option value="grade">学年</option>
                                <option value="gender">性別</option>
                                <option value="faculty">学部</option>
                            </select>
                            <button type="button" class="btn btn-outline-secondary" id="teamSortSecondaryDir" onclick="toggleTeamSortDirection('secondary')" title="昇順/降順切り替え">↑</button>
                        </div>
                    </div>
                    <span id="teamSaveStatus" class="small text-muted"></span>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="teamsSummary" class="px-3 pt-3"></div>
                <div id="teamsContainer">
                    <div class="text-center p-4 text-muted">読み込み中...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 雑費タブ ── -->
    <div class="tab-pane fade" id="tabExpenses">
        <div class="card shadow-sm mb-3">
            <div class="card-header">雑費追加</div>
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">項目名</label>
                        <input type="text" class="form-control" id="expenseName" placeholder="例: コート代">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">金額（円）</label>
                        <input type="number" class="form-control" id="expenseAmount" value="0" min="0">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" onclick="addExpense()">追加</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div id="expensesContainer">
                    <div class="text-center p-4 text-muted">読み込み中...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 費用計算タブ ── -->
    <div class="tab-pane fade" id="tabCalc">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-end mb-3">
                    <button class="btn btn-primary" onclick="loadCalc()">計算する</button>
                </div>
                <div id="calcResult"></div>
            </div>
        </div>
    </div>

    <!-- ── 申込URLタブ ── -->
    <div class="tab-pane fade" id="tabToken">
        <div class="card shadow-sm">
            <div class="card-body">
                <p class="text-muted small mb-3">
                    発行したURLを会員にLINEで共有すると、学籍番号でログインして申し込めます。
                </p>
                <div id="tokenArea">
                    <div class="text-center text-muted py-3">読み込み中...</div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary" onclick="generateToken()">
                        <i class="bi bi-link-45deg"></i> URLを発行する
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
const EVENT_ID = <?= (int)$eventId ?>;
let isActive   = <?= $event['is_active'] ? 'true' : 'false' ?>;
let capacity   = <?= $event['capacity'] !== null ? (int)$event['capacity'] : 'null' ?>;

// ──────────────────────────────────
// 初期ロード
// ──────────────────────────────────
document.querySelector('a[href="#tabApplications"]').addEventListener('shown.bs.tab', loadApplications);
document.querySelector('a[href="#tabTeams"]').addEventListener('shown.bs.tab', loadTeams);
document.querySelector('a[href="#tabExpenses"]').addEventListener('shown.bs.tab', loadExpenses);
document.querySelector('a[href="#tabCalc"]').addEventListener('shown.bs.tab', loadCalc);

// ──────────────────────────────────
// 基本情報保存
// ──────────────────────────────────
async function saveInfo() {
    const cap = document.getElementById('infoCapacity').value;
    const payload = {
        title:             document.getElementById('infoTitle').value.trim(),
        event_date:        document.getElementById('infoDate').value,
        event_time:        document.getElementById('infoTime').value        || null,
        location:          document.getElementById('infoLocation').value.trim() || null,
        description:       document.getElementById('infoDescription').value.trim() || null,
        participation_fee: parseInt(document.getElementById('infoFee').value) || 0,
        capacity:          cap !== '' ? parseInt(cap) : null,
        deadline:          document.getElementById('infoDeadline').value || null,
        allow_waitlist:    document.getElementById('infoAllowWaitlist').checked ? 1 : 0,
        is_active:         document.getElementById('infoIsActive').checked ? 1 : 0,
    };

    try {
        const res  = await fetch(`/api/events/${EVENT_ID}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.success) {
            isActive = !!payload.is_active;
            capacity = payload.capacity;
            document.getElementById('toggleActiveBtn').textContent =
                isActive ? '非公開にする' : '会員ページに公開する';
            showToast('保存しました', 'success');
        } else {
            alert(data.error?.message || '保存に失敗しました');
        }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

// ──────────────────────────────────
// 公開切り替え
// ──────────────────────────────────
async function toggleActive() {
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/toggle-active`, { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            isActive = !!data.data.is_active;
            document.getElementById('toggleActiveBtn').textContent =
                isActive ? '非公開にする' : '会員ページに公開する';
            document.getElementById('infoIsActive').checked = isActive;
            showToast(data.message, 'success');
        }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

// ──────────────────────────────────
// 申込者一覧
// ──────────────────────────────────
async function loadApplications() {
    const container = document.getElementById('applicationsContainer');
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/applications`);
        const data = await res.json();
        const submitted = data.data?.submitted || [];
        const waitlist  = data.data?.waitlist  || [];

        document.getElementById('appCountBadge').textContent = submitted.length;

        // 定員表示
        const capDisplay = document.getElementById('capacityDisplay');
        if (capacity !== null) {
            const isFull = submitted.length >= capacity;
            const cls    = isFull ? 'bg-danger' : (submitted.length / capacity >= 0.8 ? 'bg-warning text-dark' : 'bg-primary');
            capDisplay.innerHTML = `<span class="badge ${cls} fs-6">${submitted.length} / ${capacity}</span>`;
        } else {
            capDisplay.innerHTML = `<span class="text-muted">${submitted.length}人申込中</span>`;
        }

        let html = '';

        // ── 参加確定一覧 ──
        if (submitted.length === 0) {
            html += '<div class="text-center p-4 text-muted">申込者はまだいません</div>';
        } else {
            const rows = submitted.map((a, i) => {
                const promoted = parseInt(a.promoted) === 1;
                const rowCls   = promoted ? 'table-warning' : '';
                const badge    = promoted
                    ? '<span class="badge bg-warning text-dark ms-1" title="キャンセル待ちから繰り上げ">繰り上げ</span>'
                    : '';
                return `
                <tr class="${rowCls}">
                    <td>${i + 1}</td>
                    <td class="fw-semibold">${esc(a.name_kanji)}${badge}</td>
                    <td>${esc(gradeLabel(a.grade))}</td>
                    <td>${esc(a.gender === 'male' ? '男' : '女')}</td>
                    <td class="small text-muted">${esc(a.department || '—')}</td>
                    <td class="small text-muted">${esc(a.line_name || '—')}</td>
                    <td class="small text-muted">${formatDateTime(a.created_at)}</td>
                    <td><button class="btn btn-sm btn-outline-danger" onclick="cancelApplication(${a.id})">取消</button></td>
                </tr>`;
            }).join('');

            html += `
            <div class="px-3 pt-3 pb-1">
                <span class="fw-semibold">参加確定</span>
                <span class="badge bg-primary ms-1">${submitted.length}人</span>
            </div>
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>#</th><th>氏名</th><th>学年</th><th>性別</th><th>学科</th><th>LINE名</th><th>申込日時</th><th></th></tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>`;
        }

        // ── キャンセル待ち一覧 ──
        if (waitlist.length > 0) {
            const wRows = waitlist.map((a, i) => `
                <tr class="table-secondary">
                    <td class="text-muted">${i + 1}</td>
                    <td class="fw-semibold">${esc(a.name_kanji)}</td>
                    <td>${esc(gradeLabel(a.grade))}</td>
                    <td>${esc(a.gender === 'male' ? '男' : '女')}</td>
                    <td class="small text-muted">${esc(a.department || '—')}</td>
                    <td class="small text-muted">${esc(a.line_name || '—')}</td>
                    <td class="small text-muted">${formatDateTime(a.created_at)}</td>
                    <td><button class="btn btn-sm btn-outline-danger" onclick="cancelApplication(${a.id})">取消</button></td>
                </tr>`).join('');

            html += `
            <div class="px-3 pt-3 pb-1 border-top">
                <span class="fw-semibold text-secondary">キャンセル待ち</span>
                <span class="badge bg-secondary ms-1">${waitlist.length}人</span>
                <small class="text-muted ms-2">参加確定者がキャンセルすると自動で繰り上がります</small>
            </div>
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>順番</th><th>氏名</th><th>学年</th><th>性別</th><th>学科</th><th>LINE名</th><th>登録日時</th><th></th></tr>
                </thead>
                <tbody>${wRows}</tbody>
            </table>`;
        }

        container.innerHTML = html;
    } catch (e) {
        container.innerHTML = '<div class="text-center p-4 text-danger">読み込みに失敗しました</div>';
    }
}

async function cancelApplication(id) {
    if (!confirm('この申込をキャンセルしますか？')) return;
    try {
        const res  = await fetch(`/api/event-applications/${id}/cancel`, { method: 'POST' });
        const data = await res.json();
        if (data.success) { loadApplications(); }
        else { alert(data.error?.message || 'キャンセルに失敗しました'); }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

// ──────────────────────────────────
// 雑費
// ──────────────────────────────────
async function loadExpenses() {
    const container = document.getElementById('expensesContainer');
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}`);
        const data = await res.json();
        const expenses = data.data?.expenses || [];

        if (expenses.length === 0) {
            container.innerHTML = '<div class="text-center p-4 text-muted">雑費はありません</div>';
            return;
        }

        const total = expenses.reduce((s, e) => s + parseInt(e.amount), 0);
        const rows  = expenses.map(e => `
            <tr>
                <td>${esc(e.name)}</td>
                <td class="text-end">${Number(e.amount).toLocaleString()}円</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteExpense(${e.id})">削除</button>
                </td>
            </tr>`).join('');

        container.innerHTML = `
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>項目名</th><th class="text-end">金額</th><th></th></tr>
                </thead>
                <tbody>${rows}</tbody>
                <tfoot class="table-light fw-semibold">
                    <tr><td>合計</td><td class="text-end">${total.toLocaleString()}円</td><td></td></tr>
                </tfoot>
            </table>`;
    } catch (e) {
        container.innerHTML = '<div class="text-center p-4 text-danger">読み込みに失敗しました</div>';
    }
}

async function addExpense() {
    const name   = document.getElementById('expenseName').value.trim();
    const amount = parseInt(document.getElementById('expenseAmount').value) || 0;
    if (!name) { alert('項目名を入力してください'); return; }

    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/expenses`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, amount }),
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('expenseName').value   = '';
            document.getElementById('expenseAmount').value = '0';
            loadExpenses();
        } else {
            alert(data.error?.message || '追加に失敗しました');
        }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

async function deleteExpense(id) {
    if (!confirm('この雑費を削除しますか？')) return;
    try {
        const res  = await fetch(`/api/event-expenses/${id}`, { method: 'DELETE' });
        const data = await res.json();
        if (data.success) { loadExpenses(); }
        else { alert(data.error?.message || '削除に失敗しました'); }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

// ──────────────────────────────────
// 費用計算
// ──────────────────────────────────
async function loadCalc() {
    const container = document.getElementById('calcResult');
    container.innerHTML = '<div class="text-center text-muted">計算中...</div>';
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/calculate`);
        const data = await res.json();
        const d    = data.data;

        if (!data.success) {
            container.innerHTML = '<div class="text-danger">計算に失敗しました</div>';
            return;
        }

        const expenseRows = (d.expenses || []).map(e => `
            <tr><td>${esc(e.name)}</td><td class="text-end">${Number(e.amount).toLocaleString()}円</td></tr>
        `).join('');

        container.innerHTML = `
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card text-center border-primary">
                        <div class="card-body py-3">
                            <div class="fs-4 fw-bold text-primary">${d.applicant_count}人</div>
                            <div class="small text-muted">申込人数</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <div class="fs-4 fw-bold">${Number(d.participation_fee).toLocaleString()}円</div>
                            <div class="small text-muted">参加費</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <div class="fs-4 fw-bold">${Number(d.expense_per_person).toLocaleString()}円</div>
                            <div class="small text-muted">雑費負担（1人）</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card text-center border-success">
                        <div class="card-body py-3">
                            <div class="fs-4 fw-bold text-success">${Number(d.total_per_person).toLocaleString()}円</div>
                            <div class="small text-muted">1人あたり合計</div>
                        </div>
                    </div>
                </div>
            </div>
            ${d.expenses.length > 0 ? `
            <h6 class="mb-2">雑費内訳</h6>
            <table class="table table-sm">
                <thead class="table-light"><tr><th>項目</th><th class="text-end">金額</th></tr></thead>
                <tbody>${expenseRows}</tbody>
                <tfoot class="fw-semibold"><tr><td>合計</td><td class="text-end">${Number(d.total_expenses).toLocaleString()}円</td></tr></tfoot>
            </table>` : '<p class="text-muted">雑費は登録されていません</p>'}
            ${d.applicant_count === 0 ? '<div class="alert alert-warning mt-3">申込者がいないため1人あたりの費用は計算できません</div>' : ''}`;
    } catch (e) {
        container.innerHTML = '<div class="text-danger">通信エラーが発生しました</div>';
    }
}

// ──────────────────────────────────
// ユーティリティ
// ──────────────────────────────────
function esc(str) {
    return String(str ?? '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function gradeLabel(g) {
    if (g === 'OB' || g === 'OG') return g;
    return g + '年';
}

function formatDateTime(str) {
    if (!str) return '—';
    const d = new Date(str);
    return `${d.getFullYear()}/${d.getMonth()+1}/${d.getDate()} ${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`;
}

function showToast(message, type = 'success') {
    const t = document.createElement('div');
    t.className = `toast align-items-center text-bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
    t.setAttribute('role', 'alert');
    t.innerHTML = `<div class="d-flex"><div class="toast-body">${esc(message)}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
    document.body.appendChild(t);
    const toast = new bootstrap.Toast(t, { delay: 3000 });
    toast.show();
    t.addEventListener('hidden.bs.toast', () => t.remove());
}

// ──────────────────────────────────
// 班決め（グループ分け）
// ──────────────────────────────────
let teamMembers   = [];   // 参加確定者（team_no を保持）
let teamConstraints = [];  // 制約一覧
let teamsLoaded   = false;

let teamSortConfig = {
    primary:   { key: 'team_no',   direction: 1 },
    secondary: { key: 'name_kana', direction: 1 },
};

let teamView = 'teams';   // 'teams'（班ごとカード） / 'list'（一覧表）

// 班ビュー / 一覧ビュー切替
function setTeamView(view) {
    teamView = view;
    document.getElementById('teamViewBtn').className = view === 'teams' ? 'btn btn-primary' : 'btn btn-outline-primary';
    document.getElementById('listViewBtn').className = view === 'list'  ? 'btn btn-primary' : 'btn btn-outline-primary';
    // 並べ替えは一覧ビューのときだけ表示
    document.getElementById('teamSortControls').style.display = view === 'list' ? 'flex' : 'none';
    renderTeamTable();
}

function gradeToNumber(grade) {
    if (grade === 'OB' || grade === 'OG') return 99;
    if (grade === 'M1') return 5;
    if (grade === 'M2') return 6;
    const n = parseInt(grade);
    return isNaN(n) ? 98 : n;
}

function compareTeamMembers(a, b, key) {
    if (key === 'team_no') {
        // 未割り当て(null)は末尾へ
        const ta = (a.team_no === null || a.team_no === undefined || a.team_no === '') ? Infinity : parseInt(a.team_no);
        const tb = (b.team_no === null || b.team_no === undefined || b.team_no === '') ? Infinity : parseInt(b.team_no);
        return ta - tb;
    } else if (key === 'name_kana') {
        return (a.name_kana || '').localeCompare(b.name_kana || '', 'ja');
    } else if (key === 'grade') {
        return gradeToNumber(a.grade) - gradeToNumber(b.grade);
    } else if (key === 'gender') {
        const order = { 'male': 1, 'female': 2 };
        return (order[a.gender] || 3) - (order[b.gender] || 3);
    } else if (key === 'faculty') {
        return (a.faculty || '').localeCompare(b.faculty || '', 'ja');
    }
    return 0;
}

function applyTeamSorting() {
    teamSortConfig.primary.key   = document.getElementById('teamSortPrimary').value;
    teamSortConfig.secondary.key = document.getElementById('teamSortSecondary').value || '';
    renderTeamTable();
}

function toggleTeamSortDirection(level) {
    if (level === 'primary') {
        teamSortConfig.primary.direction *= -1;
        document.getElementById('teamSortPrimaryDir').textContent = teamSortConfig.primary.direction === 1 ? '↑' : '↓';
    } else {
        teamSortConfig.secondary.direction *= -1;
        document.getElementById('teamSortSecondaryDir').textContent = teamSortConfig.secondary.direction === 1 ? '↑' : '↓';
    }
    renderTeamTable();
}

async function loadTeams() {
    if (teamsLoaded) return;
    const container = document.getElementById('teamsContainer');
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/teams`);
        const data = await res.json();
        teamMembers     = (data.data?.members || []).map(m => ({
            ...m,
            team_no: (m.team_no === null || m.team_no === undefined) ? null : parseInt(m.team_no),
        }));
        teamConstraints = data.data?.constraints || [];
        teamsLoaded = true;

        renderConstraintSelects();
        renderConstraints();
        renderTeamTable();
    } catch (e) {
        container.innerHTML = '<div class="text-center p-4 text-danger">読み込みに失敗しました</div>';
    }
}

// 制約用セレクトボックスを更新
function renderConstraintSelects() {
    const opts = '<option value="">選択してください</option>' +
        [...teamMembers]
            .sort((a, b) => (a.name_kana || '').localeCompare(b.name_kana || '', 'ja'))
            .map(m => `<option value="${m.member_id}">${esc(m.name_kanji)}（${esc(gradeLabel(m.grade))}）</option>`)
            .join('');
    document.getElementById('constraintMemberA').innerHTML = opts;
    document.getElementById('constraintMemberB').innerHTML = opts;
}

function renderConstraints() {
    const container = document.getElementById('constraintsContainer');
    if (teamConstraints.length === 0) {
        container.innerHTML = '<div class="text-muted small">制約はまだありません</div>';
        return;
    }
    container.innerHTML = teamConstraints.map(c => {
        const badge = c.type === 'together'
            ? '<span class="badge bg-success">絶対一緒</span>'
            : '<span class="badge bg-danger">絶対別</span>';
        return `
            <span class="badge bg-light text-dark border me-2 mb-2 p-2">
                ${badge}
                <span class="ms-1">${esc(c.member_a_name)} と ${esc(c.member_b_name)}</span>
                <button class="btn-close ms-2" style="font-size:.6rem;vertical-align:middle;" onclick="deleteConstraint(${c.id})" title="削除"></button>
            </span>`;
    }).join('');
}

async function addConstraint() {
    const memberA = document.getElementById('constraintMemberA').value;
    const memberB = document.getElementById('constraintMemberB').value;
    const type    = document.getElementById('constraintType').value;

    if (!memberA || !memberB) { alert('2名のメンバーを選択してください'); return; }
    if (memberA === memberB)  { alert('異なるメンバーを選択してください'); return; }

    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/constraints`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ member_a_id: parseInt(memberA), member_b_id: parseInt(memberB), type }),
        });
        const data = await res.json();
        if (data.success) {
            teamConstraints = data.data.constraints || [];
            renderConstraints();
            document.getElementById('constraintMemberA').value = '';
            document.getElementById('constraintMemberB').value = '';
            showToast('制約を追加しました', 'success');
        } else {
            alert(data.error?.message || '追加に失敗しました');
        }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

async function deleteConstraint(id) {
    if (!confirm('この制約を削除しますか？')) return;
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/constraints/${id}`, { method: 'DELETE' });
        const data = await res.json();
        if (data.success) {
            teamConstraints = teamConstraints.filter(c => parseInt(c.id) !== id);
            renderConstraints();
            showToast('制約を削除しました', 'success');
        } else {
            alert(data.error?.message || '削除に失敗しました');
        }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

// 自動振り分け（制約を尊重 + 選択基準でバランス）
function autoAssignTeams() {
    const teamCount = parseInt(document.getElementById('teamCount').value);
    if (!teamCount || teamCount < 1) { alert('班の数を正しく入力してください'); return; }
    if (teamMembers.length === 0) { alert('参加確定者がいません'); return; }

    const useGrade   = document.getElementById('balanceGrade').checked;
    const useGender  = document.getElementById('balanceGender').checked;
    const useFaculty = document.getElementById('balanceFaculty').checked;

    // ── 1. together 制約でユニオンファインド（グループ化） ──
    const idToMember = {};
    teamMembers.forEach(m => { idToMember[m.member_id] = m; });

    const parent = {};
    teamMembers.forEach(m => { parent[m.member_id] = m.member_id; });
    function find(x) { while (parent[x] !== x) { parent[x] = parent[parent[x]]; x = parent[x]; } return x; }
    function union(a, b) { const ra = find(a), rb = find(b); if (ra !== rb) parent[ra] = rb; }

    teamConstraints.forEach(c => {
        if (c.type === 'together' && idToMember[c.member_a_id] && idToMember[c.member_b_id]) {
            union(parseInt(c.member_a_id), parseInt(c.member_b_id));
        }
    });

    // クラスタ（同じ班に入るべき人のまとまり）を作成
    const clusters = {};
    teamMembers.forEach(m => {
        const root = find(m.member_id);
        (clusters[root] = clusters[root] || []).push(m);
    });
    let clusterList = Object.values(clusters);

    // ── 2. apart 制約マップ（member_id -> Set(禁止相手)） ──
    const apartMap = {};
    teamConstraints.forEach(c => {
        if (c.type === 'apart') {
            const a = parseInt(c.member_a_id), b = parseInt(c.member_b_id);
            (apartMap[a] = apartMap[a] || new Set()).add(b);
            (apartMap[b] = apartMap[b] || new Set()).add(a);
        }
    });

    // ── 3. クラスタを大きい順に並べ、貪欲法で各班へ ──
    clusterList.sort((a, b) => b.length - a.length);

    const teams = Array.from({ length: teamCount }, () => []);

    // 各班ごとの属性カウント（バランス評価用）
    function teamHasApartConflict(teamIdx, cluster) {
        for (const m of teams[teamIdx]) {
            for (const cm of cluster) {
                if (apartMap[m.member_id] && apartMap[m.member_id].has(cm.member_id)) return true;
            }
        }
        return false;
    }

    // バランススコア: 配置後にどれだけ偏るか（小さいほど良い）
    function balanceCost(teamIdx, cluster) {
        let cost = teams[teamIdx].length + cluster.length; // 人数均等が基本
        const counts = (key, valFn) => {
            const c = {};
            teams[teamIdx].forEach(m => { const v = valFn(m); c[v] = (c[v] || 0) + 1; });
            cluster.forEach(m => { const v = valFn(m); c[v] = (c[v] || 0) + 1; });
            // 同属性の重複が多いほどペナルティ
            return Object.values(c).reduce((s, n) => s + (n > 1 ? (n - 1) * 2 : 0), 0);
        };
        if (useGrade)   cost += counts('grade',   m => gradeToNumber(m.grade));
        if (useGender)  cost += counts('gender',  m => m.gender || '');
        if (useFaculty) cost += counts('faculty', m => m.faculty || '');
        return cost;
    }

    let unplaced = [];
    clusterList.forEach(cluster => {
        // apart 制約に違反しない班の中で、最もコストの低い班を選ぶ
        let best = -1, bestCost = Infinity;
        for (let t = 0; t < teamCount; t++) {
            if (teamHasApartConflict(t, cluster)) continue;
            const cost = balanceCost(t, cluster);
            if (cost < bestCost) { bestCost = cost; best = t; }
        }
        if (best === -1) {
            // どの班にも入れられない（apart制約が厳しすぎ）→ 一番人数の少ない班へ
            unplaced.push(cluster);
        } else {
            cluster.forEach(m => { m.team_no = best + 1; });
            teams[best].push(...cluster);
        }
    });

    // 配置できなかったクラスタは人数最少の班へ強制配置
    unplaced.forEach(cluster => {
        let min = 0;
        for (let t = 1; t < teamCount; t++) if (teams[t].length < teams[min].length) min = t;
        cluster.forEach(m => { m.team_no = min + 1; });
        teams[min].push(...cluster);
    });

    renderTeamTable();
    saveTeams();   // 自動振り分け結果を即保存

    if (unplaced.length > 0) {
        showToast('一部、絶対別の制約を満たせない班割りになりました', 'warning');
    } else {
        showToast('自動振り分けしました', 'success');
    }
}

function renderTeamTable() {
    const container = document.getElementById('teamsContainer');
    const summary   = document.getElementById('teamsSummary');

    if (teamMembers.length === 0) {
        summary.innerHTML = '';
        container.innerHTML = '<div class="text-center p-4 text-muted">参加確定者がいません</div>';
        return;
    }

    // サマリー（班ごとの人数）
    const teamCounts = {};
    teamMembers.forEach(m => {
        const key = (m.team_no === null) ? '未割り当て' : `${m.team_no}班`;
        teamCounts[key] = (teamCounts[key] || 0) + 1;
    });
    const summaryBadges = Object.keys(teamCounts)
        .sort((a, b) => {
            if (a === '未割り当て') return 1;
            if (b === '未割り当て') return -1;
            return parseInt(a) - parseInt(b);
        })
        .map(k => {
            const cls = k === '未割り当て' ? 'bg-secondary' : 'bg-primary';
            return `<span class="badge ${cls} me-1 mb-1">${esc(k)}: ${teamCounts[k]}人</span>`;
        }).join('');
    summary.innerHTML = `<div class="mb-2">${summaryBadges}</div>`;

    if (teamView === 'teams') {
        renderTeamCards(container);
    } else {
        renderTeamList(container);
    }
}

// ── 一覧ビュー（ソート可能な表） ──
function renderTeamList(container) {
    const sorted = [...teamMembers].sort((a, b) => {
        let cmp = compareTeamMembers(a, b, teamSortConfig.primary.key);
        if (cmp !== 0) return cmp * teamSortConfig.primary.direction;
        if (teamSortConfig.secondary.key) {
            cmp = compareTeamMembers(a, b, teamSortConfig.secondary.key);
            if (cmp !== 0) return cmp * teamSortConfig.secondary.direction;
        }
        return (a.name_kana || '').localeCompare(b.name_kana || '', 'ja');
    });

    const rows = sorted.map((m, i) => {
        return `
        <tr>
            <td>${i + 1}</td>
            <td class="fw-semibold">${esc(m.name_kanji)}</td>
            <td>${esc(gradeLabel(m.grade))}</td>
            <td>${esc(m.gender === 'male' ? '男' : '女')}</td>
            <td class="small text-muted">${esc(m.faculty || '—')}</td>
            <td class="small text-muted">${esc(m.department || '—')}</td>
            <td style="width:90px;">
                <input type="number" class="form-control form-control-sm" min="1" value="${m.team_no ?? ''}"
                       placeholder="—" onchange="updateTeamNo(${m.id}, this.value)">
            </td>
        </tr>`;
    }).join('');

    container.innerHTML = `
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr><th>#</th><th>氏名</th><th>学年</th><th>性別</th><th>学部</th><th>学科</th><th>班番号</th></tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>`;
}

// ── 班ビュー（班ごとのカード） ──
const TEAM_COLORS = [
    '#0d6efd', '#198754', '#dc3545', '#fd7e14', '#6f42c1',
    '#0dcaf0', '#d63384', '#20c997', '#ffc107', '#6610f2',
];

function genderLabel(g) { return g === 'male' ? '男' : (g === 'female' ? '女' : '—'); }

function renderTeamCards(container) {
    // 班番号ごとにグループ化
    const groups = {};        // team_no -> [members]
    const unassigned = [];
    teamMembers.forEach(m => {
        if (m.team_no === null) unassigned.push(m);
        else (groups[m.team_no] = groups[m.team_no] || []).push(m);
    });

    const teamNos = Object.keys(groups).map(Number).sort((a, b) => a - b);

    if (teamNos.length === 0 && unassigned.length === teamMembers.length) {
        container.innerHTML = `
            <div class="text-center p-4 text-muted">
                まだ班が割り当てられていません。<br>
                上の「班の自動振り分け」を実行するか、一覧ビューで班番号を入力してください。
            </div>`;
        return;
    }

    // 各メンバーカード内のミニ情報
    const memberChip = (m) => {
        const gColor = m.gender === 'male' ? 'text-primary' : (m.gender === 'female' ? 'text-danger' : 'text-muted');
        return `
            <div class="d-flex justify-content-between align-items-center py-1 px-2 border-bottom team-member-row">
                <div class="text-truncate">
                    <span class="fw-semibold">${esc(m.name_kanji)}</span>
                    <span class="small text-muted ms-1">${esc(gradeLabel(m.grade))}</span>
                    <span class="small ${gColor} ms-1">${genderLabel(m.gender)}</span>
                </div>
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <span class="small text-muted text-truncate d-none d-md-inline" style="max-width:90px;">${esc(m.department || m.faculty || '')}</span>
                    <select class="form-select form-select-sm team-move-select" style="width:auto;" onchange="updateTeamNo(${m.id}, this.value)">
                        ${teamMoveOptions(m.team_no, teamNos)}
                    </select>
                </div>
            </div>`;
    };

    // 班ごとの属性内訳バッジ
    const breakdown = (members) => {
        const males = members.filter(m => m.gender === 'male').length;
        const females = members.filter(m => m.gender === 'female').length;
        const gradeCount = {};
        members.forEach(m => { const g = gradeLabel(m.grade); gradeCount[g] = (gradeCount[g] || 0) + 1; });
        const gradeBadges = Object.keys(gradeCount)
            .sort((a, b) => gradeToNumber(a.replace('年','')) - gradeToNumber(b.replace('年','')))
            .map(g => `<span class="badge bg-light text-dark border me-1">${esc(g)}×${gradeCount[g]}</span>`)
            .join('');
        return `
            <div class="small mt-1">
                <span class="text-primary">男${males}</span> /
                <span class="text-danger">女${females}</span>
                <span class="ms-2">${gradeBadges}</span>
            </div>`;
    };

    const teamCard = (no, members) => {
        const color = TEAM_COLORS[(no - 1) % TEAM_COLORS.length];
        const sorted = [...members].sort((a, b) => (a.name_kana || '').localeCompare(b.name_kana || '', 'ja'));
        return `
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:${color};">
                        <span class="fw-bold fs-5">${no}班</span>
                        <span class="badge bg-light text-dark">${members.length}人</span>
                    </div>
                    <div class="card-body p-0">
                        ${sorted.map(memberChip).join('')}
                    </div>
                    <div class="card-footer py-2" style="border-top:2px solid ${color};">
                        ${breakdown(members)}
                    </div>
                </div>
            </div>`;
    };

    let html = '<div class="row g-3 p-3">';
    teamNos.forEach(no => { html += teamCard(no, groups[no]); });

    // 未割り当て
    if (unassigned.length > 0) {
        const sorted = [...unassigned].sort((a, b) => (a.name_kana || '').localeCompare(b.name_kana || '', 'ja'));
        html += `
            <div class="col-12">
                <div class="card border-secondary">
                    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                        <span class="fw-bold">未割り当て</span>
                        <span class="badge bg-light text-dark">${unassigned.length}人</span>
                    </div>
                    <div class="card-body p-0">
                        ${sorted.map(memberChip).join('')}
                    </div>
                </div>
            </div>`;
    }
    html += '</div>';
    container.innerHTML = html;
}

// 班移動用セレクトの選択肢
function teamMoveOptions(currentNo, teamNos) {
    let opts = `<option value="" ${currentNo === null ? 'selected' : ''}>未</option>`;
    teamNos.forEach(no => {
        opts += `<option value="${no}" ${currentNo === no ? 'selected' : ''}>${no}班</option>`;
    });
    return opts;
}

function updateTeamNo(appId, value) {
    const m = teamMembers.find(x => parseInt(x.id) === parseInt(appId));
    if (!m) return;
    m.team_no = (value === '' || value === null) ? null : parseInt(value);
    if (teamView === 'teams') {
        // 班ビューはメンバーが該当の班カードへ移動するよう全体を再描画
        renderTeamTable();
    } else {
        // 一覧ビューはサマリーだけ即時更新（入力中の体感を優先し並び替えはしない）
        renderTeamSummaryOnly();
    }
    scheduleAutoSave();
}

function renderTeamSummaryOnly() {
    const summary = document.getElementById('teamsSummary');
    const teamCounts = {};
    teamMembers.forEach(m => {
        const key = (m.team_no === null) ? '未割り当て' : `${m.team_no}班`;
        teamCounts[key] = (teamCounts[key] || 0) + 1;
    });
    const summaryBadges = Object.keys(teamCounts)
        .sort((a, b) => {
            if (a === '未割り当て') return 1;
            if (b === '未割り当て') return -1;
            return parseInt(a) - parseInt(b);
        })
        .map(k => {
            const cls = k === '未割り当て' ? 'bg-secondary' : 'bg-primary';
            return `<span class="badge ${cls} me-1 mb-1">${esc(k)}: ${teamCounts[k]}人</span>`;
        }).join('');
    summary.innerHTML = `<div class="mb-2">${summaryBadges}</div>`;
}

// ── 自動保存（デバウンス付き） ──
let autoSaveTimer = null;

function setSaveStatus(text, cls) {
    const el = document.getElementById('teamSaveStatus');
    if (el) el.innerHTML = `<span class="${cls || 'text-muted'}">${esc(text)}</span>`;
}

// 変更があってから少し待ってまとめて保存する
function scheduleAutoSave() {
    setSaveStatus('未保存の変更があります…', 'text-muted');
    if (autoSaveTimer) clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(saveTeams, 800);
}

async function saveTeams() {
    if (autoSaveTimer) { clearTimeout(autoSaveTimer); autoSaveTimer = null; }

    const assignments = {};
    teamMembers.forEach(m => { assignments[m.id] = m.team_no; });

    setSaveStatus('保存中…', 'text-muted');
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/teams`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ assignments }),
        });
        const data = await res.json();
        if (data.success) {
            setSaveStatus('✓ 自動保存しました', 'text-success');
        } else {
            setSaveStatus('保存に失敗しました', 'text-danger');
        }
    } catch (e) {
        setSaveStatus('通信エラー（未保存）', 'text-danger');
    }
}

// ──────────────────────────────────
// 申込URLトークン
// ──────────────────────────────────
document.querySelector('a[href="#tabToken"]').addEventListener('shown.bs.tab', loadToken);

async function loadToken() {
    const area = document.getElementById('tokenArea');
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/token`);
        const data = await res.json();
        const tok  = data.data?.token;
        renderToken(tok);
    } catch (e) {
        area.innerHTML = '<div class="text-danger">読み込みに失敗しました</div>';
    }
}

function renderToken(tok) {
    const area = document.getElementById('tokenArea');
    if (!tok) {
        area.innerHTML = '<div class="text-muted">申込URLは発行されていません</div>';
        return;
    }
    const url     = `${location.origin}/apply/event/${tok.token}`;
    const expired = tok.expires_at && new Date(tok.expires_at) < new Date();
    area.innerHTML = `
        <div class="border rounded p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="small text-muted">有効期限: ${tok.expires_at ? tok.expires_at.substring(0, 10) : '無期限'}
                        ${expired ? '<span class="badge bg-danger ms-1">期限切れ</span>' : ''}
                    </div>
                </div>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteToken()">
                    <i class="bi bi-trash"></i> 無効化
                </button>
            </div>
            <div class="input-group">
                <input type="text" class="form-control form-control-sm" value="${esc(url)}" readonly id="tokenUrlInput">
                <button class="btn btn-outline-secondary btn-sm" onclick="copyTokenUrl()">
                    <i class="bi bi-clipboard"></i> コピー
                </button>
            </div>
        </div>`;
}

async function generateToken() {
    if (!confirm('申込URLを発行しますか？既存のURLは無効になります。')) return;
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/token`, { method: 'POST' });
        const data = await res.json();
        if (data.success) { renderToken(data.data.token); showToast('URLを発行しました', 'success'); }
        else { alert(data.error?.message || '発行に失敗しました'); }
    } catch (e) { alert('通信エラーが発生しました'); }
}

async function deleteToken() {
    if (!confirm('このURLを無効にしますか？')) return;
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/token`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ _method: 'DELETE' }),
        });
        const data = await res.json();
        if (data.success) { renderToken(null); showToast('URLを無効化しました', 'success'); }
        else { alert(data.error?.message || '削除に失敗しました'); }
    } catch (e) { alert('通信エラーが発生しました'); }
}

function copyTokenUrl() {
    const input = document.getElementById('tokenUrlInput');
    if (input) navigator.clipboard.writeText(input.value).then(() => showToast('コピーしました', 'success'));
}
</script>
