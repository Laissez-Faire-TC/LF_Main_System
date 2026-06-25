<?php
/**
 * 企画申し込み Step4: 完了ページ
 * 変数: $event, $member, $application
 */
$isWaitlisted = ($application['status'] ?? '') === 'waitlisted';
$action       = $guestAction ?? null;   // updated / cancelled / waitlisted / submitted

if ($action === 'cancelled') {
    $icon = 'x-circle-fill text-danger'; $heading = 'キャンセルしました'; $lead = 'またのお申し込みをお待ちしています。';
} elseif ($action === 'updated') {
    $icon = 'check-circle-fill text-success'; $heading = '申し込み内容を変更しました'; $lead = '変更内容で受け付けました。';
} elseif ($isWaitlisted) {
    $icon = 'clock-history text-warning'; $heading = 'キャンセル待ちに登録しました'; $lead = 'キャンセルが出た場合、自動的に繰り上がります。';
} else {
    $icon = 'check-circle-fill text-success'; $heading = '申し込みが完了しました'; $lead = '幹部からの案内をお待ちください。';
}
$showEventCard = ($action !== 'cancelled');
?>
<div class="container py-5 text-center" style="max-width: 520px;">
    <i class="bi bi-<?= $icon ?>" style="font-size: 3.5rem;"></i>
    <h4 class="mt-3 fw-bold"><?= $heading ?></h4>
    <p class="text-muted"><?= $lead ?></p>

    <?php if ($event && $showEventCard): ?>
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

    <?php if (empty($isGuest)): ?>
    <a href="/member/home" class="btn btn-outline-primary">会員ページへ戻る</a>
    <?php else: ?>
    <p class="small text-muted mb-0">このページは閉じていただいて構いません。</p>
    <?php endif; ?>
</div>
