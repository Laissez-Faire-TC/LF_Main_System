<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName ?? '合宿費用計算アプリ') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="/public/css/style.css" rel="stylesheet">
</head>
<body>
    <?php if (Auth::check()): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/dashboard"><?= htmlspecialchars($appName ?? 'サークル管理') ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/camps">
                            <i class="bi bi-house-gear"></i> 合宿管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/expeditions">
                            <i class="bi bi-geo-alt"></i> 遠征管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/events">
                            <i class="bi bi-calendar-event"></i> 企画管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/members">
                            <i class="bi bi-people"></i> 会員名簿
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/merchandise">
                            <i class="bi bi-bag"></i> 物販管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/hp">
                            <i class="bi bi-globe2"></i> HP管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/enrollment-management">
                            <i class="bi bi-person-plus"></i> 入会管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/academic-years">
                            <i class="bi bi-calendar3"></i> 年度管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/settings">
                            <i class="bi bi-gear"></i> システム設定
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/guide">
                            <i class="bi bi-question-circle"></i> 使い方
                        </a>
                    </li>
                    <?php if (Auth::isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin-users">
                            <i class="bi bi-shield-lock"></i> 幹部・権限
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <button class="btn btn-outline-light btn-sm ms-2" onclick="logout()">
                            <i class="bi bi-box-arrow-right"></i> ログアウト
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <main class="container py-4">
        <?= $content ?>
    </main>

    <footer class="text-center text-muted py-3">
        <small>&copy; <?= date('Y') ?> サークル管理システム</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/public/js/app.js"></script>
</body>
</html>
