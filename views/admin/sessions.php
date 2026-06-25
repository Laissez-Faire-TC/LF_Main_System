<?php
/**
 * 幹部ログインセッション（Admin限定）
 * 渡される変数: $sessions, $total, $actors, $online, $filters, $page, $totalPages, $perPage, $nameMap, $onlineWindow
 */

/** 秒数を「2時間15分」「3分」「45秒」のように整形 */
$fmtDuration = function (int $sec): string {
    if ($sec < 0) $sec = 0;
    $h = intdiv($sec, 3600);
    $m = intdiv($sec % 3600, 60);
    $s = $sec % 60;
    if ($h > 0) return $h . '時間' . ($m > 0 ? $m . '分' : '');
    if ($m > 0) return $m . '分';
    return $s . '秒';
};

/** オンライン判定: 継続中（ended_at なし）かつ直近 $onlineWindow 秒以内にアクセス */
$isOnline = function (array $s) use ($onlineWindow): bool {
    if (!empty($s['ended_at'])) return false;
    $last = strtotime($s['last_seen_at']);
    return ($last !== false) && (time() - $last <= $onlineWindow);
};
?>
<div class="pt-3 mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/dashboard">ダッシュボード</a></li>
            <li class="breadcrumb-item active">ログイン履歴</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <div>
            <h4 class="fw-normal mb-1"><i class="bi bi-person-badge"></i> 幹部のログイン履歴</h4>
            <p class="text-muted small mb-0">ログイン・ログアウト・滞在時間・現在のオンライン状況を確認できます。Adminのみ閲覧可能です。</p>
        </div>
        <span class="ms-auto badge bg-success-subtle text-success-emphasis fs-6">
            <i class="bi bi-circle-fill" style="font-size:.5rem;vertical-align:middle"></i>
            現在オンライン <?= count($online) ?> 名
        </span>
    </div>
</div>

<?php if (!empty($online)): ?>
<div class="card card-body mb-3">
    <div class="small text-muted mb-2">現在オンラインの幹部（直近<?= (int)floor($onlineWindow / 60) ?>分以内にアクセス）</div>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($online as $o):
            $aid  = (int)($o['admin_user_id'] ?? 0);
            $name = $o['admin_name'] ?: ($nameMap[$aid] ?? '') ?: ($o['admin_email'] ?? '（共有パスワード）');
        ?>
        <span class="badge bg-light text-dark border">
            <i class="bi bi-circle-fill text-success" style="font-size:.5rem;vertical-align:middle"></i>
            <?= htmlspecialchars($name) ?>
        </span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- 絞り込み -->
