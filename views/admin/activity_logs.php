<?php
/**
 * 幹部活動ログ（Admin限定）
 * 渡される変数: $logs, $total, $actors, $filters, $page, $totalPages, $perPage
 */
$config   = require CONFIG_PATH . '/admin.php';
$features = $config['permissions'] ?? [];
?>
<div class="pt-3 mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/dashboard">ダッシュボード</a></li>
            <li class="breadcrumb-item active">活動ログ</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-normal mb-1"><i class="bi bi-clock-history"></i> 幹部の活動ログ</h4>
        <p class="text-muted small mb-0">幹部システムでの操作履歴（作成・更新・削除）を確認できます。Adminのみ閲覧可能です。</p>
    </div>
</div>

<!-- 絞り込み -->
<form method="get" action="/admin-activity-logs" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small mb-1">機能</label>
            <select name="feature" class="form-select form-select-sm">
                <option value="">すべて</option>
                <?php foreach ($features as $f): ?>
                <option value="<?= htmlspecialchars($f['key']) ?>" <?= ($filters['feature'] ?? '') === $f['key'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($f['label']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
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
            <label class="form-label small mb-1">期間（開始）</label>
            <input type="date" name="from" value="<?= htmlspecialchars($filters['from'] ?? '') ?>" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">期間（終了）</label>
            <input type="date" name="to" value="<?= htmlspecialchars($filters['to'] ?? '') ?>" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">キーワード</label>
            <input type="text" name="keyword" value="<?= htmlspecialchars($filters['keyword'] ?? '') ?>" placeholder="メール・操作内容" class="form-control form-control-sm">
        </div>
    </div>
    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> 絞り込む</button>
        <a href="/admin-activity-logs" class="btn btn-outline-secondary btn-sm">リセット</a>
        <span class="ms-auto align-self-center text-muted small"><?= number_format($total) ?> 件</span>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:150px;">日時</th>
                    <th style="width:160px;">操作者</th>
                    <th style="width:110px;">機能</th>
                    <th style="width:70px;">操作</th>
                    <th>操作内容 / 変更差分</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">該当する活動ログがありません。</td></tr>
            <?php else: foreach ($logs as $log):
                $dt        = date('Y/n/j H:i:s', strtotime($log['created_at']));
                // 操作者: 記録時の名前 → 無ければ現在の幹部マスタから補完 → それも無ければメール
                $aid       = isset($log['admin_user_id']) ? (int)$log['admin_user_id'] : 0;
                $actorName = $log['admin_name'] ?: ($nameMap[$aid] ?? '');
                $actorMail = $log['admin_email'] ?: '';
                $method    = strtoupper($log['method'] ?? '');
                $badgeCls  = $method === 'DELETE' ? 'bg-danger'
                           : ($method === 'PUT' ? 'bg-warning text-dark' : 'bg-success');
                $changes   = !empty($log['changes_json']) ? json_decode($log['changes_json'], true) : null;
                $rowId     = 'chg-' . (int)$log['id'];
            ?>
                <tr>
                    <td class="text-nowrap small"><?= htmlspecialchars($dt) ?></td>
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
                    <td><span class="badge bg-secondary-subtle text-secondary-emphasis"><?= htmlspecialchars(AdminActivityLog::featureLabel($log['feature'])) ?></span></td>
                    <td><span class="badge <?= $badgeCls ?>"><?= htmlspecialchars(AdminActivityLog::methodLabel($method)) ?></span></td>
                    <td class="small">
                        <div class="d-flex align-items-center gap-2">
                            <span><?= htmlspecialchars($log['action_label'] ?? '') ?></span>
                            <?php if ($changes): ?>
                            <button class="btn btn-link btn-sm p-0 text-decoration-none" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#<?= $rowId ?>">
                                <i class="bi bi-chevron-down"></i> 差分
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php if ($changes): ?>
                        <div class="collapse mt-2" id="<?= $rowId ?>">
                            <?php
                            // 権限の一括変更（付与/剥奪をまとめて1行記録）
                            if (isset($changes['granted']) || isset($changes['revoked'])):
                                $granted = $changes['granted'] ?? [];
                                $revoked = $changes['revoked'] ?? [];
                                $target  = $changes['target'] ?? '';
                                if ($target !== ''): ?>
                                <div><span class="text-muted">対象者：</span>
                                    <span class="fw-semibold"><?= htmlspecialchars((string)$target) ?></span></div>
                                <?php endif;
                                if ($granted): ?>
                                <div><span class="text-muted">付与：</span>
                                    <?php foreach ($granted as $g): ?>
                                        <span class="badge bg-success-subtle text-success-emphasis me-1"><?= htmlspecialchars((string)$g) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif;
                                if ($revoked): ?>
                                <div><span class="text-muted">剥奪：</span>
                                    <?php foreach ($revoked as $r): ?>
                                        <span class="badge bg-danger-subtle text-danger-emphasis me-1"><?= htmlspecialchars((string)$r) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif;
                            // DELETE: 削除内容、UPDATE: before/after、INSERT: 挿入値
                            elseif (isset($changes['_deleted'])):
                                foreach ($changes['_deleted'] as $row): ?>
                                <div class="border rounded p-2 mb-1 bg-light">
                                    <?php foreach ($row as $k => $v):
                                        if (AdminActivityLog::isHiddenColumn((string)$k)) continue; ?>
                                    <div><span class="text-muted"><?= htmlspecialchars(AdminActivityLog::columnLabel((string)$k)) ?>：</span>
                                        <span class="text-danger"><?= htmlspecialchars(AdminActivityLog::formatValue((string)$k, $v)) ?></span></div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endforeach;
                            else:
                                $shown = 0;
                                foreach ($changes as $col => $diff):
                                    if ($col === '_rows') {
                                        echo '<div class="text-muted">他 ' . ((int)$diff - 1) . ' 件も同時更新</div>';
                                        continue;
                                    }
                                    if ($col === '_count') {
                                        echo '<div class="text-muted">同一操作を ' . (int)$diff . ' 件まとめて記録</div>';
                                        $shown++;
                                        continue;
                                    }
                                    if (AdminActivityLog::isHiddenColumn((string)$col)) continue;
                                    $shown++;
                                    if (is_array($diff) && array_key_exists('_json', $diff)):
                                        // JSONカラム（しおり items 等）の中で、変わったフィールドだけ表示
                                    ?>
                                        <div class="mb-1"><span class="text-muted"><?= htmlspecialchars(AdminActivityLog::columnLabel((string)$col)) ?>の変更：</span></div>
                                        <div class="ms-3">
                                        <?php foreach ($diff['_json'] as $f => $fd):
                                            // JSON内フィールドの値を人間向けに整形する。
                                            // 配列（持ち物・部屋割り・スケジュール等）は生JSONを出さず、
                                            // 各要素から代表的なテキストを抜き出して読点で連結する。
                                            $fmt = function ($v) {
                                                if ($v === null || $v === '') return '（空）';
                                                if (is_scalar($v)) return (string)$v;
                                                if ($v === []) return '（なし）';
                                                if (is_array($v)) {
                                                    // 要素から表示名になりそうなキーを順に探す
                                                    $pick = function ($el) {
                                                        if (is_scalar($el)) return (string)$el;
                                                        if (!is_array($el)) return '';
                                                        foreach (['text','name','title','room_no','category','time','label','content'] as $k) {
                                                            if (isset($el[$k]) && $el[$k] !== '') return (string)$el[$k];
                                                        }
                                                        // 部屋割りのような入れ子は category だけ拾う
                                                        return '';
                                                    };
                                                    $parts = array_values(array_filter(array_map($pick, $v), fn($s) => $s !== ''));
                                                    // 中身が空の要素ばかりなら実質「なし」
                                                    if (!$parts) return '（なし）';
                                                    return implode('、', $parts);
                                                }
                                                return (string)$v;
                                            };
                                        ?>
                                            <div>
                                                <span class="text-muted"><?= htmlspecialchars(AdminActivityLog::columnLabel((string)$f)) ?>：</span>
                                                <span class="text-danger text-decoration-line-through"><?= htmlspecialchars($fmt($fd['before'] ?? null)) ?></span>
                                                <i class="bi bi-arrow-right mx-1"></i>
                                                <span class="text-success"><?= htmlspecialchars($fmt($fd['after'] ?? null)) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                        </div>
                                    <?php elseif (is_array($diff) && (array_key_exists('before', $diff) || array_key_exists('after', $diff))): ?>
                                        <div>
                                            <span class="text-muted"><?= htmlspecialchars(AdminActivityLog::columnLabel((string)$col)) ?>：</span>
                                            <?php if (array_key_exists('before', $diff)): ?>
                                                <span class="text-danger text-decoration-line-through"><?= htmlspecialchars(AdminActivityLog::formatValue((string)$col, $diff['before'])) ?></span>
                                                <i class="bi bi-arrow-right mx-1"></i>
                                            <?php endif; ?>
                                            <span class="text-success"><?= htmlspecialchars(AdminActivityLog::formatValue((string)$col, $diff['after'] ?? null)) ?></span>
                                        </div>
                                    <?php elseif (is_array($diff)):
                                        // 文字列の配列（例: 追加/削除したスロット名・権限名）はバッジで列挙
                                        $isList = $diff === array_values($diff);
                                        // 追加は緑・削除は赤、その他は中立のバッジ色
                                        $badge = ((string)$col === '追加') ? 'bg-success-subtle text-success-emphasis'
                                               : (((string)$col === '削除') ? 'bg-danger-subtle text-danger-emphasis'
                                               : 'bg-light text-dark border');
                                    ?>
                                        <div>
                                            <span class="text-muted"><?= htmlspecialchars(AdminActivityLog::columnLabel((string)$col)) ?>：</span>
                                            <?php if ($isList): foreach ($diff as $v): ?>
                                                <span class="badge <?= $badge ?> me-1"><?= htmlspecialchars(is_scalar($v) ? (string)$v : json_encode($v, JSON_UNESCAPED_UNICODE)) ?></span>
                                            <?php endforeach; else: ?>
                                                <span class="text-success"><?= htmlspecialchars(json_encode($diff, JSON_UNESCAPED_UNICODE)) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: // INSERT（カラム=値・スカラ） ?>
                                        <div>
                                            <span class="text-muted"><?= htmlspecialchars(AdminActivityLog::columnLabel((string)$col)) ?>：</span>
                                            <span class="text-success"><?= htmlspecialchars(AdminActivityLog::formatValue((string)$col, $diff)) ?></span>
                                        </div>
                                    <?php endif;
                                endforeach;
                                if ($shown === 0): ?>
                                    <div class="text-muted">表示する変更項目はありません（内部項目のみ）。</div>
                                <?php endif;
                            endif; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1):
    // 現在のクエリ（page以外）を維持する
    $qs = $_GET; unset($qs['page']);
    $base = '/admin-activity-logs?' . http_build_query($qs);
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
