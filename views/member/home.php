<div class="pt-3 mb-4">
    <h4 class="fw-normal mb-1">こんにちは、<?= htmlspecialchars($memberName) ?> さん</h4>
    <p class="text-muted small mb-0">Laissez-Faire T.C. 会員ページ</p>
</div>

<?php if (!empty($pendingCollections)): ?>
<h6 class="text-uppercase text-muted fw-bold mb-3 small">振込確認フォーム</h6>
<div class="card border-danger mb-4">
    <div class="card-header bg-danger bg-opacity-10 border-danger">
        <i class="bi bi-cash-coin"></i> 振込確認をお願いします
    </div>
    <div class="card-body p-0">
        <?php foreach ($pendingCollections as $i => $pc):
            $isPastDeadline = $pc['deadline'] < date('Y-m-d');
        ?>
        <div class="d-flex justify-content-between align-items-center p-3 <?= $i < count($pendingCollections) - 1 ? 'border-bottom' : '' ?>">
            <div>
                <h6 class="mb-1"><?= htmlspecialchars($pc['camp_name']) ?></h6>
                <small class="text-muted">
                    <?= number_format((int)($pc['custom_amount'] ?? $pc['default_amount'])) ?>円
                    <?php if ($isPastDeadline): ?>
                    <span class="badge bg-danger ms-1">期限超過</span>
                    <?php else: ?>
                    ・期限 <?= date('n/j', strtotime($pc['deadline'])) ?>
                    <?php endif; ?>
                </small>
            </div>
            <a href="/member/collection/<?= (int)$pc['collection_id'] ?>" class="btn btn-danger btn-sm">振込確認</a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($pendingExpeditionCollections)): ?>
<h6 class="text-uppercase text-muted fw-bold mb-3 small">遠征 振込確認フォーム</h6>
<div class="card border-danger mb-4">
    <div class="card-header bg-danger bg-opacity-10 border-danger">
        <i class="bi bi-cash-coin"></i> 振込確認をお願いします（遠征）
    </div>
    <div class="card-body p-0">
        <?php foreach ($pendingExpeditionCollections as $i => $pc):
            $isPastDeadline = !empty($pc['deadline']) && $pc['deadline'] < date('Y-m-d');
            $roundLabel     = (int)$pc['round'] === 1 ? '第1回' : '第2回';
            $isRefund       = (int)$pc['amount'] < 0;
        ?>
        <div class="d-flex justify-content-between align-items-center p-3 <?= $i < count($pendingExpeditionCollections) - 1 ? 'border-bottom' : '' ?>">
            <div>
                <h6 class="mb-1">
                    <?= htmlspecialchars($pc['expedition_name']) ?>
                    <span class="badge bg-secondary ms-1"><?= $roundLabel ?></span>
                </h6>
                <small class="text-muted">
                    <?php if ($isRefund): ?>
                    <span class="text-success">返金 <?= number_format(-(int)$pc['amount']) ?>円</span>
                    <?php else: ?>
                    <?= number_format((int)$pc['amount']) ?>円
                    <?php endif; ?>
                    <?php if ($isPastDeadline): ?>
                    <span class="badge bg-danger ms-1">期限超過</span>
                    <?php elseif (!empty($pc['deadline'])): ?>
                    ・期限 <?= date('n/j', strtotime($pc['deadline'])) ?>
                    <?php endif; ?>
                </small>
            </div>
            <a href="/member/expedition-collection/<?= (int)$pc['collection_id'] ?>" class="btn btn-danger btn-sm">振込確認</a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($pendingFees)): ?>
<h6 class="text-uppercase text-muted fw-bold mb-3 small">入会金振込確認</h6>
<div class="card border-danger mb-4">
    <div class="card-header bg-danger bg-opacity-10 border-danger">
        <i class="bi bi-bank"></i> 入会金の振込確認をお願いします
    </div>
    <div class="card-body p-0">
        <?php foreach ($pendingFees as $i => $pf):
            $isPastDeadline = $pf['deadline'] < date('Y-m-d');
            $amount = $pf['effective_amount'] !== null ? number_format((int)$pf['effective_amount']) . '円' : '金額未設定';
        ?>
        <div class="d-flex justify-content-between align-items-center p-3 <?= $i < count($pendingFees) - 1 ? 'border-bottom' : '' ?>">
            <div>
                <h6 class="mb-1"><?= htmlspecialchars($pf['fee_name']) ?></h6>
                <small class="text-muted">
                    <?= $amount ?>
                    <?php if ($isPastDeadline): ?>
                    <span class="badge bg-danger ms-1">期限超過</span>
                    <?php else: ?>
                    ・期限 <?= date('n/j', strtotime($pf['deadline'])) ?>
                    <?php endif; ?>
                </small>
            </div>
            <a href="/member/membership-fee/<?= (int)$pf['membership_fee_id'] ?>" class="btn btn-danger btn-sm">振込確認</a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($renewOpen): ?>
