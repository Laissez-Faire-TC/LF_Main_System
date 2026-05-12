<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-bag"></i> 物販管理</h2>
    <button class="btn btn-primary" onclick="openCreateModal()">
        <i class="bi bi-plus"></i> 新規商品
    </button>
</div>

<!-- 未入会の購入者（学籍番号が会員DBに無い） -->
<div id="unenrolledSection" class="card border-danger mb-4 d-none">
    <div class="card-header bg-danger bg-opacity-10 border-danger d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-exclamation-octagon-fill text-danger"></i>
            未入会の購入者
            <span class="badge bg-danger" id="unenrolledCount">0</span>
        </span>
        <small class="text-muted">入会フォーム提出待ち。提出後に自動で紐付けされます。</small>
    </div>
    <div class="card-body p-0" id="unenrolledList"></div>
</div>

<!-- 入会済みだがマッチング待ち -->
<div id="pendingSection" class="card border-warning mb-4 d-none">
    <div class="card-header bg-warning bg-opacity-25 border-warning d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-person-check"></i>
            DB登録済み（マッチング待ち）
            <span class="badge bg-warning text-dark" id="pendingCount">0</span>
        </span>
        <button class="btn btn-warning btn-sm" onclick="matchAll()">
            <i class="bi bi-people"></i> 会員DBと一括マッチング
        </button>
    </div>
    <div class="card-body p-0" id="pendingList"></div>
</div>

<div id="merchandiseList">
    <div class="text-center text-muted py-5">読み込み中...</div>
</div>

<!-- 新規作成モーダル -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新規商品</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label">商品名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="newName" placeholder="例: レッセTシャツ 2026年版">
                </div>
                <div class="mb-2">
                    <label class="form-label">価格 (円)</label>
                    <input type="number" class="form-control" id="newPrice" min="0" value="0">
                </div>
                <div class="mb-2">
                    <label class="form-label">説明</label>
                    <textarea class="form-control" id="newDesc" rows="3"></textarea>
                </div>
                <div id="createErr" class="alert alert-danger d-none"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button class="btn btn-primary" onclick="createMerchandise()">作成</button>
            </div>
        </div>
    </div>
</div>

<script>
let _createModal;
document.addEventListener('DOMContentLoaded', () => {
    _createModal = new bootstrap.Modal(document.getElementById('createModal'));
    loadList();
    loadPending();
});

async function loadPending() {
    const res  = await fetch('/api/merchandise/pending-orders');
    const data = await res.json();

    const matchedSec    = document.getElementById('pendingSection');
    const unenrolledSec = document.getElementById('unenrolledSection');

    if (!data.success || !data.data.orders.length) {
        matchedSec.classList.add('d-none');
        unenrolledSec.classList.add('d-none');
        return;
    }

    const matched    = data.data.orders.filter(o =>  o.matched_member_id);
    const unenrolled = data.data.orders.filter(o => !o.matched_member_id);

    renderPendingList(matchedSec,    'pendingList',    'pendingCount',    matched,    'matched');
    renderPendingList(unenrolledSec, 'unenrolledList', 'unenrolledCount', unenrolled, 'unenrolled');
}

function renderPendingList(section, listId, countId, orders, kind) {
    if (!orders.length) {
        section.classList.add('d-none');
        return;
    }
    section.classList.remove('d-none');
    document.getElementById(countId).textContent = orders.length;
    document.getElementById(listId).innerHTML = orders.map(o => {
        const items = o.items.map(it => `
            ${escapeHtml(it.merchandise_name)}${it.color_name ? '／' + escapeHtml(it.color_name) : ''}${it.size_name ? '／' + escapeHtml(it.size_name) : ''} × ${it.quantity}
        `).join('、 ');

        const ageInfo = formatAge(o.created_at, kind);
        const tag = kind === 'unenrolled'
            ? '<span class="badge bg-danger ms-1"><i class="bi bi-exclamation-circle"></i> 未入会</span>'
            : `<span class="badge bg-success ms-1">DB登録済み: ${escapeHtml(o.matched_member_name || '')}</span>`;

        return `
        <div class="p-3 border-bottom">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>${escapeHtml(o.buyer_name)}</strong>
                    <span class="badge bg-secondary ms-1">${escapeHtml(o.pending_student_id)}</span>
                    ${tag}
                    ${ageInfo.badge}
                    <div class="small text-muted mt-1">
                        ${o.pending_line_name ? `LINE: ${escapeHtml(o.pending_line_name)}` : ''}
                        ${o.pending_phone     ? ` ／ TEL: ${escapeHtml(o.pending_phone)}`  : ''}
                    </div>
                    <div class="small mt-1">${items}</div>
                </div>
                <div class="text-end">
                    <div class="fw-bold">¥${Number(o.total_amount).toLocaleString()}</div>
                    <small class="text-muted">${o.created_at ? o.created_at.substring(0, 16).replace('T', ' ') : ''}</small>
                </div>
            </div>
        </div>`;
    }).join('');
}

