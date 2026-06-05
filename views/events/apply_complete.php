<?php
/**
 * 企画申し込み Step4: 完了ページ
 * 変数: $event, $member, $application
 */
$isWaitlisted = ($application['status'] ?? '') === 'waitlisted';
?>
<div class="container py-5 text-center" style="max-width: 520px;">
    <i class="bi bi-<?= $isWaitlisted ? 'clock-history text-warning' : 'check-circle-fill text-success' ?>"
       style="font-size: 3.5rem;"></i>
    <h4 class="mt-3 fw-bold">
        <?= $isWaitlisted ? 'キャンセル待ちに登録しました' : '申し込みが完了しました' ?>
    </h4>
    <p class="text-muted">
        <?= $isWaitlisted
            ? 'キャンセルが出た場合、自動的に繰り上がります。'
            : '幹部からの案内をお待ちください。' ?>
    </p>

    <?php if ($event): ?>
    <div class="card mb-4 text-start">
        <div class="card-body">
            <h6 class="fw-bold mb-2"><?= htmlspecialchars($event['title'] ?? '') ?></h6>
            <?php if ($event['event_date']): ?>
            <div class="small text-muted">
                <i class="bi bi-calendar"></i>
                <?= date('Y年n月j日', strtotime($event['event_date'])) ?>
                <?= !empty($event['event_time']) ? '　' . substr($event['event_time'], 0, 5) . '〜' : '' ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($event['location'])): ?>
            <div class="small text-muted mt-1">
                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($event['location']) ?>
            </div>
            <?php endif; ?>
            <?php if ((int)$event['participation_fee'] > 0): ?>
            <div class="small text-muted mt-1">
                <i class="bi bi-cash"></i> 参加費: ¥<?= number_format((int)$event['participation_fee']) ?>
            </div>
            <?php endif; ?>
            <?php if ($application && !empty($application['note'])): ?>
            <div class="small text-muted mt-1">
                <i class="bi bi-chat-left-text"></i> 備考: <?= htmlspecialchars($application['note']) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <a href="/member/home" class="btn btn-outline-primary">会員ページへ戻る</a>
</div>
