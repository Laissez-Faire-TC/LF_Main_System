<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<style>
.ql-container { font-size: 0.9rem; border-radius: 0 0 .375rem .375rem; }
.ql-toolbar { border-radius: .375rem .375rem 0 0; background: #f8f9fa; }
.ql-editor { min-height: 120px; }
.schedule-img-thumb {
    width: 60px; height: 45px; object-fit: cover;
    border-radius: .25rem; border: 1px solid #dee2e6;
    flex-shrink: 0; cursor: pointer;
}
.news-img-preview {
    max-width: 100%; max-height: 200px; border-radius: .375rem;
    border: 1px solid #dee2e6; display: none; margin-top: .5rem;
}
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-globe2"></i> HP管理</h1>
        <a href="/" target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-box-arrow-up-right"></i> 公開HPを確認
        </a>
    </div>

    <!-- タブ -->
    <ul class="nav nav-tabs mb-3" id="hpTabs">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabAbout">About</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabNews">News</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabSchedule">Schedule</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabContact">Contact</button></li>
    </ul>

    <div class="tab-content">

        <!-- ========== About タブ ========== -->
        <div class="tab-pane fade show active" id="tabAbout">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>About セクション</strong>
                    <button class="btn btn-primary btn-sm" onclick="saveAbout()">保存</button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">メイン説明文</label>
                        <div id="aboutDescriptionEditor"></div>
                    </div>

                    <hr>
                    <div class="mb-3">
                        <label class="form-label fw-bold">基本情報テーブル</label>
                        <div id="aboutInfoRows">
                            <?php
                            $aboutInfo = json_decode($settings['about_info'] ?? '[]', true) ?: [];
                            foreach ($aboutInfo as $i => $row): ?>
                            <div class="row g-2 mb-2 about-info-row">
                                <div class="col-4">
                                    <input type="text" class="form-control" placeholder="項目名" value="<?= htmlspecialchars($row['th']) ?>">
                                </div>
                                <div class="col-7">
                                    <input type="text" class="form-control" placeholder="内容" value="<?= htmlspecialchars($row['td']) ?>">
                                </div>
                                <div class="col-1">
                                    <button class="btn btn-outline-danger btn-sm w-100" onclick="removeRow(this)">✕</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm mt-1" onclick="addInfoRow()">+ 行を追加</button>
                    </div>

                    <hr>
                    <div class="mb-3">
                        <label class="form-label fw-bold">実績 (Achievements)</label>
                        <div id="achievementRows">
                            <?php
                            $achievements = json_decode($settings['about_achievements'] ?? '[]', true) ?: [];
                            foreach ($achievements as $ach): ?>
                            <div class="row g-2 mb-2 achievement-row">
                                <div class="col-5">
                                    <input type="text" class="form-control" placeholder="大会名" value="<?= htmlspecialchars($ach['event']) ?>">
                                </div>
                                <div class="col-6">
                                    <input type="text" class="form-control" placeholder="結果" value="<?= htmlspecialchars($ach['result']) ?>">
                                </div>
                                <div class="col-1">
                                    <button class="btn btn-outline-danger btn-sm w-100" onclick="removeRow(this)">✕</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm mt-1" onclick="addAchievementRow()">+ 行を追加</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== News タブ ========== -->
        <div class="tab-pane fade" id="tabNews">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>ニュースカード一覧</strong>
                    <button class="btn btn-primary btn-sm" onclick="openNewsModal(null)">+ 追加</button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>日付</th>
                                <th>タイトル</th>
                                <th>アンカーID</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="newsTableBody">
                            <?php foreach ($news as $n): ?>
                            <tr data-id="<?= $n['id'] ?>">
                                <td><?= htmlspecialchars($n['news_date']) ?></td>
                                <td><?= htmlspecialchars($n['title']) ?></td>
                                <td><code><?= htmlspecialchars($n['anchor_id'] ?? '') ?></code></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="openNewsModal(<?= $n['id'] ?>)">編集</button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteNews(<?= $n['id'] ?>)">削除</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========== Schedule タブ ========== -->
        <div class="tab-pane fade" id="tabSchedule">
            <div class="row g-3">
                <!-- 月一覧 -->
                <div class="col-md-3">
                    <div class="list-group" id="scheduleList">
                        <?php foreach ($schedule as $s): ?>
                        <button class="list-group-item list-group-item-action <?= $s['month_key'] === 'april' ? 'active' : '' ?>"
                                onclick="selectScheduleMonth(<?= $s['id'] ?>, this)"
                                data-id="<?= $s['id'] ?>">
                            <span class="fw-bold"><?= htmlspecialchars($s['month_label']) ?></span>
                            <small class="d-block text-truncate"><?= htmlspecialchars($s['title']) ?></small>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 編集エリア -->
                <div class="col-md-9">
                    <div class="card" id="scheduleEditor" style="display:none;">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong id="scheduleEditorTitle"></strong>
                            <button class="btn btn-primary btn-sm" onclick="saveSchedule()">保存</button>
                        </div>
                        <div class="card-body">
                            <input type="hidden" id="scheduleId">

                            <div class="mb-3">
                                <label class="form-label fw-bold">月の表示</label>
                                <input type="text" class="form-control" id="scheduleMonthLabel" placeholder="例：4月">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">タイトル</label>
                                <input type="text" class="form-control" id="scheduleTitle">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">本文</label>
                                <div id="scheduleTextEditor"></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">新入生Q&amp;A <small class="text-muted fw-normal">（4月のみ使用）</small></label>
                                <div id="scheduleExtraEditor"></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">画像</label>
                                <div id="scheduleImageList" class="mb-2"></div>
                                <div class="d-flex gap-2 align-items-center mt-2">
                                    <input type="text" class="form-control form-control-sm" id="scheduleImageInput" placeholder="パスを直接入力して追加">
                                    <button class="btn btn-outline-secondary btn-sm text-nowrap" type="button" onclick="addScheduleImage()">追加</button>
                                </div>
                                <div class="mt-2">
                                    <label class="form-label text-muted small mb-1">または画像をアップロード：</label>
                                    <input type="file" class="form-control form-control-sm" id="scheduleImageUpload" accept="image/*" onchange="uploadScheduleImage()">
                                </div>
                                <div id="scheduleUploadStatus" class="mt-1"></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">タイプ</label>
                                <select class="form-select" id="scheduleType">
                                    <option value="normal">通常</option>
                                    <option value="study">テスト期間</option>
                                    <option value="espazio">エスパジオ</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="scheduleEditorPlaceholder" class="text-center text-muted py-5">
                        ← 左の月を選んで編集してください
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== Contact タブ ========== -->
        <div class="tab-pane fade" id="tabContact">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Contact / SNSリンク</strong>
                    <button class="btn btn-primary btn-sm" onclick="saveContact()">保存</button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Instagram URL</label>
                        <input type="url" class="form-control" id="contactInstagram"
                               value="<?= htmlspecialchars($settings['contact_instagram'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">X (Twitter) URL</label>
                        <input type="url" class="form-control" id="contactTwitter"
                               value="<?= htmlspecialchars($settings['contact_twitter'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ニュース編集モーダル -->
<div class="modal fade" id="newsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newsModalTitle">ニュース追加</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="newsId">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">日付</label>
                        <input type="text" class="form-control" id="newsDate" placeholder="例: 2026.04.01">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">アンカーID <small class="text-muted">(ページ内リンク用)</small></label>
                        <input type="text" class="form-control" id="newsAnchor" placeholder="例: news-ski">
                    </div>
                    <div class="col-12">
                        <label class="form-label">タイトル</label>
                        <input type="text" class="form-control" id="newsTitle">
                    </div>
                    <div class="col-12">
                        <label class="form-label">説明文</label>
                        <textarea class="form-control" id="newsDesc" rows="3"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">画像</label>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="text" class="form-control" id="newsImage" placeholder="アップロード後に自動入力されます">
                            <label class="btn btn-outline-secondary btn-sm text-nowrap mb-0" style="cursor:pointer;">
                                📁 選択
                                <input type="file" id="newsImageUpload" accept="image/*" style="display:none;">
                            </label>
                        </div>
                        <div id="newsUploadStatus" class="mt-1 small"></div>
                        <img id="newsImagePreview" class="news-img-preview" src="" alt="プレビュー">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" onclick="saveNews()">保存</button>
            </div>
        </div>
    </div>
</div>

<script>
// ─────────────────────────────────────────
// Quill エディタ
// ─────────────────────────────────────────
const TOOLBAR = [
    ['bold', 'italic', 'underline'],
    [{ 'header': [2, 3, false] }],
    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
    ['clean']
];
let quillAbout, quillText, quillExtra;

// ─────────────────────────────────────────
// 共通ユーティリティ
// ─────────────────────────────────────────
function removeRow(btn) {
    btn.closest('.row').remove();
}

function showToast(msg, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} position-fixed bottom-0 end-0 m-3`;
    toast.style.zIndex = 9999;
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

async function apiPut(url, data) {
    const res = await fetch(url, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    });
    if (!res.ok && res.status !== 400 && res.status !== 422) {
        throw new Error(`サーバーエラー (HTTP ${res.status})`);
    }
    return res.json();
}
async function apiPost(url, data) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    });
    if (!res.ok && res.status !== 400 && res.status !== 422) {
        throw new Error(`サーバーエラー (HTTP ${res.status})`);
    }
    return res.json();
}
async function apiDelete(url) {
    const res = await fetch(url, { method: 'DELETE' });
    if (!res.ok && res.status !== 404) {
        throw new Error(`サーバーエラー (HTTP ${res.status})`);
    }
    return res.json();
}

// ─────────────────────────────────────────
// About
// ─────────────────────────────────────────
function addInfoRow() {
    document.getElementById('aboutInfoRows').insertAdjacentHTML('beforeend', `
        <div class="row g-2 mb-2 about-info-row">
            <div class="col-4"><input type="text" class="form-control" placeholder="項目名"></div>
            <div class="col-7"><input type="text" class="form-control" placeholder="内容"></div>
            <div class="col-1"><button class="btn btn-outline-danger btn-sm w-100" onclick="removeRow(this)">✕</button></div>
        </div>`);
}

function addAchievementRow() {
    document.getElementById('achievementRows').insertAdjacentHTML('beforeend', `
        <div class="row g-2 mb-2 achievement-row">
            <div class="col-5"><input type="text" class="form-control" placeholder="大会名"></div>
            <div class="col-6"><input type="text" class="form-control" placeholder="結果"></div>
            <div class="col-1"><button class="btn btn-outline-danger btn-sm w-100" onclick="removeRow(this)">✕</button></div>
        </div>`);
}

async function saveAbout() {
    const infoRows = [];
    document.querySelectorAll('.about-info-row').forEach(row => {
        const inputs = row.querySelectorAll('input');
        if (inputs[0].value.trim()) {
            infoRows.push({ th: inputs[0].value, td: inputs[1].value });
        }
    });

    const achRows = [];
    document.querySelectorAll('.achievement-row').forEach(row => {
        const inputs = row.querySelectorAll('input');
        if (inputs[0].value.trim()) {
            achRows.push({ event: inputs[0].value, result: inputs[1].value });
        }
    });

    try {
        const d = await apiPut('/api/hp/settings', {
            about_description: quillAbout.root.innerHTML,
            about_info: JSON.stringify(infoRows),
            about_achievements: JSON.stringify(achRows),
        });
        showToast(d.success ? '保存しました' : (d.error?.message || 'エラー'), d.success ? 'success' : 'danger');
    } catch (err) {
        showToast('通信エラー: ' + err.message, 'danger');
    }
}

// ─────────────────────────────────────────
// ニュース管理
// ─────────────────────────────────────────
let _allNews = <?= json_encode($news, JSON_UNESCAPED_UNICODE) ?>;

function updateNewsImagePreview(src) {
    const preview = document.getElementById('newsImagePreview');
    if (src) {
        preview.src = src;
        preview.style.display = 'block';
        preview.onerror = () => { preview.style.display = 'none'; };
    } else {
        preview.style.display = 'none';
    }
}

function openNewsModal(id) {
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('newsModal'));
    if (id === null) {
        document.getElementById('newsModalTitle').textContent = 'ニュース追加';
        document.getElementById('newsId').value = '';
        document.getElementById('newsDate').value = '';
        document.getElementById('newsTitle').value = '';
        document.getElementById('newsDesc').value = '';
        document.getElementById('newsImage').value = '';
        document.getElementById('newsAnchor').value = '';
        updateNewsImagePreview('');
    } else {
        const item = _allNews.find(n => n.id == id);
        if (!item) return;
        document.getElementById('newsModalTitle').textContent = 'ニュース編集';
        document.getElementById('newsId').value = item.id;
        document.getElementById('newsDate').value = item.news_date;
        document.getElementById('newsTitle').value = item.title;
        document.getElementById('newsDesc').value = item.description || '';
        document.getElementById('newsImage').value = item.image_path || '';
        document.getElementById('newsAnchor').value = item.anchor_id || '';
        updateNewsImagePreview(item.image_path || '');
    }
    modal.show();
}

async function saveNews() {
    const saveBtn = document.querySelector('#newsModal .btn-primary');
    saveBtn.disabled = true;
    saveBtn.textContent = '保存中...';

    const id = document.getElementById('newsId').value;
    const payload = {
        news_date:     document.getElementById('newsDate').value,
        title:         document.getElementById('newsTitle').value,
        description:   document.getElementById('newsDesc').value,
        image_path:    document.getElementById('newsImage').value || null,
        anchor_id:     document.getElementById('newsAnchor').value || null,
    };

    try {
        const d = id
            ? await apiPut(`/api/hp/news/${id}`, payload)
            : await apiPost('/api/hp/news', payload);

        if (d.success) {
            showToast('保存しました');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('newsModal')).hide();
            location.reload();
        } else {
            showToast(d.error?.message || 'エラーが発生しました', 'danger');
            saveBtn.disabled = false;
            saveBtn.textContent = '保存';
        }
    } catch (err) {
        showToast('通信エラー: ' + err.message, 'danger');
        saveBtn.disabled = false;
        saveBtn.textContent = '保存';
    }
}

async function deleteNews(id) {
    if (!confirm('このニュースを削除しますか？')) return;
    try {
        const d = await apiDelete(`/api/hp/news/${id}`);
        if (d.success) {
            showToast('削除しました');
            document.querySelector(`#newsTableBody tr[data-id="${id}"]`)?.remove();
        } else {
            showToast(d.error?.message || '削除に失敗しました', 'danger');
        }
    } catch (err) {
        showToast('通信エラー: ' + err.message, 'danger');
    }
}