<!-- 継続入会 -->
<h6 class="text-uppercase text-muted fw-bold mb-3 small">継続入会</h6>
<div class="card border-success mb-4">
    <div class="card-header bg-success bg-opacity-10 border-success">
        <i class="bi bi-arrow-repeat"></i> <?= htmlspecialchars((string)$renewYear) ?>年度 継続入会受付中
    </div>
    <div class="d-flex justify-content-between align-items-center p-3">
        <div>
            <?php if ($alreadyRenewed): ?>
            <span class="text-muted small"><?= htmlspecialchars((string)$renewYear) ?>年度への継続登録が完了しています</span>
            <?php else: ?>
            <span class="small">昨年度の情報を引き継いで<?= htmlspecialchars((string)$renewYear) ?>年度に登録できます</span>
            <?php endif; ?>
        </div>
        <div>
            <?php if ($alreadyRenewed): ?>
            <span class="badge bg-success"><i class="bi bi-check-circle"></i> 登録済み</span>
            <?php else: ?>
            <a href="/renew/confirm?member_id=<?= (int)$memberId ?>" class="btn btn-success btn-sm">
                継続入会する <i class="bi bi-arrow-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($activeCamps)): ?>
<!-- 募集中の合宿 -->
<h6 class="text-uppercase text-muted fw-bold mb-3 small">募集中の合宿</h6>
<div class="card border-warning mb-4">
    <div class="card-header bg-warning bg-opacity-25 border-warning">
        <i class="bi bi-megaphone"></i> 申込受付中
    </div>
    <div class="card-body p-0">
        <?php foreach ($activeCamps as $i => $camp): ?>
        <div class="d-flex justify-content-between align-items-center p-3 <?= $i < count($activeCamps) - 1 ? 'border-bottom' : '' ?>">
            <div>
                <h6 class="mb-1"><?= htmlspecialchars($camp['camp_name']) ?></h6>
                <small class="text-muted">
                    <?= date('Y/n/j', strtotime($camp['start_date'])) ?> 〜 <?= date('n/j', strtotime($camp['end_date'])) ?>
                    <?php if ($camp['deadline']): ?>
                    &nbsp;・&nbsp;締切 <?= date('n/j', strtotime($camp['deadline'])) ?>
                    <?php endif; ?>
                </small>
            </div>
            <a href="/apply/<?= htmlspecialchars($camp['token']) ?>" class="btn btn-warning btn-sm">
                申し込む
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($activeExpeditions)): ?>
<!-- 募集中の遠征 -->
<h6 class="text-uppercase text-muted fw-bold mb-3 small">募集中の遠征</h6>
<div class="card border-warning mb-4">
    <div class="card-header bg-warning bg-opacity-25 border-warning">
        <i class="bi bi-backpack"></i> 申込受付中
    </div>
    <div class="card-body p-0">
        <?php foreach ($activeExpeditions as $i => $ex): ?>
        <div class="d-flex justify-content-between align-items-center p-3 <?= $i < count($activeExpeditions) - 1 ? 'border-bottom' : '' ?>">
            <div>
                <h6 class="mb-1"><?= htmlspecialchars($ex['name']) ?></h6>
                <small class="text-muted">
                    <?= date('Y/n/j', strtotime($ex['start_date'])) ?> 〜 <?= date('n/j', strtotime($ex['end_date'])) ?>
                    <?php
                    $deadlineDisplay = !empty($ex['deadline']) ? $ex['deadline'] : (!empty($ex['expires_at']) ? $ex['expires_at'] : null);
                    if ($deadlineDisplay): ?>
                    &nbsp;・&nbsp;締切 <?= date('n/j', strtotime($deadlineDisplay)) ?>
                    <?php endif; ?>
                </small>
            </div>
            <?php if ($ex['already_applied']): ?>
            <span class="badge bg-success"><i class="bi bi-check-circle"></i> 申込済</span>
            <?php else: ?>
            <a href="/apply/expedition/<?= htmlspecialchars($ex['token']) ?>" class="btn btn-warning btn-sm">
                申し込む
            </a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($expenseExpeditions)): ?>
