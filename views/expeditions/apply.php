<?php
// Step 1: 学籍番号ログインページ
// 変数: $expedition, $token
?>
<div class="card shadow">
    <div class="card-body p-4">
        <h2 class="text-center mb-2"><?= htmlspecialchars($expedition['name'] ?? '遠征') ?></h2>
        <p class="text-center text-muted mb-4">
            <?= htmlspecialchars($expedition['start_date'] ?? '') ?> 〜 <?= htmlspecialchars($expedition['end_date'] ?? '') ?>
        </p>

        <hr class="my-4">

        <h5 class="mb-3">学籍番号を入力してください</h5>
        <p class="text-muted small mb-3">会員名簿に登録されている学籍番号でログインします。</p>

        <div class="mb-3">
            <input type="text" class="form-control form-control-lg" id="studentIdInput"
                   placeholder="例: 1Y25F001-1"
                   autocomplete="off"
                   autocapitalize="characters"
                   inputmode="latin"
                   lang="en"
                   pattern="[A-Z0-9\-]*"
                   style="text-transform: uppercase; ime-mode: disabled;"
                   oninput="this.value = this.value.replace(/[Ａ-Ｚａ-ｚ０-９]/g, c => String.fromCharCode(c.charCodeAt(0) - 0xFEE0)).replace(/[ー－−―‐]/g, '-').toUpperCase().replace(/[^A-Z0-9\-]/g, '')"
                   onkeydown="if(event.key==='Enter') doLogin()">
        </div>

        <div id="loginError" class="alert alert-danger d-none"></div>

        <div class="d-grid">
            <button class="btn btn-primary btn-lg" id="loginBtn" onclick="doLogin()">
                ログインして次へ <i class="bi bi-arrow-right"></i>
            </button>
        </div>
    </div>
</div>

<script>
async function doLogin() {
    const studentId = document.getElementById('studentIdInput').value.trim();
    const errEl     = document.getElementById('loginError');
    const btn       = document.getElementById('loginBtn');
    errEl.classList.add('d-none');

    if (!studentId) {
        errEl.textContent = '学籍番号を入力してください';
        errEl.classList.remove('d-none');
        return;
    }

    btn.disabled     = true;
    btn.textContent  = 'ログイン中...';

    try {
        const res    = await fetch('/api/member/login', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    `student_id=${encodeURIComponent(studentId)}`,
        });
        const result = await res.json();

        if (result.success) {
            window.location.href = '/apply/expedition/<?= htmlspecialchars($token) ?>/confirm';
        } else {
            errEl.textContent = result.error?.message || 'ログインに失敗しました。学籍番号を確認してください。';
            errEl.classList.remove('d-none');
            btn.disabled    = false;
            btn.textContent = 'ログインして次へ ›';
        }
    } catch (err) {
        errEl.textContent = '通信エラーが発生しました';
        errEl.classList.remove('d-none');
        btn.disabled    = false;
        btn.textContent = 'ログインして次へ ›';
    }
}

document.getElementById('studentIdInput').focus();
</script>
