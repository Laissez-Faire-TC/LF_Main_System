<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>企画一覧</h1>
    <button class="btn btn-primary" onclick="showCreateModal()">
        + 新規企画作成
    </button>
</div>

<!-- 企画一覧テーブル -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div id="eventsTableContainer">
            <div class="text-center p-4 text-muted">読み込み中...</div>
        </div>
    </div>
</div>

<!-- 作成/編集モーダル -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalTitle">企画作成</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editEventId">
                <div class="mb-3">
                    <label class="form-label fw-semibold">タイトル <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="eventTitle" placeholder="例: 春季歓迎テニス大会">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">開催日 <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="eventDate">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">開始時刻</label>
                        <input type="time" class="form-control" id="eventTime">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">場所</label>
                    <input type="text" class="form-control" id="eventLocation" placeholder="例: 〇〇テニスコート">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">概要</label>
                    <textarea class="form-control" id="eventDescription" rows="3" placeholder="イベントの説明を入力"></textarea>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">参加費（円）</label>
                        <input type="number" class="form-control" id="eventFee" value="0" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">定員</label>
                        <input type="number" class="form-control" id="eventCapacity" placeholder="空欄 = 制限なし" min="1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">申込期限</label>
                        <input type="date" class="form-control" id="eventDeadline">
                        <div class="form-text">期限を過ぎると会員ページから非表示になります</div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="eventAllowWaitlist">
                            <label class="form-check-label" for="eventAllowWaitlist">キャンセル待ちを受け付ける</label>
                            <div class="form-text">定員超過後もキャンセル待ちで申し込み可能にする</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="eventIsActive">
                            <label class="form-check-label" for="eventIsActive">会員ページに公開する</label>
                        </div>
                    </div>
                </div>

                <hr class="my-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="eventAllowGuest" onchange="onAllowGuestChange()">
                            <label class="form-check-label" for="eventAllowGuest">会員以外（ゲスト）の申し込みを受け付ける</label>
                        </div>
                    </div>
                    <div class="col-md-6" id="guestTypeWrap" style="display:none;">
                        <label class="form-label fw-semibold">ゲストフォームの種別 <span class="text-danger">*</span></label>
                        <select class="form-select" id="eventGuestType">
                            <option value="shinkan">新歓（氏名・カナ・学科で本人特定）</option>
                            <option value="obog">OBOG（氏名・カナ・代で本人特定）</option>
                        </select>
                        <div class="form-text">本人特定の項目が種別ごとに変わります。作成後に変更も可能です。</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" onclick="saveEvent()">保存</button>
            </div>
        </div>
    </div>
</div>

<!-- 削除確認モーダル -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">企画の削除</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>「<span id="deleteEventTitle"></span>」を削除しますか？</p>
                <p class="text-danger small mb-0">申込データも含めてすべて削除されます。この操作は取り消せません。</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">削除する</button>
            </div>
        </div>
    </div>
</div>

<script>
let events = [];
let deleteTargetId = null;

// ──────────────────────────────────
// 初期ロード
// ──────────────────────────────────
async function loadEvents() {
    try {
        const res  = await fetch('/api/events');
        const data = await res.json();
        events = data.data || [];
        renderTable();
    } catch (e) {
        document.getElementById('eventsTableContainer').innerHTML =
            '<div class="text-center p-4 text-danger">読み込みに失敗しました</div>';
    }
}

function renderTable() {
    const container = document.getElementById('eventsTableContainer');

    if (events.length === 0) {
        container.innerHTML = `
            <div class="text-center p-5 text-muted">
                <i class="bi bi-calendar-x" style="font-size:2rem;"></i>
                <p class="mt-2 mb-0">企画がありません。「+ 新規企画作成」から作成してください。</p>
            </div>`;
        return;
    }

    const rows = events.map(e => {
        const capacityBadge = buildCapacityBadge(e);
        const dateStr     = formatDate(e.event_date) + (e.event_time ? ' ' + e.event_time.slice(0,5) : '');
        const deadlinePast = e.deadline && new Date(e.deadline + 'T23:59:59') < new Date();
        const activeBadge = !e.is_active
            ? '<span class="badge bg-secondary">非公開</span>'
            : deadlinePast
                ? '<span class="badge bg-warning text-dark">締め切り済み</span>'
                : '<span class="badge bg-success">公開中</span>';
        const deadlineBadge = e.deadline
            ? `<span class="badge ${deadlinePast ? 'bg-danger' : 'bg-light text-dark border'}">${formatDate(e.deadline)}</span>`
            : '<span class="text-muted small">なし</span>';

        return `
        <tr>
            <td>
                <a href="/events/${e.id}" class="fw-semibold text-decoration-none">${esc(e.title)}</a>
            </td>
            <td class="text-nowrap">${dateStr}</td>
            <td>${esc(e.location || '—')}</td>
            <td class="text-end">${Number(e.participation_fee).toLocaleString()}円</td>
            <td>${capacityBadge}</td>
            <td>${deadlineBadge}</td>
            <td>${activeBadge}</td>
            <td class="text-end text-nowrap">
                <button class="btn btn-sm btn-outline-secondary me-1" onclick="showEditModal(${e.id})">編集</button>
                <button class="btn btn-sm btn-outline-danger" onclick="showDeleteModal(${e.id})">削除</button>
            </td>
        </tr>`;
    }).join('');

    container.innerHTML = `
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>タイトル</th>
                    <th>日時</th>
                    <th>場所</th>
                    <th class="text-end">参加費</th>
                    <th>申込</th>
                    <th>期限</th>
                    <th>状態</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>`;
}

