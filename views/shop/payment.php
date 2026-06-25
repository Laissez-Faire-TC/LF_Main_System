<?php
/**
 * 物販 支払いフォーム（会員ログイン必須）
 * 必須変数:
 *   $order : MerchandiseOrder::findForMember() の結果（items 付き）
 *   $mode  : 'member'
 *
 * 購入者本人のみアクセス可。注文合計を初期表示し、購入者が実際に振り込んだ
 * 金額に編集できる。「支払いが完了しました」チェック＋金額で報告する。
 */
$total       = (int)($order['total_amount'] ?? 0);
$isSubmitted = !empty($order['payment_submitted']);
$isPaid      = ($order['payment_status'] ?? '') === 'paid';
$isCancelled = ($order['payment_status'] ?? '') === 'cancelled';
// 既に申告済みなら申告金額、未申告なら注文合計を初期表示
$initialAmount = $order['paid_amount'] !== null ? (int)$order['paid_amount'] : $total;
?>
<div class="pt-3 mb-4">
    <a href="/member/store" class="text-decoration-none">&larr; ショップに戻る</a>
    <h4 class="mt-2 fw-normal"><i class="bi bi-credit-card"></i> 支払いフォーム</h4>
    <p class="text-muted small mb-0">振込が完了したら、金額を確認のうえ報告してください。</p>
</div>

<div class="card mb-4">
    <div class="card-header">
        <strong>ご注文 #<?= (int)$order['id'] ?></strong>
        <small class="text-muted ms-2"><?= htmlspecialchars($order['created_at'] ?? '') ?></small>
    </div>
    <div class="card-body">
        <ul class="list-unstyled small mb-3">
            <?php foreach (($order['items'] ?? []) as $it): ?>
            <li class="d-flex justify-content-between border-bottom py-1">
                <span>
                    <?= htmlspecialchars($it['merchandise_name']) ?>
                    <?= !empty($it['color_name']) ? '／' . htmlspecialchars($it['color_name']) : '' ?>
                    <?= !empty($it['size_name'])  ? '／' . htmlspecialchars($it['size_name'])  : '' ?>
                    × <?= (int)$it['quantity'] ?>
                </span>
                <span>¥<?= number_format((int)$it['subtotal']) ?></span>
            </li>
            <?php endforeach; ?>
            <li class="d-flex justify-content-between pt-2 fw-bold">
                <span>注文合計</span>
                <span class="text-primary fs-5">¥<?= number_format($total) ?></span>
            </li>
        </ul>
    </div>
</div>

<?php if ($isCancelled): ?>
<div class="alert alert-secondary">
    <i class="bi bi-x-circle"></i> この注文はキャンセルされています。
</div>

<?php elseif ($isPaid): ?>
<div class="alert alert-success d-flex align-items-center gap-2">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div>
        <strong>入金確認済みです</strong><br>
        <small class="text-muted">担当者による入金確認が完了しています。</small>
    </div>
</div>

<?php elseif ($isSubmitted): ?>
<div class="alert alert-success d-flex align-items-center gap-2">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div>
        <strong>支払い完了を報告済みです</strong><br>
        <small class="text-muted">
            報告金額: <?= number_format($initialAmount) ?>円
            <?php if (!empty($order['payment_submitted_at'])): ?>
            ・ 報告日時: <?= htmlspecialchars(substr($order['payment_submitted_at'], 0, 16)) ?>
            <?php endif; ?>
        </small>
    </div>
</div>
<div class="alert alert-warning">
    <i class="bi bi-clock"></i> 担当者による入金確認をお待ちください。
</div>

<?php else: ?>
<!-- 未報告フォーム -->
<div class="card">
    <div class="card-body">
        <form id="paymentForm">
            <div class="mb-3">
                <label for="paidAmount" class="form-label fw-bold">
                    お支払い金額 <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text">¥</span>
                    <input type="number" class="form-control" id="paidAmount"
                           value="<?= $initialAmount ?>" min="0" step="1" inputmode="numeric" required>
                </div>
                <div class="form-text small">
                    注文合計が初期表示されています。実際に振り込んだ金額と異なる場合は修正してください。
                </div>
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" id="paidCheck">
                <label class="form-check-label fw-bold" for="paidCheck">
                    支払いが完了しました
                </label>
            </div>

            <div id="formError" class="alert alert-danger d-none"></div>

            <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                <i class="bi bi-send-check"></i> 報告する
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
document.getElementById('paymentForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const paid      = document.getElementById('paidCheck').checked;
    const amountEl  = document.getElementById('paidAmount');
    const amount    = parseInt(amountEl.value, 10);
    const errorDiv  = document.getElementById('formError');
    const submitBtn = document.getElementById('submitBtn');

    if (isNaN(amount) || amount < 0) {
        errorDiv.textContent = '金額を正しく入力してください';
        errorDiv.classList.remove('d-none');
        return;
    }
    if (!paid) {
        errorDiv.textContent = '「支払いが完了しました」にチェックしてください';
        errorDiv.classList.remove('d-none');
        return;
    }

    errorDiv.classList.add('d-none');
    submitBtn.disabled  = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> 送信中...';

    try {
        const res = await fetch('/api/member/store/orders/<?= (int)$order['id'] ?>/submit-payment', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ paid_amount: amount }),
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            errorDiv.textContent = data.error?.message || '報告に失敗しました';
            errorDiv.classList.remove('d-none');
            submitBtn.disabled  = false;
            submitBtn.innerHTML = '<i class="bi bi-send-check"></i> 報告する';
        }
    } catch (err) {
        errorDiv.textContent = '通信エラーが発生しました';
        errorDiv.classList.remove('d-none');
        submitBtn.disabled  = false;
        submitBtn.innerHTML = '<i class="bi bi-send-check"></i> 報告する';
    }
});
</script>
