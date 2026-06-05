<?php
// 遠征詳細ページ - 7タブ構成
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= url('/expeditions') ?>" class="text-decoration-none">&larr; 戻る</a>
        <h1 class="mt-2" id="expeditionTitle">読み込み中...</h1>
    </div>
</div>

<!-- タブナビゲーション -->
<ul class="nav nav-tabs mb-4" id="expeditionTabs">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabBasic">基本情報</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabParticipants" onclick="loadParticipants()">参加者管理</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabCars" onclick="loadCars()">車割</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabTeams" onclick="loadTeams()">チーム分け</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabApplication" onclick="loadApplicationUrl()">申し込みURL</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabCollection" onclick="loadCollection()">集金管理</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabBooklet" onclick="loadBooklet()">しおり</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabCarExpense" onclick="loadCarExpenses()">レンタカー清算</button>
    </li>
</ul>

<!-- タブコンテンツ -->
<div class="tab-content">

    <!-- ===== タブ1: 基本情報 ===== -->
    <div class="tab-pane fade show active" id="tabBasic">
        <!-- サブタブ -->
        <ul class="nav nav-tabs mb-3" id="basicSubTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#basicSubInfo">基本情報</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#basicSubSubsidy">補助金</a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="basicSubInfo">
                <div id="basicInfo">読み込み中...</div>
            </div>
            <div class="tab-pane fade" id="basicSubSubsidy">
                <div id="subsidyInfo">読み込み中...</div>
            </div>
        </div>
    </div>

    <!-- ===== タブ2: 参加者管理 ===== -->
    <div class="tab-pane fade" id="tabParticipants">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>参加者一覧 <span class="badge bg-secondary" id="participantCount">0</span></h4>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-success btn-sm" id="btnExportXlsx" href="#" target="_blank">
                    <i class="bi bi-file-earmark-excel"></i> Excel
                </a>
                <a class="btn btn-outline-danger btn-sm" id="btnExportPdf" href="#" target="_blank">
                    <i class="bi bi-file-earmark-pdf"></i> PDF
                </a>
                <a class="btn btn-outline-secondary btn-sm" id="btnExportMeibo" href="#" target="_blank">
                    <i class="bi bi-file-earmark-spreadsheet"></i> 参加者名簿
                </a>
                <a class="btn btn-outline-info btn-sm" id="btnExportEspajio" href="#" target="_blank">
                    <i class="bi bi-file-earmark-excel"></i> エスパジオ登録
                </a>
                <button class="btn btn-primary btn-sm" onclick="showAddParticipantModal()">+ 参加者を追加</button>
            </div>
        </div>

        <!-- 検索・並び替え -->
        <div class="row mb-3">
            <div class="col-md-4">
                <input type="text" class="form-control form-control-sm"
                       id="participantSearch"
                       placeholder="名前で検索..."
                       oninput="filterParticipants()">
            </div>
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- 第1ソート -->
                    <div class="input-group input-group-sm" style="width: auto;">
                        <span class="input-group-text">並び替え</span>
                        <select class="form-select form-select-sm"
                                id="sortPrimary"
                                onchange="applySorting()"
                                style="width: auto;">
                            <option value="id">登録順</option>
                            <option value="name">名前</option>
                            <option value="grade" selected>学年</option>
                            <option value="gender">性別</option>
                        </select>
                        <button type="button" class="btn btn-outline-secondary"
                                id="sortPrimaryDir"
                                onclick="toggleSortDirection('primary')"
                                title="昇順/降順切り替え">↑</button>
                    </div>
                    <!-- 第2ソート -->
                    <div class="input-group input-group-sm" style="width: auto;">
                        <span class="input-group-text">→</span>
                        <select class="form-select form-select-sm"
                                id="sortSecondary"
                                onchange="applySorting()"
                                style="width: auto;">
                            <option value="">なし</option>
                            <option value="id">登録順</option>
                            <option value="name">名前</option>
                            <option value="grade">学年</option>
                            <option value="gender" selected>性別</option>
                        </select>
                        <button type="button" class="btn btn-outline-secondary"
                                id="sortSecondaryDir"
                                onclick="toggleSortDirection('secondary')"
                                title="昇順/降順切り替え">↑</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="participantList">読み込み中...</div>
        <div id="allergySummary" class="mt-3"></div>
    </div>

    <!-- ===== タブ3: 車割 ===== -->
    <div class="tab-pane fade" id="tabCars">
        <h4 class="mb-3">車割</h4>

        <!-- 往路/復路 サブタブ -->
        <ul class="nav nav-tabs mb-3" id="carTripTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#carTabOutbound">往路</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#carTabReturn">復路</a>
            </li>
        </ul>

        <!-- 推奨下車駅解析バー -->
        <div class="d-flex align-items-center gap-2 mb-3 p-2 bg-light rounded">
            <span class="small text-muted">推奨下車駅:</span>
            <button class="btn btn-outline-secondary btn-sm" id="resolveStationsBtn" onclick="resolveStations()">
                <i class="bi bi-geo-fill"></i> 解析
            </button>
            <span class="small text-muted" id="resolveStationsStatus"></span>
        </div>

        <div class="tab-content">
            <!-- 往路 -->
            <div class="tab-pane fade show active" id="carTabOutbound">
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <button class="btn btn-primary btn-sm" onclick="showAddCarModal('outbound')">+ 車を追加</button>
                    <button class="btn btn-outline-success btn-sm" onclick="autoAssignOutboundCars()">
                        <i class="bi bi-magic"></i> 往路を自動作成
                    </button>
                </div>
                <div id="carListOutbound">読み込み中...</div>
            </div>

            <!-- 復路 -->
            <div class="tab-pane fade" id="carTabReturn">
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <button class="btn btn-primary btn-sm" onclick="showAddCarModal('return')">+ 車を追加</button>
                    <button class="btn btn-outline-success btn-sm" onclick="showAutoAssignReturnModal()">
                        <i class="bi bi-geo-alt"></i> 復路を自動作成
                    </button>
                </div>
                <div id="carListReturn">読み込み中...</div>
            </div>
        </div>

    </div>

    <!-- ===== タブ4: チーム分け ===== -->
    <div class="tab-pane fade" id="tabTeams">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>チーム分け</h4>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-success btn-sm" onclick="showAutoAssignModal()">
                    <i class="bi bi-shuffle"></i> 自動割り当て
                </button>
                <button class="btn btn-primary btn-sm" onclick="showAddTeamModal()">+ チームを追加</button>
            </div>
        </div>
        <div id="teamBoard" class="d-flex flex-wrap gap-3">読み込み中...</div>
    </div>

    <!-- ===== タブ5: 申し込みURL ===== -->
    <div class="tab-pane fade" id="tabApplication">
        <div id="applicationUrlContent">読み込み中...</div>
    </div>

    <!-- ===== タブ6: 集金管理 ===== -->
    <div class="tab-pane fade" id="tabCollection">
        <div id="collectionContent">読み込み中...</div>
    </div>

    <!-- ===== タブ7: しおり ===== -->
    <div class="tab-pane fade" id="tabBooklet">
        <div id="bookletContent">読み込み中...</div>
    </div>

    <!-- ===== タブ8: レンタカー清算 ===== -->
    <div class="tab-pane fade" id="tabCarExpense">
        <!-- 申請期限設定 -->
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="fw-bold small">費用申請期限</span>
                    <input type="date" class="form-control form-control-sm" id="expenseDeadlineInput" style="width:auto;">
                    <button class="btn btn-sm btn-outline-primary" onclick="saveExpenseDeadline()">保存</button>
                    <span id="expenseDeadlineSaved" class="text-success small d-none"><i class="bi bi-check-circle"></i> 保存しました</span>
                    <div class="ms-auto d-flex gap-2">
                        <a class="btn btn-outline-success btn-sm" id="btnExpenseXlsx" href="#" target="_blank">
                            <i class="bi bi-file-earmark-excel"></i> Excel
                        </a>
                        <a class="btn btn-outline-danger btn-sm" id="btnExpensePdf" href="#" target="_blank">
                            <i class="bi bi-file-earmark-pdf"></i> PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- 申請一覧 -->
        <div id="carExpenseList">読み込み中...</div>
        <!-- 清算一覧 -->
        <div id="carSettlementList" class="mt-3"></div>
    </div>

</div>

<!-- ===== 参加者追加モーダル ===== -->
<div class="modal fade" id="addParticipantModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">参加者を追加</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">名前で検索</label>
                    <input type="text" class="form-control" id="participantSearchInput" placeholder="氏名またはフリガナを入力" oninput="searchMembers(this.value)">
                </div>
                <div id="participantSearchResults"></div>
                <input type="hidden" id="newParticipantMemberId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" onclick="addParticipant()">追加</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== 車追加モーダル ===== -->
<!-- ===== 往路自動作成モーダル ===== -->
<div class="modal fade" id="carAutoAssignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-magic"></i> 往路を自動作成</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    「車の予約をする」で登録した人ごとに車を作成します。<br>
                    各車の定員を設定してから「実行」を押してください。
                </p>
                <div id="autoAssignBookerList"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-success" id="carAutoAssignExecBtn" onclick="executeCarAutoAssign()">
                    <i class="bi bi-play-fill"></i> 実行
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addCarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">車を追加</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="newCarTripType" value="both">
                <div class="mb-3">
                    <label class="form-label">車名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="newCarName" placeholder="例: 田中号">
                </div>
                <div class="mb-3">
                    <label class="form-label">定員</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="newCarCapacity" min="1" value="6">
                        <span class="input-group-text">人</span>
                    </div>
                </div>
                <div class="mb-3" id="departureClassField" style="display:none;">
                    <label class="form-label">出発時限 <span class="text-muted small">（この車に乗れる最遅の授業終了時限）</span></label>
                    <select class="form-select" id="newCarDepartureClass">
                        <option value="0">授業なし（早出）</option>
                        <option value="1">1限終わり</option>
                        <option value="2">2限終わり</option>
                        <option value="3">3限終わり</option>
                        <option value="4">4限終わり</option>
                        <option value="5">5限終わり</option>
                        <option value="6">6限終わり（最終）</option>
                    </select>
                    <div class="form-text">例: 「3限終わり」を選ぶと、3限以前に授業が終わる人がこの車に割り当てられます</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">レンタカー代</label>
                    <div class="input-group">
                        <span class="input-group-text">¥</span>
                        <input type="number" class="form-control" id="newCarRentalFee" min="0" value="0">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">高速代</label>
                    <div class="input-group">
                        <span class="input-group-text">¥</span>
                        <input type="number" class="form-control" id="newCarHighwayFee" min="0" value="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" onclick="addCar()">追加</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== 乗員追加モーダル ===== -->
<div class="modal fade" id="addCarMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">乗員を追加</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">参加者</label>
                    <select class="form-select" id="carMemberSelect"></select>
                </div>
                <div class="mb-3">
                    <label class="form-label">役割</label>
                    <select class="form-select" id="carMemberRole">
                        <option value="passenger">乗客</option>
                        <option value="driver">ドライバー</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" onclick="addCarMember()">追加</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== チーム追加モーダル ===== -->
<div class="modal fade" id="addTeamModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">チームを追加</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">チーム名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="newTeamName" placeholder="例: Aチーム">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" onclick="addTeam()">追加</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== 自動割り当てモーダル ===== -->
<div class="modal fade" id="autoAssignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">男女バランス自動割り当て</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="autoAssignPreview">読み込み中...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-success" id="autoAssignExecBtn" onclick="executeAutoAssign()" disabled>
                    <i class="bi bi-shuffle"></i> 割り当て実行
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== 復路自動作成モーダル ===== -->
<div class="modal fade" id="autoAssignReturnModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-geo-alt"></i> 復路を自動作成</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    「車の予約をする」で登録した人ごとに車を作成します。<br>
                    各車の定員と割り当て方式を設定してから「実行」を押してください。
                </p>
                <div id="autoAssignReturnDriverList"></div>
                <div class="mb-3">
                    <label class="form-label fw-bold">割り当て方式</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="returnAssignMode"
                               id="modeByStation" value="by_station" checked>
                        <label class="form-check-label" for="modeByStation">
                            <strong>主要駅で固める</strong>
                            <div class="text-muted small">住所の最寄り路線から高田馬場・新宿・渋谷・東京のどこで降りるか判定し、同じ駅グループを同じ車に</div>
                        </label>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="radio" name="returnAssignMode"
                               id="modeByDriverHome" value="by_driver_home">
                        <label class="form-check-label" for="modeByDriverHome">
                            <strong>家の近くで固める</strong>
                            <div class="text-muted small">ドライバーの家を軸に、住所が近い参加者を同じ車に</div>
                        </label>
                    </div>
                </div>
                <div class="alert alert-info small mb-0">
                    <i class="bi bi-info-circle"></i>
                    住所が未登録の参加者はデフォルト（高田馬場方面）として扱われます。<br>
                    処理に数秒かかる場合があります（住所ごとにAPI呼び出し）。
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-success" id="autoAssignReturnExecBtn"
                        onclick="autoAssignReturnCars()">
                    <i class="bi bi-play-fill"></i> 実行
                </button>
            </div>
        </div>
    </div>
</div>

<!-- SortableJS CDN（チーム分けD&D用） -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script>
// PHP から遠征IDを埋め込む
const expeditionId = <?= $data['id'] ?>;

// 各タブのロード済みフラグ（二重ロード防止）
let participantsLoaded = false;
let carsLoaded = false;
let teamsLoaded = false;
let applicationLoaded = false;
let collectionLoaded = false;
let _bookletData      = null;
let _bookletSaving    = false;
let _bookletSaveTimer = null;

// モーダルインスタンス
let addParticipantModal, addCarModal, addCarMemberModal, addCarPayerModal, addTeamModal, autoAssignModal, carAutoAssignModal, autoAssignReturnModal;

// 現在操作中の車ID（乗員・立替者追加時に使用）
let currentCarId = null;

// 参加者キャッシュ（各モーダルのセレクト生成に使用）
let participantsCache = [];

// 参加者一覧のソート設定（合宿管理と同仕様）
let sortConfig = {
    primary:   { key: 'grade',  direction: 1 },
    secondary: { key: 'gender', direction: 1 }
};

