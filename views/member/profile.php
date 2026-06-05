<div class="pt-3 mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/member/home">ホーム</a></li>
            <li class="breadcrumb-item active">登録情報の変更</li>
        </ol>
    </nav>
    <h4 class="fw-normal mb-1">登録情報の変更</h4>
    <p class="text-muted small mb-0">変更したい項目のみご入力ください。氏名・学籍番号等の変更は幹部にご連絡ください。</p>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle"></i> 情報を更新しました。変更内容は幹部に通知されます。
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($justLinked)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle"></i>
    <?= htmlspecialchars($justLinked === 'google' ? 'Google' : 'LINE') ?> アカウントを連携しました。次回から連携でログインできます。
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($oauthError)): ?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($oauthError) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div id="alertArea"></div>

<!-- 変更可能な情報フォーム -->
<form id="profileForm">
    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-pencil"></i> 変更可能な情報
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="phone" class="form-label">電話番号</label>
                    <input type="tel" class="form-control" id="phone" name="phone"
                           value="" placeholder="例: 090-1234-5678">
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">メールアドレス</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="" placeholder="例: taro@example.com">
                </div>
                <div class="col-12">
                    <label for="address" class="form-label">住所</label>
                    <input type="text" class="form-control" id="address" name="address"
                           value="" placeholder="例: 東京都新宿区西早稲田1-1-1">
                </div>
                <div class="col-12">
                    <label for="emergency_contact" class="form-label">緊急連絡先</label>
                    <input type="text" class="form-control" id="emergency_contact" name="emergency_contact"
                           value="" placeholder="例: 父 090-0000-0000">
                    <div class="form-text">続柄と電話番号を入力してください</div>
                </div>
                <div class="col-md-6">
                    <label for="line_name" class="form-label">LINE名</label>
                    <input type="text" class="form-control" id="line_name" name="line_name"
                           value="" placeholder="例: 山田太郎">
                </div>
                <div class="col-12">
                    <label for="allergy" class="form-label">アレルギー</label>
                    <input type="text" class="form-control" id="allergy" name="allergy"
                           value="" placeholder="例: 卵・乳製品（なければ空欄）">
                </div>
                <div class="col-12">
                    <label class="form-label">SNS掲載同意</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="sns_allowed" id="sns_yes" value="1">
                            <label class="form-check-label" for="sns_yes">同意する</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="sns_allowed" id="sns_no" value="0">
                            <label class="form-check-label" for="sns_no">同意しない</label>
                        </div>
                    </div>
                    <div class="form-text">活動記録や合宿写真等をSNSに掲載することへの同意（変更したい場合のみ選択）</div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <a href="/member/home" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> ホームに戻る
            </a>
            <button type="submit" class="btn btn-primary" id="submitBtn">
                <i class="bi bi-check-lg"></i> 変更を保存する
            </button>
        </div>
    </div>
</form>

<?php if (!empty($googleEnabled) || !empty($lineEnabled)):
    $linkedCount = count($linkedMap ?? []);
?>
<!-- 外部アカウント連携（ログイン方法） -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-shield-lock"></i> ログイン方法（外部アカウント連携）
    </div>
    <div class="card-body">
        <p class="small text-muted">
            Google・LINE を連携すると、学籍番号の代わりにそのアカウントでログインできます。<br>
            <strong>1つでも連携すると、学籍番号でのログインはできなくなります</strong>（連携アカウントでのみログイン）。
            複数連携しておくと、片方が使えなくなってももう片方でログインできます。
        </p>

        <?php if ($linkedCount === 1): ?>
        <div class="alert alert-warning small py-2">
            <i class="bi bi-exclamation-triangle"></i>
            現在1つだけ連携中です。万一そのアカウントが使えなくなった場合に備え、もう一方も連携するか、幹部に解除を依頼してください。
        </div>
        <?php endif; ?>

        <?php
        $providers = [];
        if (!empty($googleEnabled)) $providers['google'] = ['label' => 'Google', 'icon' => 'bi-google', 'btn' => 'btn-outline-dark', 'style' => ''];
        if (!empty($lineEnabled))   $providers['line']   = ['label' => 'LINE',   'icon' => 'bi-chat-fill', 'btn' => '', 'style' => 'background-color:#06C755;color:#fff;'];
        foreach ($providers as $key => $p):
            $linked = $linkedMap[$key] ?? null;
        ?>
        <div class="d-flex align-items-center justify-content-between border rounded p-2 mb-2">
            <span><i class="bi <?= $p['icon'] ?>"></i> <?= $p['label'] ?></span>
            <?php if ($linked): ?>
                <span class="d-flex align-items-center gap-2">
                    <span class="badge bg-success">連携済み</span>
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="unlinkOauth('<?= $key ?>', '<?= $p['label'] ?>')">解除</button>
                </span>
            <?php else: ?>
                <a href="/member/oauth/<?= $key ?>/start" class="btn btn-sm <?= $p['btn'] ?>" style="<?= $p['style'] ?>">連携する</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
async function unlinkOauth(provider, label) {
    if (!confirm(label + ' の連携を解除しますか？\n他に連携がない場合、解除後は学籍番号でのログインに戻ります。')) return;
    try {
        const res  = await fetch('/api/member/oauth/' + provider + '/unlink', { method: 'POST' });
        const json = await res.json();
        if (json.success) {
            window.location.reload();
        } else {
            alert(json.error?.message || '解除に失敗しました');
        }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}
</script>

<script>
document.getElementById('profileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>保存中...';

    const form = e.target;
    const textFields = ['phone', 'address', 'emergency_contact', 'email', 'allergy', 'line_name'];
    const data = {};
    textFields.forEach(name => {
        const v = (form[name]?.value || '').trim();
        if (v !== '') data[name] = v;
    });
    const snsChecked = form.querySelector('input[name="sns_allowed"]:checked');
    if (snsChecked) data.sns_allowed = snsChecked.value;

    const alertArea = document.getElementById('alertArea');
    alertArea.innerHTML = '';

    if (Object.keys(data).length === 0) {
        alertArea.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> 変更したい項目を入力してください</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> 変更を保存する';
        return;
    }

    try {
        const res  = await fetch('/api/member/profile', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        });
        const json = await res.json();

        if (json.success) {
            window.location.href = '/member/profile?success=1';
        } else {
            alertArea.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> ${json.error?.message || '更新に失敗しました'}</div>`;
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> 変更を保存する';
        }
    } catch (err) {
        alertArea.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> 通信エラーが発生しました</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> 変更を保存する';
    }
});
</script>
