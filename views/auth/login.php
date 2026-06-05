<div class="row justify-content-center mt-5">
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-body p-4">
                <h2 class="text-center mb-4">幹部システム</h2>

                <div id="loginError" class="alert alert-danger d-none"></div>

                <?php if (!empty($oauthError)): ?>
                <div class="alert alert-warning small"><?= htmlspecialchars($oauthError) ?></div>
                <?php endif; ?>

                <?php if (!empty($googleEnabled)): ?>
                <div class="d-grid mb-3">
                    <a href="/admin/oauth/google/start" class="btn btn-outline-dark d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-google"></i> Googleでログイン
                    </a>
                </div>
                <div class="text-center text-muted small mb-3">または共有パスワードでログイン</div>
                <?php endif; ?>

                <form id="loginForm" onsubmit="return handleLogin(event)">
                    <div class="mb-3">
                        <label for="password" class="form-label">パスワード</label>
                        <input type="password" class="form-control" id="password" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">ログイン</button>
                </form>

                <div class="text-center mt-3">
                    <a href="/" class="btn btn-outline-secondary btn-sm">← HPに戻る</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function handleLogin(e) {
    e.preventDefault();

    const password = document.getElementById('password').value;
    const errorDiv = document.getElementById('loginError');

    try {
        const res = await fetch('/index.php?route=api/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ password })
        });

        const data = await res.json();

        if (data.success) {
            window.location.href = '/dashboard';
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
