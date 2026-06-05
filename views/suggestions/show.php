<?php
/**
 * 目安箱 投稿詳細（幹部）
 * 変数: $suggestion, $replyTree, $isAdmin
 */
$apiBase   = '/api/suggestions';   // 幹部用 返信エンドポイント
$backUrl   = '/suggestions';

/** 返信ツリーを再帰描画 */
function renderReplies(array $nodes, int $depth = 0) {
    foreach ($nodes as $r) {
        $isAdminReply = $r['author_type'] === 'admin';
        $indent = min($depth, 4) * 1.5;
        ?>
        <div class="reply-node" style="margin-left: <?= $indent ?>rem;">
            <div class="card mb-2 <?= $isAdminReply ? 'border-primary' : '' ?>">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="fw-bold <?= $isAdminReply ? 'text-primary' : '' ?>">
                            <?php if ($isAdminReply): ?>
                            <i class="bi bi-shield-check"></i> 幹部
                            <?php else: ?>
                            <i class="bi bi-person"></i> <?= htmlspecialchars($r['author_name'] ?? '会員') ?>
                            <?php endif; ?>
                        </small>
                        <small class="text-muted"><?= date('n/j H:i', strtotime($r['created_at'])) ?></small>
                    </div>
                    <div class="small mb-2" style="white-space: pre-wrap;"><?= htmlspecialchars($r['body']) ?></div>
                    <button class="btn btn-link btn-sm p-0 text-decoration-none"
                            onclick="showReplyForm(<?= (int)$r['id'] ?>)">
                        <i class="bi bi-reply"></i> 返信
                    </button>
                    <div id="replyForm-<?= (int)$r['id'] ?>" class="mt-2 d-none">
                        <textarea class="form-control form-control-sm mb-1" rows="2" placeholder="返信を入力"></textarea>
                        <button class="btn btn-primary btn-sm" onclick="submitReply(<?= (int)$r['id'] ?>, this)">送信</button>
                        <button class="btn btn-link btn-sm" onclick="hideReplyForm(<?= (int)$r['id'] ?>)">キャンセル</button>
                    </div>
                </div>
            </div>
            <?php if (!empty($r['children'])) renderReplies($r['children'], $depth + 1); ?>
        </div>
        <?php
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= $backUrl ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> 一覧へ戻る
    </a>
    <div class="d-flex gap-2">
        <?php if ($suggestion['status'] === 'closed'): ?>
        <button class="btn btn-outline-secondary btn-sm" onclick="setStatus('open')">
            <i class="bi bi-arrow-counterclockwise"></i> 未対応に戻す
        </button>
        <?php else: ?>
        <button class="btn btn-outline-dark btn-sm" onclick="setStatus('closed')">
            <i class="bi bi-check-circle"></i> 対応済にする
        </button>
        <?php endif; ?>
        <button class="btn btn-outline-danger btn-sm" onclick="deleteSuggestion()">
            <i class="bi bi-trash"></i> 削除
        </button>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-light">
        <div class="d-flex flex-wrap gap-1 align-items-center mb-1">
            <?php if ($suggestion['category_name']): ?>
            <span class="badge bg-light text-dark border"><?= htmlspecialchars($suggestion['category_name']) ?></span>
            <?php endif; ?>
            <?= $suggestion['visibility'] === 'public'
                ? '<span class="badge bg-info">全体公開</span>'
                : '<span class="badge bg-secondary">プライベート</span>' ?>
            <?php if ($suggestion['status'] === 'closed'): ?>
            <span class="badge bg-dark">対応済</span>
            <?php endif; ?>
        </div>
        <h5 class="mb-0"><?= htmlspecialchars($suggestion['title']) ?></h5>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            <i class="bi bi-person"></i> <?= htmlspecialchars($suggestion['author_name'] ?? '（退会者）') ?>
            ・<?= date('Y年n月j日 H:i', strtotime($suggestion['created_at'])) ?>
        </p>
        <div style="white-space: pre-wrap;"><?= htmlspecialchars($suggestion['body']) ?></div>
    </div>
</div>

<h6 class="text-muted fw-bold mb-3"><i class="bi bi-chat-dots"></i> 返信</h6>

<div class="mb-3">
    <?php if (empty($replyTree)): ?>
    <p class="text-muted small">まだ返信はありません。</p>
    <?php else: renderReplies($replyTree); endif; ?>
</div>

<!-- 投稿への直接返信フォーム -->
<div class="card border-primary">
    <div class="card-body">
        <label class="form-label fw-bold small">返信を投稿</label>
        <textarea class="form-control mb-2" id="rootReplyBody" rows="3" placeholder="返信内容を入力してください"></textarea>
        <div id="rootReplyErr" class="text-danger small mb-2 d-none"></div>
        <button class="btn btn-primary" onclick="submitRootReply(this)">
            <i class="bi bi-send"></i> 返信する
        </button>
    </div>
</div>

<script>
const SUGGESTION_ID = <?= (int)$suggestion['id'] ?>;
const API_BASE = '<?= $apiBase ?>';

function showReplyForm(id) {
    document.getElementById(`replyForm-${id}`).classList.remove('d-none');
}
function hideReplyForm(id) {
    document.getElementById(`replyForm-${id}`).classList.add('d-none');
}

async function postReply(body, parentReplyId, btn) {
    if (!body.trim()) { alert('返信内容を入力してください'); return false; }
    if (btn) btn.disabled = true;
    try {
        const res = await fetch(`${API_BASE}/${SUGGESTION_ID}/replies`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ body, parent_reply_id: parentReplyId }),
        });
        const data = await res.json();
        if (data.success) { location.reload(); return true; }
        alert(data.error?.message || '返信に失敗しました');
    } catch { alert('通信エラーが発生しました'); }
    if (btn) btn.disabled = false;
    return false;
}

function submitReply(parentReplyId, btn) {
    const ta = btn.closest('[id^="replyForm-"]').querySelector('textarea');
    postReply(ta.value, parentReplyId, btn);
}

function submitRootReply(btn) {
    const ta  = document.getElementById('rootReplyBody');
    const err = document.getElementById('rootReplyErr');
    err.classList.add('d-none');
    if (!ta.value.trim()) { err.textContent = '返信内容を入力してください'; err.classList.remove('d-none'); return; }
    postReply(ta.value, null, btn);
}

async function setStatus(status) {
    const res = await fetch(`/api/suggestions/${SUGGESTION_ID}/status`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status }),
    });
    const data = await res.json();
    if (data.success) location.reload();
    else alert(data.error?.message || '更新に失敗しました');
}

async function deleteSuggestion() {
    if (!confirm('この投稿を削除しますか？返信もすべて削除されます。')) return;
    const res = await fetch(`/api/suggestions/${SUGGESTION_ID}`, { method: 'DELETE' });
    const data = await res.json();
    if (data.success) location.href = '<?= $backUrl ?>';
    else alert(data.error?.message || '削除に失敗しました');
}
</script>
