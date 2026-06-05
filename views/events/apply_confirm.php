<?php
/**
 * 企画申し込み Step2: 確認ページ
 * 変数: $event, $token, $member, $alreadyApplied, $existingStatus,
 *       $isFull, $remaining, $capacity, $confirmedCount, $waitlistCount, $isDeadlinePassed
 */
$dateLabel = $event['event_date'] ? date('Y年n月j日', strtotime($event['event_date'])) : '';
$timeLabel = !empty($event['event_time']) ? substr($event['event_time'], 0, 5) : '';
?>
<div class="container py-4" style="max-width: 640px;">
    <div class="mb-3">
        <h4 class="fw-bold mb-0"><?= htmlspecialchars($event['title'] ?? '') ?></h4>
        <?php if ($dateLabel): ?>
        <p class="text-muted mb-0"><?= $dateLabel ?><?= $timeLabel ? '　' . $timeLabel . '〜' : '' ?>
            <?= !empty($event['location']) ? '　' . htmlspecialchars($event['location']) : '' ?>
        </p>
        <?php endif; ?>
    </div>

    <?php if (!empty($event['description'])): ?>
    <div class="card mb-3">
        <div class="card-body small"><?= nl2br(htmlspecialchars($event['description'])) ?></div>
    </div>
    <?php endif; ?>

    <!-- 定員状況 -->
    <?php if ($capacity !== null): ?>
    <div class="card mb-3">
        <div class="card-body py-2">
            <?php $pct = min(100, round($confirmedCount / $capacity * 100)); ?>
            <div class="d-flex justify-content-between small mb-1">
                <span>定員 <?= $capacity ?>人</span>
                <span><?= $confirmedCount ?>人申込中<?= $remaining > 0 ? "（残り{$remaining}人）" : '（満員）' ?></span>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar <?= $pct >= 100 ? 'bg-danger' : ($pct >= 80 ? 'bg-warning' : 'bg-primary') ?>"
                     style="width: <?= $pct ?>%"></div>
            </div>
            <?php if ($isFull && $event['allow_waitlist']): ?>
            <div class="small text-muted mt-1">
                キャンセル待ち <?= $waitlistCount ?>人
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 参加費 -->
    <?php if ((int)$event['participation_fee'] > 0): ?>
    <div class="alert alert-info py-2 mb-3 small">
        <i class="bi bi-cash"></i> 参加費: <strong>¥<?= number_format((int)$event['participation_fee']) ?></strong>
    </div>
    <?php endif; ?>

    <!-- 会員情報 -->
    <div class="card mb-3">
        <div class="card-header small fw-semibold">申し込み者情報</div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <tbody>
                    <tr><th class="ps-3 fw-normal text-muted" style="width:35%">氏名</th><td><?= htmlspecialchars($member['name_kanji'] ?? '') ?></td></tr>
                    <tr><th class="ps-3 fw-normal text-muted">学籍番号</th><td><?= htmlspecialchars($member['student_id'] ?? '') ?></td></tr>
                    <tr><th class="ps-3 fw-normal text-muted">学年</th><td><?= htmlspecialchars($member['grade'] ?? '') ?>年</td></tr>
                    <tr><th class="ps-3 fw-normal text-muted">LINE名</th><td><?= htmlspecialchars($member['line_name'] ?? '—') ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($alreadyApplied): ?>
    <!-- すでに申し込み済み -->
    <div class="alert alert-<?= $existingStatus === 'waitlisted' ? 'warning' : 'success' ?> text-center">
        <i class="bi bi-check-circle-fill"></i>
        <?= $existingStatus === 'waitlisted' ? 'キャンセル待ちで申し込み済みです' : 'すでに申し込み済みです（参加確定）' ?>
    </div>
    <div class="text-center">
        <a href="/member/home" class="btn btn-outline-secondary btn-sm">会員ページへ戻る</a>
    </div>

    <?php elseif ($isDeadlinePassed): ?>
    <div class="alert alert-secondary text-center">申し込み期限を過ぎています</div>

    <?php elseif ($isFull && !$event['allow_waitlist']): ?>
    <div class="alert alert-danger text-center">定員に達しているため申し込みできません</div>

    <?php else: ?>
    <!-- 申し込みフォーム -->
    <div class="card mb-3">
        <div class="card-body">
            <?php if ($isFull && $event['allow_waitlist']): ?>
            <div class="alert alert-warning small">
                <i class="bi bi-clock"></i>
                定員に達しているため<strong>キャンセル待ち</strong>として登録されます（現在<?= $waitlistCount ?>人待ち）
            </div>
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label small">備考（任意）</label>
                <textarea class="form-control form-control-sm" id="noteInput" rows="2"
                          placeholder="質問・アレルギー情報など"></textarea>
            </div>
            <div id="applyError" class="alert alert-danger d-none"></div>
            <button type="button" class="btn btn-primary w-100" id="applyBtn" onclick="submitApply()">
                <?= ($isFull && $event['allow_waitlist']) ? 'キャンセル待ちで申し込む' : '申し込む' ?>
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
const APPLY_TOKEN = <?= json_encode($token) ?>;

async function submitApply() {
    const btn   = document.getElementById('applyBtn');
    const errEl = document.getElementById('applyError');
    errEl.classList.add('d-none');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> 送信中...';

    try {
        const res  = await fetch(`/api/apply/event/${APPLY_TOKEN}`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ note: document.getElementById('noteInput').value.trim() }),
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = `/apply/event/${APPLY_TOKEN}/complete`;
        } else {
            errEl.textContent = data.error?.message || '申し込みに失敗しました';
            errEl.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '申し込む';
        }
    } catch (e) {
        errEl.textContent = '通信エラーが発生しました';
        errEl.classList.remove('d-none');
        btn.disabled = false;
        btn.innerHTML = '申し込む';
    }
}
</script>