// ─────────────────────────────────────────
// スケジュール管理
// ─────────────────────────────────────────
let _scheduleData = <?= json_encode(array_column($schedule, null, 'id'), JSON_UNESCAPED_UNICODE) ?>;

function selectScheduleMonth(id, btnEl) {
    document.querySelectorAll('#scheduleList button').forEach(b => b.classList.remove('active'));
    btnEl.classList.add('active');

    const s = _scheduleData[id];
    document.getElementById('scheduleId').value = id;
    document.getElementById('scheduleEditorTitle').textContent = s.month_label + ' - ' + s.month_en;
    document.getElementById('scheduleMonthLabel').value = s.month_label || '';
    document.getElementById('scheduleTitle').value = s.title;
    document.getElementById('scheduleType').value = s.type || 'normal';

    quillText.clipboard.dangerouslyPasteHTML(s.text_html || '');
    quillExtra.clipboard.dangerouslyPasteHTML(s.extra_html || '');

    renderScheduleImages(Array.isArray(s.images) ? s.images : []);

    document.getElementById('scheduleEditor').style.display = '';
    document.getElementById('scheduleEditorPlaceholder').style.display = 'none';
}

function renderScheduleImages(images) {
    const container = document.getElementById('scheduleImageList');
    container.innerHTML = '';

    if (images.length === 0) {
        container.innerHTML = '<p class="text-muted small mb-0">画像はまだありません</p>';
        return;
    }

    images.forEach((img, i) => {
        const row = document.createElement('div');
        row.className = 'd-flex align-items-center gap-2 mb-2 schedule-img-row';

        const thumb = document.createElement('img');
        thumb.src = img;
        thumb.alt = '';
        thumb.className = 'schedule-img-thumb';
        thumb.title = 'クリックで拡大';
        thumb.onclick = () => window.open(img, '_blank');
        thumb.onerror = function() { this.style.visibility = 'hidden'; };

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control form-control-sm';
        input.value = img;
        input.addEventListener('change', function() {
            thumb.src = this.value;
            thumb.style.visibility = 'visible';
        });

        const btn = document.createElement('button');
        btn.className = 'btn btn-sm btn-outline-danger flex-shrink-0';
        btn.textContent = '✕';
        btn.onclick = () => removeScheduleImage(i);

        row.appendChild(thumb);
        row.appendChild(input);
        row.appendChild(btn);
        container.appendChild(row);
    });
}