function formatAge(createdAt, kind) {
    if (!createdAt) return { badge: '' };
    const created = new Date(createdAt.replace(' ', 'T'));
    const days = Math.floor((Date.now() - created.getTime()) / 86400000);

    let label, cls;
    if (days < 1)        { label = '本日';                cls = 'bg-light text-dark border'; }
    else if (days < 7)   { label = `${days}日前`;          cls = 'bg-light text-dark border'; }
    else if (days < 14)  { label = `${days}日前`;          cls = 'bg-warning text-dark'; }
    else if (days < 30)  { label = `${days}日前`;          cls = 'bg-warning text-dark'; }
    else                 { label = `${days}日前 放置`;     cls = 'bg-danger'; }

    // 未入会セクションは7日以上で目立たせる
    if (kind === 'unenrolled' && days >= 7) cls = 'bg-danger';

    return { badge: `<span class="badge ${cls} ms-1"><i class="bi bi-clock"></i> ${label}</span>` };
}

async function matchAll() {
    if (!confirm('会員DBに登録済みの学籍番号を持つ注文を一括で紐付けますか？')) return;
    const res  = await fetch('/api/merchandise/pending-orders/match-all', { method: 'POST' });
    const data = await res.json();
    if (data.success) {
        alert(`${data.data.matched} 件マッチング完了。残り未マッチ: ${data.data.unmatched} 件`);
        loadPending();
    } else {
        alert('マッチングに失敗しました');
    }
}

async function loadList() {
    const res  = await fetch('/api/merchandise');
    const data = await res.json();
    const root = document.getElementById('merchandiseList');
    if (!data.success || !data.data.merchandise.length) {
        root.innerHTML = '<div class="card"><div class="card-body text-center text-muted py-5">商品が登録されていません</div></div>';
        return;
    }

    const now = new Date();
    root.innerHTML = `<div class="row g-3">${data.data.merchandise.map(m => {
        const start    = m.sale_start ? new Date(m.sale_start) : null;
        const end      = m.sale_end   ? new Date(m.sale_end)   : null;
        const inSale   = m.is_active == 1
                         && (!start || start <= now)
                         && (!end   || end   >= now);
        const status   = inSale
            ? '<span class="badge bg-success">販売中</span>'
            : (m.is_active == 1
                ? (start && start > now ? '<span class="badge bg-info">販売前</span>' : '<span class="badge bg-secondary">販売終了</span>')
                : '<span class="badge bg-secondary">停止中</span>');
        return `
        <div class="col-md-6 col-lg-4">
            <a class="card h-100 text-decoration-none text-dark" href="/merchandise/${m.id}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="mb-0">${escapeHtml(m.name)}</h5>
                        ${status}
                    </div>
                    <p class="text-muted small mb-2">${escapeHtml(m.description || '').substring(0, 80)}</p>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold text-primary">¥${Number(m.price).toLocaleString()}</span>
                        <small class="text-muted">注文 ${m.order_count} 件</small>
                    </div>
                </div>
            </a>
        </div>`;
    }).join('')}</div>`;
}

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s ?? '';
    return div.innerHTML;
}

function openCreateModal() {
    document.getElementById('newName').value  = '';
    document.getElementById('newPrice').value = '0';
    document.getElementById('newDesc').value  = '';
    document.getElementById('createErr').classList.add('d-none');
    _createModal.show();
}

async function createMerchandise() {
    const name  = document.getElementById('newName').value.trim();
    const price = parseInt(document.getElementById('newPrice').value) || 0;
    const desc  = document.getElementById('newDesc').value.trim();
    const err   = document.getElementById('createErr');
    err.classList.add('d-none');

    if (!name) {
        err.textContent = '商品名を入力してください';
        err.classList.remove('d-none');
        return;
    }

    const res  = await fetch('/api/merchandise', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ name, price, description: desc, is_active: 1 }),
    });
    const data = await res.json();
    if (data.success) {
        _createModal.hide();
        location.href = '/merchandise/' + data.data.id;
    } else {
        err.textContent = data.error?.message || '作成に失敗しました';
        err.classList.remove('d-none');
    }
}
</script>