document.addEventListener('DOMContentLoaded', () => {
    // モーダルを初期化
    addParticipantModal = new bootstrap.Modal(document.getElementById('addParticipantModal'));
    addCarModal         = new bootstrap.Modal(document.getElementById('addCarModal'));
    carAutoAssignModal  = new bootstrap.Modal(document.getElementById('carAutoAssignModal'));
    addCarMemberModal   = new bootstrap.Modal(document.getElementById('addCarMemberModal'));
    addTeamModal        = new bootstrap.Modal(document.getElementById('addTeamModal'));
    autoAssignModal       = new bootstrap.Modal(document.getElementById('autoAssignModal'));
    autoAssignReturnModal = new bootstrap.Modal(document.getElementById('autoAssignReturnModal'));

    // ページロード時に基本情報を取得
    loadBasicInfo();
});

// ==============================
// ユーティリティ
// ==============================
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

function showToast(message, type = 'info', duration = 3000) {
    const toast = document.createElement('div');
    toast.className = 'position-fixed bottom-0 end-0 p-3';
    toast.style.zIndex = '11';
    toast.innerHTML = `
        <div class="toast show" role="alert">
            <div class="toast-body">${escapeHtml(message)}</div>
        </div>
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), duration);
}

// ==============================
// タブ1: 基本情報
// ==============================
async function loadBasicInfo() {
    try {
        const res  = await fetch(`/api/expeditions/${expeditionId}`);
        const data = await res.json();
        if (data.success) {
            renderBasicInfo(data.data);
            renderSubsidyInfo(data.data);
            document.getElementById('expeditionTitle').textContent = data.data.name || '遠征詳細';
        } else {
            document.getElementById('basicInfo').innerHTML = '<div class="alert alert-danger">読み込みに失敗しました</div>';
        }
    } catch (err) {
        console.error(err);
        document.getElementById('basicInfo').innerHTML = '<div class="alert alert-danger">通信エラーが発生しました</div>';
    }
}

let _basicSaveTimer = null;

function scheduleBasicSave() {
    clearTimeout(_basicSaveTimer);
    _basicSaveTimer = setTimeout(() => saveBasicInfo(true), 1500);
}

function setBasicSaveStatus(status) {
    const el = document.getElementById('basicSaveStatus');
    if (!el) return;
    if (status === 'saving') el.textContent = '保存中...';
    else if (status === 'saved') el.textContent = '自動保存済み';
    else if (status === 'error') el.textContent = '保存失敗';
}

function renderBasicInfo(e) {
    document.getElementById('basicInfo').innerHTML = `
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                基本情報を編集
                <span id="basicSaveStatus" class="text-muted small"></span>
            </div>
            <div class="card-body">
                <form id="basicInfoForm">
                    <div class="mb-3">
                        <label class="form-label">イベント名 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editName" value="${escapeHtml(e.name)}" required
                               oninput="scheduleBasicSave()">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">開始日</label>
                            <input type="date" class="form-control" id="editStartDate" value="${e.start_date || ''}"
                                   onchange="scheduleBasicSave()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">終了日</label>
                            <input type="date" class="form-control" id="editEndDate" value="${e.end_date || ''}"
                                   onchange="scheduleBasicSave()">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">参加費</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" id="editBaseFee" min="0" value="${e.base_fee || 0}"
                                       onchange="scheduleBasicSave()">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">前泊費</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" id="editPreNightFee" min="0" value="${e.pre_night_fee || 0}"
                                       onchange="scheduleBasicSave()">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">昼食費</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" id="editLunchFee" min="0" value="${e.lunch_fee || 0}"
                                       onchange="scheduleBasicSave()">
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12 mb-1"><label class="form-label fw-bold">定員（空欄=無制限）</label></div>
                        <div class="col-md-6">
                            <label class="form-label text-primary">男性定員</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="editCapacityMale" min="1"
                                       value="${e.capacity_male !== null && e.capacity_male !== undefined ? e.capacity_male : ''}"
                                       onchange="scheduleBasicSave()">
                                <span class="input-group-text">人</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-danger">女性定員</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="editCapacityFemale" min="1"
                                       value="${e.capacity_female !== null && e.capacity_female !== undefined ? e.capacity_female : ''}"
                                       onchange="scheduleBasicSave()">
                                <span class="input-group-text">人</span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    `;
}

function renderSubsidyInfo(e) {
    // API レスポンスの participants（show エンドポイントで付与）か participantsCache を使用
    const ptList         = e.participants || participantsCache || [];
    const confirmedCount = ptList.filter(p => p.status === 'confirmed').length;
    const subsidy        = parseInt(e.subsidy) || 0;
    const perPerson      = confirmedCount > 0 ? Math.floor(subsidy / confirmedCount) : 0;
    const baseFee        = parseInt(e.base_fee) || 0;
    const effectiveFee   = Math.max(0, baseFee - perPerson);

    document.getElementById('subsidyInfo').innerHTML = `
        <div class="card">
            <div class="card-header">補助金設定</div>
            <div class="card-body">
                <div class="row g-3 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label">補助金額（全体）</label>
                        <div class="input-group">
                            <span class="input-group-text">¥</span>
                            <input type="number" class="form-control" id="editSubsidy" min="0" value="${subsidy}"
                                   oninput="updateSubsidyPreview()">
                        </div>
                        <div class="form-text">参加費から人数で割って減額されます</div>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary" onclick="saveSubsidy()">保存</button>
                        <span id="subsidySaved" class="text-success ms-2 d-none">保存しました</span>
                    </div>
                </div>
                <div class="card bg-light p-3" id="subsidyPreview">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="text-muted small">確定参加者数</div>
                            <div class="fw-bold" id="previewCount">${confirmedCount} 人</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">一人あたり減額</div>
                            <div class="fw-bold text-danger" id="previewPerPerson">¥${perPerson.toLocaleString()}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">設定参加費</div>
                            <div class="fw-bold">¥${baseFee.toLocaleString()}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">実質参加費</div>
                            <div class="fw-bold text-success" id="previewEffective">¥${effectiveFee.toLocaleString()}</div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-info small mt-3 mb-0">
                    <i class="bi bi-info-circle"></i>
                    保存すると第1回集金データが自動で再生成されます。端数は切り捨てです。
                </div>
            </div>
        </div>`;

    // 入力値変更時のリアルタイムプレビュー用に参加者数を保持
    window._subsidyConfirmedCount = confirmedCount;
    window._subsidyBaseFee        = baseFee;
}

function updateSubsidyPreview() {
    const subsidy      = parseInt(document.getElementById('editSubsidy').value) || 0;
    const count        = window._subsidyConfirmedCount || 0;
    const baseFee      = window._subsidyBaseFee || 0;
    const perPerson    = count > 0 ? Math.floor(subsidy / count) : 0;
    const effectiveFee = Math.max(0, baseFee - perPerson);
    document.getElementById('previewPerPerson').textContent  = '¥' + perPerson.toLocaleString();
    document.getElementById('previewEffective').textContent  = '¥' + effectiveFee.toLocaleString();
}

async function saveSubsidy() {
    const subsidy = parseInt(document.getElementById('editSubsidy').value) || 0;
    try {
        const res = await fetch(`/api/expeditions/${expeditionId}`, {
            method:  'PUT',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ subsidy }),
        });
        const data = await res.json();
        if (!data.success) {
            alert(data.error?.message || '保存に失敗しました');
            return;
        }

        const el = document.getElementById('subsidySaved');
        el.classList.remove('d-none');
        setTimeout(() => el.classList.add('d-none'), 3000);

        // 第1回集金が既に生成済みであれば自動で再生成
        const colRes  = await fetch(`/api/expeditions/${expeditionId}/collection`);
        const colData = await colRes.json();
        if (colData.success) {
            const round1 = (colData.data || []).find(c => Number(c.round) === 1);
            if (round1) {
                // 再生成（確認なし）
                await fetch(`/api/expeditions/${expeditionId}/collection/generate`, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({ round: 1 }),
                });
                // 集金タブが開いていれば再描画
                collectionLoaded = false;
                if (document.getElementById('tabCollection')?.classList.contains('show')) {
                    await loadCollection();
                }
                el.textContent = '保存・集金データを更新しました';
                setTimeout(() => { el.classList.add('d-none'); el.textContent = '保存しました'; }, 3000);
            }
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

async function saveBasicInfo(auto = false) {
    const name = document.getElementById('editName')?.value.trim();
    if (!name) return; // イベント名未入力時はスキップ

    const capMaleVal   = document.getElementById('editCapacityMale').value.trim();
    const capFemaleVal = document.getElementById('editCapacityFemale').value.trim();

    const data = {
        name:             name,
        start_date:       document.getElementById('editStartDate').value || null,
        end_date:         document.getElementById('editEndDate').value || null,

        base_fee:         parseInt(document.getElementById('editBaseFee').value) || 0,
        pre_night_fee:    parseInt(document.getElementById('editPreNightFee').value) || 0,
        lunch_fee:        parseInt(document.getElementById('editLunchFee').value) || 0,
        capacity_male:    capMaleVal   !== '' ? parseInt(capMaleVal)   : null,
        capacity_female:  capFemaleVal !== '' ? parseInt(capFemaleVal) : null,
    };

    setBasicSaveStatus('saving');
    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}`, {
            method:  'PUT',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(data),
        });
        const result = await res.json();
        if (result.success) {
            setBasicSaveStatus('saved');
            document.getElementById('expeditionTitle').textContent = name;
            if (!auto) showToast('保存しました');
        } else {
            setBasicSaveStatus('error');
            if (!auto) alert(result.error?.message || '保存に失敗しました');
        }
    } catch (err) {
        setBasicSaveStatus('error');
        if (!auto) alert('通信エラーが発生しました');
    }
}

// ==============================
// タブ2: 参加者管理
// ==============================
async function loadParticipants() {
    // 既にロード済みの場合はスキップ
    if (participantsLoaded) return;
    participantsLoaded = true;

    try {
        const res  = await fetch(`/api/expeditions/${expeditionId}/participants`);
        const data = await res.json();
        if (data.success) {
            participantsCache = data.data || [];
            renderParticipants();
            // エクスポートボタンのURLをセット
            document.getElementById('btnExportXlsx').href  = `/api/expeditions/${expeditionId}/export/xlsx`;
            document.getElementById('btnExportPdf').href   = `/api/expeditions/${expeditionId}/export/pdf`;
            document.getElementById('btnExportMeibo').href   = `/api/expeditions/${expeditionId}/export/activity-meibo`;
            document.getElementById('btnExportEspajio').href = `/api/expeditions/${expeditionId}/export/espajio`;
        } else {
            document.getElementById('participantList').innerHTML = '<div class="alert alert-danger">読み込みに失敗しました</div>';
        }
    } catch (err) {
        console.error(err);
        document.getElementById('participantList').innerHTML = '<div class="alert alert-danger">通信エラーが発生しました</div>';
    }
}

// 10月引退ルール: 3年生は10月以降（10月〜3月）OB扱い
function isRetiredAsOB(grade) {
    if (grade !== 3) return false;
    const month = new Date().getMonth();
    return month >= 9 || month <= 2;
}

function getGradeGenderLabel(grade, gender) {
    if (grade === null && gender === null) return '';
    if (grade === 3 && isRetiredAsOB(grade)) {
        if (gender === 'male')   return 'OB';
        if (gender === 'female') return 'OG';
        return 'OB/OG';
    }
    if (grade === 0 || grade === '0') {
        if (gender === 'male')   return 'OB';
        if (gender === 'female') return 'OG';
        return 'OB/OG';
    }
    const gradeStr  = grade  ? grade  : '';
    const genderStr = gender === 'male' ? '男' : (gender === 'female' ? '女' : '');
    return gradeStr + genderStr;
}

// ソート用の実効学年を取得（10月引退ルール適用）
function getEffectiveGradeForSort(grade) {
    if (grade === null || grade === undefined) return 999;
    const g = parseInt(grade);
    if (g === 0) return 99;
    if (g === 3 && isRetiredAsOB(g)) return 99;
    return g;
}

// ソートキーによる比較関数
function compareByKey(a, b, key) {
    if (key === 'id') {
        return (a.id || 0) - (b.id || 0);
    } else if (key === 'name') {
        return (a.name_kanji || '').localeCompare(b.name_kanji || '', 'ja');
    } else if (key === 'grade') {
        return getEffectiveGradeForSort(a.grade) - getEffectiveGradeForSort(b.grade);
    } else if (key === 'gender') {
        const order = { 'male': 1, 'female': 2 };
        return (order[a.gender] || 3) - (order[b.gender] || 3);
    }
    return 0;
}

// ソート方向切り替え
function toggleSortDirection(level) {
    if (level === 'primary') {
        sortConfig.primary.direction *= -1;
        document.getElementById('sortPrimaryDir').textContent = sortConfig.primary.direction === 1 ? '↑' : '↓';
    } else {
        sortConfig.secondary.direction *= -1;
        document.getElementById('sortSecondaryDir').textContent = sortConfig.secondary.direction === 1 ? '↑' : '↓';
    }
    renderParticipants();
}

// ソート適用
function applySorting() {
    sortConfig.primary.key   = document.getElementById('sortPrimary').value;
    sortConfig.secondary.key = document.getElementById('sortSecondary').value || '';
    renderParticipants();
}

// 検索フィルタ適用
function filterParticipants() {
    renderParticipants();
}

// 検索+ソート済みの参加者リストを返す
function getSortedFilteredParticipants(source) {
    let participants = [...(source || [])];

    const searchEl   = document.getElementById('participantSearch');
    const searchText = searchEl ? searchEl.value.toLowerCase() : '';
    if (searchText) {
        participants = participants.filter(p => (p.name_kanji || '').toLowerCase().includes(searchText));
    }

    participants.sort((a, b) => {
        let cmp = compareByKey(a, b, sortConfig.primary.key);
        if (cmp !== 0) return cmp * sortConfig.primary.direction;

        if (sortConfig.secondary.key) {
            cmp = compareByKey(a, b, sortConfig.secondary.key);
            if (cmp !== 0) return cmp * sortConfig.secondary.direction;
        }

        return (a.name_kanji || '').localeCompare(b.name_kanji || '', 'ja');
    });

    return participants;
}

