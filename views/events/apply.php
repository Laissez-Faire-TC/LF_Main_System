<?php
/**
 * 企画申し込み Step1: 学籍番号ログイン
 * 変数: $event, $token
 */
?>
<div class="container py-5" style="max-width: 480px;">
    <div class="text-center mb-4">
        <h4 class="fw-bold"><?= htmlspecialchars($event['title'] ?? '企画') ?></h4>
        <p class="text-muted mb-0">申し込みには会員番号（学籍番号）でのログインが必要です</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <div class="mb-3">
                <label class="form-label fw-semibold">学籍番号</label>
                <input type="text" class="form-control form-control-lg" id="studentIdInput"
                       placeholder="例: 1Y25F158-5"
                       inputmode="latin"
                       autocapitalize="characters"
                       autocomplete="off"
                       pattern="[A-Z0-9\-]+">
                <div class="form-text">半角英数字・ハイフンで入力してください（自動変換されます）</div>
            </div>
            <div id="loginError" class="alert alert-danger d-none"></div>
            <button type="button" class="btn btn-primary w-100 btn-lg" id="loginBtn" onclick="doLogin()">
                ログイン
            </button>
        </div>
    </div>
</div>

<script>
const APPLY_TOKEN = <?= json_encode($token) ?>;

// 学籍番号の自動変換
const input = document.getElementById('studentIdInput');
const sanitize = (v) => v
    .replace(/[！-～]/g, s => String.fromCharCode(s.charCodeAt(0) - 0xFEE0))
    .replace(/　/g, '')
    .toUpperCase()
    .replace(/[^A-Z0-9\-]/g, '');

let composing = false;
input.addEventListener('compositionstart', () => { composing = true; });
input.addEventListener('compositionend', function () {
    composing = false;
    const a = sanitize(this.value);
    if (this.value !== a) { this.value = a; this.setSelectionRange(a.length, a.length); }
});
input.addEventListener('input', function () {
    if (composing) return;
    const pos = this.selectionStart, before = this.value;
    const after = sanitize(before);
    if (before === after) return;
    this.value = after;
    const diff = before.length - after.length;
    this.setSelectionRange(Math.max(0, pos - diff), Math.max(0, pos - diff));
});
input.addEventListener('paste', function (e) {
    e.preventDefault();
    const text = (e.clipboardData || window.clipboardData).getData('text');
    const s = sanitize(text);
    const st = this.selectionStart, en = this.selectionEnd;
    this.value = this.value.substring(0, st) + s + this.value.substring(en);
    this.setSelectionRange(st + s.length, st + s.length);
});
input.addEventListener('keydown', (e) => { if (e.key === 'Enter') doLogin(); });

async function doLogin() {
    const studentId = input.value.trim();
    const errEl     = document.getElementById('loginError');
    const btn       = document.getElementById('loginBtn');
    errEl.classList.add('d-none');

    if (!studentId) {
        errEl.textContent = '学籍番号を入力してください';
        errEl.classList.remove('d-none');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> 確認中...';

    try {
        const res  = await fetch('/api/member/login', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ student_id: studentId }),
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = `/apply/event/${APPLY_TOKEN}/confirm`;
        } else {
            errEl.textContent = data.error?.message || 'ログインに失敗しました。学籍番号をご確認ください。';
            errEl.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = 'ログイン';
        }
    } catch (e) {
        errEl.textContent = '通信エラーが発生しました';
        errEl.classList.remove('d-none');
        btn.disabled = false;
        btn.innerHTML = 'ログイン';
    }
}
</script>
