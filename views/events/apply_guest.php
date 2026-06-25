<?php
/**
 * 企画 ゲスト（非会員）申込フォーム（新歓 / OBOG）
 * 変数: $event, $token, $fields（カスタム項目）, $guestType（'shinkan'|'obog'|null）, $matchOptions（学科 or 代）
 *
 * フロー:
 *   Step1: 氏名・カナ・(代/学科) を入力して照合
 *     → この企画に申込済みなら内容を表示（変更・キャンセル）
 *     → 未申込 or 一致なしなら新規申込フォーム
 */
$isObog   = ($guestType === 'obog');
$isShinkan= ($guestType === 'shinkan');
$matchLabel = $isObog ? '代' : ($isShinkan ? '学科' : '区分');
?>
<div class="container py-5" style="max-width: 560px;">
    <div class="text-center mb-4">
        <h4 class="fw-bold"><?= htmlspecialchars($event['title'] ?? '企画') ?></h4>
        <p class="text-muted mb-0">
            <?= $isObog ? 'OB・OGの方の申し込みフォーム' : ($isShinkan ? '新歓申し込みフォーム' : '会員以外の方の申し込みフォーム') ?>
        </p>
    </div>

    <!-- 企画情報 -->
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <?php if (!empty($event['event_date'])): ?>
            <div class="small text-muted">
                <i class="bi bi-calendar"></i>
                <?= date('Y年n月j日', strtotime($event['event_date'])) ?>
                <?= !empty($event['event_time']) ? '　' . substr($event['event_time'], 0, 5) . '〜' : '' ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($event['location'])): ?>
            <div class="small text-muted mt-1"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($event['location']) ?></div>
            <?php endif; ?>
            <?php if ((int)($event['participation_fee'] ?? 0) > 0): ?>
            <div class="small text-muted mt-1"><i class="bi bi-cash"></i> 参加費: ¥<?= number_format((int)$event['participation_fee']) ?></div>
            <?php endif; ?>
            <?php if (!empty($event['description'])): ?>
            <div class="small mt-2" style="white-space: pre-wrap;"><?= htmlspecialchars($event['description']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Step1: 本人特定 ── -->
    <div class="card shadow-sm" id="stepIdentify">
        <div class="card-body p-4">
            <p class="small text-muted">
                まずはご本人の情報を入力してください。過去に申し込み済みの場合は内容を確認・変更できます。
            </p>
            <div class="mb-3">
                <label class="form-label fw-semibold">氏名 <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="keyName" maxlength="255" autocomplete="name" placeholder="例: 山田　太郎">
                <div class="form-text">姓と名の間は全角スペースに統一されます</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">氏名（カナ） <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="keyNameKana" maxlength="255" placeholder="例: ヤマダ　タロウ">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold"><?= htmlspecialchars($matchLabel) ?> <span class="text-danger">*</span></label>
                <?php if (!empty($matchOptions)): ?>
                <select class="form-select" id="keyMatch">
                    <option value="">選択してください</option>
                    <?php foreach ($matchOptions as $o): ?>
                        <option value="<?= htmlspecialchars($o) ?>"><?= htmlspecialchars($o) ?><?= $isObog ? '代' : '' ?></option>
                    <?php endforeach; ?>
                </select>
                <?php else: ?>
                <input type="text" class="form-control" id="keyMatch" maxlength="255">
                <?php endif; ?>
            </div>
            <div id="identifyError" class="alert alert-danger d-none"></div>
            <button type="button" class="btn btn-primary w-100 btn-lg" id="identifyBtn" onclick="doLookup()">次へ</button>
            <div class="text-center mt-3">
                <a href="/apply/event/<?= htmlspecialchars($token) ?>" class="small text-muted">← 会員の方はこちら</a>
            </div>
        </div>
    </div>

    <!-- ── 既存申込の表示（変更・キャンセル） ── -->
    <div class="card shadow-sm d-none" id="stepExisting">
        <div class="card-body p-4">
            <div class="alert alert-info">
                <i class="bi bi-check-circle"></i> この企画にすでにお申し込みいただいています。
                <span id="existingStatusLabel"></span>
            </div>
            <h6 class="fw-bold">現在の申し込み内容</h6>
            <div id="existingSummary" class="mb-3"></div>

            <button type="button" class="btn btn-outline-primary w-100 mb-2" onclick="showEditForm()">申し込み内容を変更する</button>
            <button type="button" class="btn btn-outline-danger w-100" onclick="doCancel()">申し込みをキャンセルする</button>
            <div class="text-center mt-3">
                <a href="#" class="small text-muted" onclick="backToIdentify(event)">← 戻る</a>
            </div>
        </div>
    </div>

    <!-- ── 申込フォーム（新規 / 変更 共通） ── -->
    <div class="card shadow-sm d-none" id="stepForm">
        <div class="card-body p-4">
            <div id="formModeLabel" class="fw-bold mb-3"></div>
            <div class="mb-3 small text-muted" id="formWho"></div>

            <!-- カスタム項目 -->
            <?php foreach ($fields as $f): ?>
                <?php
                    $fid      = (int)$f['id'];
                    $required = !empty($f['is_required']);
                    $opts     = is_array($f['options'] ?? null) ? $f['options'] : [];
                ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <?= htmlspecialchars($f['label']) ?>
                        <?= $required ? '<span class="text-danger">*</span>' : '' ?>
                    </label>
                    <?php if (!empty($f['description'])): ?>
                        <div class="form-text mb-1" style="white-space: pre-wrap;"><?= htmlspecialchars($f['description']) ?></div>
                    <?php endif; ?>

                    <?php if ($f['type'] === 'textarea'): ?>
                        <textarea class="form-control guest-field" data-fid="<?= $fid ?>" rows="3"></textarea>
                    <?php elseif ($f['type'] === 'select'): ?>
                        <select class="form-select guest-field" data-fid="<?= $fid ?>">
                            <option value="">選択してください</option>
                            <?php foreach ($opts as $o): ?>
                                <option value="<?= htmlspecialchars($o) ?>"><?= htmlspecialchars($o) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($f['type'] === 'radio'): ?>
                        <div>
                            <?php foreach ($opts as $i => $o): ?>
                                <div class="form-check">
                                    <input class="form-check-input guest-field" type="radio"
                                           name="field_<?= $fid ?>" data-fid="<?= $fid ?>"
                                           id="field_<?= $fid ?>_<?= $i ?>" value="<?= htmlspecialchars($o) ?>">
                                    <label class="form-check-label" for="field_<?= $fid ?>_<?= $i ?>"><?= htmlspecialchars($o) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <input type="text" class="form-control guest-field" data-fid="<?= $fid ?>" maxlength="255">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div id="formError" class="alert alert-danger d-none"></div>
            <button type="button" class="btn btn-primary w-100 btn-lg" id="formSubmitBtn" onclick="submitForm()">申し込む</button>
            <div class="text-center mt-3">
                <a href="#" class="small text-muted" onclick="backToIdentify(event)">← 戻る</a>
            </div>
        </div>
    </div>
</div>

<script>
const APPLY_TOKEN = <?= json_encode($token) ?>;
const GUEST_FIELDS = <?= json_encode(array_map(fn($f) => ['id' => (int)$f['id'], 'label' => $f['label']], $fields), JSON_UNESCAPED_UNICODE) ?>;
let formMode = 'create';   // 'create' | 'edit'
let currentKeys = null;    // {name, name_kana, match_key}

function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// 氏名の姓名間スペースを全角に矯正（半角/全角の連続スペースを全角1つにまとめ、前後を除去）
function normalizeName(v) {
    return String(v ?? '')
        .replace(/[\s　]+/g, '　')   // 半角/全角スペースの連続 → 全角スペース1つ
        .replace(/^　|　$/g, '');         // 先頭・末尾の全角スペースを除去
}

// 氏名・カナ欄に入力中・離脱時の矯正を適用
['keyName', 'keyNameKana'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    let composing = false;
    el.addEventListener('compositionstart', () => { composing = true; });
    el.addEventListener('compositionend', function () {
        composing = false;
        const a = normalizeName(this.value);
        if (this.value !== a) this.value = a;
    });
    el.addEventListener('blur', function () {
        const a = normalizeName(this.value);
        if (this.value !== a) this.value = a;
    });
});