function getCurrentScheduleImages() {
    return Array.from(document.querySelectorAll('.schedule-img-row input')).map(i => i.value);
}

function addScheduleImage() {
    const val = document.getElementById('scheduleImageInput').value.trim();
    if (!val) return;
    const id = document.getElementById('scheduleId').value;
    const imgs = getCurrentScheduleImages();
    imgs.push(val);
    _scheduleData[id].images = imgs;
    renderScheduleImages(imgs);
    document.getElementById('scheduleImageInput').value = '';
}

function removeScheduleImage(idx) {
    const id = document.getElementById('scheduleId').value;
    const imgs = getCurrentScheduleImages();
    imgs.splice(idx, 1);
    _scheduleData[id].images = imgs;
    renderScheduleImages(imgs);
}

async function uploadScheduleImage() {
    const fileInput = document.getElementById('scheduleImageUpload');
    const file = fileInput.files[0];
    if (!file) return;

    const statusEl = document.getElementById('scheduleUploadStatus');
    statusEl.innerHTML = '<span class="text-muted small">アップロード中...</span>';

    const fd = new FormData();
    fd.append('image', file);
    const res = await fetch('/api/hp/upload', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
        const id = document.getElementById('scheduleId').value;
        const imgs = getCurrentScheduleImages();
        imgs.push(data.data.path);
        _scheduleData[id].images = imgs;
        renderScheduleImages(imgs);
        statusEl.innerHTML = `<span class="text-success small">✓ アップロード完了</span>`;
        fileInput.value = '';
    } else {
        statusEl.innerHTML = `<span class="text-danger small">${data.error?.message || 'エラー'}</span>`;
    }
}

