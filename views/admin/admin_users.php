<div class="pt-3 mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/dashboard">ダッシュボード</a></li>
            <li class="breadcrumb-item active">幹部・権限管理</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-normal mb-1"><i class="bi bi-shield-lock"></i> 幹部・権限管理</h4>
            <p class="text-muted small mb-0">Googleアカウント単位で幹部ログインを許可し、機能ごとの権限を設定します。</p>
        </div>
        <button class="btn btn-primary" onclick="openAdminUserModal()"><i class="bi bi-person-plus"></i> 幹部を追加</button>
    </div>
</div>

<div id="alertArea"></div>

<div class="alert alert-info small">
    <i class="bi bi-info-circle"></i>
    幹部はここで登録したメールの <strong>Googleアカウントでログイン</strong> します。<br>
    <strong>Admin</strong> は全機能＋この権限管理画面を操作できます。固定Admin（設定ファイル指定）は降格・削除できません。
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>メール / 名前</th>
                    <th style="width:90px;">区分</th>
                    <th>権限</th>
                    <th style="width:90px;">状態</th>
                    <th style="width:120px;">操作</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">まだ幹部が登録されていません。</td></tr>
            <?php else: foreach ($users as $u):
                $permLabels = [];
                foreach ($permDefs as $pd) {
                    if (in_array($pd['key'], $u['permissions'], true)) $permLabels[] = $pd['label'];
                }
            ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($u['email']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($u['name'] ?? '') ?>
                            <?php if (empty($u['google_sub'])): ?><span class="badge bg-warning text-dark ms-1">未ログイン</span><?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php if ($u['is_admin']): ?>
                            <span class="badge bg-danger">Admin</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">幹部</span>
                        <?php endif; ?>
                        <?php if (!empty($u['is_fixed'])): ?><span class="badge bg-dark" title="固定Admin（変更不可）">固定</span><?php endif; ?>
                    </td>
                    <td class="small">
                        <?php if ($u['is_admin']): ?>
                            <span class="text-muted">全機能</span>
                        <?php elseif (empty($permLabels)): ?>
                            <span class="text-muted">なし</span>
                        <?php else: ?>
                            <?= htmlspecialchars(implode('・', $permLabels)) ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int)$u['is_active'] === 1): ?>
                            <span class="badge bg-success">有効</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">無効</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"
                            onclick='editAdminUser(<?= json_encode($u, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>編集</button>
                        <?php if (empty($u['is_fixed'])): ?>
                        <button class="btn btn-sm btn-outline-danger"
                            onclick="deleteAdminUser(<?= (int)$u['id'] ?>, '<?= htmlspecialchars($u['email']) ?>')">削除</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 追加・編集モーダル -->
<div class="modal fade" id="adminUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="adminUserModalTitle">幹部を追加</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="auId">
        <div class="mb-3">
          <label class="form-label">メールアドレス（Googleアカウント） <span class="text-danger">*</span></label>
          <input type="email" class="form-control" id="auEmail" placeholder="例: taro@gmail.com">
          <div class="form-text">このメールのGoogleアカウントでログインできるようになります。</div>
        </div>
        <div class="mb-3">
          <label class="form-label">表示名</label>
          <input type="text" class="form-control" id="auName" placeholder="任意">
        </div>
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" id="auIsAdmin" onchange="toggleAdminPerms()">
          <label class="form-check-label" for="auIsAdmin">Admin（全権・権限管理が可能）</label>
        </div>
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" id="auIsActive" checked>
          <label class="form-check-label" for="auIsActive">有効（ログイン可能）</label>
        </div>
        <div id="permArea">
          <label class="form-label">権限（アクセスできる機能）</label>
          <div class="row g-2">
            <?php foreach ($permDefs as $pd): ?>
            <div class="col-6">
              <div class="form-check">
                <input class="form-check-input perm-check" type="checkbox" value="<?= htmlspecialchars($pd['key']) ?>" id="perm_<?= htmlspecialchars($pd['key']) ?>"
                  <?= $pd['key'] === 'members' ? 'onchange="toggleFieldPerms()"' : '' ?>>
                <label class="form-check-label" for="perm_<?= htmlspecialchars($pd['key']) ?>"><?= htmlspecialchars($pd['label']) ?></label>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <?php if (!empty($fieldDefs)): ?>
          <hr class="my-2">
          <label class="form-label mb-1">会員の個人情報項目（閲覧を許可する項目）</label>
          <div class="form-text mb-2">
            個人情報は<strong>原則非表示</strong>です。「会員名簿」権限があっても、ここでチェックした項目だけが表示されます（住所・電話などを見せたい場合は明示的にチェック）。Adminは常に全項目を閲覧できます。
          </div>
          <div id="fieldPermArea" class="row g-2">
            <?php foreach ($fieldDefs as $fd): ?>
            <div class="col-6">
              <div class="form-check">
                <input class="form-check-input perm-check field-perm-check" type="checkbox" value="<?= htmlspecialchars($fd['key']) ?>" id="perm_<?= htmlspecialchars($fd['key']) ?>">
                <label class="form-check-label" for="perm_<?= htmlspecialchars($fd['key']) ?>"><?= htmlspecialchars($fd['label']) ?></label>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
        <div id="fixedNote" class="alert alert-secondary small mt-2 d-none">
          このアカウントは固定Adminです。区分・状態は変更できません。
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
        <button type="button" class="btn btn-primary" id="auSaveBtn" onclick="saveAdminUser()">保存</button>
      </div>
    </div>
  </div>