function buildCapacityBadge(e) {
    const count    = parseInt(e.application_count) || 0;
    const capacity = e.capacity !== null ? parseInt(e.capacity) : null;

    if (capacity === null) {
        return `<span class="badge bg-light text-dark border">${count}人</span>`;
    }

    const isFull = count >= capacity;
    const cls    = isFull ? 'bg-danger' : (count / capacity >= 0.8 ? 'bg-warning text-dark' : 'bg-primary');
    return `<span class="badge ${cls}">${count}/${capacity}</span>`;
}

// ──────────────────────────────────
// 作成モーダル
// ──────────────────────────────────
function showCreateModal() {
    document.getElementById('eventModalTitle').textContent = '企画作成';
    document.getElementById('editEventId').value    = '';
    document.getElementById('eventTitle').value     = '';
    document.getElementById('eventDate').value      = '';
    document.getElementById('eventTime').value      = '';
    document.getElementById('eventLocation').value  = '';
    document.getElementById('eventDescription').value = '';
    document.getElementById('eventFee').value       = '0';
    document.getElementById('eventCapacity').value      = '';
    document.getElementById('eventDeadline').value      = '';
    document.getElementById('eventAllowWaitlist').checked = false;
    document.getElementById('eventIsActive').checked     = false;
    document.getElementById('eventAllowGuest').checked   = false;
    document.getElementById('eventGuestType').value      = 'shinkan';
    onAllowGuestChange();
    new bootstrap.Modal(document.getElementById('eventModal')).show();
}

// ゲスト受付ONのときだけ種別選択を表示
function onAllowGuestChange() {
    const on = document.getElementById('eventAllowGuest').checked;
    document.getElementById('guestTypeWrap').style.display = on ? 'block' : 'none';
}

function showEditModal(id) {
    const e = events.find(x => x.id == id);
    if (!e) return;

    document.getElementById('eventModalTitle').textContent = '企画編集';
    document.getElementById('editEventId').value    = e.id;
    document.getElementById('eventTitle').value     = e.title;
    document.getElementById('eventDate').value      = e.event_date;
    document.getElementById('eventTime').value      = e.event_time ? e.event_time.slice(0,5) : '';
    document.getElementById('eventLocation').value  = e.location  || '';
    document.getElementById('eventDescription').value = e.description || '';
    document.getElementById('eventFee').value       = e.participation_fee;
    document.getElementById('eventCapacity').value        = e.capacity !== null ? e.capacity : '';
    document.getElementById('eventDeadline').value        = e.deadline || '';
    document.getElementById('eventAllowWaitlist').checked = !!parseInt(e.allow_waitlist);
    document.getElementById('eventIsActive').checked      = !!parseInt(e.is_active);
    document.getElementById('eventAllowGuest').checked    = !!parseInt(e.allow_guest);
    document.getElementById('eventGuestType').value       = e.guest_type || 'shinkan';
    onAllowGuestChange();
    new bootstrap.Modal(document.getElementById('eventModal')).show();
}

async function saveEvent() {
    const id    = document.getElementById('editEventId').value;
    const title = document.getElementById('eventTitle').value.trim();
    const date  = document.getElementById('eventDate').value;

    if (!title) { alert('タイトルを入力してください'); return; }
    if (!date)  { alert('開催日を入力してください'); return; }

    const payload = {
        title:             title,
        event_date:        date,
        event_time:        document.getElementById('eventTime').value        || null,
        location:          document.getElementById('eventLocation').value.trim() || null,
        description:       document.getElementById('eventDescription').value.trim() || null,
        participation_fee: parseInt(document.getElementById('eventFee').value) || 0,
        capacity:          document.getElementById('eventCapacity').value !== ''
                               ? parseInt(document.getElementById('eventCapacity').value) : null,
        deadline:          document.getElementById('eventDeadline').value || null,
        allow_waitlist:    document.getElementById('eventAllowWaitlist').checked ? 1 : 0,
        is_active:         document.getElementById('eventIsActive').checked ? 1 : 0,
        allow_guest:       document.getElementById('eventAllowGuest').checked ? 1 : 0,
        guest_type:        document.getElementById('eventAllowGuest').checked
                               ? document.getElementById('eventGuestType').value : null,
    };

    try {
        const method = id ? 'PUT' : 'POST';
        const url    = id ? `/api/events/${id}` : '/api/events';
        const res    = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('eventModal')).hide();
            loadEvents();
        } else {
            alert(data.error?.message || '保存に失敗しました');
        }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

// ──────────────────────────────────
// 削除
// ──────────────────────────────────
function showDeleteModal(id) {
    const e = events.find(x => x.id == id);
    if (!e) return;
    deleteTargetId = id;
    document.getElementById('deleteEventTitle').textContent = e.title;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

async function confirmDelete() {
    if (!deleteTargetId) return;
    try {
        const res  = await fetch(`/api/events/${deleteTargetId}`, { method: 'DELETE' });
        const data = await res.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
            loadEvents();
        } else {
            alert(data.error?.message || '削除に失敗しました');
        }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

// ──────────────────────────────────
// ユーティリティ
// ──────────────────────────────────
function esc(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;');
}

function formatDate(str) {
    if (!str) return '—';
    const d = new Date(str + 'T00:00:00');
    return `${d.getFullYear()}/${d.getMonth()+1}/${d.getDate()}`;
}

loadEvents();
</script>