async function saveSchedule() {
    const id = document.getElementById('scheduleId').value;
    const images = getCurrentScheduleImages();
    const saveBtn = document.querySelector('#scheduleEditor .btn-primary');
    saveBtn.disabled = true;
    saveBtn.textContent = '保存中...';

    try {
        const d = await apiPut(`/api/hp/schedule/${id}`, {
            month_label: document.getElementById('scheduleMonthLabel').value,
            title:      document.getElementById('scheduleTitle').value,
            text_html:  quillText.root.innerHTML,
            extra_html: quillExtra.root.innerHTML,
            type:       document.getElementById('scheduleType').value,
            images:     images,
        });

        if (d.success) {
            const btn = document.querySelector(`#scheduleList button[data-id="${id}"]`);
            if (btn) {
                btn.querySelector('small').textContent = document.getElementById('scheduleTitle').value;
                const lbl = btn.querySelector('.fw-bold');
                if (lbl) lbl.textContent = document.getElementById('scheduleMonthLabel').value;
            }
            _scheduleData[id].month_label = document.getElementById('scheduleMonthLabel').value;
            _scheduleData[id].title     = document.getElementById('scheduleTitle').value;
            _scheduleData[id].text_html = quillText.root.innerHTML;
            _scheduleData[id].extra_html= quillExtra.root.innerHTML;
            _scheduleData[id].images    = images;
            showToast('保存しました');
        } else {
            showToast(d.error?.message || 'エラー', 'danger');
        }
    } catch (err) {
        showToast('通信エラー: ' + err.message, 'danger');
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = '保存';
    }
}