function getKeys() {
    return {
        name:      normalizeName(document.getElementById('keyName').value),
        name_kana: normalizeName(document.getElementById('keyNameKana').value),
        match_key: document.getElementById('keyMatch').value.trim(),
    };
}

function showStep(id) {
    ['stepIdentify', 'stepExisting', 'stepForm'].forEach(s =>
        document.getElementById(s).classList.toggle('d-none', s !== id));
}

function backToIdentify(e) {
    if (e) e.preventDefault();
    showStep('stepIdentify');
}

// ── Step1: 照合 ──
async function doLookup() {
    const keys  = getKeys();
    const errEl = document.getElementById('identifyError');
    const btn   = document.getElementById('identifyBtn');
    errEl.classList.add('d-none');

    if (!keys.name || !keys.name_kana || !keys.match_key) {
        errEl.textContent = 'すべての項目を入力してください';
        errEl.classList.remove('d-none');
        return;
    }
    currentKeys = keys;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> 確認中...';

    try {
        const res  = await fetch(`/api/apply/event/${APPLY_TOKEN}/guest/lookup`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(keys),
        });
        const data = await res.json();
        btn.disabled = false;
        btn.innerHTML = '次へ';

        if (data.success && data.data.matched) {
            renderExisting(data.data.application);
            showStep('stepExisting');
        } else {
            // 未申込 or 一致なし → 新規申込フォーム
            formMode = 'create';
            prepareForm(null);
            showStep('stepForm');
        }
    } catch (e) {
        errEl.textContent = '通信エラーが発生しました';
        errEl.classList.remove('d-none');
        btn.disabled = false;
        btn.innerHTML = '次へ';
    }
}

