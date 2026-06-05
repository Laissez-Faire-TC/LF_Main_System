<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="/merchandise" class="text-muted text-decoration-none small">
            <i class="bi bi-arrow-left"></i> 物販一覧
        </a>
        <h2 class="mb-0 mt-1" id="merchandiseTitle">読み込み中...</h2>
    </div>
    <button class="btn btn-outline-danger btn-sm" onclick="deleteMerchandise()">
        <i class="bi bi-trash"></i> 削除
    </button>
</div>

<ul class="nav nav-tabs mb-3" id="merchTabs">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabBasic">基本情報</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabColors">色（画像）</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabSizes">サイズ</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabOrders">注文一覧</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabUrls">公開URL</a></li>
</ul>

<div class="tab-content">
    <!-- ===== タブ1: 基本情報 ===== -->
    <div class="tab-pane fade show active" id="tabBasic">
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">商品名 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editName" onchange="scheduleSave()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">価格 (円)</label>
                        <input type="number" class="form-control" id="editPrice" min="0" onchange="scheduleSave()">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">販売開始日時</label>
                        <input type="datetime-local" class="form-control" id="editSaleStart" onchange="scheduleSave()">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">販売終了日時</label>
                        <input type="datetime-local" class="form-control" id="editSaleEnd" onchange="scheduleSave()">
                    </div>
                    <div class="col-12">
                        <label class="form-label">説明</label>
                        <textarea class="form-control" id="editDesc" rows="4" onchange="scheduleSave()"
                                  placeholder="商品の説明、素材、デザインの由来など"></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="editActive" onchange="scheduleSave()">
                            <label class="form-check-label" for="editActive">販売中（チェックを外すと非公開）</label>
                        </div>
                    </div>
                </div>
                <div class="text-muted small mt-2"><span id="saveStatus"></span></div>
            </div>
        </div>
    </div>

    <!-- ===== タブ2: 色 ===== -->
    <div class="tab-pane fade" id="tabColors">
        <div class="card">
            <div class="card-body">
                <p class="text-muted small">色ごとに画像を1枚アップロードできます。並び順がそのまま会員側の表示順になります。</p>
                <div id="colorList"></div>
                <button class="btn btn-outline-primary btn-sm mt-2" onclick="addColorRow()">
                    <i class="bi bi-plus"></i> 色を追加
                </button>
                <div class="d-flex justify-content-end mt-3">
                    <button class="btn btn-primary" onclick="saveColors()">
                        <i class="bi bi-save"></i> 色を保存
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== タブ3: サイズ ===== -->
    <div class="tab-pane fade" id="tabSizes">
        <div class="card">
            <div class="card-body">
                <p class="text-muted small">用意するサイズを入力してください（例: S, M, L, XL）。</p>
                <div id="sizeList"></div>
                <button class="btn btn-outline-primary btn-sm mt-2" onclick="addSizeRow()">
                    <i class="bi bi-plus"></i> サイズを追加
                </button>
                <div class="d-flex justify-content-end mt-3">
                    <button class="btn btn-primary" onclick="saveSizes()">
                        <i class="bi bi-save"></i> サイズを保存
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== タブ4: 注文一覧 ===== -->
    <div class="tab-pane fade" id="tabOrders">
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title">商品別 集計</h6>
                <div id="summaryArea" class="text-muted small">読み込み中...</div>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <div class="btn-group btn-group-sm" role="group">
                <button class="btn btn-outline-secondary active" data-status="" onclick="filterOrders('')">全て</button>
                <button class="btn btn-outline-secondary" data-status="unpaid" onclick="filterOrders('unpaid')">未入金</button>
                <button class="btn btn-outline-secondary" data-status="paid" onclick="filterOrders('paid')">入金済</button>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-success btn-sm" onclick="exportOrders('xlsx')">
                    <i class="bi bi-file-earmark-excel"></i> Excel出力
                </button>
                <button class="btn btn-outline-danger btn-sm" onclick="exportOrders('pdf')">
                    <i class="bi bi-file-earmark-pdf"></i> PDF出力
                </button>
                <button class="btn btn-outline-primary btn-sm" onclick="loadOrders()">
                    <i class="bi bi-arrow-clockwise"></i> 更新
                </button>
            </div>
        </div>
        <div id="ordersList"></div>
    </div>

    <!-- ===== タブ5: 公開URL ===== -->
    <div class="tab-pane fade" id="tabUrls">
        <div class="card">
            <div class="card-body">
                <p class="text-muted small">
                    この商品専用の公開ショップURLを発行できます。URLを受け取った方は会員ログイン後にこの商品を注文できます。
                </p>
                <div class="d-flex gap-2 mb-3">
                    <input type="text" class="form-control" id="tokenLabel" placeholder="ラベル（例: 2026年OB会用）">
                    <button class="btn btn-primary" onclick="generateToken()">
                        <i class="bi bi-link-45deg"></i> 新規発行
                    </button>
                </div>
                <div id="tokenList"></div>
            </div>
        </div>
    </div>