<form method="get" action="/admin-sessions" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small mb-1">操作者</label>
            <select name="admin_user_id" class="form-select form-select-sm">
                <option value="">すべて</option>
                <?php foreach ($actors as $a):
                    $aid  = (int)$a['admin_user_id'];
                    $name = $a['admin_name'] ?: ($nameMap[$aid] ?? '');
                    $mail = $a['admin_email'] ?: '';
                    if ($name !== '') {
                        $label = $mail !== '' ? $name . '（' . $mail . '）' : $name;
                    } else {
                        $label = $mail !== '' ? $mail : '（共有パスワード）';
                    }
                ?>
                <option value="<?= (int)$a['admin_user_id'] ?>" <?= (string)($filters['admin_user_id'] ?? '') === (string)$a['admin_user_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">ログイン方式</label>
            <select name="login_type" class="form-select form-select-sm">
                <option value="">すべて</option>
                <option value="google"   <?= ($filters['login_type'] ?? '') === 'google'   ? 'selected' : '' ?>>Google</option>
                <option value="password" <?= ($filters['login_type'] ?? '') === 'password' ? 'selected' : '' ?>>共有パスワード</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">期間（開始）</label>
            <input type="date" name="from" value="<?= htmlspecialchars($filters['from'] ?? '') ?>" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">期間（終了）</label>
            <input type="date" name="to" value="<?= htmlspecialchars($filters['to'] ?? '') ?>" class="form-control form-control-sm">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">&nbsp;</label>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="online" value="1" id="onlineOnly" <?= !empty($filters['online']) ? 'checked' : '' ?>>
                <label class="form-check-label small" for="onlineOnly">継続中のみ表示</label>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> 絞り込む</button>
        <a href="/admin-sessions" class="btn btn-outline-secondary btn-sm">リセット</a>
        <span class="ms-auto align-self-center text-muted small"><?= number_format($total) ?> 件</span>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:180px;">操作者</th>
                    <th style="width:90px;">方式</th>
                    <th style="width:150px;">ログイン</th>
                    <th style="width:150px;">ログアウト</th>
                    <th style="width:110px;">滞在時間</th>
                    <th>状態 / IP</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($sessions)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">該当するログイン履歴がありません。</td></tr>
            <?php else: foreach ($sessions as $s):
                $aid       = (int)($s['admin_user_id'] ?? 0);
                $actorName = $s['admin_name'] ?: ($nameMap[$aid] ?? '');
                $actorMail = $s['admin_email'] ?: '';
                $type      = $s['login_type'] ?? '';
                $online    = $isOnline($s);

                $startTs = strtotime($s['started_at']);
                $endTs   = !empty($s['ended_at']) ? strtotime($s['ended_at']) : strtotime($s['last_seen_at']);
                $duration = ($startTs !== false && $endTs !== false) ? max(0, $endTs - $startTs) : 0;

                $reasonLabel = ['logout' => 'ログアウト', 'timeout' => '期限切れ'][$s['end_reason'] ?? ''] ?? '';
            ?>
                <tr>
                    <td class="small">
                        <?php if ($actorName !== ''): ?>
                            <span class="fw-semibold"><?= htmlspecialchars($actorName) ?></span>
                            <?php if ($actorMail !== ''): ?>
                                <br><span class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($actorMail) ?></span>
                            <?php endif; ?>
                        <?php elseif ($actorMail !== ''): ?>
                            <span class="fw-semibold"><?= htmlspecialchars($actorMail) ?></span>
                        <?php else: ?>
                            <span class="text-muted">（共有パスワード）</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($type === 'google'): ?>
                            <span class="badge bg-primary-subtle text-primary-emphasis">Google</span>
                        <?php elseif ($type === 'password'): ?>
                            <span class="badge bg-secondary-subtle text-secondary-emphasis">パスワード</span>
                        <?php else: ?>
                            <span class="text-muted small"><?= htmlspecialchars($type) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-nowrap small"><?= htmlspecialchars(date('Y/n/j H:i', (int)$startTs)) ?></td>
                    <td class="text-nowrap small">
                        <?php if (!empty($s['ended_at'])): ?>
                            <?= htmlspecialchars(date('Y/n/j H:i', (int)$endTs)) ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= htmlspecialchars($fmtDuration($duration)) ?></td>
                    <td class="small">
                        <?php if ($online): ?>
                            <span class="badge bg-success"><i class="bi bi-circle-fill" style="font-size:.5rem;vertical-align:middle"></i> オンライン</span>
                        <?php elseif (empty($s['ended_at'])): ?>
                            <span class="badge bg-secondary-subtle text-secondary-emphasis">離席中</span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($reasonLabel) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($s['ip_address'])): ?>
                            <span class="text-muted ms-1" style="font-size:.75rem;"><?= htmlspecialchars($s['ip_address']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1):
    $qs = $_GET; unset($qs['page']);
    $base = '/admin-sessions?' . http_build_query($qs);
    $sep  = ($qs ? '&' : '');
?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center mb-0">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= htmlspecialchars($base . $sep . 'page=' . ($page - 1)) ?>">前へ</a>
        </li>
        <li class="page-item disabled"><span class="page-link"><?= $page ?> / <?= $totalPages ?></span></li>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= htmlspecialchars($base . $sep . 'page=' . ($page + 1)) ?>">次へ</a>
        </li>
    </ul>
</nav>
<?php endif; ?>
