<?php
/**
 * 会員ペナルティ点（Admin限定）
 * 渡される変数: $rows（summaryAll の結果）, $penaltyConfig（config/penalty.php）
 */
$pts = $penaltyConfig['points_per_day'] ?? [];
?>
<div class="pt-3 mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/dashboard">ダッシュボード</a></li>
            <li class="breadcrumb-item active">ペナルティ点</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-normal mb-1"><i class="bi bi-exclamation-triangle"></i> 会員のペナルティ点</h4>
        <p class="text-muted small mb-0">
            入金期限の遅れ（合宿費・遠征費・物販）を自動集計し、点数が多いほど未納が多いことを表します。Adminのみ閲覧・操作できます。
        </p>
    </div>
</div>

<!-- ルール表示 -->
<div class="alert alert-light border small d-flex flex-wrap gap-3 align-items-center">
    <span class="text-muted">加点ルール（期限超過かつ未入金の集金1件につき、遅延1日あたり）:</span>
    <span class="badge bg-secondary-subtle text-secondary-emphasis">合宿費 +<?= (int)($pts['camp'] ?? 0) ?>点/日</span>
    <span class="badge bg-secondary-subtle text-secondary-emphasis">遠征費 +<?= (int)($pts['expedition'] ?? 0) ?>点/日</span>
    <span class="badge bg-secondary-subtle text-secondary-emphasis">物販 +<?= (int)($pts['merchandise'] ?? 0) ?>点/日</span>
    <span class="text-muted ms-auto">未入金分は遅延日数で再集計／入金確認時に遅延を確定し以降も維持／手動分は加点・減点を記録</span>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>会員名</th>
                    <th style="width:120px;">学籍番号</th>
                    <th class="text-center" style="width:90px;">合宿<br><span class="fw-normal text-muted" style="font-size:.7rem;">未入金遅延</span></th>
                    <th class="text-center" style="width:90px;">遠征<br><span class="fw-normal text-muted" style="font-size:.7rem;">未入金遅延</span></th>
                    <th class="text-center" style="width:90px;">物販<br><span class="fw-normal text-muted" style="font-size:.7rem;">未入金遅延</span></th>
                    <th class="text-center" style="width:90px;">手動</th>
                    <th class="text-center" style="width:110px;">合計点</th>
                    <th style="width:90px;"></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">会員が登録されていません。</td></tr>
            <?php else: foreach ($rows as $r):
                $total = (int)$r['total_points'];
                $rowCls = $total >= 20 ? 'table-danger' : ($total >= 10 ? 'table-warning' : '');
            ?>
                <tr class="<?= $rowCls ?>">
                    <td class="fw-semibold"><?= htmlspecialchars($r['name']) ?></td>
                    <td class="small text-muted"><?= htmlspecialchars((string)($r['student_id'] ?? '')) ?></td>
                    <td class="text-center small"><?= $r['camp_overdue'] > 0 ? (int)$r['camp_overdue'] . '日' : '<span class="text-muted">–</span>' ?></td>
                    <td class="text-center small"><?= $r['expedition_overdue'] > 0 ? (int)$r['expedition_overdue'] . '日' : '<span class="text-muted">–</span>' ?></td>
                    <td class="text-center small"><?= $r['merchandise_overdue'] > 0 ? (int)$r['merchandise_overdue'] . '日' : '<span class="text-muted">–</span>' ?></td>
                    <td class="text-center small">
                        <?php if ((int)$r['manual_points'] !== 0): ?>
                            <span class="<?= (int)$r['manual_points'] > 0 ? 'text-danger' : 'text-success' ?>">
                                <?= ((int)$r['manual_points'] > 0 ? '+' : '') . (int)$r['manual_points'] ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">–</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="badge <?= $total >= 20 ? 'bg-danger' : ($total >= 10 ? 'bg-warning text-dark' : ($total > 0 ? 'bg-secondary' : 'bg-light text-muted border')) ?>">
                            <?= $total ?> 点
                        </span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="openPenaltyDetail(<?= (int)$r['member_id'] ?>, '<?= htmlspecialchars(addslashes($r['name']), ENT_QUOTES) ?>')">
                            詳細
                        </button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 詳細モーダル -->
<div class="modal fade" id="penaltyModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> <span id="pmName"></span> のペナルティ内訳</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="pmLoading" class="text-center text-muted py-4">読み込み中…</div>
        <div id="pmBody" class="d-none">
          <h6 class="text-muted small">未入金の遅延（自動集計・現在進行中）</h6>
          <div id="pmOverdue" class="mb-4"></div>

          <h6 class="text-muted small">入金確認済みで確定した遅延（永続）</h6>
          <div id="pmConfirmed" class="mb-4"></div>

          <h6 class="text-muted small">手動調整</h6>
          <form id="pmAdjustForm" class="row g-2 align-items-end mb-3" onsubmit="return submitAdjust(event)">
            <div class="col-4">
              <label class="form-label small mb-1">点数（加点は正・取消は負）</label>
              <input type="number" id="pmPoints" class="form-control form-control-sm" placeholder="例: 5 / -5" required>
            </div>
            <div class="col">
              <label class="form-label small mb-1">理由</label>
              <input type="text" id="pmReason" class="form-control form-control-sm" placeholder="調整理由" required>
            </div>
            <div class="col-auto">
              <button type="submit" class="btn btn-primary btn-sm">記録</button>
            </div>
          </form>
          <div id="pmAdjustments"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