</div>

<script>
const merchandiseId = <?= (int)$id ?>;
let merchandise     = null;
let saveTimer       = null;
let _orderFilter    = '';

document.addEventListener('DOMContentLoaded', async () => {
    await loadMerchandise();   // 集計表で色・サイズ定義を使うため先に読み込む
    loadOrders();
    loadSummary();
    loadTokens();
});

async function loadMerchandise() {
    const res  = await fetch('/api/merchandise/' + merchandiseId);
    const data = await res.json();
    if (!data.success) {
        document.getElementById('merchandiseTitle').textContent = '商品が見つかりません';
        return;
    }
    merchandise = data.data;
    renderBasic();
    renderColors();
    renderSizes();
}

function renderBasic() {
    const m = merchandise;
    document.getElementById('merchandiseTitle').textContent = m.name;
    document.getElementById('editName').value      = m.name || '';
    document.getElementById('editPrice').value     = m.price || 0;
    document.getElementById('editDesc').value      = m.description || '';
    document.getElementById('editSaleStart').value = (m.sale_start || '').replace(' ', 'T').substring(0, 16);
    document.getElementById('editSaleEnd').value   = (m.sale_end   || '').replace(' ', 'T').substring(0, 16);
    document.getElementById('editActive').checked  = (m.is_active == 1);
}

function scheduleSave() {
    clearTimeout(saveTimer);
    document.getElementById('saveStatus').textContent = '保存中...';
    saveTimer = setTimeout(saveBasic, 500);
}

async function saveBasic() {
    const body = {
        name:        document.getElementById('editName').value.trim(),
        price:       parseInt(document.getElementById('editPrice').value) || 0,
        description: document.getElementById('editDesc').value,
        sale_start:  document.getElementById('editSaleStart').value || null,
        sale_end:    document.getElementById('editSaleEnd').value   || null,
        is_active:   document.getElementById('editActive').checked ? 1 : 0,
    };
    const res = await fetch('/api/merchandise/' + merchandiseId, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ ...body, _method: 'PUT' }),
    });
    const data = await res.json();
    document.getElementById('saveStatus').textContent = data.success ? '保存しました' : '保存に失敗しました';
    if (data.success) {
        merchandise = data.data;
        document.getElementById('merchandiseTitle').textContent = merchandise.name;
        setTimeout(() => document.getElementById('saveStatus').textContent = '', 1500);
    }
}

// ===== 色 =====
function renderColors() {
    const root = document.getElementById('colorList');
    root.innerHTML = '';
    (merchandise.colors || []).forEach((c, i) => addColorRow(c, i));
    if (!merchandise.colors || merchandise.colors.length === 0) {
        addColorRow();
    }
}

