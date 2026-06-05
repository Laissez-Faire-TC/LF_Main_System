<div style="max-width: 420px; margin: 3rem auto;">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h2 class="text-center mb-1 fw-normal">会員ログイン</h2>
            <p class="text-center text-muted small mb-4">Laissez-Faire T.C.</p>

            <div id="loginError" class="alert alert-danger d-none"></div>

            <?php if (!empty($oauthError)): ?>
            <div class="alert alert-warning small py-2"><?= htmlspecialchars($oauthError) ?></div>
            <?php endif; ?>

            <?php if (!empty($returnTo)): ?>
            <div class="alert alert-info small py-2">ログイン後、元のページへ戻ります</div>
            <?php endif; ?>

            <?php $returnQuery = !empty($returnTo) ? '?return=' . urlencode($returnTo) : ''; ?>
            <?php if (!empty($googleEnabled) || !empty($lineEnabled)): ?>
            <div class="d-grid gap-2 mb-3">
                <?php if (!empty($googleEnabled)): ?>
                <a href="/member/oauth/google/start<?= $returnQuery ?>" class="btn btn-outline-dark d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-google"></i> Googleでログイン
                </a>
                <?php endif; ?>
                <?php if (!empty($lineEnabled)): ?>
                <a href="/member/oauth/line/start<?= $returnQuery ?>" class="btn d-flex align-items-center justify-content-center gap-2" style="background-color:#06C755;color:#fff;">
                    <i class="bi bi-chat-fill"></i> LINEでログイン
                </a>
                <?php endif; ?>
            </div>
            <div class="text-center text-muted small mb-2">または学籍番号でログイン</div>
            <p class="text-center text-muted" style="font-size:.75rem;">
                ※ 外部アカウント連携を設定した方は、上のボタンからのみログインできます。
            </p>
            <?php endif; ?>

            <form id="memberLoginForm" onsubmit="return handleMemberLogin(event)">
                <input type="hidden" id="returnTo" value="<?= htmlspecialchars($returnTo ?? '') ?>">
                <div class="mb-3">
                    <label for="student_id" class="form-label">学籍番号</label>
                    <input type="text" class="form-control" id="student_id"
                           placeholder="例: 1Y25F158-5" required autofocus>
                    <div class="form-text">半角・大文字で入力（自動変換されます）</div>
                </div>
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary">ログイン</button>
                </div>
            </form>

            <?php if (!empty($returnTo) && strpos($returnTo, '/store') === 0): ?>
            <hr class="my-4">
            <div class="alert alert-warning small mb-2">
                <i class="bi bi-exclamation-circle"></i>
                まだ会員登録されていない方は、下のボタンから学籍番号を入力して購入できます。<br>
                <span class="text-muted">（後日、入会登録時に自動で紐付けされます）</span>
            </div>
            <div class="d-grid">
                <a href="/store/pending" class="btn btn-warning fw-bold">
                    <i class="bi bi-cart-plus"></i> 学籍番号を入力して購入する
                </a>
            </div>
            <?php endif; ?>

            <hr class="my-4">
            <div class="d-flex justify-content-between align-items-center">
                <a href="/portal" class="text-muted small">← ポータルに戻る</a>
                <a href="/login" class="text-muted small">幹部ログイン →</a>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const input = document.getElementById('student_id');
    if (!input) return;

    const sanitize = (val) => val
        .replace(/[！-～]/g, s => String.fromCharCode(s.charCodeAt(0) - 0xFEE0))
        .replace(/　/g, '')
        .toUpperCase()
        .replace(/[^A-Z0-9\-]/g, '');

    let composing = false;
    input.addEventListener('compositionstart', () => { composing = true; });
    input.addEventListener('compositionend', function() {
        composing = false;
        const after = sanitize(this.value);
        if (this.value !== after) {
            this.value = after;
            this.setSelectionRange(after.length, after.length);
        }
    });

    input.addEventListener('input', function() {
        if (composing) return;
        const pos = this.selectionStart;
        const before = this.value;
        const after = sanitize(before);
        if (before === after) return;
        this.value = after;
        const diff = before.length - after.length;
        this.setSelectionRange(Math.max(0, pos - diff), Math.max(0, pos - diff));
    });
})();

async function handleMemberLogin(e) {
    e.preventDefault();

    const studentId = document.getElementById('student_id').value.trim();
    const returnTo  = document.getElementById('returnTo').value;
    const errorDiv  = document.getElementById('loginError');
    errorDiv.classList.add('d-none');

    try {
        const res = await fetch('/index.php?route=api/member/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ student_id: studentId, return: returnTo || null })
        });

        const data = await res.json();

        if (data.success) {
            window.location.href = data.data?.redirect || '/member/home';
        } else {
            errorDiv.textContent = data.error?.message || 'ログインに失敗しました';
            errorDiv.classList.remove('d-none');
        }
    } catch (err) {
        errorDiv.textContent = '通信エラーが発生しました';
        errorDiv.classList.remove('d-none');
    }

    return false;
}
</script>