<!-- レンタカー費用申請 -->
<h6 class="text-uppercase text-muted fw-bold mb-3 small">レンタカー費用申請</h6>
<div class="card border-info mb-4">
    <div class="card-header bg-info bg-opacity-10 border-info">
        <i class="bi bi-receipt"></i> 費用申請
    </div>
    <div class="card-body p-0">
        <?php foreach ($expenseExpeditions as $i => $ex): ?>
        <?php $exp = $ex['my_expense']; ?>
        <div class="p-3 <?= $i < count($expenseExpeditions) - 1 ? 'border-bottom' : '' ?>">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h6 class="mb-0"><?= htmlspecialchars($ex['name']) ?></h6>
                    <small class="text-muted">申請期限: <?= date('Y/n/j', strtotime($ex['expense_deadline'])) ?></small>
                </div>
                <?php if ($exp): ?>
                <span class="badge bg-success"><i class="bi bi-check-circle"></i> 申請済み</span>
                <?php endif; ?>
            </div>
            <!-- 申請フォーム（折りたたみ） -->
            <div class="accordion" id="expenseAccordion<?= $ex['id'] ?>">
                <div class="accordion-item border-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= $exp ? '' : 'collapsed' ?> py-2 px-3 bg-light" type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#expenseForm<?= $ex['id'] ?>">
                            <?= $exp ? '申請内容を確認・修正する' : '費用を申請する' ?>
                        </button>
                    </h2>
                    <div id="expenseForm<?= $ex['id'] ?>" class="accordion-collapse collapse <?= $exp ? 'show' : '' ?>">
                        <div class="accordion-body pt-2">
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small mb-1">レンタカー代 (円)</label>
                                    <input type="number" class="form-control form-control-sm" min="0"
                                           id="rental_<?= $ex['id'] ?>"
                                           value="<?= (int)($exp['rental_fee'] ?? 0) ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1">ガソリン代 (円)</label>
                                    <input type="number" class="form-control form-control-sm" min="0"
                                           id="gas_<?= $ex['id'] ?>"
                                           value="<?= (int)($exp['gas_fee'] ?? 0) ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1">高速料金 (円)</label>
                                    <input type="number" class="form-control form-control-sm" min="0"
                                           id="highway_<?= $ex['id'] ?>"
                                           value="<?= (int)($exp['highway_fee'] ?? 0) ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1">その他 (円)</label>
                                    <input type="number" class="form-control form-control-sm" min="0"
                                           id="other_<?= $ex['id'] ?>"
                                           value="<?= (int)($exp['other_fee'] ?? 0) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small mb-1">その他の内訳</label>
                                    <input type="text" class="form-control form-control-sm"
                                           id="otherDesc_<?= $ex['id'] ?>"
                                           placeholder="例: 駐車場代"
                                           value="<?= htmlspecialchars($exp['other_description'] ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small mb-1">備考</label>
                                    <input type="text" class="form-control form-control-sm"
                                           id="note_<?= $ex['id'] ?>"
                                           value="<?= htmlspecialchars($exp['note'] ?? '') ?>">
                                </div>
                            </div>
                            <div id="expenseErr_<?= $ex['id'] ?>" class="alert alert-danger d-none py-1 small mb-2"></div>
                            <button class="btn btn-info btn-sm w-100"
                                    onclick="submitExpense(<?= $ex['id'] ?>)">
                                <i class="bi bi-send"></i> 申請する
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<script>
async function submitExpense(expeditionId) {
    const body = {
        rental_fee:        parseInt(document.getElementById('rental_'    + expeditionId).value) || 0,
        gas_fee:           parseInt(document.getElementById('gas_'       + expeditionId).value) || 0,
        highway_fee:       parseInt(document.getElementById('highway_'   + expeditionId).value) || 0,
        other_fee:         parseInt(document.getElementById('other_'     + expeditionId).value) || 0,
        other_description: document.getElementById('otherDesc_' + expeditionId).value.trim(),
        note:              document.getElementById('note_'      + expeditionId).value.trim(),
    };
    const errEl = document.getElementById('expenseErr_' + expeditionId);
    errEl.classList.add('d-none');

    try {
        const res    = await fetch(`/api/member/expedition/${expeditionId}/car-expense`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(body),
        });
        const result = await res.json();
        if (result.success) {
            location.reload();
        } else {
            errEl.textContent = result.error?.message || '申請に失敗しました';
            errEl.classList.remove('d-none');
        }
    } catch {
        errEl.textContent = '通信エラーが発生しました';
        errEl.classList.remove('d-none');
    }
}
</script>
<?php endif; ?>