var pmMemberId = null;
var pmModalInstance = null;

// Bootstrap 読み込み後に初めて生成する（読み込み前に new すると ReferenceError でスクリプトが止まるため）
function getPmModal() {
    if (!pmModalInstance) {
        pmModalInstance = bootstrap.Modal.getOrCreateInstance(document.getElementById('penaltyModal'));
    }
    return pmModalInstance;
}

function openPenaltyDetail(memberId, name) {
    pmMemberId = memberId;
    document.getElementById('pmName').textContent = name;
    document.getElementById('pmBody').classList.add('d-none');
    document.getElementById('pmLoading').classList.remove('d-none');
    document.getElementById('pmPoints').value = '';
    document.getElementById('pmReason').value = '';
    getPmModal().show();
    loadPenaltyDetail();
}

async function loadPenaltyDetail() {
    const res = await fetch('/api/member-penalties/' + pmMemberId);
    const json = await res.json();
    if (!json.success) { alert(json.message || '取得に失敗しました'); return; }

    const d = json.data.detail;
    renderOverdue(d.overdue_items);
    renderConfirmed(d.confirmed_items || []);
    renderAdjustments(d.adjustments);

    document.getElementById('pmLoading').classList.add('d-none');
    document.getElementById('pmBody').classList.remove('d-none');
}

function renderOverdue(items) {
    const labels = { camp: '合宿費', expedition: '遠征費', merchandise: '物販' };
    let html = '';
    let any = false;
    for (const kind of ['camp', 'expedition', 'merchandise']) {
        const list = items[kind] || [];
        if (list.length === 0) continue;
        any = true;
        html += '<div class="mb-2"><span class="badge bg-secondary-subtle text-secondary-emphasis">' + labels[kind] + '</span>';
        html += '<ul class="list-unstyled small mb-0 mt-1">';
        for (const it of list) {
            const days = parseInt(it.days_late || 0);
            html += '<li class="d-flex justify-content-between border-bottom py-1">'
                  + '<span>' + escapeHtml(it.title || '') + '</span>'
                  + '<span class="text-muted">期限 ' + escapeHtml(it.deadline || '')
                  + '／<span class="text-danger">' + days + '日遅延</span>'
                  + (it.amount != null ? '／' + Number(it.amount).toLocaleString() + '円' : '') + '</span></li>';
        }
        html += '</ul></div>';
    }
    document.getElementById('pmOverdue').innerHTML = any
        ? html
        : '<p class="text-muted small mb-0">現在、未入金の遅延はありません。</p>';
}

// 入金確認済みで確定した遅延（永続化分）
function renderConfirmed(items) {
    const labels = { camp: '合宿費', expedition: '遠征費', merchandise: '物販' };
    if (!items || items.length === 0) {
        document.getElementById('pmConfirmed').innerHTML =
            '<p class="text-muted small mb-0">確定した遅延はありません。</p>';
        return;
    }
    let html = '<ul class="list-unstyled small mb-0">';
    for (const it of items) {
        html += '<li class="d-flex justify-content-between border-bottom py-1">'
              + '<span><span class="badge bg-secondary-subtle text-secondary-emphasis me-1">' + (labels[it.kind] || it.kind) + '</span>'
              + escapeHtml(it.title || '') + '</span>'
              + '<span class="text-muted">期限 ' + escapeHtml(it.deadline || '')
              + '／' + parseInt(it.days_late) + '日遅延 '
              + '<span class="text-danger fw-semibold">+' + parseInt(it.points) + '点</span></span></li>';
    }
    html += '</ul>';
    document.getElementById('pmConfirmed').innerHTML = html;
}

function renderAdjustments(adjustments) {
    if (!adjustments || adjustments.length === 0) {
        document.getElementById('pmAdjustments').innerHTML = '<p class="text-muted small mb-0">手動調整の記録はありません。</p>';
        return;
    }
    let html = '<table class="table table-sm small mb-0"><thead><tr><th>日時</th><th>点数</th><th>理由</th><th>操作者</th></tr></thead><tbody>';
    for (const a of adjustments) {
        const p = Number(a.points);
        const cls = p > 0 ? 'text-danger' : 'text-success';
        html += '<tr><td class="text-nowrap">' + escapeHtml(a.created_at || '') + '</td>'
              + '<td class="' + cls + '">' + (p > 0 ? '+' : '') + p + '</td>'
              + '<td>' + escapeHtml(a.reason || '') + '</td>'
              + '<td class="text-muted">' + escapeHtml(a.admin_name || '') + '</td></tr>';
    }
    html += '</tbody></table>';
    document.getElementById('pmAdjustments').innerHTML = html;
}

async function submitAdjust(e) {
    e.preventDefault();
    const points = parseInt(document.getElementById('pmPoints').value, 10);
    const reason = document.getElementById('pmReason').value.trim();
    if (!points || !reason) { alert('点数と理由を入力してください'); return false; }

    const res = await fetch('/api/member-penalties/' + pmMemberId + '/adjust', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ points, reason })
    });
    const json = await res.json();
    if (!json.success) { alert(json.message || '調整に失敗しました'); return false; }

    document.getElementById('pmPoints').value = '';
    document.getElementById('pmReason').value = '';
    loadPenaltyDetail();
    return false;
}

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
}
</script>
