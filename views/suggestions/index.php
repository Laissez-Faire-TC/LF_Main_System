<?php
$visLabel = function ($v) {
    return $v === 'public'
        ? '<span class="badge bg-info">全体公開</span>'
        : '<span class="badge bg-secondary">プライベート</span>';
};
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1"><i class="bi bi-inbox"></i> 目安箱</h1>
        <p class="text-muted mb-0">会員からの要望・質問・相談の確認と返信</p>
    </div>
    <a href="/dashboard" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> ダッシュボード
    </a>
</div>

<!-- カテゴリ絞り込み -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="text-muted small me-2">カテゴリ:</span>
            <a href="/suggestions" class="btn btn-sm <?= $selectedCat === null ? 'btn-primary' : 'btn-outline-primary' ?>">
                すべて
            </a>
            <?php foreach ($categories as $cat): ?>
            <a href="/suggestions?category=<?= (int)$cat['id'] ?>"
               class="btn btn-sm <?= $selectedCat === (int)$cat['id'] ? 'btn-primary' : 'btn-outline-primary' ?> <?= $cat['is_active'] ? '' : 'opacity-50' ?>">
                <?= htmlspecialchars($cat['name']) ?>
                <?php if (!$cat['is_active']): ?><i class="bi bi-eye-slash"></i><?php endif; ?>
            </a>
            <?php endforeach; ?>
            <button class="btn btn-sm btn-outline-secondary ms-auto" data-bs-toggle="modal" data-bs-target="#categoryModal">
                <i class="bi bi-gear"></i> カテゴリ管理
            </button>
        </div>
    </div>
</div>

<!-- 投稿一覧 -->
<div class="card">
    <div class="card-header bg-light">
        <i class="bi bi-list-ul"></i> 投稿一覧
        <span class="badge bg-secondary ms-1"><?= count($suggestions) ?></span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($suggestions)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
            <p class="mt-2 mb-0">該当する投稿はありません</p>
        </div>
        <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($suggestions as $s): ?>
            <a href="/suggestions/<?= (int)$s['id'] ?>" class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1 me-3">
                        <div class="mb-1">
                            <?php if ($s['category_name']): ?>
                            <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($s['category_name']) ?></span>
                            <?php endif; ?>
                            <?= $visLabel($s['visibility']) ?>
                            <?php if ($s['status'] === 'closed'): ?>
                            <span class="badge bg-dark">対応済</span>
                            <?php endif; ?>
                        </div>
                        <h6 class="mb-1"><?= htmlspecialchars($s['title']) ?></h6>
                        <small class="text-muted">
                            <i class="bi bi-person"></i> <?= htmlspecialchars($s['author_name'] ?? '（退会者）') ?>
                            ・<?= date('Y/n/j H:i', strtotime($s['created_at'])) ?>
                            <?php if ((int)$s['reply_count'] > 0): ?>
                            ・<i class="bi bi-chat-dots"></i> <?= (int)$s['reply_count'] ?>件の返信
                            <?php endif; ?>
                        </small>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- カテゴリ管理モーダル -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">カテゴリ管理</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="categoryList" class="mb-3"></div>
                <hr>
                <div class="input-group">
                    <input type="text" class="form-control" id="newCategoryName" placeholder="新しいカテゴリ名">
                    <button class="btn btn-primary" onclick="addCategory()">追加</button>
                </div>
                <div id="categoryErr" class="text-danger small mt-2 d-none"></div>
            </div>
        </div>
    </div>
</div>

<script>
const categoryModalEl = document.getElementById('categoryModal');

async function loadCategories() {
    const res  = await fetch('/api/suggestion-categories');
    const data = await res.json();
    const list = document.getElementById('categoryList');
    const cats = data.data?.categories || [];
    if (cats.length === 0) {
        list.innerHTML = '<p class="text-muted small mb-0">カテゴリがありません</p>';
        return;
    }
    list.innerHTML = cats.map(c => `
        <div class="d-flex align-items-center gap-2 mb-2" data-id="${c.id}">
            <input type="text" class="form-control form-control-sm" value="${escapeHtml(c.name)}">
            <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" ${c.is_active == 1 ? 'checked' : ''} title="表示">
            </div>
            <button class="btn btn-sm btn-outline-primary" onclick="saveCategory(${c.id}, this)">保存</button>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(${c.id})"><i class="bi bi-trash"></i></button>
        </div>
    `).join('');
}

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML.replace(/"/g, '&quot;');
}

async function addCategory() {
    const input = document.getElementById('newCategoryName');
    const err   = document.getElementById('categoryErr');
    const name  = input.value.trim();
    err.classList.add('d-none');
    if (!name) { err.textContent = 'カテゴリ名を入力してください'; err.classList.remove('d-none'); return; }
    const res = await fetch('/api/suggestion-categories', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name }),
    });
    const data = await res.json();
    if (data.success) { input.value = ''; loadCategories(); }
    else { err.textContent = data.error?.message || '追加に失敗しました'; err.classList.remove('d-none'); }
}

async function saveCategory(id, btn) {
    const row     = btn.closest('[data-id]');
    const name    = row.querySelector('input[type="text"]').value.trim();
    const active  = row.querySelector('input[type="checkbox"]').checked ? 1 : 0;
    const res = await fetch(`/api/suggestion-categories/${id}`, {
        method: 'PUT', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, is_active: active }),
    });
    const data = await res.json();
    if (data.success) {
        btn.innerHTML = '<i class="bi bi-check"></i>';
        setTimeout(() => { btn.textContent = '保存'; }, 1000);
    } else {
        alert(data.error?.message || '保存に失敗しました');
    }
}

async function deleteCategory(id) {
    if (!confirm('このカテゴリを削除しますか？\n（紐づく投稿は「未分類」になります）')) return;
    const res = await fetch(`/api/suggestion-categories/${id}`, { method: 'DELETE' });
    const data = await res.json();
    if (data.success) loadCategories();
    else alert(data.error?.message || '削除に失敗しました');
}

categoryModalEl.addEventListener('show.bs.modal', loadCategories);
// 閉じたらカテゴリ反映のためリロード
categoryModalEl.addEventListener('hidden.bs.modal', () => location.reload());
</script>