// ── 既存申込の表示 ──
function renderExisting(app) {
    document.getElementById('existingStatusLabel').textContent =
        app.status === 'waitlisted' ? '（現在キャンセル待ちです）' : '';

    let html = '<ul class="list-group mb-0">';
    html += `<li class="list-group-item d-flex justify-content-between"><span class="text-muted">氏名</span><span>${esc(currentKeys.name)}</span></li>`;
    GUEST_FIELDS.forEach(f => {
        const v = app.values && app.values[f.id] != null ? app.values[f.id] : '—';
        html += `<li class="list-group-item d-flex justify-content-between"><span class="text-muted">${esc(f.label)}</span><span>${esc(v) || '—'}</span></li>`;
    });
    if (app.note) {
        html += `<li class="list-group-item d-flex justify-content-between"><span class="text-muted">備考</span><span>${esc(app.note)}</span></li>`;
    }
    html += '</ul>';
    document.getElementById('existingSummary').innerHTML = html;

    // 変更フォームに現在値をセットできるよう保持
    window._existingApp = app;
}

function showEditForm() {
    formMode = 'edit';
    prepareForm(window._existingApp);
    showStep('stepForm');
}

// ── フォーム初期化（new=null / edit=既存app） ──
function prepareForm(app) {
    document.getElementById('formModeLabel').textContent =
        formMode === 'edit' ? '申し込み内容の変更' : '申し込み内容の入力';
    document.getElementById('formWho').textContent = `${currentKeys.name} さん`;
    document.getElementById('formSubmitBtn').textContent = formMode === 'edit' ? '変更を保存する' : '申し込む';
    document.getElementById('formError').classList.add('d-none');

    // 各項目に既存値を反映（新規なら空に）
    document.querySelectorAll('.guest-field').forEach(el => {
        const fid = el.dataset.fid;
        const val = (app && app.values && app.values[fid] != null) ? String(app.values[fid]) : '';
        if (el.type === 'radio') {
            el.checked = (el.value === val);
        } else {
            el.value = val;
        }
    });
}

function collectValues() {
    const values = {};
    document.querySelectorAll('.guest-field').forEach(el => {
        const fid = el.dataset.fid;
        if (el.type === 'radio') {
            if (el.checked) values[fid] = el.value;
        } else {
            values[fid] = el.value.trim();
        }
    });
    return values;
}

// ── 申込 / 変更の送信 ──
async function submitForm() {
    const errEl = document.getElementById('formError');
    const btn   = document.getElementById('formSubmitBtn');
    errEl.classList.add('d-none');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> 送信中...';

    const url = formMode === 'edit'
        ? `/api/apply/event/${APPLY_TOKEN}/guest/update`
        : `/api/apply/event/${APPLY_TOKEN}/guest`;

    try {
        const res  = await fetch(url, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ...currentKeys, values: collectValues() }),
        });
        const data = await res.json();
        if (data.success) {
            const status = data.data?.status || 'submitted';
            const action = formMode === 'edit' ? 'updated' : status;
            window.location.href = `/apply/event/${APPLY_TOKEN}/complete?guest=1&status=${status}&action=${action}`;
        } else {
            errEl.textContent = data.error?.message || '送信に失敗しました';
            errEl.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = formMode === 'edit' ? '変更を保存する' : '申し込む';
        }
    } catch (e) {
        errEl.textContent = '通信エラーが発生しました';
        errEl.classList.remove('d-none');
        btn.disabled = false;
        btn.innerHTML = formMode === 'edit' ? '変更を保存する' : '申し込む';
    }
}

// ── キャンセル ──
async function doCancel() {
    if (!confirm('この企画への申し込みをキャンセルします。よろしいですか？')) return;
    try {
        const res  = await fetch(`/api/apply/event/${APPLY_TOKEN}/guest/cancel`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(currentKeys),
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = `/apply/event/${APPLY_TOKEN}/complete?guest=1&action=cancelled`;
        } else {
            alert(data.error?.message || 'キャンセルに失敗しました');
        }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}
</script>