function renderParticipants() {
    const participants = participantsCache || [];
    document.getElementById('participantCount').textContent = participants.length;

    if (participants.length === 0) {
        document.getElementById('participantList').innerHTML = '<div class="alert alert-info">参加者はまだいません</div>';
        return;
    }

    const confirmed  = getSortedFilteredParticipants(participants.filter(p => p.status === 'confirmed'));
    const waitlisted = getSortedFilteredParticipants(participants.filter(p => p.status === 'waitlisted'));

    const makeRows = (list) => list.map(p => {
        const gradeGender  = getGradeGenderLabel(p.grade, p.gender);
        const allergyHtml  = p.allergy
            ? `<span class="text-danger" title="${escapeHtml(p.allergy)}">${escapeHtml(p.allergy.length > 20 ? p.allergy.slice(0, 20) + '…' : p.allergy)}</span>`
            : '<span class="text-muted small">なし</span>';

        // 車に乗るか チェックボックス
        const joiningCheck = `<input type="checkbox" class="form-check-input" ${p.is_joining_car != 0 ? 'checked' : ''}
            onchange="updateParticipant(${p.id}, 'is_joining_car', this.checked ? 1 : 0)">`;

        // 役割 セレクト
        const roleSelect = `<select class="form-select form-select-sm" style="width:auto;min-width:90px"
            onchange="updateParticipant(${p.id}, 'driver_type', this.value)">
            <option value="none"       ${(p.driver_type || 'none') === 'none'       ? 'selected' : ''}>乗客</option>
            <option value="driver"     ${(p.driver_type || 'none') === 'driver'     ? 'selected' : ''}>ドライバー</option>
            <option value="sub_driver" ${(p.driver_type || 'none') === 'sub_driver' ? 'selected' : ''}>サブドライバー</option>
        </select>`;

        // 何限終わりか セレクト
        const classSelect = `<select class="form-select form-select-sm" style="width:auto;min-width:80px"
            onchange="updateParticipant(${p.id}, 'friday_last_class', this.value === '' ? null : parseInt(this.value))">
            <option value="" ${p.friday_last_class == null ? 'selected' : ''}>—</option>
            <option value="0" ${p.friday_last_class == 0 ? 'selected' : ''}>早出</option>
            ${[1,2,3,4,5,6].map(n => `<option value="${n}" ${p.friday_last_class == n ? 'selected' : ''}>${n}限後</option>`).join('')}
        </select>`;

        // 車を予約するか チェックボックス（ON時は乗車フラグも自動でON）
        const bookCheck = `<input type="checkbox" class="form-check-input" ${p.can_book_car == 1 ? 'checked' : ''}
            onchange="toggleCanBookCar(${p.id}, this.checked)">`;

        const timescarHtml = p.timescar_number
            ? `<span class="small">${escapeHtml(p.timescar_number)}</span>`
            : '<span class="text-muted small">—</span>';

        return `
        <tr>
            <td>${escapeHtml(p.name_kanji)}</td>
            <td class="text-center small">${escapeHtml(gradeGender)}</td>
            <td>${allergyHtml}</td>
            <td class="text-center">${joiningCheck}</td>
            <td>${roleSelect}</td>
            <td>${classSelect}</td>
            <td class="text-center">${bookCheck}</td>
            <td class="text-center small">${timescarHtml}</td>
            <td class="text-center">
                <input type="checkbox" ${p.pre_night ? 'checked' : ''}
                    onchange="updateParticipant(${p.id}, 'pre_night', this.checked)">
            </td>
            <td class="text-center">
                <input type="checkbox" ${p.lunch ? 'checked' : ''}
                    onchange="updateParticipant(${p.id}, 'lunch', this.checked)">
            </td>
            <td>
                <div class="d-flex gap-1">
                    ${p.status === 'waitlisted' ?
                      `<button class="btn btn-outline-success btn-sm py-0" onclick="confirmParticipant(${p.id})">確定</button>` : ''}
                    <button class="btn btn-outline-danger btn-sm py-0" onclick="deleteParticipant(${p.id})">削除</button>
                </div>
            </td>
        </tr>`;
    }).join('');

    const tableHtml = (rows, emptyMsg) => rows ? `<tbody>${rows}</tbody>` : `<tbody><tr><td colspan="12" class="text-muted">${emptyMsg}</td></tr></tbody>`;
    const thead = (cls) => `<thead class="${cls}">
        <tr>
            <th>名前</th>
            <th class="text-center">学年性別</th>
            <th>アレルギー</th>
            <th class="text-center">乗車</th>
            <th>役割</th>
            <th>時限</th>
            <th class="text-center">車予約</th>
            <th class="text-center">タイムズ番号</th>
            <th class="text-center">前泊</th>
            <th class="text-center">昼食</th>
            <th></th>
        </tr>
    </thead>`;

    document.getElementById('participantList').innerHTML = `
        <div class="table-responsive mb-3">
            <h6>確定参加者（${confirmed.length}人）</h6>
            <table class="table table-sm table-hover">
                ${thead('table-light')}
                ${tableHtml(makeRows(confirmed), '確定参加者なし')}
            </table>
        </div>
        ${waitlisted.length > 0 ? `
        <div class="table-responsive">
            <h6 class="text-warning"><i class="bi bi-clock"></i> キャンセル待ち（${waitlisted.length}人）</h6>
            <table class="table table-sm table-hover">
                ${thead('table-warning')}
                ${tableHtml(makeRows(waitlisted), '')}
            </table>
        </div>
        ` : ''}
    `;

    // アレルギーまとめセクション
    const allergyList = participants.filter(p => p.allergy);
    const allergyEl   = document.getElementById('allergySummary');
    if (allergyList.length > 0) {
        allergyEl.innerHTML = `
            <div class="card border-danger">
                <div class="card-header bg-danger text-white py-2">
                    <i class="bi bi-exclamation-triangle-fill"></i> アレルギーのある参加者（${allergyList.length}名）
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>名前</th><th>学年性別</th><th>アレルギー内容</th></tr>
                        </thead>
                        <tbody>
                            ${allergyList.map(p => `
                                <tr>
                                    <td>${escapeHtml(p.name_kanji)}</td>
                                    <td class="text-center small">${escapeHtml(getGradeGenderLabel(p.grade, p.gender))}</td>
                                    <td class="text-danger">${escapeHtml(p.allergy)}</td>
                                </tr>`).join('')}
                        </tbody>
                    </table>
                </div>
            </div>`;
    } else {
        allergyEl.innerHTML = '';
    }
}


async function confirmParticipant(pid) {
    await updateParticipant(pid, 'status', 'confirmed');
}

let memberSearchTimer = null;

function showAddParticipantModal() {
    document.getElementById('participantSearchInput').value = '';
    document.getElementById('participantSearchResults').innerHTML = '';
    document.getElementById('newParticipantMemberId').value = '';
    addParticipantModal.show();
}

function searchMembers(query) {
    clearTimeout(memberSearchTimer);
    const resultsEl = document.getElementById('participantSearchResults');
    document.getElementById('newParticipantMemberId').value = '';

    if (!query.trim()) {
        resultsEl.innerHTML = '';
        return;
    }

    resultsEl.innerHTML = '<div class="text-muted small mt-1">検索中...</div>';

    memberSearchTimer = setTimeout(async () => {
        try {
            const res  = await fetch(`/api/members?search=${encodeURIComponent(query.trim())}&per_page=20`);
            const data = await res.json();
            if (!data.success) { resultsEl.innerHTML = '<div class="text-danger small mt-1">取得に失敗しました</div>'; return; }

            const existingIds = new Set(participantsCache.map(p => parseInt(p.member_id)));
            const members = (data.data.members || []).filter(m => !existingIds.has(parseInt(m.id)));

            if (members.length === 0) {
                resultsEl.innerHTML = '<div class="text-muted small mt-1">該当する会員が見つかりません</div>';
                return;
            }

            resultsEl.innerHTML = '<div class="list-group mt-1">' +
                members.map(m => `
                    <button type="button" class="list-group-item list-group-item-action py-2"
                            onclick="selectParticipantMember(${m.id}, '${escapeHtml(m.name_kanji)}')">
                        ${escapeHtml(m.name_kanji)}
                        <span class="text-muted small ms-2">${escapeHtml(m.name_kana)}</span>
                    </button>
                `).join('') +
            '</div>';
        } catch (err) {
            resultsEl.innerHTML = '<div class="text-danger small mt-1">取得に失敗しました</div>';
        }
    }, 300);
}

function selectParticipantMember(id, name) {
    document.getElementById('newParticipantMemberId').value = id;
    document.getElementById('participantSearchInput').value = name;
    document.getElementById('participantSearchResults').innerHTML =
        `<div class="alert alert-success py-1 mt-1 small">選択中: ${escapeHtml(name)}</div>`;
}

