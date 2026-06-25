<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0"><i class="bi bi-bag"></i> 物販管理</h2>
    <button class="btn btn-primary" id="createMerchBtn" onclick="openCreateModal()">
        <i class="bi bi-plus"></i> 新規商品
    </button>
</div>

<!-- タブ -->
<ul class="nav nav-tabs mb-4" id="merchTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-products" data-bs-toggle="tab" data-bs-target="#pane-products" type="button" role="tab">
            <i class="bi bi-grid"></i> 商品一覧
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-payments" data-bs-toggle="tab" data-bs-target="#pane-payments" type="button" role="tab">
            <i class="bi bi-cash-coin"></i> 支払い確認
        </button>
    </li>
</ul>

<div class="tab-content">
<!-- ===== 商品一覧タブ ===== -->
<div class="tab-pane fade show active" id="pane-products" role="tabpanel">

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

</div><!-- /pane-products -->

<!-- ===== 支払い確認タブ ===== -->
<div class="tab-pane fade" id="pane-payments" role="tabpanel">

    <!-- 入金確認済みの合計金額 -->
    <div class="row g-3 mb-3">
        <div class="col-md-5">
            <div class="card h-100 border-success">
                <div class="card-body">
                    <div class="text-muted small mb-1">
                        <i class="bi bi-cash-stack text-success"></i> 入金確認済みの総額（全期間）
                    </div>
                    <div class="fs-4 fw-bold text-success" id="paidTotalAll">¥0</div>
                    <div class="small text-muted" id="paidCountAll">0 件</div>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2">
                        <i class="bi bi-calendar-range"></i> 期間を指定して総額を確認
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-sm">
                            <label class="form-label small mb-1">開始日</label>
                            <input type="date" class="form-control form-control-sm" id="paidFrom">
                        </div>
                        <div class="col-sm">
                            <label class="form-label small mb-1">終了日</label>
                            <input type="date" class="form-control form-control-sm" id="paidTo">
                        </div>
                        <div class="col-sm-auto">
                            <button class="btn btn-sm btn-outline-secondary" type="button" id="paidRangeClear">クリア</button>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="fs-5 fw-bold" id="paidTotalRange">¥0</span>
                        <span class="small text-muted ms-1" id="paidCountRange">0 件</span>
                        <span class="small text-muted ms-1">（入金確認日 基準）</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 現金計算 -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-calculator"></i> 現金計算</span>
            <button class="btn btn-sm btn-outline-secondary" type="button" id="cashClear">
                <i class="bi bi-arrow-counterclockwise"></i> クリア
            </button>
        </div>
        <div class="card-body">
            <div class="row g-2" id="cashRows"></div>
            <hr>
            <div class="d-flex justify-content-end align-items-baseline gap-2">
                <span class="text-muted">合計</span>
                <span class="fs-3 fw-bold text-primary" id="cashTotal">¥0</span>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small mb-1">購入者検索</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="paySearch"
                               placeholder="氏名・カナ・学籍番号で検索">
                        <button class="btn btn-outline-secondary" type="button" id="paySearchClear" title="クリア">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">支払い状態</label>
                    <select class="form-select" id="payStatusFilter">
                        <option value="">すべて</option>
                        <option value="unpaid" selected>未入金</option>
                        <option value="paid">入金済</option>
                        <option value="cancelled">キャンセル</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="payReportedOnly">
                        <label class="form-check-label small" for="payReportedOnly">
                            振込報告済みのみ
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="text-muted small">確認待ち（未入金かつ振込報告済み）を上に表示します。</span>
        <span class="badge bg-secondary" id="payCount">0 件</span>
    </div>

    <div id="paymentsList">
        <div class="text-center text-muted py-5">読み込み中...</div>
    </div>
</div><!-- /pane-payments -->

</div><!-- /tab-content -->

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
let _payLoaded = false;
let _paySearchTimer = null;