// ─────────────────────────────────────────
// Contact
// ─────────────────────────────────────────
async function saveContact() {
    try {
        const d = await apiPut('/api/hp/settings', {
            contact_instagram: document.getElementById('contactInstagram').value,
            contact_twitter:   document.getElementById('contactTwitter').value,
        });
        showToast(d.success ? '保存しました' : (d.error?.message || 'エラー'), d.success ? 'success' : 'danger');
    } catch (err) {
        showToast('通信エラー: ' + err.message, 'danger');
    }
}

// ─────────────────────────────────────────
// 初期化
// ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Quill 初期化
    quillAbout = new Quill('#aboutDescriptionEditor', { theme: 'snow', modules: { toolbar: TOOLBAR } });
    quillText  = new Quill('#scheduleTextEditor',     { theme: 'snow', modules: { toolbar: TOOLBAR } });
    quillExtra = new Quill('#scheduleExtraEditor',    { theme: 'snow', modules: { toolbar: TOOLBAR } });

    // About の初期値をセット
    quillAbout.clipboard.dangerouslyPasteHTML(<?= json_encode($settings['about_description'] ?? '', JSON_UNESCAPED_UNICODE) ?>);

    // ニュース画像 - パス入力でプレビュー更新
    document.getElementById('newsImage').addEventListener('input', function() {
        updateNewsImagePreview(this.value);
    });

    // ニュース画像アップロード
    document.getElementById('newsImageUpload').addEventListener('change', async function() {
        const file = this.files[0];
        if (!file) return;
        const statusEl = document.getElementById('newsUploadStatus');
        statusEl.innerHTML = '<span class="text-muted">アップロード中...</span>';
        const fd = new FormData();
        fd.append('image', file);
        try {
            const res = await fetch('/api/hp/upload', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                document.getElementById('newsImage').value = data.data.path;
                updateNewsImagePreview(data.data.path);
                statusEl.innerHTML = '<span class="text-success">✓ アップロード完了</span>';
            } else {
                statusEl.innerHTML = `<span class="text-danger">${data.error?.message || 'エラー'}</span>`;
            }
        } catch (err) {
            statusEl.innerHTML = `<span class="text-danger">通信エラー</span>`;
        }
    });

    // スケジュール：先頭の月を自動選択
    const first = document.querySelector('#scheduleList button');
    if (first) first.click();
});
</script>