<?php if (!empty($expeditionBooklets)): ?>
<!-- 遠征しおり -->
<h6 class="text-uppercase text-muted fw-bold mb-3 small">遠征しおり</h6>
<div class="card border-primary mb-4">
    <div class="card-header bg-primary bg-opacity-10 border-primary">
        <i class="bi bi-map"></i> しおりを見る
    </div>
    <div class="card-body p-0">
        <?php foreach ($expeditionBooklets as $i => $eb): ?>
        <div class="d-flex justify-content-between align-items-center p-3 <?= $i < count($expeditionBooklets) - 1 ? 'border-bottom' : '' ?>">
            <div>
                <h6 class="mb-1"><?= htmlspecialchars($eb['name']) ?></h6>
                <small class="text-muted"><?= date('Y/n/j', strtotime($eb['start_date'])) ?> 〜 <?= date('n/j', strtotime($eb['end_date'])) ?></small>
            </div>
            <a href="/public/expedition-booklet/<?= htmlspecialchars($eb['public_token']) ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-map"></i> しおりを開く
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($bookletCamps)): ?>
<!-- しおり -->
<h6 class="text-uppercase text-muted fw-bold mb-3 small">合宿しおり</h6>
<div class="card border-success mb-4">
    <div class="card-header bg-success bg-opacity-10 border-success">
        <i class="bi bi-book"></i> しおりを見る
    </div>
    <div class="card-body p-0">
        <?php foreach ($bookletCamps as $i => $bc): ?>
        <div class="d-flex justify-content-between align-items-center p-3 <?= $i < count($bookletCamps) - 1 ? 'border-bottom' : '' ?>">
            <div>
                <h6 class="mb-1"><?= htmlspecialchars($bc['name']) ?></h6>
                <small class="text-muted"><?= date('Y/n/j', strtotime($bc['start_date'])) ?> 〜 <?= date('n/j', strtotime($bc['end_date'])) ?></small>
            </div>
            <a href="/member/camp/<?= (int)$bc['id'] ?>/booklet" class="btn btn-outline-success btn-sm">
                <i class="bi bi-book"></i> しおりを開く
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($activeEvents)): ?>
<!-- 募集中の企画 -->
<h6 class="text-uppercase text-muted fw-bold mb-3 small">募集中の企画・イベント</h6>
<div id="eventsSection">
<?php foreach ($activeEvents as $i => $ev):
    $count    = (int)$ev['application_count'];
    $capacity = $ev['capacity'] !== null ? (int)$ev['capacity'] : null;
    $isFull   = $capacity !== null && $count >= $capacity;
    $applied  = ($ev['my_status'] === 'submitted');
?>
<div class="card border-primary mb-3 shadow-sm">
    <div class="card-header bg-primary bg-opacity-10 border-primary d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar-event"></i> <?= htmlspecialchars($ev['title']) ?></span>
        <?php if ($capacity !== null): ?>
        <span class="badge <?= $isFull ? 'bg-danger' : ($count / $capacity >= 0.8 ? 'bg-warning text-dark' : 'bg-primary') ?>">
            <?= $count ?>/<?= $capacity ?>
        </span>
        <?php else: ?>
        <span class="badge bg-light text-dark border"><?= $count ?>人申込中</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="row g-2 mb-3">
            <?php if ($ev['event_date']): ?>
            <div class="col-auto">
                <small class="text-muted"><i class="bi bi-calendar3"></i>
                <?= date('Y年n月j日', strtotime($ev['event_date'])) ?>
                <?php if ($ev['event_time']): ?>
                <?= date('G:i', strtotime($ev['event_time'])) ?>〜
                <?php endif; ?>
                </small>
            </div>
            <?php endif; ?>
            <?php if ($ev['location']): ?>
            <div class="col-auto">
                <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($ev['location']) ?></small>
            </div>
            <?php endif; ?>
            <?php if ((int)$ev['participation_fee'] > 0): ?>
            <div class="col-auto">
                <small class="text-muted"><i class="bi bi-cash"></i> <?= number_format((int)$ev['participation_fee']) ?>円</small>
            </div>
            <?php endif; ?>
        </div>
        <?php if ($ev['description']): ?>
        <p class="small mb-3"><?= nl2br(htmlspecialchars($ev['description'])) ?></p>
        <?php endif; ?>
        <div class="d-flex justify-content-end align-items-center gap-2">
            <?php
            $waitlisted = ($ev['my_status'] === 'waitlisted');
            $myWaitlistPos = (int)($ev['my_waitlist_position'] ?? 0);
            ?>
            <?php if ($applied): ?>
                <span class="badge bg-success">申込済</span>
                <button class="btn btn-sm btn-outline-danger"
                        onclick="cancelEvent(<?= (int)$ev['id'] ?>, this)">キャンセル</button>
            <?php elseif ($waitlisted): ?>
                <span class="badge bg-secondary">キャンセル待ち <?= $myWaitlistPos ?>番目</span>
                <button class="btn btn-sm btn-outline-danger"
                        onclick="cancelEvent(<?= (int)$ev['id'] ?>, this)">取り消す</button>
            <?php elseif ($isFull && $ev['allow_waitlist']): ?>
                <span class="text-muted small me-1">定員満員</span>
                <button class="btn btn-sm btn-outline-secondary"
                        onclick="showApplyModal(<?= (int)$ev['id'] ?>, true)">キャンセル待ちで申し込む</button>
            <?php elseif ($isFull): ?>
                <span class="badge bg-danger">定員締め切り</span>
            <?php else: ?>
                <button class="btn btn-sm btn-primary"
                        onclick="showApplyModal(<?= (int)$ev['id'] ?>, false)">申し込む</button>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($activeCamps) && empty($activeExpeditions) && empty($activeEvents) && empty($pendingCollections) && empty($pendingExpeditionCollections) && empty($pendingFees) && empty($bookletCamps) && empty($expeditionBooklets) && !$renewOpen): ?>