document.addEventListener('DOMContentLoaded', () => {
    _createModal = new bootstrap.Modal(document.getElementById('createModal'));
    loadList();
    loadPending();
    initCashCalc();

    const createBtn = document.getElementById('createMerchBtn');

    // 支払い確認タブを初めて開いたときに読み込む
    document.getElementById('tab-payments').addEventListener('shown.bs.tab', () => {
        createBtn.classList.add('d-none'); // 支払いタブでは新規商品ボタンを隠す
        if (!_payLoaded) { _payLoaded = true; loadPayments(); loadPaidTotals(); }
    });
    document.getElementById('tab-products').addEventListener('shown.bs.tab', () => {
        createBtn.classList.remove('d-none');
    });

    // 検索（入力300ms後に自動実行）
    const searchInput = document.getElementById('paySearch');
    searchInput.addEventListener('input', () => {
        clearTimeout(_paySearchTimer);
        _paySearchTimer = setTimeout(loadPayments, 300);
    });
    document.getElementById('paySearchClear').addEventListener('click', () => {
        searchInput.value = '';
        loadPayments();
    });
    document.getElementById('payStatusFilter').addEventListener('change', loadPayments);
    document.getElementById('payReportedOnly').addEventListener('change', loadPayments);

    // 入金確認済み総額の期間フィルタ
    document.getElementById('paidFrom').addEventListener('change', loadPaidTotals);
    document.getElementById('paidTo').addEventListener('change', loadPaidTotals);
    document.getElementById('paidRangeClear').addEventListener('click', () => {
        document.getElementById('paidFrom').value = '';
        document.getElementById('paidTo').value = '';
        loadPaidTotals();
    });
});

// ===== 現金計算 =====
const CASH_DENOMS = [
    { v: 10000, label: '1万円札' },
    { v: 5000,  label: '5千円札' },
    { v: 1000,  label: '千円札' },
    { v: 500,   label: '500円玉' },
    { v: 100,   label: '100円玉' },
    { v: 50,    label: '50円玉' },
    { v: 10,    label: '10円玉' },
    { v: 5,     label: '5円玉' },
    { v: 1,     label: '1円玉' },
];

function initCashCalc() {
    const root = document.getElementById('cashRows');
    if (!root) return;
    root.innerHTML = CASH_DENOMS.map(d => `
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text" style="min-width: 6rem;">${d.label}</span>
                <input type="number" class="form-control text-end cash-qty" data-v="${d.v}"
                       min="0" step="1" inputmode="numeric" placeholder="0">
                <span class="input-group-text">枚</span>
                <span class="input-group-text text-muted cash-sub" style="min-width: 6.5rem;" data-v="${d.v}">¥0</span>
            </div>
        </div>`).join('');

    root.querySelectorAll('.cash-qty').forEach(inp => {
        inp.addEventListener('input', updateCashTotal);
    });
    document.getElementById('cashClear').addEventListener('click', () => {
        root.querySelectorAll('.cash-qty').forEach(inp => { inp.value = ''; });
        updateCashTotal();
    });
    updateCashTotal();
}

function updateCashTotal() {
    let total = 0;
    document.querySelectorAll('#cashRows .cash-qty').forEach(inp => {
        const v   = Number(inp.dataset.v);
        const qty = Math.max(0, parseInt(inp.value) || 0);
        const sub = v * qty;
        total += sub;
        const subEl = document.querySelector(`#cashRows .cash-sub[data-v="${v}"]`);
        if (subEl) {
            subEl.textContent = '¥' + sub.toLocaleString();
            subEl.classList.toggle('text-muted', sub === 0);
            subEl.classList.toggle('fw-bold', sub > 0);
        }
    });
    document.getElementById('cashTotal').textContent = '¥' + total.toLocaleString();
}

// 入金確認済み注文の総額（全期間＋指定期間）を読み込む
async function loadPaidTotals() {
    const from = document.getElementById('paidFrom').value;
    const to   = document.getElementById('paidTo').value;

    // 全期間の総額
    try {
        const res  = await fetch('/api/merchandise/paid-total');
        const data = await res.json();
        if (data.success) {
            document.getElementById('paidTotalAll').textContent = '¥' + Number(data.data.total).toLocaleString();
            document.getElementById('paidCountAll').textContent = `${data.data.count} 件`;
        }
    } catch (e) { /* 表示はそのまま */ }

    // 指定期間の総額
    const params = new URLSearchParams();
    if (from) params.set('from', from);
    if (to)   params.set('to', to);
    try {
        const res  = await fetch('/api/merchandise/paid-total?' + params.toString());
        const data = await res.json();
        if (data.success) {
            document.getElementById('paidTotalRange').textContent = '¥' + Number(data.data.total).toLocaleString();
            document.getElementById('paidCountRange').textContent = `${data.data.count} 件`;
        }
    } catch (e) { /* 表示はそのまま */ }
}