async function addParticipant() {
    const memberId = document.getElementById('newParticipantMemberId').value;
    if (!memberId) {
        alert('会員を選択してください');
        return;
    }

    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/participants`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ member_id: parseInt(memberId) }),
        });
        const result = await res.json();
        if (result.success) {
            addParticipantModal.hide();
            participantsLoaded = false;
            await loadParticipants();
            showToast('参加者を追加しました');
        } else {
            alert(result.error?.message || '追加に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

async function updateParticipant(pid, field, value) {
    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/participants/${pid}`, {
            method:  'PUT',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ [field]: value }),
        });
        const result = await res.json();
        if (!result.success) {
            alert(result.error?.message || '更新に失敗しました');
            // 再ロードして状態を元に戻す
            participantsLoaded = false;
            loadParticipants();
        } else {
            showToast('更新しました', 'success', 1500);
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

// 車予約チェックの切り替え。ON にしたら「車に乗る（is_joining_car）」も自動で ON にする
// （車を予約する＝その車に乗る、というデータ整合性を保つ）。
async function toggleCanBookCar(pid, checked) {
    const payload = checked
        ? { can_book_car: 1, is_joining_car: 1 }
        : { can_book_car: 0 };
    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/participants/${pid}`, {
            method:  'PUT',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        });
        const result = await res.json();
        if (!result.success) {
            alert(result.error?.message || '更新に失敗しました');
            participantsLoaded = false;
            loadParticipants();
        } else {
            showToast('更新しました', 'success', 1500);
            // 乗車フラグも変えた場合は表示を最新化（乗車チェックの見た目を同期）
            if (checked) {
                participantsLoaded = false;
                loadParticipants();
            }
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

async function deleteParticipant(pid) {
    if (!confirm('この参加者を削除しますか？')) return;

    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/participants/${pid}`, { method: 'DELETE' });
        const result = await res.json();
        if (result.success) {
            participantsLoaded = false;
            await loadParticipants();
            showToast('削除しました');
        } else {
            alert(result.error?.message || '削除に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

// ==============================
// タブ3: 車割
// ==============================
// 推奨下車駅キャッシュ: { member_id: { drop_station: "高田馬場"|..., nearest_station: "練馬"|... } | null }
window._idealStations = null;

async function resolveStations() {
    const btn    = document.getElementById('resolveStationsBtn');
    const status = document.getElementById('resolveStationsStatus');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    status.textContent = '住所を解析中...（数秒かかる場合があります）';

    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/cars/resolve-stations`, { method: 'POST' });
        const result = await res.json();
        if (result.success) {
            window._idealStations = result.data || {};
            // 車リストを再描画してバッジを反映
            carsLoaded = false;
            await loadCars();
            const resolved = Object.values(window._idealStations).filter(v => v !== null).length;
            const total    = Object.keys(window._idealStations).length;
            status.textContent = `解析完了（${resolved}/${total}人）`;
            btn.innerHTML = '<i class="bi bi-geo-fill"></i> 再解析';
        } else {
            status.textContent = '解析に失敗しました';
            btn.innerHTML = '<i class="bi bi-geo-fill"></i> 解析';
        }
    } catch (err) {
        status.textContent = '通信エラーが発生しました';
        btn.innerHTML = '<i class="bi bi-geo-fill"></i> 解析';
    } finally {
        btn.disabled = false;
    }
}

async function loadCars() {
    if (carsLoaded) return;
    carsLoaded = true;

    // 参加者キャッシュが空の場合は取得する
    if (participantsCache.length === 0) {
        try {
            const res  = await fetch(`/api/expeditions/${expeditionId}/participants`);
            const data = await res.json();
            if (data.success) participantsCache = data.data || [];
        } catch (err) {
            console.error(err);
        }
    }

    try {
        const res  = await fetch(`/api/expeditions/${expeditionId}/cars`);
        const data = await res.json();
        if (data.success) {
            renderCars(data.data || []);
        } else {
            document.getElementById('carListOutbound').innerHTML = '<div class="alert alert-danger">読み込みに失敗しました</div>';
            document.getElementById('carListReturn').innerHTML = '';
        }
    } catch (err) {
        console.error(err);
        document.getElementById('carListOutbound').innerHTML = '<div class="alert alert-danger">通信エラーが発生しました</div>';
        document.getElementById('carListReturn').innerHTML = '';
    }
}

function renderCarList(cars, tripType) {
    const targetId = tripType === 'return' ? 'carListReturn' : 'carListOutbound';
    if (cars.length === 0) {
        document.getElementById(targetId).innerHTML = '<div class="alert alert-info">車がまだ登録されていません</div>';
        return;
    }

    const roleLabel    = { driver: 'ドライバー', sub_driver: 'サブドライバー', passenger: '乗客' };
    const stationColor = { '高田馬場': 'primary', '新宿': 'success', '渋谷': 'warning', '東京': 'danger' };

    const html = cars.map(car => {
        const isReturn = car.trip_type === 'return';

        // 乗員リスト
        const membersHtml = (car.car_members || []).map(m => {
            // 3列目: 復路=推奨下車駅＋最寄り駅、往路/両路=時限
            let col3;
            if (isReturn) {
                const stObj = window._idealStations?.[m.member_id];
                if (stObj) {
                    const dropBadge    = stObj.drop_station
                        ? `<span class="badge bg-${stationColor[stObj.drop_station] || 'secondary'} me-1">${stObj.drop_station}</span>`
                        : '';
                    const nearestBadge = stObj.nearest_station
                        ? `<span class="badge bg-light text-dark border">${escapeHtml(stObj.nearest_station)}</span>`
                        : '';
                    col3 = `<div class="d-flex flex-wrap gap-1 justify-content-center">${dropBadge}${nearestBadge}</div>`;
                } else {
                    col3 = '<span class="text-muted">―</span>';
                }
            } else {
                col3 = m.friday_last_class == null ? '―'
                     : m.friday_last_class == 0    ? '早出'
                     : m.friday_last_class + '限';
            }
            const timescarHtml = m.timescar_number
                ? `<span class="small">${escapeHtml(m.timescar_number)}</span>`
                : '<span class="text-muted small">—</span>';
            return `
            <tr>
                <td>${escapeHtml(m.name_kanji)}</td>
                <td>
                    <select class="form-select form-select-sm" onchange="updateCarMember(${car.id}, ${m.id}, 'role', this.value)">
                        <option value="driver"     ${m.role === 'driver'     ? 'selected' : ''}>ドライバー</option>
                        <option value="sub_driver" ${m.role === 'sub_driver' ? 'selected' : ''}>サブドライバー</option>
                        <option value="passenger"  ${m.role === 'passenger'  ? 'selected' : ''}>乗客</option>
                    </select>
                </td>
                <td class="text-center small text-muted">${col3}</td>
                <td class="text-center small">${timescarHtml}</td>
                <td>
                    <button class="btn btn-outline-danger btn-sm py-0" onclick="deleteCarMember(${car.id}, ${m.id})">削除</button>
                </td>
            </tr>
        `; }).join('');

        const depLabel  = (car.trip_type === 'outbound' && car.departure_class != null)
            ? `<span class="badge bg-success ms-1">${car.departure_class == 0 ? '早出' : car.departure_class + '限後出発'}</span>`
            : '';
        const tripBadge = car.trip_type === 'outbound' ? '<span class="badge bg-primary ms-2">往路</span>' + depLabel
                        : car.trip_type === 'return'   ? '<span class="badge bg-secondary ms-2">復路</span>'
                        : '';

        return `
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><strong>${escapeHtml(car.name)}</strong>${tripBadge}</span>
                    <button class="btn btn-outline-danger btn-sm" onclick="deleteCar(${car.id})">車を削除</button>
                </div>
                <div class="card-body">
                    <p class="mb-1">
                        定員: ${car.capacity}人
                        レンタカー代: ¥${Number(car.rental_fee || 0).toLocaleString()}
                        高速代: ¥${Number(car.highway_fee || 0).toLocaleString()}
                    </p>

                    <h6 class="mt-3">乗員</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>名前</th>
                                    <th>役割</th>
                                    <th class="text-center">${isReturn ? '推奨下車駅 / 最寄り駅' : '時限'}</th>
                                    <th class="text-center">タイムズ番号</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>${membersHtml || '<tr><td colspan="5" class="text-muted">乗員なし</td></tr>'}</tbody>
                        </table>
                    </div>
                    <button class="btn btn-outline-primary btn-sm mb-3" onclick="showAddCarMemberModal(${car.id})">+ 乗員を追加</button>

                </div>
            </div>
        `;
    }).join('');

    document.getElementById(targetId).innerHTML = html;
}

function renderCars(cars) {
    carsCache = cars; // 重複チェック用にキャッシュ
    const outbound = cars.filter(c => c.trip_type !== 'return');
    const returning = cars.filter(c => c.trip_type === 'return');
    renderCarList(outbound,  'outbound');
    renderCarList(returning, 'return');
}

function showAddCarModal(tripType = 'both') {
    document.getElementById('newCarName').value        = '';
    document.getElementById('newCarCapacity').value    = '6';
    document.getElementById('newCarRentalFee').value   = '0';
    document.getElementById('newCarHighwayFee').value  = '0';
    document.getElementById('newCarTripType').value    = tripType;
    document.getElementById('newCarDepartureClass').value = '6';
    document.getElementById('departureClassField').style.display = (tripType === 'outbound') ? 'block' : 'none';
    addCarModal.show();
}

async function addCar() {
    const name = document.getElementById('newCarName').value.trim();
    if (!name) {
        alert('車名を入力してください');
        return;
    }

    const tripType = document.getElementById('newCarTripType').value || 'both';
    const data = {
        name:            name,
        capacity:        parseInt(document.getElementById('newCarCapacity').value)   || 6,
        rental_fee:      parseInt(document.getElementById('newCarRentalFee').value)  || 0,
        highway_fee:     parseInt(document.getElementById('newCarHighwayFee').value) || 0,
        trip_type:       tripType,
        departure_class: tripType === 'outbound'
            ? parseInt(document.getElementById('newCarDepartureClass').value, 10)
            : null,
    };

    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/cars`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(data),
        });
        const result = await res.json();
        if (result.success) {
            addCarModal.hide();
            carsLoaded = false;
            await loadCars();
            showToast('車を追加しました');
        } else {
            alert(result.error?.message || '追加に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

async function autoAssignOutboundCars() {
    try {
        const res  = await fetch(`/api/expeditions/${expeditionId}/participants`);
        const data = await res.json();
        if (!data.success) { alert('参加者の取得に失敗しました'); return; }

        const allParticipants = data.data || [];
        // 車予約にチェックがある人は対象にする（is_joining_car は問わない。
        // 車を予約する＝その車に乗るため、後から車予約だけ付けた人も漏れなく表示する）。
        const bookers = allParticipants.filter(p => parseInt(p.can_book_car) === 1);
        if (bookers.length === 0) {
            alert('車を予約できる人が登録されていません。\n参加者管理タブで「車予約」にチェックを入れてください。');
            return;
        }

        // ドライバー能力がある参加者の総数（役割がドライバーまたはサブドライバー）
        const totalDrivers = allParticipants.filter(p =>
            p.driver_type === 'driver' || p.driver_type === 'sub_driver'
        ).length;

        window._autoAssignBookers     = bookers;
        window._autoAssignTotalDrivers = totalDrivers;
        // 車に乗る対象の全参加者（固定指定の「人」プルダウン用）
        window._autoAssignAllParticipants = allParticipants.filter(p =>
            parseInt(p.is_joining_car) === 1 || parseInt(p.can_book_car) === 1
        );
        // 初期状態は全員「使用」。チェック状態を member_id => bool で保持
        window._autoAssignUseCar = {};
        bookers.forEach(b => { window._autoAssignUseCar[b.member_id] = true; });
        // 各車の定員（member_id => 人数）と一人運転の選択（member_id => bool）。
        // 再描画でDOMが作り直されても入力値を保持するためにここで管理する。
        window._autoAssignCapacities = {};
        window._autoAssignSolo       = {};
        // 乗員の固定指定: [{ member_id, car_owner_id }, ...]
        window._autoAssignPinned = [];

        renderAutoAssignBookerList();
        carAutoAssignModal.show();
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

// 自動生成モーダルの車一覧を描画（「使用」チェックの状態に応じて台数・不足数を再計算）
function renderAutoAssignBookerList() {
    // 再描画でDOMが消える前に、入力済みの値（固定指定・定員・一人運転）を保存しておく
    syncPinnedFromDom();
    syncBookerSettingsFromDom();

    const bookers      = window._autoAssignBookers || [];
    const totalDrivers = window._autoAssignTotalDrivers || 0;
    const useCar       = window._autoAssignUseCar || {};

    // 実際に使う（チェックされた）車だけを対象に台数・不足数を計算
    const selected  = bookers.filter(b => useCar[b.member_id]);
    const carCount  = selected.length;
    const shortfall = carCount * 2 - totalDrivers; // 不足ドライバー数（使用車ベース）
    window._autoAssignShortfall = shortfall;

    const classLabel = (v) => v == null ? '不明' : v == 0 ? '早出' : `${v}限後出発`;

    const savedCaps = window._autoAssignCapacities || {};
    const savedSolo = window._autoAssignSolo || {};

    const rows = bookers.map(b => {
        const on = !!useCar[b.member_id];
        // 保存済みの定員があればそれを、なければデフォルト6を使う
        const capVal   = savedCaps[b.member_id] != null ? savedCaps[b.member_id] : 6;
        const soloOn   = !!savedSolo[b.member_id];
        return `
            <tr class="${on ? '' : 'table-secondary text-muted'}">
                <td class="text-center">
                    <input type="checkbox" class="form-check-input use-car-check" id="bookerUse_${b.member_id}"
                           value="${b.member_id}" ${on ? 'checked' : ''}
                           onchange="toggleAutoAssignUseCar(${b.member_id}, this.checked)">
                </td>
                <td>${escapeHtml(b.name_kanji)}</td>
                <td><span class="badge bg-success">${classLabel(b.friday_last_class)}</span></td>
                <td>
                    <div class="input-group input-group-sm" style="width:110px;">
                        <input type="number" class="form-control" id="bookerCap_${b.member_id}"
                               value="${capVal}" min="1" max="20" ${on ? '' : 'disabled'}>
                        <span class="input-group-text">人</span>
                    </div>
                </td>
                ${shortfall > 0 ? `<td class="text-center">
                    ${on ? `<input type="checkbox" class="form-check-input solo-check" id="bookerSolo_${b.member_id}"
                           value="${b.member_id}" ${soloOn ? 'checked' : ''}>` : ''}
                </td>` : ''}
            </tr>`;
    }).join('');

    const soloColHeader = shortfall > 0
        ? `<th class="text-center">一人運転<span class="text-muted small ms-1">（${shortfall}台選択）</span></th>`
        : '';

    let statusAlert;
    if (carCount === 0) {
        statusAlert = `
            <div class="alert alert-danger py-2 small mb-2">
                <i class="bi bi-exclamation-triangle"></i>
                車を1台も使用しない設定です。少なくとも1台は「使用」にチェックしてください。
            </div>`;
    } else if (shortfall > 0) {
        statusAlert = `
            <div class="alert alert-warning py-2 small mb-2">
                <i class="bi bi-exclamation-triangle"></i>
                ドライバー能力のある人が合計 ${totalDrivers} 人で、使用する車 ${carCount} 台に2人ずつ配置するには ${shortfall} 人不足しています。<br>
                <strong>一人で運転する車を ${shortfall} 台選んでください。</strong>
            </div>`;
    } else {
        statusAlert = `
            <div class="alert alert-success py-2 small mb-2">
                <i class="bi bi-check-circle"></i>
                ドライバー ${totalDrivers} 人 / 使用する車 ${carCount} 台。サブドライバーは自動で割り当てます。
            </div>`;
    }

    document.getElementById('autoAssignBookerList').innerHTML = `
        ${statusAlert}
        <div class="table-responsive">
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">使用</th>
                        <th>車を借りる人</th>
                        <th>出発時限</th>
                        <th>定員</th>
                        ${soloColHeader}
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
        <div class="alert alert-info py-2 small mb-2">
            <i class="bi bi-info-circle"></i>
            既存の往路の車はすべて削除され、「使用」にチェックした車に置き換わります
        </div>
        ${renderPinnedSection(selected)}
    `;
}

// 乗員の固定指定セクションを組み立てる（特定の人を特定のドライバーの車に固定）
function renderPinnedSection(selectedBookers) {
    const allParticipants = window._autoAssignAllParticipants || [];
    const pinned          = window._autoAssignPinned || [];

    // 車の選択肢＝使用するドライバー（選択された booker）
    const carOptions = selectedBookers.map(b =>
        `<option value="${b.member_id}">${escapeHtml(b.name_kanji)}車</option>`
    ).join('');

    // 人の選択肢＝車に乗る全参加者
    const personOptions = allParticipants.map(p =>
        `<option value="${p.member_id}">${escapeHtml(p.name_kanji)}</option>`
    ).join('');

    const rows = pinned.map((pin, i) => `
        <div class="d-flex align-items-center gap-2 mb-2 pinned-row">
            <select class="form-select form-select-sm pinned-person" style="max-width:160px;">
                <option value="">― 参加者を選択 ―</option>
                ${allParticipants.map(p =>
                    `<option value="${p.member_id}" ${String(p.member_id) === String(pin.member_id) ? 'selected' : ''}>${escapeHtml(p.name_kanji)}</option>`
                ).join('')}
            </select>
            <span class="text-muted small">を</span>
            <select class="form-select form-select-sm pinned-car" style="max-width:160px;">
                <option value="">― 車を選択 ―</option>
                ${selectedBookers.map(b =>
                    `<option value="${b.member_id}" ${String(b.member_id) === String(pin.car_owner_id) ? 'selected' : ''}>${escapeHtml(b.name_kanji)}車</option>`
                ).join('')}
            </select>
            <span class="text-muted small">に固定</span>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePinnedRow(${i})">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>`).join('');

    return `
        <div class="border rounded p-2">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong class="small"><i class="bi bi-pin-angle"></i> 乗員の固定指定（任意）</strong>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPinnedRow()">
                    <i class="bi bi-plus-lg"></i> 固定を追加
                </button>
            </div>
            <div class="text-muted small mb-2">特定の人を特定の車に必ず乗せたい場合に指定します（残りは自動で割り当てます）。</div>
            ${rows || '<div class="text-muted small">指定なし</div>'}
        </div>`;
}

// 固定指定行の現在の選択値を DOM から読み取り、_autoAssignPinned に保存する
function syncPinnedFromDom() {
    const rows = document.querySelectorAll('#autoAssignBookerList .pinned-row');
    if (rows.length === 0) return; // 未描画なら何もしない（初回 or リセット時は既存値を維持）
    const pinned = [];
    rows.forEach(row => {
        pinned.push({
            member_id:    row.querySelector('.pinned-person')?.value || '',
            car_owner_id: row.querySelector('.pinned-car')?.value    || '',
        });
    });
    window._autoAssignPinned = pinned;
}

// 各車の定員・一人運転の現在の入力値を DOM から読み取り、再描画後も保持できるよう保存する。
// （固定追加やアラート解消などの再描画で入力値がデフォルトに戻るのを防ぐ）
function syncBookerSettingsFromDom() {
    if (!window._autoAssignCapacities) window._autoAssignCapacities = {};
    if (!window._autoAssignSolo)       window._autoAssignSolo       = {};
    const bookers = window._autoAssignBookers || [];
    bookers.forEach(b => {
        const capEl = document.getElementById(`bookerCap_${b.member_id}`);
        if (capEl && capEl.value !== '') {
            window._autoAssignCapacities[b.member_id] = parseInt(capEl.value, 10) || 6;
        }
        const soloEl = document.getElementById(`bookerSolo_${b.member_id}`);
        if (soloEl) {
            window._autoAssignSolo[b.member_id] = soloEl.checked;
        }
    });
}

function addPinnedRow() {
    syncPinnedFromDom();
    if (!window._autoAssignPinned) window._autoAssignPinned = [];
    window._autoAssignPinned.push({ member_id: '', car_owner_id: '' });
    renderAutoAssignBookerList();
}

function removePinnedRow(index) {
    syncPinnedFromDom();
    if (!window._autoAssignPinned) return;
    window._autoAssignPinned.splice(index, 1);
    renderAutoAssignBookerList();
}

// 「使用」チェック切り替え時：状態を保存して再描画（台数・不足数を再計算）
function toggleAutoAssignUseCar(memberId, checked) {
    if (!window._autoAssignUseCar) window._autoAssignUseCar = {};
    window._autoAssignUseCar[memberId] = checked;
    renderAutoAssignBookerList();
}

async function executeCarAutoAssign() {
    const allBookers = window._autoAssignBookers  || [];
    const shortfall  = window._autoAssignShortfall || 0;
    const useCar     = window._autoAssignUseCar || {};

    // 「使用」にチェックした車だけを対象にする
    const bookers = allBookers.filter(b => useCar[b.member_id]);
    if (bookers.length === 0) {
        alert('車を1台も使用しない設定です。少なくとも1台は「使用」にチェックしてください。');
        carAutoAssignModal.show();
        return;
    }

    const capacities     = {};
    const soloBookers     = [];
    const selectedBookers = [];

    for (const b of bookers) {
        selectedBookers.push(parseInt(b.member_id));
        capacities[b.member_id] = parseInt(document.getElementById(`bookerCap_${b.member_id}`).value, 10) || 6;
        if (shortfall > 0 && document.getElementById(`bookerSolo_${b.member_id}`)?.checked) {
            soloBookers.push(parseInt(b.member_id));
        }
    }

    // 不足台数ちょうど選択されているか確認
    if (shortfall > 0 && soloBookers.length !== shortfall) {
        alert(`一人で運転する車をちょうど ${shortfall} 台選んでください（現在 ${soloBookers.length} 台）`);
        carAutoAssignModal.show();
        return;
    }

    // 乗員の固定指定を収集（人 → 乗せる車のドライバーID）
    syncPinnedFromDom();
    const pinnedMembers = {};
    const selectedSet   = new Set(selectedBookers);
    for (const pin of (window._autoAssignPinned || [])) {
        const mid = parseInt(pin.member_id);
        const cid = parseInt(pin.car_owner_id);
        if (!pin.member_id || !pin.car_owner_id) continue; // 未入力行はスキップ
        if (!selectedSet.has(cid)) {
            alert('固定先の車が「使用する車」に含まれていません。指定を見直してください。');
            carAutoAssignModal.show();
            return;
        }
        if (pinnedMembers[mid] !== undefined) {
            alert('同じ人を複数の車に固定することはできません。');
            carAutoAssignModal.show();
            return;
        }
        pinnedMembers[mid] = cid;
    }

    // 定員合計チェック
    const totalCapacity = Object.values(capacities).reduce((s, v) => s + v, 0);
    const joiningCount  = (participantsCache || []).filter(p => parseInt(p.is_joining_car) === 1).length;
    if (joiningCount > 0 && totalCapacity < joiningCount) {
        const ok = confirm(
            `定員合計（${totalCapacity}人）が乗車予定人数（${joiningCount}人）より少ないです。\n` +
            `${joiningCount - totalCapacity}人が最後の車に溢れる可能性があります。\n\nこのまま実行しますか？`
        );
        if (!ok) { carAutoAssignModal.show(); return; }
    }

    carAutoAssignModal.hide();

    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/cars/auto-assign`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ capacities, solo_bookers: soloBookers, selected_bookers: selectedBookers, pinned_members: pinnedMembers }),
        });
        const result = await res.json();
        if (result.success) {
            carsLoaded = false;
            await loadCars();
            const warnings = result.data?.warnings || [];
            if (warnings.length > 0) {
                showToast('往路を自動作成しました（警告あり）', 'warning');
                alert('【警告】\n' + warnings.join('\n'));
            } else {
                showToast('往路を自動作成しました');
            }
        } else {
            alert(result.error?.message || '自動作成に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

async function showAutoAssignReturnModal() {
    document.getElementById('autoAssignReturnDriverList').innerHTML = '読み込み中...';
    autoAssignReturnModal.show();

    try {
        const res  = await fetch(`/api/expeditions/${expeditionId}/participants`);
        const data = await res.json();
        if (!data.success) {
            document.getElementById('autoAssignReturnDriverList').innerHTML =
                '<div class="alert alert-danger small">参加者の取得に失敗しました</div>';
            return;
        }

        const drivers = (data.data || []).filter(p => parseInt(p.can_book_car) === 1);
        if (drivers.length === 0) {
            document.getElementById('autoAssignReturnDriverList').innerHTML =
                '<div class="alert alert-warning small">車を予約できる人が登録されていません。<br>申し込み時に「車の予約をする」を選択した参加者が必要です。</div>';
            document.getElementById('autoAssignReturnExecBtn').disabled = true;
            return;
        }

        document.getElementById('autoAssignReturnExecBtn').disabled = false;
        window._autoAssignReturnDrivers = drivers;
        window._autoAssignReturnStations = null;

        // モードに応じてテーブルを描画（stationMap=null は解析中）
        const makeRows = (stationMap, showStation) => drivers.map(d => {
            // stationMap の値は { drop_station, nearest_station } | null
            const stObj       = stationMap?.[d.member_id];
            const autoStation = stObj?.drop_station || '';
            const stations = ['高田馬場', '新宿', '渋谷', '東京'];
            const options = stations.map(s =>
                `<option value="${s}" ${autoStation === s ? 'selected' : ''}>${s}</option>`
            ).join('');
            const stationCell = showStation ? `
                <td>
                    <select class="form-select form-select-sm" id="returnStation_${d.member_id}" style="width:110px;">
                        ${options}
                    </select>
                    ${stationMap === null ? `<span class="text-muted" style="font-size:.75em;">解析中...</span>` : ''}
                </td>` : `<td style="display:none;"><input type="hidden" id="returnStation_${d.member_id}" value=""></td>`;
            return `
            <tr>
                <td>${escapeHtml(d.name_kanji)}</td>
                ${stationCell}
                <td>
                    <div class="input-group input-group-sm" style="width:100px;">
                        <input type="number" class="form-control" id="returnCap_${d.member_id}"
                               value="6" min="1" max="20">
                        <span class="input-group-text">人</span>
                    </div>
                </td>
            </tr>`;
        }).join('');

        const renderTable = (stationMap) => {
            const mode = document.querySelector('input[name="returnAssignMode"]:checked')?.value || 'by_station';
            const showStation = mode === 'by_station';
            const stationHeader = showStation ? '<th>下車駅（自動判定・変更可）</th>' : '';
            document.getElementById('autoAssignReturnDriverList').innerHTML = `
                <div class="table-responsive mb-3">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr><th>車を借りる人</th>${stationHeader}<th>定員</th></tr>
                        </thead>
                        <tbody>${makeRows(stationMap, showStation)}</tbody>
                    </table>
                </div>`;
        };

        // モード切替時に再描画
        document.querySelectorAll('input[name="returnAssignMode"]').forEach(radio => {
            radio.addEventListener('change', () => renderTable(window._autoAssignReturnStations ?? null));
        });

        renderTable(null); // まず空で描画

        // 住所から駅を自動判定して初期値をセット（by_station 用）
        try {
            const sRes = await fetch(`/api/expeditions/${expeditionId}/cars/resolve-stations`, { method: 'POST' });
            const sData = await sRes.json();
            if (sData.success) {
                const driverIds = new Set(drivers.map(d => String(d.member_id)));
                window._autoAssignReturnStations = Object.fromEntries(
                    Object.entries(sData.data || {}).filter(([id]) => driverIds.has(id))
                );
                renderTable(window._autoAssignReturnStations);
            }
        } catch (_) { /* 解析失敗しても続行 */ }

    } catch (err) {
        document.getElementById('autoAssignReturnDriverList').innerHTML =
            '<div class="alert alert-danger small">通信エラーが発生しました</div>';
    }
}

async function autoAssignReturnCars() {
    const drivers = window._autoAssignReturnDrivers || [];
    const capacities        = {};
    const preferredStations = {};
    for (const d of drivers) {
        capacities[d.member_id] = parseInt(document.getElementById(`returnCap_${d.member_id}`)?.value, 10) || 6;
        const station = document.getElementById(`returnStation_${d.member_id}`)?.value || '';
        if (station) preferredStations[d.member_id] = station;
    }

    // 定員合計チェック
    const totalCapacity = Object.values(capacities).reduce((s, v) => s + v, 0);
    const joiningCount  = (participantsCache || []).filter(p => parseInt(p.is_joining_car) === 1).length;
    if (joiningCount > 0 && totalCapacity < joiningCount) {
        const ok = confirm(
            `定員合計（${totalCapacity}人）が乗車予定人数（${joiningCount}人）より少ないです。\n` +
            `${joiningCount - totalCapacity}人が最後の車に溢れる可能性があります。\n\nこのまま実行しますか？`
        );
        if (!ok) return;
    }

    const mode = document.querySelector('input[name="returnAssignMode"]:checked')?.value || 'by_station';
    autoAssignReturnModal.hide();

    const btn = document.getElementById('autoAssignReturnExecBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> 処理中...';

    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/cars/auto-assign-return`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ mode, capacities, preferred_stations: preferredStations }),
        });
        const result = await res.json();
        if (result.success) {
            // 推奨下車駅を解析してから車リストを描画
            carsLoaded = false;
            window._idealStations = null;
            try {
                await resolveStations(); // 成功すれば内部で loadCars() まで呼ぶ
            } catch (_) {
                await loadCars(); // 解析失敗でも車リストは必ず更新
            }
            document.querySelector('[href="#carTabReturn"]')?.click();
            const warnings = result.data?.warnings || [];
            if (warnings.length > 0) {
                showToast('復路を自動作成しました（警告あり）', 'warning');
                alert('【警告】\n' + warnings.join('\n'));
            } else {
                showToast('復路を自動作成しました');
            }
        } else {
            alert(result.error?.message || '自動作成に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-play-fill"></i> 実行';
    }
}

async function deleteCar(cid) {
    if (!confirm('この車を削除しますか？')) return;

    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/cars/${cid}`, { method: 'DELETE' });
        const result = await res.json();
        if (result.success) {
            carsLoaded = false;
            await loadCars();
            showToast('削除しました');
        } else {
            alert(result.error?.message || '削除に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

// 車データキャッシュ（重複チェック用）
let carsCache = [];

function showAddCarMemberModal(cid) {
    currentCarId = cid;
    // 現在の車に既に乗っている member_id だけ除外（往路・復路は別車に乗れる）
    const thisCar = carsCache.find(c => c.id === cid);
    const assignedInThisCar = new Set(
        (thisCar?.car_members || []).map(m => parseInt(m.member_id))
    );
    const sel = document.getElementById('carMemberSelect');
    const options = participantsCache.filter(p => !assignedInThisCar.has(parseInt(p.member_id)));
    if (options.length === 0) {
        alert('全ての参加者がすでにこの車に追加済みです');
        return;
    }
    sel.innerHTML = options.map(p =>
        `<option value="${p.member_id}">${escapeHtml(p.name_kanji)}</option>`
    ).join('');
    document.getElementById('carMemberRole').value = 'passenger';
    addCarMemberModal.show();
}

async function addCarMember() {
    const pid  = document.getElementById('carMemberSelect').value;
    const role = document.getElementById('carMemberRole').value;
    if (!pid) { alert('参加者を選択してください'); return; }

    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/cars/${currentCarId}/members`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ member_id: parseInt(pid), role }),
        });
        const result = await res.json();
        if (result.success) {
            addCarMemberModal.hide();
            carsLoaded = false;
            await loadCars();
            showToast('乗員を追加しました');
        } else {
            alert(result.error?.message || '追加に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

async function updateCarMember(cid, mid, field, value) {
    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/cars/${cid}/members/${mid}`, {
            method:  'PUT',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ [field]: value }),
        });
        const result = await res.json();
        if (!result.success) {
            alert(result.error?.message || '更新に失敗しました');
            carsLoaded = false;
            loadCars();
        } else {
            showToast('更新しました', 'success', 1500);
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

async function deleteCarMember(cid, mid) {
    if (!confirm('乗員を削除しますか？')) return;

    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/cars/${cid}/members/${mid}`, { method: 'DELETE' });
        const result = await res.json();
        if (result.success) {
            carsLoaded = false;
            await loadCars();
            showToast('削除しました');
        } else {
            alert(result.error?.message || '削除に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

// ==============================
// タブ4: チーム分け
// ==============================
async function loadTeams() {
    if (teamsLoaded) return;
    teamsLoaded = true;

    try {
        const res  = await fetch(`/api/expeditions/${expeditionId}/teams`);
        const data = await res.json();
        if (data.success) {
            renderTeams(data.data);
        } else {
            document.getElementById('teamBoard').innerHTML = '<div class="alert alert-danger">読み込みに失敗しました</div>';
        }
    } catch (err) {
        console.error(err);
        document.getElementById('teamBoard').innerHTML = '<div class="alert alert-danger">通信エラーが発生しました</div>';
    }
}

/**
 * チームメンバーをOB/OG→4年→3年→2年→1年の順にソートする
 * OB/OG（grade=0）は入学年昇順（古い=先輩が先）
 */
function sortTeamMembers(members) {
    return [...members].sort((a, b) => {
        const ga = parseInt(a.grade) || 0;
        const gb = parseInt(b.grade) || 0;
        // OB/OG=0を先頭、現役は学年降順（4>3>2>1）
        const rankA = ga === 0 ? -1 : (5 - ga);
        const rankB = gb === 0 ? -1 : (5 - gb);
        if (rankA !== rankB) return rankA - rankB;
        // OB/OG同士は入学年昇順（古い先輩が先）
        if (ga === 0) {
            return (parseInt(a.enrollment_year) || 9999) - (parseInt(b.enrollment_year) || 9999);
        }
        return 0;
    });
}

function renderTeams(teamsData) {
    const board      = document.getElementById('teamBoard');
    const unassigned = teamsData.unassigned || [];
    const teams      = teamsData.teams      || [];

    // チーム選択肢（未割り当てメンバーの行で使用）
    const teamOptions = teams.map(t =>
        `<option value="${t.id}">${escapeHtml(t.name)}</option>`
    ).join('');

    let html = '';

    // 未割り当てカード
    html += `
        <div class="card" style="min-width:240px; max-width:320px;">
            <div class="card-header fw-bold">未割り当て（${unassigned.length}人）</div>
            <ul class="list-group list-group-flush" id="team-unassigned">`;

    if (unassigned.length === 0) {
        html += `<li class="list-group-item text-muted py-2 px-2">全員割り当て済み</li>`;
    } else {
        unassigned.forEach(p => {
            html += `
                <li class="list-group-item py-2 px-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="flex-grow-1">${escapeHtml(p.name_kanji)}</span>
                        ${teams.length > 0 ? `
                        <select class="form-select form-select-sm" style="width:auto;" id="team-select-${p.member_id}">
                            ${teamOptions}
                        </select>
                        <button class="btn btn-sm btn-primary" onclick="assignToTeam(${p.member_id})">追加</button>
                        ` : '<span class="text-muted small">チームなし</span>'}
                    </div>
                </li>`;
        });
    }

    html += `</ul></div>`;

    // 各チームカード
    teams.forEach(team => {
        html += `
            <div class="card" style="min-width:200px; max-width:280px;">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <span class="team-name fw-bold flex-grow-1" id="team-name-${team.id}">${escapeHtml(team.name)}</span>
                    <small class="text-muted text-nowrap">${(team.members || []).length}人</small>
                    <button class="btn btn-sm btn-outline-secondary py-0 px-1" title="チーム名を編集"
                            onclick="startEditTeamName(${team.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger py-0 px-1" title="チームを削除"
                            onclick="deleteTeam(${team.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <ul class="list-group list-group-flush">`;

        if ((team.members || []).length === 0) {
            html += `<li class="list-group-item text-muted py-2 px-2">メンバーなし</li>`;
        } else {
            sortTeamMembers(team.members || []).forEach(m => {
                html += `
                    <li class="list-group-item py-1 px-2 d-flex justify-content-between align-items-center">
                        <span>${escapeHtml(m.name_kanji)}</span>
                        <button class="btn btn-sm btn-outline-danger py-0" onclick="removeFromTeamWithIds(${team.id}, ${m.id})">外す</button>
                    </li>`;
            });
        }

        html += `</ul></div>`;
    });

    board.innerHTML = html;
}

// 未割り当てメンバーをチームに追加
async function assignToTeam(memberMemberId) {
    const select = document.getElementById(`team-select-${memberMemberId}`);
    if (!select) return;
    const teamId = parseInt(select.value);
    if (!teamId) return;

    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/teams/${teamId}/members`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ member_id: memberMemberId }),
        });
        const result = await res.json();
        if (!result.success) {
            alert('追加に失敗しました: ' + (result.error?.message || ''));
            return;
        }
    } catch (err) {
        console.error(err);
        alert('通信エラーが発生しました');
        return;
    }
    teamsLoaded = false;
    await loadTeams();
}

// チームメンバーを外す（teamId: チームID、teamMemberId: expedition_team_members.id）
async function removeFromTeamWithIds(teamId, teamMemberId) {
    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/teams/${teamId}/members/${teamMemberId}`, {
            method: 'DELETE',
        });
        const result = await res.json();
        if (!result.success) {
            alert('削除に失敗しました: ' + (result.error?.message || ''));
            return;
        }
    } catch (err) {
        console.error(err);
        alert('通信エラーが発生しました');
        return;
    }
    teamsLoaded = false;
    await loadTeams();
}

function showAddTeamModal() {
    document.getElementById('newTeamName').value = '';
    addTeamModal.show();
}

async function addTeam() {
    const name = document.getElementById('newTeamName').value.trim();
    if (!name) { alert('チーム名を入力してください'); return; }

    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/teams`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ name }),
        });
        const result = await res.json();
        if (result.success) {
            addTeamModal.hide();
            teamsLoaded = false;
            await loadTeams();
            showToast('チームを追加しました');
        } else {
            alert(result.error?.message || '追加に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

// チーム名のインライン編集
function startEditTeamName(teamId) {
    const span = document.getElementById(`team-name-${teamId}`);
    if (!span || span.querySelector('input')) return; // 既に編集中なら無視

    const currentName = span.textContent.trim();

    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'form-control form-control-sm';
    input.value = currentName;
    input.dataset.original = currentName;

    input.addEventListener('blur', () => saveTeamName(teamId, input));
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); input.blur(); }
        if (e.key === 'Escape') { cancelTeamName(teamId, input); }
    });

    span.textContent = '';
    span.appendChild(input);
    input.focus();
    input.select();
}

async function saveTeamName(teamId, input) {
    const name     = input.value.trim();
    const original = input.dataset.original || '';

    if (!name) {
        cancelTeamName(teamId, input);
        return;
    }
    if (name === original) {
        cancelTeamName(teamId, input);
        return;
    }

    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/teams/${teamId}`, {
            method:  'PUT',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ name }),
        });
        const result = await res.json();
        if (result.success) {
            const span = document.getElementById(`team-name-${teamId}`);
            if (span) span.textContent = name;
            showToast('チーム名を更新しました', 'success', 1500);
        } else {
            alert(result.error?.message || '更新に失敗しました');
            cancelTeamName(teamId, input);
        }
    } catch (err) {
        alert('通信エラーが発生しました');
        cancelTeamName(teamId, input);
    }
}