<div class="card mb-4">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-calendar-x" style="font-size: 2rem;"></i>
        <p class="mt-2 mb-0">現在募集中の合宿・イベントはありません</p>
    </div>
</div>
<?php endif; ?>

<div class="card mb-3 border-danger">
    <div class="card-body d-flex justify-content-between align-items-center py-3">
        <div>
            <i class="bi bi-inbox text-danger"></i>
            <span class="ms-2 small">目安箱 — 要望・質問・相談を幹部に届ける</span>
        </div>
        <a href="/member/suggestions" class="btn btn-outline-danger btn-sm">
            目安箱へ <i class="bi bi-chevron-right"></i>
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body d-flex justify-content-between align-items-center py-3">
        <div>
            <i class="bi bi-bag-heart text-primary"></i>
            <span class="ms-2 small">サークルオリジナルアイテム販売</span>
        </div>
        <a href="/member/store" class="btn btn-outline-primary btn-sm">
            ショップへ <i class="bi bi-chevron-right"></i>
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body d-flex justify-content-between align-items-center py-3">
        <div>
            <i class="bi bi-person-gear text-secondary"></i>
            <span class="ms-2 small">電話番号・住所・アレルギー等の変更</span>
        </div>
        <a href="/member/profile" class="btn btn-outline-secondary btn-sm">
            登録情報を変更する <i class="bi bi-chevron-right"></i>
        </a>
    </div>
</div>

<div class="text-muted small text-center mt-4">
    <p class="mb-0">ご不明な点は幹事長までご連絡ください</p>
</div>

<!-- 企画申し込みモーダル -->
<div class="modal fade" id="applyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="applyModalTitle">企画申し込み</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">備考（任意）</label>
                <textarea class="form-control" id="eventNoteInput" rows="3"
                          placeholder="幹事への連絡事項があれば入力してください"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="applyModalBtn" onclick="submitApplyModal()">申し込む</button>
            </div>
        </div>
    </div>
</div>

<script>
let _applyEventId = null;
let _applyWaitlist = false;

function showApplyModal(eventId, isWaitlist) {
    _applyEventId = eventId;
    _applyWaitlist = isWaitlist;
    document.getElementById('applyModalTitle').textContent = isWaitlist ? 'キャンセル待ちで申し込む' : '企画申し込み';
    document.getElementById('applyModalBtn').textContent = isWaitlist ? 'キャンセル待ちに登録' : '申し込む';
    document.getElementById('eventNoteInput').value = '';
    new bootstrap.Modal(document.getElementById('applyModal')).show();
}

async function submitApplyModal() {
    const btn = document.getElementById('applyModalBtn');
    const note = document.getElementById('eventNoteInput').value.trim();
    btn.disabled = true;
    try {
        const res  = await fetch(`/api/member/events/${_applyEventId}/apply`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ note: note || null }),
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.error?.message || '申し込みに失敗しました');
            btn.disabled = false;
        }
    } catch (e) {
        alert('通信エラーが発生しました');
        btn.disabled = false;
    }
}

async function cancelEvent(eventId, btn) {
    if (!confirm('申し込みをキャンセルしますか？')) return;
    btn.disabled = true;
    try {
        const res  = await fetch(`/api/member/events/${eventId}/apply`, { method: 'DELETE' });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.error?.message || 'キャンセルに失敗しました');
            btn.disabled = false;
        }
    } catch (e) {
        alert('通信エラーが発生しました');
        btn.disabled = false;
    }
}
</script>