function addColorRow(c = null, i = null) {
    const root = document.getElementById('colorList');
    const idx  = root.children.length;
    const row  = document.createElement('div');
    row.className = 'border rounded p-3 mb-2 color-row';
    row.dataset.index = idx;
    row.innerHTML = `
        <div class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="form-label small">色名</label>
                <input type="text" class="form-control form-control-sm color-name" value="${escapeHtml(c?.color_name || '')}" placeholder="例: ブラック">
            </div>
            <div class="col-md-5">
                <label class="form-label small">画像</label>
                <input type="file" class="form-control form-control-sm color-image-file" accept="image/*" onchange="uploadColorImage(this, ${idx})">
                <input type="hidden" class="color-image-path" value="${c?.image_path || ''}">
                <div class="color-image-preview mt-1">
                    ${c?.image_path ? `<img src="${c.image_path}" style="max-height: 60px;" class="rounded">` : ''}
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small">並び</label>
                <input type="number" class="form-control form-control-sm color-sort" value="${c?.sort_order ?? idx}">
            </div>
            <div class="col-md-2 text-end">
                <button class="btn btn-outline-danger btn-sm" onclick="this.closest('.color-row').remove()">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;
    root.appendChild(row);
}

async function uploadColorImage(input, idx) {
    const file = input.files[0];
    if (!file) return;
    const formData = new FormData();
    formData.append('image', file);
    const res  = await fetch('/api/merchandise/upload-image', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
        const row = input.closest('.color-row');
        row.querySelector('.color-image-path').value = data.data.path;
        row.querySelector('.color-image-preview').innerHTML = `<img src="${data.data.path}" style="max-height: 60px;" class="rounded">`;
    } else {
        alert(data.error?.message || 'アップロードに失敗しました');
    }
}

async function saveColors() {
    const colors = Array.from(document.querySelectorAll('.color-row')).map((row, i) => ({
        color_name: row.querySelector('.color-name').value.trim(),
        image_path: row.querySelector('.color-image-path').value || null,
        sort_order: parseInt(row.querySelector('.color-sort').value) || i,
    })).filter(c => c.color_name);

    const res  = await fetch(`/api/merchandise/${merchandiseId}/colors`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ colors, _method: 'PUT' }),
    });
    const data = await res.json();
    if (data.success) {
        merchandise.colors = data.data.colors;
        alert('色を保存しました');
        renderColors();
    } else {
        alert(data.error?.message || '保存に失敗しました');
    }
}

// ===== サイズ =====
function renderSizes() {
    const root = document.getElementById('sizeList');
    root.innerHTML = '';
    (merchandise.sizes || []).forEach((s, i) => addSizeRow(s, i));
    if (!merchandise.sizes || merchandise.sizes.length === 0) {
        ['S', 'M', 'L', 'XL'].forEach((n, i) => addSizeRow({ size_name: n, sort_order: i }));
    }
}

function addSizeRow(s = null, i = null) {
    const root = document.getElementById('sizeList');
    const idx  = root.children.length;
    const row  = document.createElement('div');
    row.className = 'd-flex gap-2 mb-2 size-row';
    row.innerHTML = `
        <input type="text" class="form-control form-control-sm size-name" value="${escapeHtml(s?.size_name || '')}" placeholder="例: M" style="max-width: 200px;">
        <input type="number" class="form-control form-control-sm size-sort" value="${s?.sort_order ?? idx}" style="max-width: 100px;" placeholder="並び">
        <button class="btn btn-outline-danger btn-sm" onclick="this.closest('.size-row').remove()"><i class="bi bi-trash"></i></button>
    `;
    root.appendChild(row);
}

async function saveSizes() {
    const sizes = Array.from(document.querySelectorAll('.size-row')).map((row, i) => ({
        size_name:  row.querySelector('.size-name').value.trim(),
        sort_order: parseInt(row.querySelector('.size-sort').value) || i,
    })).filter(s => s.size_name);

    const res  = await fetch(`/api/merchandise/${merchandiseId}/sizes`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ sizes, _method: 'PUT' }),
    });
    const data = await res.json();
    if (data.success) {
        merchandise.sizes = data.data.sizes;
        alert('サイズを保存しました');
        renderSizes();
    } else {
        alert(data.error?.message || '保存に失敗しました');
    }
}

// ===== 注文 =====
function filterOrders(status) {
    _orderFilter = status;
    document.querySelectorAll('[data-status]').forEach(b => {
        b.classList.toggle('active', (b.dataset.status || '') === status);
    });
    loadOrders();
}

async function loadOrders() {
    const url  = `/api/merchandise/${merchandiseId}/orders` + (_orderFilter ? `?status=${_orderFilter}` : '');
    const res  = await fetch(url);
    const data = await res.json();
    const root = document.getElementById('ordersList');
    if (!data.success || !data.data.orders.length) {
        root.innerHTML = '<div class="card"><div class="card-body text-center text-muted">注文がありません</div></div>';
        return;
    }
    root.innerHTML = data.data.orders.map(o => {
        const items = o.items.filter(it => it.merchandise_id == merchandiseId).map(it => `
            <li class="small">
                ${escapeHtml(it.merchandise_name)}
                ${it.color_name ? `／${escapeHtml(it.color_name)}` : ''}
                ${it.size_name  ? `／${escapeHtml(it.size_name)}`  : ''}
                × ${it.quantity}（¥${Number(it.subtotal).toLocaleString()}）
            </li>
        `).join('');
        const paidBadge = o.payment_status === 'paid'
            ? '<span class="badge bg-success">入金済</span>'
            : (o.payment_status === 'cancelled'
                ? '<span class="badge bg-secondary">キャンセル</span>'
                : '<span class="badge bg-warning text-dark">未入金</span>');
        const submittedBadge = (o.payment_status === 'unpaid' && Number(o.payment_submitted) === 1)
            ? `<span class="badge bg-info text-dark ms-1" title="${o.payment_submitted_at ? '報告: ' + String(o.payment_submitted_at).substring(0,16).replace('T',' ') : ''}">
                 <i class="bi bi-send-check"></i> 振込報告済
               </span>`
            : '';
        const confirmBtnClass = (o.payment_status !== 'paid' && Number(o.payment_submitted) === 1)
            ? 'btn-primary'
            : (o.payment_status === 'paid' ? 'btn-outline-success' : 'btn-success');
        return `
        <div class="card mb-2 ${(o.payment_status === 'unpaid' && Number(o.payment_submitted) === 1) ? 'border-info' : ''}">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${escapeHtml(o.buyer_name)}</strong>
                        ${o.member_id ? `<small class="text-muted ms-1">（会員）</small>` : ''}
                        ${paidBadge}${submittedBadge}
                        <small class="text-muted ms-2">${o.created_at ? o.created_at.substring(0, 16).replace('T', ' ') : ''}</small>
                    </div>
                    <div>
                        <span class="fw-bold me-2">¥${Number(o.total_amount).toLocaleString()}</span>
                        <button class="btn btn-sm ${confirmBtnClass}"
                                onclick="togglePaid(${o.id})">
                            ${o.payment_status === 'paid' ? '未入金に戻す' : '入金確認'}
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteOrder(${o.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <ul class="mt-2 mb-1">${items}</ul>
                ${o.buyer_contact ? `<small class="text-muted">連絡先: ${escapeHtml(o.buyer_contact)}</small>` : ''}
                ${o.notes ? `<div class="text-muted small">備考: ${escapeHtml(o.notes)}</div>` : ''}
            </div>
        </div>`;
    }).join('');
}

async function togglePaid(id) {
    const res  = await fetch(`/api/merchandise/orders/${id}/toggle-paid`, { method: 'POST' });
    const data = await res.json();
    if (data.success) {
        loadOrders();
        loadSummary();
    } else {
        alert('更新に失敗しました');
    }
}

async function deleteOrder(id) {
    if (!confirm('この注文を削除しますか？')) return;
    try {
        const res  = await fetch(`/api/merchandise/orders/${id}`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ _method: 'DELETE' }),
        });
        const data = await res.json();
        if (data.success) {
            loadOrders();
            loadSummary();
        } else {
            alert('削除に失敗しました: ' + (data.error?.message || '不明なエラー'));
        }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

function exportOrders(format) {
    const params = new URLSearchParams();
    if (_orderFilter) params.set('status', _orderFilter);
    const qs  = params.toString() ? '?' + params.toString() : '';
    const url = `/api/merchandise/${merchandiseId}/export/${format}${qs}`;
    if (format === 'pdf') {
        window.open(url, '_blank');
    } else {
        window.location.href = url;
    }
}

async function loadSummary() {
    const res  = await fetch(`/api/merchandise/${merchandiseId}/summary`);
    const data = await res.json();
    const root = document.getElementById('summaryArea');
    if (!data.success || !data.data.summary.length) {
        root.innerHTML = '注文がまだありません';
        return;
    }

    // 商品の色・サイズ定義から行・列を組み立て、データに登場するが未定義のものは末尾に追加
    const colorList = (merchandise?.colors || []).map(c => c.color_name);
    const sizeList  = (merchandise?.sizes  || []).map(s => s.size_name);

    data.data.summary.forEach(s => {
        const cn = s.color_name || '-';
        const sn = s.size_name  || '-';
        if (!colorList.includes(cn)) colorList.push(cn);
        if (!sizeList.includes(sn))  sizeList.push(sn);
    });

    // 集計マップ {color: {size: {qty, amount}}}
    const grid = {};
    data.data.summary.forEach(s => {
        const cn = s.color_name || '-';
        const sn = s.size_name  || '-';
        if (!grid[cn]) grid[cn] = {};
        grid[cn][sn] = {
            qty:    Number(s.total_quantity || 0),
            amount: Number(s.total_amount   || 0),
        };
    });

    // 行・列・全体合計
    const rowTotals = {};
    const colTotals = {};
    let grandQty = 0, grandAmount = 0;
    colorList.forEach(cn => {
        rowTotals[cn] = { qty: 0, amount: 0 };
        sizeList.forEach(sn => {
            const cell = grid[cn]?.[sn];
            if (cell) {
                rowTotals[cn].qty    += cell.qty;
                rowTotals[cn].amount += cell.amount;
                if (!colTotals[sn]) colTotals[sn] = { qty: 0, amount: 0 };
                colTotals[sn].qty    += cell.qty;
                colTotals[sn].amount += cell.amount;
                grandQty    += cell.qty;
                grandAmount += cell.amount;
            }
        });
    });

    root.innerHTML = `
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 text-center align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-start" rowspan="2" style="min-width: 90px;">色 ＼ サイズ</th>
                        <th colspan="${sizeList.length}">サイズ別 数量</th>
                        <th rowspan="2">合計数量</th>
                    </tr>
                    <tr>
                        ${sizeList.map(sn => `<th>${escapeHtml(sn)}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
                    ${colorList.map(cn => `
                        <tr>
                            <td class="text-start fw-bold">${escapeHtml(cn)}</td>
                            ${sizeList.map(sn => {
                                const cell = grid[cn]?.[sn];
                                return `<td class="${cell ? '' : 'text-muted'}">${cell ? cell.qty : '-'}</td>`;
                            }).join('')}
                            <td class="fw-bold table-light">${rowTotals[cn].qty || '-'}</td>
                        </tr>
                    `).join('')}
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th class="text-start">合計</th>
                        ${sizeList.map(sn => `<th>${colTotals[sn]?.qty || '-'}</th>`).join('')}
                        <th class="text-primary">${grandQty}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="text-end small text-muted mt-2">合計金額: <strong class="text-primary">¥${grandAmount.toLocaleString()}</strong></div>
    `;
}

// ===== 公開URL =====
async function loadTokens() {
    const res  = await fetch(`/api/merchandise/${merchandiseId}/tokens`);
    const data = await res.json();
    const root = document.getElementById('tokenList');
    if (!data.success || !data.data.tokens.length) {
        root.innerHTML = '<div class="text-muted small">公開URLはまだ発行されていません</div>';
        return;
    }
    const baseUrl = location.origin;
    root.innerHTML = data.data.tokens.map(t => {
        const url     = `${baseUrl}/store/${t.token}`;
        const expired = t.expires_at && new Date(t.expires_at) < new Date();
        return `
        <div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-center">
            <div>
                <strong>${escapeHtml(t.label || '公開URL')}</strong>
                ${expired ? '<span class="badge bg-secondary ms-1">期限切れ</span>' : ''}
                <div><small class="text-muted">期限: ${t.expires_at ? t.expires_at.substring(0, 16).replace('T', ' ') : '無期限'}</small></div>
                <div><a href="${url}" target="_blank" class="small">${url}</a></div>
            </div>
            <div>
                <button class="btn btn-sm btn-outline-primary" onclick="copyText('${url}')">
                    <i class="bi bi-clipboard"></i> コピー
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteToken(${t.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>`;
    }).join('');
}

async function generateToken() {
    const label = document.getElementById('tokenLabel').value.trim();
    const res   = await fetch(`/api/merchandise/${merchandiseId}/tokens`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ label }),
    });
    const data = await res.json();
    if (data.success) {
        document.getElementById('tokenLabel').value = '';
        loadTokens();
    } else {
        alert('発行に失敗しました');
    }
}

async function deleteToken(id) {
    if (!confirm('このURLを無効にしますか？')) return;
    try {
        const res  = await fetch(`/api/merchandise/tokens/${id}`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ _method: 'DELETE' }),
        });
        const data = await res.json();
        if (data.success) loadTokens();
        else alert('削除に失敗しました: ' + (data.error?.message || '不明なエラー'));
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

function copyText(text) {
    navigator.clipboard.writeText(text).then(() => alert('コピーしました'));
}

async function deleteMerchandise() {
    if (!confirm('この商品を削除しますか？関連する注文・色・サイズもすべて削除されます。')) return;
    try {
        const res = await fetch('/api/merchandise/' + merchandiseId, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ _method: 'DELETE' }),
        });
        const data = await res.json();
        if (data.success) {
            location.href = '/merchandise';
        } else {
            alert('削除に失敗しました: ' + (data.error?.message || '不明なエラー'));
        }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s ?? '';
    return div.innerHTML;
}
</script>
