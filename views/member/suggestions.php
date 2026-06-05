<?php
/**
 * 会員 目安箱（一覧＋投稿フォーム）
 * 変数: $memberName, $memberId, $categories, $suggestions, $selectedCat
 */
?>
<div class="d-flex justify-content-between align-items-center pt-3 mb-3">
    <div>
        <h4 class="fw-normal mb-1"><i class="bi bi-inbox"></i> 目安箱</h4>
        <p class="text-muted small mb-0">要望・質問・相談を幹部に届けられます</p>
    </div>
    <a href="/member/home" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-house"></i> ホーム
    </a>
</div>

<!-- 投稿フォーム -->
<div class="card mb-4 border-primary">
    <div class="card-header bg-primary bg-opacity-10 border-primary">
        <i class="bi bi-pencil-square"></i> 新しく投稿する
    </div>
    <div class="card-body">
        <div class="mb-2">
            <label class="form-label small mb-1">題名 <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="postTitle" maxlength="255" placeholder="例: 練習時間について">
        </div>
        <div class="mb-2">
            <label class="form-label small mb-1">カテゴリ</label>
            <select class="form-select" id="postCategory">
                <option value="">未選択</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label small mb-1">内容 <span class="text-danger">*</span></label>
            <textarea class="form-control" id="postBody" rows="4" placeholder="内容を入力してください"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label small mb-1">公開設定</label>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="visibility" id="visPrivate" value="private" checked>
                <label class="form-check-label small" for="visPrivate">
                    <i class="bi bi-lock"></i> プライベート
                    <span class="text-muted">（あなたと幹部だけが返信を見られます）</span>
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="visibility" id="visPublic" value="public">
                <label class="form-check-label small" for="visPublic">
                    <i class="bi bi-globe"></i> 全体公開
                    <span class="text-muted">（投稿と返信を全会員が見られます。投稿者名は他の会員には表示されません）</span>
                </label>
            </div>
        </div>
        <div id="postErr" class="alert alert-danger py-2 small d-none"></div>
        <button class="btn btn-primary w-100" id="postBtn" onclick="submitPost()">
            <i class="bi bi-send"></i> 送信する
        </button>
    </div>
</div>

<!-- カテゴリ絞り込み -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="/member/suggestions" class="btn btn-sm <?= $selectedCat === null ? 'btn-secondary' : 'btn-outline-secondary' ?>">すべて</a>
    <?php foreach ($categories as $cat): ?>
    <a href="/member/suggestions?category=<?= (int)$cat['id'] ?>"
       class="btn btn-sm <?= $selectedCat === (int)$cat['id'] ? 'btn-secondary' : 'btn-outline-secondary' ?>">
        <?= htmlspecialchars($cat['name']) ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- 投稿一覧 -->
<h6 class="text-uppercase text-muted fw-bold mb-2 small">投稿一覧</h6>
<?php if (empty($suggestions)): ?>
<div class="card mb-4">
    <div class="card-body text-center text-muted py-4">
        <i class="bi bi-inbox" style="font-size: 1.8rem;"></i>
        <p class="mt-2 mb-0 small">まだ投稿はありません</p>
    </div>
</div>
<?php else: ?>
<div class="card mb-4">
    <div class="list-group list-group-flush">
        <?php foreach ($suggestions as $s):
            $isMine = (int)$s['member_id'] === (int)$memberId;
            // 自分の投稿は「あなた」、他会員の投稿は匿名表示
            $authorDisplay = $isMine ? 'あなた' : '匿名';
        ?>
        <a href="/member/suggestions/<?= (int)$s['id'] ?>" class="list-group-item list-group-item-action">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1 me-2">
                    <div class="mb-1">
                        <?php if ($s['category_name']): ?>
                        <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($s['category_name']) ?></span>
                        <?php endif; ?>
                        <?php if ($s['visibility'] === 'public'): ?>
                        <span class="badge bg-info">全体公開</span>
                        <?php else: ?>
                        <span class="badge bg-secondary"><i class="bi bi-lock"></i> プライベート</span>
                        <?php endif; ?>
                        <?php if ($isMine): ?>
                        <span class="badge bg-success">あなたの投稿</span>
                        <?php endif; ?>
                    </div>
                    <h6 class="mb-1"><?= htmlspecialchars($s['title']) ?></h6>
                    <small class="text-muted">
                        <?= htmlspecialchars($authorDisplay) ?>
                        ・<?= date('n/j H:i', strtotime($s['created_at'])) ?>
                        <?php if ((int)$s['reply_count'] > 0): ?>
                        ・<i class="bi bi-chat-dots"></i> <?= (int)$s['reply_count'] ?>
                        <?php endif; ?>
                    </small>
                </div>
                <i class="bi bi-chevron-right text-muted"></i>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
async function submitPost() {
    const title = document.getElementById('postTitle').value.trim();
    const body  = document.getElementById('postBody').value.trim();
    const categoryId = document.getElementById('postCategory').value || null;
    const visibility = document.querySelector('input[name="visibility"]:checked').value;
    const err = document.getElementById('postErr');
    const btn = document.getElementById('postBtn');
    err.classList.add('d-none');

    if (!title) { err.textContent = '題名を入力してください'; err.classList.remove('d-none'); return; }
    if (!body)  { err.textContent = '内容を入力してください'; err.classList.remove('d-none'); return; }

    btn.disabled = true;
    try {
        const res = await fetch('/api/member/suggestions', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title, body, category_id: categoryId, visibility }),
        });
        const data = await res.json();
        if (data.success) { location.reload(); return; }
        err.textContent = data.error?.message || '送信に失敗しました';
        err.classList.remove('d-none');
    } catch {
        err.textContent = '通信エラーが発生しました';
        err.classList.remove('d-none');
    }
    btn.disabled = false;
}
</script>