</div>

<script>
let adminUserModal;
document.addEventListener('DOMContentLoaded', () => {
    adminUserModal = new bootstrap.Modal(document.getElementById('adminUserModal'));
});

function showAlert(type, msg) {
    document.getElementById('alertArea').innerHTML =
        `<div class="alert alert-${type} alert-dismissible fade show"><i class="bi bi-info-circle"></i> ${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
}

function toggleAdminPerms() {
    const isAdmin = document.getElementById('auIsAdmin').checked;
    document.getElementById('permArea').style.opacity = isAdmin ? '0.4' : '1';
    document.querySelectorAll('.perm-check').forEach(c => c.disabled = isAdmin);
    toggleFieldPerms();
}

// 個人情報項目は「明示許可制」。Admin（全権）のときだけ全項目自動表示になるので無効化。
// members 権限を持っていても、項目は個別に許可が必要（チェック可能なまま）。
function toggleFieldPerms() {
    const isAdmin = document.getElementById('auIsAdmin').checked;
    const area = document.getElementById('fieldPermArea');
    if (!area) return;
    area.style.opacity = isAdmin ? '0.4' : '1';
    document.querySelectorAll('.field-perm-check').forEach(c => {
        c.disabled = isAdmin; // Admin は常に全項目可なので個別指定は不要
    });
}

function openAdminUserModal() {
    document.getElementById('adminUserModalTitle').textContent = '幹部を追加';
    document.getElementById('auId').value = '';
    document.getElementById('auEmail').value = '';
    document.getElementById('auEmail').disabled = false;
    document.getElementById('auName').value = '';
    document.getElementById('auIsAdmin').checked = false;
    document.getElementById('auIsAdmin').disabled = false;
    document.getElementById('auIsActive').checked = true;
    document.getElementById('auIsActive').disabled = false;
    document.querySelectorAll('.perm-check').forEach(c => c.checked = false);
    document.getElementById('fixedNote').classList.add('d-none');
    toggleAdminPerms();
    adminUserModal.show();
}

function editAdminUser(u) {
    document.getElementById('adminUserModalTitle').textContent = '幹部を編集';
    document.getElementById('auId').value = u.id;
    document.getElementById('auEmail').value = u.email;
    document.getElementById('auEmail').disabled = true; // メールは変更不可
    document.getElementById('auName').value = u.name || '';
    document.getElementById('auIsAdmin').checked = !!(u.is_admin == 1 || u.is_admin === true);
    document.getElementById('auIsActive').checked = !!(u.is_active == 1 || u.is_active === true);
    document.querySelectorAll('.perm-check').forEach(c => {
        c.checked = (u.permissions || []).includes(c.value);
    });
    const fixed = !!u.is_fixed;
    document.getElementById('auIsAdmin').disabled = fixed;
    document.getElementById('auIsActive').disabled = fixed;
    document.getElementById('fixedNote').classList.toggle('d-none', !fixed);
    toggleAdminPerms();
    adminUserModal.show();
}

async function saveAdminUser() {
    const id = document.getElementById('auId').value;
    const payload = {
        email: document.getElementById('auEmail').value.trim(),
        name: document.getElementById('auName').value.trim(),
        is_admin: document.getElementById('auIsAdmin').checked,
        is_active: document.getElementById('auIsActive').checked,
        permissions: Array.from(document.querySelectorAll('.perm-check:checked')).map(c => c.value),
    };
    const btn = document.getElementById('auSaveBtn');
    btn.disabled = true;
    try {
        const url = id ? `/index.php?route=api/admin-users/${id}` : '/index.php?route=api/admin-users';
        const method = id ? 'PUT' : 'POST';
        const res = await fetch(url, { method, headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const json = await res.json();
        if (json.success) {
            location.reload();
        } else {
            showAlert('danger', json.error?.message || '保存に失敗しました');
            btn.disabled = false;
        }
    } catch (e) {
        showAlert('danger', '通信エラーが発生しました');
        btn.disabled = false;
    }
}

async function deleteAdminUser(id, email) {
    if (!confirm(email + ' を削除しますか？\nこの幹部はログインできなくなります。')) return;
    try {
        const res = await fetch(`/index.php?route=api/admin-users/${id}`, { method: 'DELETE' });
        const json = await res.json();
        if (json.success) location.reload();
        else showAlert('danger', json.error?.message || '削除に失敗しました');
    } catch (e) {
        showAlert('danger', '通信エラーが発生しました');
    }
}
</script>
