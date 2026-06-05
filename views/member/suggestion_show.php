<?php
/**
 * 会員 目安箱 投稿詳細（返信閲覧・返信）
 * 変数: $memberName, $memberId, $suggestion, $replyTree, $isOwner
 */
$myId = (int)$memberId;

/**
 * 返信ツリーを再帰描画（会員視点）
 *   - 幹部の返信は「幹部」と表示
 *   - 会員の返信は、自分なら「あなた」、他人なら匿名表示
 */
function renderMemberReplies(array $nodes, int $myId, int $depth = 0) {
    foreach ($nodes as $r) {
        $isAdminReply = $r['author_type'] === 'admin';
        $isMine       = !$isAdminReply && (int)$r['author_member_id'] === $myId;
        $indent       = min($depth, 4) * 1.5;

        if ($isAdminReply) {
            $label = '<i class="bi bi-shield-check"></i> 幹部';
            $cls   = 'text-primary';
            $border = 'border-primary';
        } elseif ($isMine) {
            $label = '<i class="bi bi-person-fill"></i> あなた';
            $cls   = 'text-success';
            $border = '';
        } else {
            $label = '<i class="bi bi-person"></i> 匿名';
            $cls   = '';
            $border = '';
        }
        ?>
        <div style="margin-left: <?= $indent ?>rem;">
            <div class="card mb-2 <?= $border ?>">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="fw-bold <?= $cls ?>"><?= $label ?></small>
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
            <?php if (!empty($r['children'])) renderMemberReplies($r['children'], $myId, $depth + 1); ?>
        </div>
        <?php
    }
}
?>

<div class="pt-3 mb-3">
    <a href="/member/suggestions" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> 目安箱へ戻る
    </a>
</div>

<div class="card mb-4">
    <div class="card-header bg-light">
        <div class="d-flex flex-wrap gap-1 align-items-center mb-1">
            <?php if ($suggestion['category_name']): ?>
            <span class="badge bg-light text-dark border"><?= htmlspecialchars($suggestion['category_name']) ?></span>
            <?php endif; ?>
            <?php if ($suggestion['visibility'] === 'public'): ?>
            <span class="badge bg-info">全体公開</span>
            <?php else: ?>
            <span class="badge bg-secondary"><i class="bi bi-lock"></i> プライベート</span>
            <?php endif; ?>
            <?php if ($isOwner): ?>
            <span class="badge bg-success">あなたの投稿</span>
            <?php endif; ?>
        </div>
        <h5 class="mb-0"><?= htmlspecialchars($suggestion['title']) ?></h5>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            <?= $isOwner ? 'あなた' : '匿名' ?>
            ・<?= date('Y年n月j日 H:i', strtotime($suggestion['created_at'])) ?>
        </p>
        <div style="white-space: pre-wrap;"><?= htmlspecialchars($suggestion['body']) ?></div>
    </div>
</div>

<h6 class="text-muted fw-bold mb-3 small"><i class="bi bi-chat-dots"></i> 返信</h6>

<div class="mb-3">
    <?php if (empty($replyTree)): ?>
    <p class="text-muted small">まだ返信はありません。</p>
    <?php else: renderMemberReplies($replyTree, $myId); endif; ?>
</div>

<!-- 投稿への直接返信フォーム -->
<div class="card border-primary mb-4">
    <div class="card-body">
        <label class="form-label fw-bold small">返信を投稿</label>
        <textarea class="form-control mb-2" id="rootReplyBody" rows="3" placeholder="返信内容を入力してください"></textarea>
        <div id="rootReplyErr" class="text-danger small mb-2 d-none"></div>
        <button class="btn btn-primary w-100" onclick="submitRootReply(this)">
            <i class="bi bi-send"></i> 返信する
        </button>
    </div>
</div>

<script>
const SUGGESTION_ID = <?= (int)$suggestion['id'] ?>;

function showReplyForm(id) { document.getElementById(`replyForm-${id}`).classList.remove('d-none'); }
function hideReplyForm(id) { document.getElementById(`replyForm-${id}`).classList.add('d-none'); }

async function postReply(body, parentReplyId, btn) {
    if (!body.trim()) { alert('返信内容を入力してください'); return; }
    if (btn) btn.disabled = true;
    try {
        const res = await fetch(`/api/member/suggestions/${SUGGESTION_ID}/replies`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ body, parent_reply_id: parentReplyId }),
        });
        const data = await res.json();
        if (data.success) { location.reload(); return; }
        alert(data.error?.message || '返信に失敗しました');
    } catch { alert('通信エラーが発生しました'); }
    if (btn) btn.disabled = false;
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
</script>