async function loadPayments() {
    const q         = document.getElementById('paySearch').value.trim();
    const status    = document.getElementById('payStatusFilter').value;
    const reported  = document.getElementById('payReportedOnly').checked;
    const root      = document.getElementById('paymentsList');
    const countEl   = document.getElementById('payCount');

    const params = new URLSearchParams();
    if (q)        params.set('q', q);
    if (status)   params.set('status', status);
    if (reported) params.set('submitted', '1');

    root.innerHTML = '<div class="text-center text-muted py-5">読み込み中...</div>';

    const res  = await fetch('/api/merchandise/payments?' + params.toString());
    const data = await res.json();
    if (!data.success) {
        root.innerHTML = '<div class="alert alert-danger">読み込みに失敗しました</div>';
        return;
    }
    const orders = data.data.orders;
    countEl.textContent = `${orders.length} 件`;

    if (!orders.length) {
        root.innerHTML = '<div class="card"><div class="card-body text-center text-muted py-5">該当する注文がありません</div></div>';
        return;
    }

    root.innerHTML = orders.map(o => {
        const items = o.items.map(it => `
            <li class="small">
                ${escapeHtml(it.merchandise_name)}${it.color_name ? '／' + escapeHtml(it.color_name) : ''}${it.size_name ? '／' + escapeHtml(it.size_name) : ''} × ${it.quantity}（¥${Number(it.subtotal).toLocaleString()}）
            </li>`).join('');

        const isReported = (o.payment_status === 'unpaid' && Number(o.payment_submitted) === 1);
        const paidBadge = o.payment_status === 'paid'
            ? '<span class="badge bg-success">入金済</span>'
            : (o.payment_status === 'cancelled'
                ? '<span class="badge bg-secondary">キャンセル</span>'
                : '<span class="badge bg-warning text-dark">未入金</span>');
        const reportedBadge = isReported
            ? `<span class="badge bg-info text-dark ms-1" title="${o.payment_submitted_at ? '報告: ' + String(o.payment_submitted_at).substring(0,16).replace('T',' ') : ''}"><i class="bi bi-send-check"></i> 振込報告済</span>`
            : '';

        // 購入者の表示名・識別子
        const buyerName = o.member_name_kanji || o.buyer_name || '(不明)';
        const idTag = o.member_id
            ? `<span class="badge bg-light text-dark border ms-1">学籍 ${escapeHtml(o.member_student_id || '-')}</span>`
            : (o.pending_student_id
                ? `<span class="badge bg-warning text-dark ms-1">未入会 ${escapeHtml(o.pending_student_id)}</span>`
                : '');

        // 申告金額（注文合計と相違あれば赤字）
        const amountLine = (Number(o.payment_submitted) === 1 && o.paid_amount !== null && o.paid_amount !== undefined)
            ? `<div class="small ${Number(o.paid_amount) !== Number(o.total_amount) ? 'text-danger fw-bold' : 'text-muted'}">
                 申告金額: ¥${Number(o.paid_amount).toLocaleString()}${Number(o.paid_amount) !== Number(o.total_amount) ? '（注文合計と相違）' : ''}
               </div>`
            : '';

        return `
        <div class="card mb-2 ${isReported ? 'border-info' : ''}">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong>${escapeHtml(buyerName)}</strong>${idTag}
                        ${paidBadge}${reportedBadge}
                        <small class="text-muted ms-2">${o.created_at ? o.created_at.substring(0, 16).replace('T', ' ') : ''}</small>
                        <ul class="mt-2 mb-1">${items}</ul>
                        ${amountLine}
                        ${o.buyer_contact ? `<small class="text-muted">連絡先: ${escapeHtml(o.buyer_contact)}</small>` : ''}
                        ${o.notes ? `<div class="text-muted small">備考: ${escapeHtml(o.notes)}</div>` : ''}
                    </div>
                    <div class="text-end" style="min-width: 9rem;">
                        <div class="fw-bold mb-1">¥${Number(o.total_amount).toLocaleString()}</div>
                        <button class="btn btn-sm ${o.payment_status === 'paid' ? 'btn-outline-success' : (isReported ? 'btn-primary' : 'btn-success')}"
                                onclick="payTogglePaid(${o.id})">
                            ${o.payment_status === 'paid' ? '未入金に戻す' : '入金確認'}
                        </button>
                    </div>
                </div>
            </div>
        </div>`;
    }).join('');
}

async function payTogglePaid(id) {
    const res  = await fetch(`/api/merchandise/orders/${id}/toggle-paid`, { method: 'POST' });
    const data = await res.json();
    if (data.success) {
        loadPayments();
        loadPaidTotals(); // 入金確認状態が変わると総額も変わる
    } else {
        alert('更新に失敗しました');
    }
}

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