function cancelTeamName(teamId, input) {
    const span = document.getElementById(`team-name-${teamId}`);
    if (span) span.textContent = input.dataset.original || '';
}

// チーム削除
async function deleteTeam(teamId) {
    if (!confirm('このチームを削除しますか？\nメンバーは未割り当てに戻ります。')) return;

    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/teams/${teamId}`, { method: 'DELETE' });
        const result = await res.json();
        if (result.success) {
            teamsLoaded = false;
            await loadTeams();
            showToast('チームを削除しました');
        } else {
            alert(result.error?.message || '削除に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

// 自動割り当てモーダルを表示
let autoAssignData = null; // プレビュー用データキャッシュ

async function showAutoAssignModal() {
    autoAssignData = null;
    document.getElementById('autoAssignPreview').innerHTML = '読み込み中...';
    document.getElementById('autoAssignExecBtn').disabled = true;
    autoAssignModal.show();

    try {
        const res  = await fetch(`/api/expeditions/${expeditionId}/teams`);
        const data = await res.json();
        if (!data.success) throw new Error('読み込み失敗');

        const unassigned = data.data.unassigned || [];
        const teams      = data.data.teams      || [];

        if (teams.length === 0) {
            document.getElementById('autoAssignPreview').innerHTML =
                '<div class="alert alert-warning">先にチームを作成してください。</div>';
            return;
        }
        if (unassigned.length === 0) {
            document.getElementById('autoAssignPreview').innerHTML =
                '<div class="alert alert-info">未割り当ての参加者がいません。</div>';
            return;
        }

        // 性別で分類（DBは 'male'/'female'）
        const males   = unassigned.filter(p => p.gender === 'male');
        const females = unassigned.filter(p => p.gender === 'female');
        const others  = unassigned.filter(p => p.gender !== 'male' && p.gender !== 'female');

        // 割り当てプレビューを計算（男女・学年バランス考慮）
        const assignments = buildAssignments(males, females, others, teams);
        autoAssignData = { assignments };

        // プレビューHTML生成
        const preview = {};
        teams.forEach(t => { preview[t.id] = { name: t.name, males: [], females: [], others: [] }; });
        assignments.forEach(a => {
            const p = unassigned.find(u => u.member_id == a.member_id);
            if (!p) return;
            const g = p.gender === 'male' ? 'males' : p.gender === 'female' ? 'females' : 'others';
            preview[a.teamId][g].push(p.name_kanji);
        });

        let html = `
            <p class="small text-muted mb-2">
                未割り当て: 男性 ${males.length}人 / 女性 ${females.length}人
                ${others.length > 0 ? ` / その他 ${others.length}人` : ''}
            </p>
            <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr><th>チーム</th><th>男性</th><th>女性</th>${others.length > 0 ? '<th>その他</th>' : ''}</tr>
                </thead>
                <tbody>`;
        teams.forEach(t => {
            const p = preview[t.id];
            html += `<tr>
                <td><strong>${escapeHtml(p.name)}</strong></td>
                <td>${p.males.length > 0 ? escapeHtml(p.males.join('、')) + `<span class="badge bg-primary ms-1">${p.males.length}</span>` : '<span class="text-muted">なし</span>'}</td>
                <td>${p.females.length > 0 ? escapeHtml(p.females.join('、')) + `<span class="badge bg-danger ms-1">${p.females.length}</span>` : '<span class="text-muted">なし</span>'}</td>
                ${others.length > 0 ? `<td>${p.others.length > 0 ? escapeHtml(p.others.join('、')) : '<span class="text-muted">なし</span>'}</td>` : ''}
            </tr>`;
        });
        html += '</tbody></table></div>';

        document.getElementById('autoAssignPreview').innerHTML = html;
        document.getElementById('autoAssignExecBtn').disabled = false;

    } catch (err) {
        document.getElementById('autoAssignPreview').innerHTML =
            '<div class="alert alert-danger">読み込みに失敗しました</div>';
    }
}

/**
 * 男女・学年バランスを考慮してチーム割り当てを計算する
 *
 * 方針:
 *   - 男女それぞれ独立してチームに均等分配（各チーム約3人ずつ、余れば一部4人）
 *   - 学年が均等になるようスネークドラフトで順番を決める
 *     例: 3チーム → 1巡目 t0,t1,t2 / 2巡目 t2,t1,t0 / 3巡目 t0,t1,t2 ...
 *   - 男女で独立してt[0]から割り当てるので、各チームが「男約3人+女約3人」になる
 */
function buildAssignments(males, females, others, teams) {
    const n = teams.length;
    if (n === 0) return [];

    // 学年でソート（0=OB/OGは最後尾）
    const byGrade = arr => [...arr].sort((a, b) => {
        const ga = parseInt(a.grade) || 99;
        const gb = parseInt(b.grade) || 99;
        return ga - gb;
    });

    // スネークドラフト: 偶数ラウンドは左→右、奇数ラウンドは右→左
    const snakeAssign = (group) => {
        const sorted = byGrade(group);
        return sorted.map((p, i) => {
            const round      = Math.floor(i / n);
            const posInRound = i % n;
            const teamIdx    = round % 2 === 0 ? posInRound : (n - 1 - posInRound);
            return { member_id: p.member_id, teamId: teams[teamIdx].id };
        });
    };

    return [
        ...snakeAssign(males),
        ...snakeAssign(females),
        ...snakeAssign(others),
    ];
}

// 自動割り当てを実行
async function executeAutoAssign() {
    if (!autoAssignData) return;
    const btn = document.getElementById('autoAssignExecBtn');
    btn.disabled = true;
    btn.textContent = '割り当て中...';

    try {
        for (const a of autoAssignData.assignments) {
            const res = await fetch(`/api/expeditions/${expeditionId}/teams/${a.teamId}/members`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ member_id: a.member_id }),
            });
            const result = await res.json();
            if (!result.success) {
                throw new Error(result.error?.message || '割り当てエラー');
            }
        }
        autoAssignModal.hide();
        teamsLoaded = false;
        await loadTeams();
        showToast('自動割り当てが完了しました');
    } catch (err) {
        alert('割り当て中にエラーが発生しました: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-shuffle"></i> 割り当て実行';
    }
}

// ==============================
// タブ5: 申し込みURL
// ==============================
async function loadApplicationUrl() {
    if (applicationLoaded) return;
    applicationLoaded = true;

    const content = document.getElementById('applicationUrlContent');

    try {
        const res  = await fetch(`/api/expeditions/${expeditionId}/application-url`);
        const data = await res.json();
        if (data.success) {
            renderApplicationUrl(data.data);
        } else {
            content.innerHTML = '<div class="alert alert-danger">読み込みに失敗しました</div>';
        }
    } catch (err) {
        console.error(err);
        content.innerHTML = '<div class="alert alert-danger">通信エラーが発生しました</div>';
    }
}

function renderApplicationUrl(data) {
    const content = document.getElementById('applicationUrlContent');

    let html = '<h4 class="mb-3">申し込みURL管理</h4>';

    // 申込み期間設定カード（URL有無に関わらず常に表示）
    const appStart   = data.application_start || '';
    const appDeadline = data.deadline || '';
    html += `
        <div class="card mb-3">
            <div class="card-header bg-light"><strong>申込み期間設定</strong></div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">申込み開始日時</label>
                        <input type="datetime-local" class="form-control" id="editApplicationStart"
                               value="${escapeHtml(appStart.replace(' ', 'T').substring(0, 16))}">
                        <div class="form-text">空欄=即時受付</div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">申込み締切日</label>
                        <input type="date" class="form-control" id="editDeadline"
                               value="${escapeHtml(appDeadline)}">
                        <div class="form-text">空欄=期限なし</div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" onclick="saveApplicationSchedule()">保存</button>
                    </div>
                </div>
                <div id="appScheduleSaved" class="text-success mt-2 d-none">保存しました</div>
            </div>
        </div>`;

    if (data.has_token) {
        const token     = data.token;
        const isActive  = token.is_active == 1;
        const hasDeadline = token.deadline !== null;
        const isExpired = hasDeadline && new Date(token.deadline) < new Date();

        html += `
            <div class="card mb-3">
                <div class="card-header bg-light"><strong>現在の申し込みURL</strong></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">URL:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="${escapeHtml(data.url)}" id="applicationUrlInput" readonly>
                            <button class="btn btn-outline-secondary" onclick="copyApplicationUrl()">
                                <i class="bi bi-clipboard"></i> コピー
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ステータス:</label>
                        <div>
                            ${isActive && !isExpired
                                ? '<span class="badge bg-success">有効</span>'
                                : '<span class="badge bg-secondary">無効</span>'}
                        </div>
                    </div>
                    <button class="btn btn-outline-warning" onclick="reissueApplicationUrl()">
                        <i class="bi bi-arrow-clockwise"></i> URLを再発行
                    </button>
                </div>
            </div>
            <div class="alert alert-warning">
                <strong><i class="bi bi-exclamation-triangle"></i> 注意</strong><br>
                新しいURLを発行すると、古いURLは無効になります。
            </div>
        `;
    } else {
        html += `
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-link-45deg" style="font-size: 3rem; color: #6c757d;"></i>
                    <h5 class="mt-3">申し込みURLが未発行です</h5>
                    <p class="text-muted">URLを発行すると、会員が遠征に申し込めるようになります。</p>
                    <button class="btn btn-primary" onclick="reissueApplicationUrl()">
                        <i class="bi bi-plus-circle"></i> 申し込みURLを発行
                    </button>
                </div>
            </div>
        `;
    }

    content.innerHTML = html;
}

async function saveApplicationSchedule() {
    const startVal    = document.getElementById('editApplicationStart').value || null;
    const deadlineVal = document.getElementById('editDeadline').value || null;

    // datetime-local → "YYYY-MM-DD HH:MM:SS" 形式に変換
    const applicationStart = startVal ? startVal.replace('T', ' ') + ':00' : null;

    try {
        const res = await fetch(`/api/expeditions/${expeditionId}`, {
            method:  'PUT',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ deadline: deadlineVal, application_start: applicationStart }),
        });
        const data = await res.json();
        if (data.success) {
            const el = document.getElementById('appScheduleSaved');
            el.classList.remove('d-none');
            setTimeout(() => el.classList.add('d-none'), 2000);
        } else {
            alert(data.error?.message || '保存に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

function copyApplicationUrl() {
    const input = document.getElementById('applicationUrlInput');
    input.select();
    document.execCommand('copy');
    showToast('URLをコピーしました');
}

async function reissueApplicationUrl() {
    if (!confirm('URLを発行/再発行しますか？\n既存のURLがある場合は無効になります。')) return;

    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/application-url`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({}),
        });
        const result = await res.json();
        if (result.success) {
            showToast('URLを発行しました');
            applicationLoaded = false;
            await loadApplicationUrl();
        } else {
            alert(result.error?.message || '発行に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

// ==============================
// タブ6: 集金管理
// ==============================
async function loadCollection() {
    if (collectionLoaded) return;
    collectionLoaded = true;

    const content = document.getElementById('collectionContent');

    try {
        const res  = await fetch(`/api/expeditions/${expeditionId}/collection`);
        const data = await res.json();
        if (data.success) {
            renderCollection(data.data);
        } else {
            content.innerHTML = '<div class="alert alert-danger">読み込みに失敗しました</div>';
        }
    } catch (err) {
        console.error(err);
        content.innerHTML = '<div class="alert alert-danger">通信エラーが発生しました</div>';
    }
}

function renderCollection(data) {
    const content = document.getElementById('collectionContent');

    // APIは配列 [{id, round, deadline, items:[...]}, ...] で返す
    const findCollection = (roundNum) => (Array.isArray(data) ? data : [])
        .find(c => Number(c.round) === roundNum) || null;

    // 第1回・第2回をアコーディオン形式で表示
    const roundDefs = [
        { id: 1, label: '第1回集金（遠征前）' },
        { id: 2, label: '第2回集金（遠征後）' },
    ];

    let accordionHtml = roundDefs.map(roundDef => {
        const col   = findCollection(roundDef.id);
        const items = col?.items || [];
        const colId = col?.id || null;
        const deadline = col?.deadline || '';

        const submittedCount = items.filter(i => i.submitted == 1).length;
        const paidCount      = items.filter(i => i.paid == 1).length;

        const tableRows = items.map(item => {
            const amountDisplay = item.amount < 0
                ? `<span class="text-success">返金 ¥${Number(-item.amount).toLocaleString()}</span>`
                : `¥${Number(item.amount).toLocaleString()}`;
            const submittedBadge = item.submitted == 1
                ? `<span class="badge bg-success">提出済</span>`
                : `<span class="badge bg-light text-muted border">未提出</span>`;

            return `
                <tr>
                    <td>
                        ${escapeHtml(item.name_kanji)}
                        <div class="text-muted small">${escapeHtml(item.name_kana || '')}</div>
                    </td>
                    <td>${amountDisplay}</td>
                    <td class="text-center">${submittedBadge}</td>
                    <td class="text-center">
                        <input type="checkbox" ${item.paid ? 'checked' : ''}
                            onchange="updateCollectionItem(${item.collection_id}, ${item.id}, this.checked)">
                    </td>
                </tr>
            `;
        }).join('');

        const tableHtml = items.length > 0 ? `
            <div class="table-responsive mb-2">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>名前</th>
                            <th>金額</th>
                            <th class="text-center">会員提出</th>
                            <th class="text-center">通帳確認済み</th>
                        </tr>
                    </thead>
                    <tbody>${tableRows}</tbody>
                </table>
            </div>
            <div class="text-muted small mb-3">提出済み: ${submittedCount}/${items.length}人　通帳確認済み: ${paidCount}/${items.length}人</div>
        ` : '<p class="text-muted mb-2">データがありません。「データ生成」ボタンで生成してください。</p>';

        // 期限設定UI（集金レコードが存在する場合のみ）
        const deadlineHtml = colId ? `
            <div class="d-flex align-items-center gap-2 mb-3">
                <label class="small text-muted mb-0">振込期限:</label>
                <input type="date" class="form-control form-control-sm" style="width:160px;"
                       id="deadline_${roundDef.id}" value="${escapeHtml(deadline)}">
                <button class="btn btn-outline-secondary btn-sm"
                        onclick="saveCollectionDeadline(${roundDef.id}, ${colId})">
                    保存
                </button>
            </div>
        ` : '';

        return `
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapseRound${roundDef.id}">
                        ${escapeHtml(roundDef.label)}
                        <span class="badge bg-secondary ms-2">${items.length}件</span>
                        ${submittedCount > 0 ? `<span class="badge bg-success ms-1">提出 ${submittedCount}</span>` : ''}
                    </button>
                </h2>
                <div id="collapseRound${roundDef.id}" class="accordion-collapse collapse">
                    <div class="accordion-body">
                        ${deadlineHtml}
                        ${tableHtml}
                        <button class="btn btn-outline-primary btn-sm" onclick="generateCollection(${roundDef.id})">
                            <i class="bi bi-arrow-clockwise"></i> データ生成
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    content.innerHTML = `
        <h4 class="mb-3">集金管理</h4>
        <div class="accordion">${accordionHtml}</div>
    `;
}

async function saveCollectionDeadline(roundNum, collectionId) {
    const deadline = document.getElementById(`deadline_${roundNum}`)?.value || '';
    try {
        const res = await fetch(`/api/expeditions/${expeditionId}/collection/${collectionId}`, {
            method:  'PUT',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ deadline: deadline || null }),
        });
        const result = await res.json();
        if (result.success) {
            showToast('振込期限を保存しました');
        } else {
            alert(result.error?.message || '保存に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

async function generateCollection(round) {
    if (!confirm(`第${round}回集金のデータを生成しますか？\n既存のデータは上書きされます。`)) return;

    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/collection/generate`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ round }),
        });
        const result = await res.json();
        if (result.success) {
            showToast('データを生成しました');
            collectionLoaded = false;
            await loadCollection();
        } else {
            alert(result.error?.message || '生成に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

async function updateCollectionItem(cid, iid, paid) {
    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/collection/${cid}/items/${iid}`, {
            method:  'PUT',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ paid }),
        });
        const result = await res.json();
        if (!result.success) {
            alert(result.error?.message || '更新に失敗しました');
            collectionLoaded = false;
            loadCollection();
        } else {
            showToast('更新しました', 'success', 1500);
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

// ==============================
// タブ7: しおり
// ==============================
let _teamsForBooklet = null;

async function loadBooklet() {
    if (_bookletData !== null) { renderBookletEditor(_bookletData); return; }
    try {
        // しおりデータとチームデータを同時取得
        const [bRes, tRes] = await Promise.all([
            fetch(`/api/expeditions/${expeditionId}/booklet`),
            fetch(`/api/expeditions/${expeditionId}/teams`),
        ]);
        const bData = await bRes.json();
        const tData = await tRes.json();

        if (!bData.success) {
            document.getElementById('bookletContent').innerHTML = '<div class="alert alert-danger">読み込みに失敗しました</div>';
            return;
        }

        _bookletData     = bData.data;
        _teamsForBooklet = (tData.success ? tData.data.teams : null) || [];

        // チーム分けを強制的に最新データで上書き
        _bookletData.team_assignment = buildTeamText(_teamsForBooklet);

        renderBookletEditor(_bookletData);

        // チームデータを取り込んだ状態で自動保存
        scheduleBookletSave();
    } catch (err) {
        console.error(err);
        document.getElementById('bookletContent').innerHTML = '<div class="alert alert-danger">通信エラーが発生しました</div>';
    }
}

function buildTeamText(teams) {
    if (!teams || !teams.length) return '';
    return teams.map(team => {
        const members = (team.members || []).map(m => `  ・${m.name_kanji}`).join('\n');
        return `【${team.name}】\n${members}`;
    }).join('\n\n');
}

function renderBookletEditor(b) {
    const publicToken = b.public_token || '';
    const publicUrl   = publicToken ? `/public/expedition-booklet/${publicToken}` : '';

    document.getElementById('bookletContent').innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">しおり編集</h4>
            <div class="d-flex gap-2 align-items-center">
                <span id="bookletSaveStatus" class="text-muted small"></span>
                <button class="btn btn-primary btn-sm" onclick="publishBooklet()">
                    ${b.published ? '再公開' : '公開'}
                </button>
            </div>
        </div>

        ${publicUrl ? `
        <div class="alert alert-success mb-3 py-2">
            <strong>公開URL</strong>
            <div class="input-group input-group-sm mt-1">
                <input type="text" class="form-control" value="${escapeHtml(window.location.origin + publicUrl)}" readonly id="bookletPublicUrlInput">
                <button class="btn btn-outline-secondary" onclick="copyBookletUrl()">コピー</button>
            </div>
        </div>
        ` : ''}

        <!-- 集合情報 -->
        <div class="card mb-3">
            <div class="card-header fw-bold">集合情報</div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label form-label-sm mb-1">開催場所</label>
                        <input type="text" class="form-control form-control-sm" id="bVenue"
                               value="${escapeHtml(b.venue || '')}" placeholder="開催場所（例: ○○テニスコート）"
                               oninput="updateBookletField('venue', this.value)">
                    </div>
                    <div class="col-12">
                        <div class="alert alert-secondary py-2 mb-0" style="font-size:.85rem;">
                            <i class="bi bi-car-front me-1"></i>集合場所・集合時間は各車のドライバーに直接確認してください
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label form-label-sm mb-1">メモ</label>
                        <textarea class="form-control form-control-sm" id="bMeetingNote" rows="2"
                                  oninput="updateBookletField('meeting_note', this.value)"
                                  placeholder="その他の連絡事項">${escapeHtml(b.meeting_note || '')}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- 持ち物 -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center fw-bold">
                持ち物
                <button class="btn btn-outline-primary btn-sm" onclick="addBringItem()">＋ 追加</button>
            </div>
            <div class="card-body p-2">
                <div id="bringItemList">${renderBringItems(b.items_to_bring || [])}</div>
            </div>
        </div>

        <!-- 車割（DBから自動表示） -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center fw-bold">
                車割
                <span class="badge bg-secondary fw-normal" style="font-size:.75rem;">車割タブから自動取り込み</span>
            </div>
            <div class="card-body p-2 text-muted small">
                車割タブで登録した内容がしおりに自動反映されます。
            </div>
        </div>

        <!-- チーム分け（自動取り込み） -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center fw-bold">
                チーム分け
                <span class="badge bg-secondary fw-normal" style="font-size:.75rem;">チームタブから自動取り込み</span>
            </div>
            <div class="card-body p-2">
                ${renderTeamDisplay(_teamsForBooklet || [])}
            </div>
        </div>

        <!-- 部屋割り -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center fw-bold">
                部屋割り
                <button class="btn btn-outline-primary btn-sm" onclick="addRoomCategory()">＋ カテゴリ追加</button>
            </div>
            <div class="card-body p-2">
                <div id="roomAssignmentList">${renderRoomAssignments(b.room_assignments || [])}</div>
            </div>
        </div>

        <!-- 備考 -->
        <div class="card mb-3">
            <div class="card-header fw-bold">備考</div>
            <div class="card-body p-2">
                <textarea class="form-control form-control-sm" id="bNotes" rows="4"
                          oninput="updateBookletField('notes', this.value)"
                          placeholder="その他の注意事項など">${escapeHtml(b.notes || '')}</textarea>
            </div>
        </div>
    `;
}

function renderTeamDisplay(teams) {
    if (!teams || !teams.length) {
        return '<p class="text-muted small mb-0">チームが登録されていません（チームタブで登録してください）</p>';
    }
    return `<div class="row g-2">` + teams.map(team => `
        <div class="col-md-4 col-sm-6">
            <div class="border rounded p-2 h-100">
                <div class="fw-bold mb-1">${escapeHtml(team.name)}</div>
                <ul class="list-unstyled mb-0">
                    ${sortTeamMembers(team.members || []).map(m => `<li class="small">・${escapeHtml(m.name_kanji)}</li>`).join('')}
                    ${!(team.members || []).length ? '<li class="text-muted small">メンバーなし</li>' : ''}
                </ul>
            </div>
        </div>`).join('') + `</div>`;
}

function updateBookletField(key, value) {
    if (!_bookletData) return;
    _bookletData[key] = value;
    scheduleBookletSave();
}

function scheduleBookletSave() {
    clearTimeout(_bookletSaveTimer);
    _bookletSaveTimer = setTimeout(() => saveBooklet(true), 1500);
}

function setBookletSaveStatus(status) {
    const el = document.getElementById('bookletSaveStatus');
    if (!el) return;
    if (status === 'saving') el.textContent = '保存中...';
    else if (status === 'saved') el.textContent = '自動保存済み';
    else if (status === 'error') el.textContent = '保存失敗';
}

/* ---- 持ち物 ---- */
function renderBringItems(items) {
    if (!items.length) return '<p class="text-muted small p-2 mb-0">まだ登録されていません</p>';
    return items.map((it, i) => `
    <div class="d-flex gap-2 align-items-center mb-1">
        <input type="checkbox" class="form-check-input mt-0" ${it.highlight ? 'checked' : ''}
               onchange="updateBringItem(${i}, 'highlight', this.checked)" title="強調">
        <input type="text" class="form-control form-control-sm" value="${escapeHtml(it.text || '')}"
               oninput="updateBringItem(${i}, 'text', this.value)" placeholder="持ち物">
        <input type="text" class="form-control form-control-sm" value="${escapeHtml(it.note || '')}"
               oninput="updateBringItem(${i}, 'note', this.value)" placeholder="補足（任意）" style="max-width:160px;">
        <button class="btn btn-outline-danger btn-sm py-0 px-1" onclick="removeBringItem(${i})">×</button>
    </div>`).join('');
}

function addBringItem() {
    _bookletData.items_to_bring = _bookletData.items_to_bring || [];
    _bookletData.items_to_bring.push({text: '', note: '', highlight: false});
    document.getElementById('bringItemList').innerHTML = renderBringItems(_bookletData.items_to_bring);
    scheduleBookletSave();
}

function updateBringItem(i, key, val) {
    if (_bookletData.items_to_bring && _bookletData.items_to_bring[i] !== undefined) {
        _bookletData.items_to_bring[i][key] = val;
        scheduleBookletSave();
    }
}

function removeBringItem(i) {
    _bookletData.items_to_bring.splice(i, 1);
    document.getElementById('bringItemList').innerHTML = renderBringItems(_bookletData.items_to_bring);
    scheduleBookletSave();
}

/* ---- 部屋割り ---- */
function renderRoomAssignments(categories) {
    if (!categories.length) return '<p class="text-muted small p-2 mb-0">まだ登録されていません</p>';
    return categories.map((cat, ci) => `
    <div class="border rounded mb-2 p-2">
        <div class="d-flex gap-2 align-items-center mb-2">
            <input type="text" class="form-control form-control-sm" style="max-width:200px;"
                   value="${escapeHtml(cat.category || '')}"
                   oninput="updateRoomCategory(${ci}, this.value)" placeholder="カテゴリ（例: 男性）">
            <button class="btn btn-outline-secondary btn-sm py-0 ms-auto" onclick="addRoom(${ci})">＋ 部屋追加</button>
            <button class="btn btn-outline-danger btn-sm py-0" onclick="removeRoomCategory(${ci})">削除</button>
        </div>
        <div id="roomList_${ci}" class="d-flex flex-wrap gap-2">
            ${renderRooms(cat.rooms || [], ci)}
        </div>
    </div>`).join('');
}

function renderRooms(rooms, ci) {
    if (!rooms.length) return '<span class="text-muted small">部屋なし</span>';
    return rooms.map((room, ri) => `
    <div class="d-flex gap-1 align-items-center border rounded px-2 py-1">
        <input type="text" class="form-control form-control-sm" style="width:80px;"
               value="${escapeHtml(room.room_no || '')}"
               oninput="updateRoom(${ci}, ${ri}, 'room_no', this.value)" placeholder="部屋番号">
        <span class="text-muted small">定員</span>
        <input type="number" class="form-control form-control-sm" style="width:60px;"
               value="${room.capacity || 0}"
               oninput="updateRoom(${ci}, ${ri}, 'capacity', parseInt(this.value) || 0)" min="0">
        <span class="text-muted small">人</span>
        <button class="btn btn-outline-danger btn-sm py-0 px-1" onclick="removeRoom(${ci}, ${ri})">×</button>
    </div>`).join('');
}

function addRoomCategory() {
    _bookletData.room_assignments = _bookletData.room_assignments || [];
    _bookletData.room_assignments.push({category: '', rooms: []});
    document.getElementById('roomAssignmentList').innerHTML = renderRoomAssignments(_bookletData.room_assignments);
    scheduleBookletSave();
}

function removeRoomCategory(ci) {
    _bookletData.room_assignments.splice(ci, 1);
    document.getElementById('roomAssignmentList').innerHTML = renderRoomAssignments(_bookletData.room_assignments);
    scheduleBookletSave();
}

function updateRoomCategory(ci, val) {
    if (_bookletData.room_assignments[ci]) {
        _bookletData.room_assignments[ci].category = val;
        scheduleBookletSave();
    }
}

function addRoom(ci) {
    _bookletData.room_assignments[ci].rooms = _bookletData.room_assignments[ci].rooms || [];
    _bookletData.room_assignments[ci].rooms.push({room_no: '', capacity: 0});
    document.getElementById(`roomList_${ci}`).innerHTML = renderRooms(_bookletData.room_assignments[ci].rooms, ci);
    scheduleBookletSave();
}

function removeRoom(ci, ri) {
    _bookletData.room_assignments[ci].rooms.splice(ri, 1);
    document.getElementById(`roomList_${ci}`).innerHTML = renderRooms(_bookletData.room_assignments[ci].rooms, ci);
    scheduleBookletSave();
}

function updateRoom(ci, ri, key, val) {
    if (_bookletData.room_assignments[ci] && _bookletData.room_assignments[ci].rooms[ri]) {
        _bookletData.room_assignments[ci].rooms[ri][key] = val;
        scheduleBookletSave();
    }
}


/* ---- 保存 ---- */
async function saveBooklet(auto = false) {
    if (_bookletSaving || !_bookletData) return;
    _bookletSaving = true;
    setBookletSaveStatus('saving');

    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/booklet`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(_bookletData),
        });
        const result = await res.json();
        if (result.success) {
            _bookletData = result.data;
            setBookletSaveStatus('saved');
            if (!auto) showToast('保存しました');
        } else {
            setBookletSaveStatus('error');
            if (!auto) alert(result.error?.message || '保存に失敗しました');
        }
    } catch (err) {
        setBookletSaveStatus('error');
        if (!auto) alert('通信エラーが発生しました');
    } finally {
        _bookletSaving = false;
    }
}

/* ---- 公開 ---- */
async function publishBooklet() {
    if (!confirm('しおりを公開しますか？')) return;

    try {
        const res    = await fetch(`/api/expeditions/${expeditionId}/booklet/publish`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({}),
        });
        const result = await res.json();
        if (result.success) {
            showToast('公開しました');
            _bookletData = null;
            await loadBooklet();
        } else {
            alert(result.error?.message || '公開に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

function copyBookletUrl() {
    const input = document.getElementById('bookletPublicUrlInput');
    if (!input) return;
    input.select();
    document.execCommand('copy');
    showToast('URLをコピーしました');
}

// ==================== タブ8: レンタカー清算 ====================

async function loadCarExpenses() {
    // エクスポートボタンのURLをセット
    document.getElementById('btnExpenseXlsx').href = `/api/expeditions/${expeditionId}/car-expenses/export/xlsx`;
    document.getElementById('btnExpensePdf').href  = `/api/expeditions/${expeditionId}/car-expenses/export/pdf`;

    try {
        // 基本情報から expense_deadline を取得してフォームにセット
        const infoRes  = await fetch(`/api/expeditions/${expeditionId}`);
        const infoData = await infoRes.json();
        if (infoData.success && infoData.data) {
            document.getElementById('expenseDeadlineInput').value = infoData.data.expense_deadline || '';
        }

        // 申請一覧と清算一覧を並行取得
        const [expRes, setRes] = await Promise.all([
            fetch(`/api/expeditions/${expeditionId}/car-expenses`),
            fetch(`/api/expeditions/${expeditionId}/car-expenses/settlement`),
        ]);
        const expData = await expRes.json();
        const setData = await setRes.json();

        if (!expData.success) {
            document.getElementById('carExpenseList').innerHTML = '<div class="alert alert-danger">読み込みに失敗しました</div>';
            return;
        }
        renderCarExpenses(expData.data || []);

        if (setData.success) {
            renderCarExpenseSettlement(setData.data);
        }
    } catch (err) {
        document.getElementById('carExpenseList').innerHTML = '<div class="alert alert-danger">通信エラーが発生しました</div>';
    }
}

function renderCarExpenses(expenses) {
    if (expenses.length === 0) {
        document.getElementById('carExpenseList').innerHTML =
            '<div class="alert alert-info">申請はまだありません。</div>';
        return;
    }

    let totalRental = 0, totalGas = 0, totalHighway = 0, totalOther = 0;
    const rows = expenses.map(e => {
        const rental  = parseInt(e.rental_fee)  || 0;
        const gas     = parseInt(e.gas_fee)     || 0;
        const highway = parseInt(e.highway_fee) || 0;
        const other   = parseInt(e.other_fee)   || 0;
        const total   = rental + gas + highway + other;
        totalRental  += rental;
        totalGas     += gas;
        totalHighway += highway;
        totalOther   += other;

        const otherLabel = other > 0 && e.other_description
            ? `¥${other.toLocaleString()}<br><small class="text-muted">${escapeHtml(e.other_description)}</small>`
            : (other > 0 ? `¥${other.toLocaleString()}` : '—');
        const noteHtml = e.note ? `<small class="text-muted">${escapeHtml(e.note)}</small>` : '';

        return `<tr>
            <td>${escapeHtml(e.name_kanji)}</td>
            <td class="text-end">${rental  > 0 ? '¥' + rental.toLocaleString()  : '—'}</td>
            <td class="text-end">${gas     > 0 ? '¥' + gas.toLocaleString()     : '—'}</td>
            <td class="text-end">${highway > 0 ? '¥' + highway.toLocaleString() : '—'}</td>
            <td class="text-end">${otherLabel}</td>
            <td class="text-end fw-bold">¥${total.toLocaleString()}</td>
            <td class="text-muted small">${noteHtml}</td>
            <td>
                <button class="btn btn-outline-danger btn-sm py-0"
                        onclick="deleteCarExpense(${e.id})">削除</button>
            </td>
        </tr>`;
    }).join('');

    const grandTotal = totalRental + totalGas + totalHighway + totalOther;

    document.getElementById('carExpenseList').innerHTML = `
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead class="table-light">
                    <tr>
                        <th>氏名</th>
                        <th class="text-end">レンタカー代</th>
                        <th class="text-end">ガソリン代</th>
                        <th class="text-end">高速料金</th>
                        <th class="text-end">その他</th>
                        <th class="text-end">合計</th>
                        <th>備考</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
                <tfoot class="table-warning fw-bold">
                    <tr>
                        <td>合計</td>
                        <td class="text-end">¥${totalRental.toLocaleString()}</td>
                        <td class="text-end">¥${totalGas.toLocaleString()}</td>
                        <td class="text-end">¥${totalHighway.toLocaleString()}</td>
                        <td class="text-end">¥${totalOther.toLocaleString()}</td>
                        <td class="text-end">¥${grandTotal.toLocaleString()}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>`;
}

function renderCarExpenseSettlement(data) {
    const el       = document.getElementById('carSettlementList');
    const items    = data.settlement  || [];
    const excluded = data.excluded    || [];

    if (items.length === 0 && excluded.length === 0) {
        el.innerHTML = '<div class="alert alert-info">清算対象の参加者がいません。</div>';
        return;
    }

    const rows = items.map(item => {
        const balance = parseInt(item.balance) || 0;
        const share   = parseInt(item.share)   || 0;
        const paid    = parseInt(item.paid)    || 0;

        let rowClass   = '';
        let statusHtml = '';

        if (!item.has_expense) {
            rowClass   = 'table-secondary';
            statusHtml = '<span class="text-muted">申請なし（負担分: ¥' + share.toLocaleString() + '）</span>';
        } else if (balance > 0) {
            rowClass   = 'table-success';
            statusHtml = `<span class="text-success fw-bold">受取: ¥${balance.toLocaleString()}</span>`;
        } else if (balance < 0) {
            rowClass   = 'table-danger';
            statusHtml = `<span class="text-danger fw-bold">支払: ¥${Math.abs(balance).toLocaleString()}</span>`;
        } else {
            statusHtml = '<span class="text-muted">清算なし</span>';
        }

        return `<tr class="${rowClass}">
            <td>${escapeHtml(item.name_kanji)}</td>
            <td class="text-end">${item.has_expense ? '¥' + paid.toLocaleString() : '—'}</td>
            <td class="text-end">¥${share.toLocaleString()}</td>
            <td>${statusHtml}</td>
        </tr>`;
    }).join('');

    // 清算対象外
    let excludedHtml = '';
    if (excluded.length > 0) {
        const exRows = excluded.map(p =>
            `<tr class="table-light text-muted">
                <td>${escapeHtml(p.name_kanji)}</td>
                <td colspan="3"><small>車割未登録のため清算対象外</small></td>
            </tr>`
        ).join('');
        excludedHtml = `
            <h6 class="mt-4 text-muted">清算対象外</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>氏名</th>
                            <th colspan="3"></th>
                        </tr>
                    </thead>
                    <tbody>${exRows}</tbody>
                </table>
            </div>`;
    }

    el.innerHTML = `
        <h6 class="mt-3">清算一覧</h6>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead class="table-light">
                    <tr>
                        <th>氏名</th>
                        <th class="text-end">立替金額</th>
                        <th class="text-end">負担分</th>
                        <th>清算</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
        <small class="text-muted">※ 負担分 = 総費用 ÷ 車割登録人数。立替金額 − 負担分 が正なら受取、負なら支払い。</small>
        ${excludedHtml}`;
}

async function saveExpenseDeadline() {
    const deadline = document.getElementById('expenseDeadlineInput').value || null;
    try {
        const res = await fetch(`/api/expeditions/${expeditionId}`, {
            method:  'PUT',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ expense_deadline: deadline }),
        });
        const data = await res.json();
        if (data.success) {
            const el = document.getElementById('expenseDeadlineSaved');
            el.classList.remove('d-none');
            setTimeout(() => el.classList.add('d-none'), 2000);
        }
    } catch (err) {
        alert('保存に失敗しました');
    }
}

async function deleteCarExpense(id) {
    if (!confirm('この申請を削除しますか？')) return;
    try {
        const res = await fetch(`/api/expeditions/${expeditionId}/car-expenses/${id}`, { method: 'DELETE' });
        const data = await res.json();
        if (data.success) loadCarExpenses();
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}
</script>
