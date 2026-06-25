<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="/events" class="text-muted small text-decoration-none">← 企画一覧</a>
        <h1 class="mb-0 mt-1"><?= htmlspecialchars($event['title']) ?></h1>
    </div>
    <div>
        <button id="toggleActiveBtn" class="btn btn-outline-secondary me-2" onclick="toggleActive()">
            <?= $event['is_active'] ? '非公開にする' : '会員ページに公開する' ?>
        </button>
    </div>
</div>

<!-- ゲスト再提出アラート（申込者データ読込後に表示） -->
<div id="resubmitAlert" class="alert alert-warning d-none align-items-center" role="alert">
    <i class="bi bi-pencil-square me-2"></i>
    <span id="resubmitAlertText"></span>
    <a href="#tabApplications" class="alert-link ms-2" onclick="document.querySelector('a[href=\'#tabApplications\']').click(); return false;">申込者を確認</a>
</div>

<!-- タブナビゲーション -->
<ul class="nav nav-tabs mb-4" id="eventTabs">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#tabInfo">基本情報</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tabApplications">
            申込者 <span class="badge bg-secondary" id="appCountBadge"><?= $event['application_count'] ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tabTeams">班決め</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tabExpenses">雑費</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tabCalc">費用計算</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tabGuestFields">質問項目</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tabToken">申込URL</a>
    </li>
</ul>

<div class="tab-content">

    <!-- ── 基本情報タブ ── -->
    <div class="tab-pane fade show active" id="tabInfo">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">タイトル</label>
                        <input type="text" class="form-control" id="infoTitle" value="<?= htmlspecialchars($event['title']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">開催日</label>
                        <input type="date" class="form-control" id="infoDate" value="<?= htmlspecialchars($event['event_date']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">開始時刻</label>
                        <input type="time" class="form-control" id="infoTime" value="<?= htmlspecialchars(substr($event['event_time'] ?? '', 0, 5)) ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">場所</label>
                        <input type="text" class="form-control" id="infoLocation" value="<?= htmlspecialchars($event['location'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">概要</label>
                        <textarea class="form-control" id="infoDescription" rows="4"><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">参加費（円）</label>
                        <input type="number" class="form-control" id="infoFee" value="<?= (int)$event['participation_fee'] ?>" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">定員</label>
                        <input type="number" class="form-control" id="infoCapacity"
                               value="<?= $event['capacity'] !== null ? (int)$event['capacity'] : '' ?>"
                               placeholder="空欄 = 制限なし" min="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">申込期限</label>
                        <input type="date" class="form-control" id="infoDeadline"
                               value="<?= htmlspecialchars($event['deadline'] ?? '') ?>">
                        <div class="form-text">期限を過ぎると会員ページから非表示</div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" id="infoAllowWaitlist"
                                   <?= $event['allow_waitlist'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="infoAllowWaitlist">キャンセル待ちを受け付ける</label>
                        </div>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="infoIsActive"
                                   <?= $event['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="infoIsActive">公開中</label>
                        </div>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="infoAllowGuest"
                                   <?= !empty($event['allow_guest']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="infoAllowGuest">会員以外（ゲスト）の申し込みを受け付ける</label>
                        </div>
                        <div class="mt-2" id="infoGuestTypeWrap" style="<?= !empty($event['allow_guest']) ? '' : 'display:none;' ?>">
                            <label class="form-label small mb-1">ゲストフォームの種別</label>
                            <select class="form-select form-select-sm" id="infoGuestType">
                                <option value="shinkan" <?= ($event['guest_type'] ?? '') === 'shinkan' ? 'selected' : '' ?>>新歓（氏名・カナ・学科で本人特定）</option>
                                <option value="obog"    <?= ($event['guest_type'] ?? '') === 'obog' ? 'selected' : '' ?>>OBOG（氏名・カナ・代で本人特定）</option>
                            </select>
                        </div>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="infoIncludeGuests"
                                   <?= !empty($event['include_guests_in_calc']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="infoIncludeGuests">ゲストを班分け・費用計算に含める</label>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    <span id="infoSaveStatus" class="small text-muted"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 申込者タブ ── -->
    <div class="tab-pane fade" id="tabApplications">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>申込者一覧</span>
                <span id="capacityDisplay" class="fw-semibold"></span>
            </div>

            <!-- 絞り込み・並び替えコントロール -->
            <div class="card-body border-bottom py-2">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small mb-1">キーワード検索</label>
                        <input type="text" class="form-control form-control-sm" id="appSearch"
                               placeholder="氏名・学科・LINE名・回答など" oninput="renderApplications()">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">区分</label>
                        <select class="form-select form-select-sm" id="appFilterKind" onchange="renderApplications()">
                            <option value="">すべて</option>
                            <option value="member">会員のみ</option>
                            <option value="guest">ゲストのみ</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">学年（会員）</label>
                        <select class="form-select form-select-sm" id="appFilterGrade" onchange="renderApplications()">
                            <option value="">すべて</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">性別（会員）</label>
                        <select class="form-select form-select-sm" id="appFilterGender" onchange="renderApplications()">
                            <option value="">すべて</option>
                            <option value="male">男</option>
                            <option value="female">女</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">並び替え</label>
                        <div class="input-group input-group-sm">
                            <select class="form-select form-select-sm" id="appSortKey" onchange="renderApplications()">
                                <option value="created_at">申込日時</option>
                                <option value="name_kana">氏名（カナ順）</option>
                                <option value="grade">学年</option>
                                <option value="gender">性別</option>
                                <option value="department">学科</option>
                                <!-- ゲストのテンプレ項目はここに動的追加 -->
                            </select>
                            <button type="button" class="btn btn-outline-secondary" id="appSortDirBtn" onclick="toggleAppSortDir()" title="昇順/降順">↑</button>
                        </div>
                    </div>
                </div>

                <!-- ゲストのテンプレ項目による絞り込み（動的生成） -->
                <div class="row g-2 align-items-end mt-0" id="appGuestFieldFilters"></div>

                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span id="appFilterSummary" class="small text-muted"></span>
                    <button class="btn btn-sm btn-link text-decoration-none" onclick="resetAppFilters()">条件をクリア</button>
                </div>
            </div>

            <div class="card-body p-0">
                <div id="applicationsContainer">
                    <div class="text-center p-4 text-muted">読み込み中...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 班決めタブ ── -->
    <div class="tab-pane fade" id="tabTeams">

        <!-- 自動振り分け設定 -->
        <div class="card shadow-sm mb-3">
            <div class="card-header">班の自動振り分け</div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">班の数</label>
                        <input type="number" class="form-control" id="teamCount" value="4" min="1" max="50">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold d-block">均等にする基準</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="balanceGrade" checked>
                            <label class="form-check-label" for="balanceGrade">学年を分散</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="balanceGender" checked>
                            <label class="form-check-label" for="balanceGender">性別を分散</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="balanceFaculty">
                            <label class="form-check-label" for="balanceFaculty">学科を分散</label>
                        </div>
                        <!-- ゲスト項目（プルダウン/ラジオ）の分散基準を動的に追加 -->
                        <span id="balanceGuestFields"></span>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button class="btn btn-primary" onclick="autoAssignTeams()">自動振り分け</button>
                    </div>
                </div>
                <div class="form-text mt-2">
                    制約（絶対一緒／絶対別）を守りつつ、選択した基準が各班でばらけるように割り当てます。手動での変更も可能です。
                </div>
            </div>
        </div>

        <!-- 制約設定 -->
        <div class="card shadow-sm mb-3">
            <div class="card-header">組み合わせの制約</div>
            <div class="card-body">
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label">メンバー1</label>
                        <select class="form-select" id="constraintMemberA"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">メンバー2</label>
                        <select class="form-select" id="constraintMemberB"></select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">種別</label>
                        <select class="form-select" id="constraintType">
                            <option value="together">絶対一緒</option>
                            <option value="apart">絶対別</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-outline-primary" onclick="addConstraint()">追加</button>
                    </div>
                </div>
                <div id="constraintsContainer">
                    <div class="text-muted small">制約はまだありません</div>
                </div>
            </div>
        </div>

        <!-- 申込者一覧（班割り当て） -->
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span>申込者一覧</span>
                    <!-- 表示切替 -->
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-primary" id="teamViewBtn" onclick="setTeamView('teams')">班ビュー</button>
                        <button type="button" class="btn btn-outline-primary" id="listViewBtn" onclick="setTeamView('list')">一覧ビュー</button>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success" id="teamExportBtn" onclick="exportTeamsAsImage()">
                        <i class="bi bi-image"></i> JPGで出力
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- 並べ替え（一覧ビューのみ） -->
                    <div id="teamSortControls" class="align-items-center gap-2 flex-wrap" style="display:none;">
                        <div class="input-group input-group-sm" style="width: auto;">
                            <span class="input-group-text">並べ替え</span>
                            <select class="form-select form-select-sm" id="teamSortPrimary" onchange="applyTeamSorting()" style="width: auto;">
                                <option value="team_no" selected>班番号</option>
                                <option value="name_kana">カナ順</option>
                                <option value="grade">学年</option>
                                <option value="gender">性別</option>
                                <option value="faculty">学部</option>
                            </select>
                            <button type="button" class="btn btn-outline-secondary" id="teamSortPrimaryDir" onclick="toggleTeamSortDirection('primary')" title="昇順/降順切り替え">↑</button>
                        </div>
                        <div class="input-group input-group-sm" style="width: auto;">
                            <span class="input-group-text">→</span>
                            <select class="form-select form-select-sm" id="teamSortSecondary" onchange="applyTeamSorting()" style="width: auto;">
                                <option value="">なし</option>
                                <option value="name_kana" selected>カナ順</option>
                                <option value="grade">学年</option>
                                <option value="gender">性別</option>
                                <option value="faculty">学部</option>
                            </select>
                            <button type="button" class="btn btn-outline-secondary" id="teamSortSecondaryDir" onclick="toggleTeamSortDirection('secondary')" title="昇順/降順切り替え">↑</button>
                        </div>
                    </div>
                    <span id="teamSaveStatus" class="small text-muted"></span>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="teamsSummary" class="px-3 pt-3"></div>
                <div id="teamsContainer">
                    <div class="text-center p-4 text-muted">読み込み中...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 雑費タブ ── -->
    <div class="tab-pane fade" id="tabExpenses">
        <div class="card shadow-sm mb-3">
            <div class="card-header">雑費追加</div>
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">項目名</label>
                        <input type="text" class="form-control" id="expenseName" placeholder="例: コート代">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">金額（円）</label>
                        <input type="number" class="form-control" id="expenseAmount" value="0" min="0">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" onclick="addExpense()">追加</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div id="expensesContainer">
                    <div class="text-center p-4 text-muted">読み込み中...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 費用計算タブ ── -->
    <div class="tab-pane fade" id="tabCalc">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-end mb-3">
                    <button class="btn btn-primary" onclick="loadCalc()">計算する</button>
                </div>
                <div id="calcResult"></div>
            </div>
        </div>
    </div>

    <!-- ── ゲスト項目タブ ── -->
    <div class="tab-pane fade" id="tabGuestFields">
        <div class="alert alert-light border small">
            申込フォームで聞きたい項目を「対象」ごとに設定します。<br>
            ・<span class="badge bg-primary">会員</span>：会員（入会者）が申し込むときに表示<br>
            ・<span class="badge bg-info text-dark">ゲスト</span>：会員以外（新歓・OB交流会の参加者など）が申し込むときに表示。氏名・カナ・代/学科はフォーム標準項目です。<br>
            ゲスト申込を受け付けるには、基本情報タブで「会員以外（ゲスト）の申し込みを受け付ける」をONにしてください。
        </div>

        <div class="card shadow-sm mb-3 border-primary">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="fw-semibold">テンプレートを使う</div>
                    <div class="small text-muted">新歓・OB交流会向けの定番項目（LINE名・学科・性別・代・テニスの経験・備考）をまとめて追加します。</div>
                </div>
                <button class="btn btn-outline-primary" onclick="applyGuestFieldTemplate()">
                    <i class="bi bi-magic"></i> テンプレートを追加
                </button>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header">項目を追加</div>
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">対象</label>
                        <select class="form-select" id="gfAudience">
                            <option value="guest">会員以外（ゲスト）</option>
                            <option value="member">会員（入会者）</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">項目名</label>
                        <input type="text" class="form-control" id="gfLabel" placeholder="例: 学年、代、現役/OB">
                    </div>
                    <div class="col-12">
                        <label class="form-label">説明文（任意）</label>
                        <input type="text" class="form-control" id="gfDescription"
                               placeholder="例: 部活・サークルでの経験や、当日の参加時間などあれば記入してください">
                        <div class="form-text">申込フォームで項目名の下に表示される案内文です</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">入力形式</label>
                        <select class="form-select" id="gfType" onchange="toggleGfOptions()">
                            <option value="text">短文（1行）</option>
                            <option value="textarea">長文（複数行）</option>
                            <option value="select">プルダウン選択</option>
                            <option value="radio">ラジオ選択</option>
                        </select>
                    </div>
                    <div class="col-md-3" id="gfOptionsWrap" style="display:none;">
                        <label class="form-label">選択肢（カンマ区切り）</label>
                        <input type="text" class="form-control" id="gfOptions" placeholder="例: 現役, OB, OG">
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="gfRequired">
                            <label class="form-check-label" for="gfRequired">必須</label>
                        </div>
                        <button class="btn btn-primary w-100" onclick="addGuestField()">追加</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">フォーム項目一覧</div>
            <div class="card-body p-0">
                <div id="guestFieldsContainer">
                    <div class="text-center p-4 text-muted">読み込み中...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 申込URLタブ ── -->
    <div class="tab-pane fade" id="tabToken">
        <div class="card shadow-sm">
            <div class="card-body">
                <p class="text-muted small mb-3">
                    発行したURLをLINE等で共有すると申し込めます。会員用URLは学籍番号でログイン、
                    ゲスト用URLはログイン不要で申込フォームが開きます。
                </p>
                <div id="tokenArea">
                    <div class="text-center text-muted py-3">読み込み中...</div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary" onclick="generateToken()">
                        <i class="bi bi-link-45deg"></i> URLを発行する
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- 申込回答の編集モーダル（会員・ゲスト共通） -->
<div class="modal fade" id="answerEditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">申込内容の編集</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="answerEditWho" class="fw-semibold mb-3"></div>
                <div id="answerEditFields"></div>
                <div class="mb-2">
                    <label class="form-label small">備考</label>
                    <textarea class="form-control form-control-sm" id="answerEditNote" rows="2"></textarea>
                </div>
                <div id="answerEditError" class="alert alert-danger d-none small"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                <button type="button" class="btn btn-primary" id="answerEditSaveBtn" onclick="saveAnswerEdit()">保存</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
const EVENT_ID = <?= (int)$eventId ?>;
const EVENT_TITLE = <?= json_encode($event['title'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
let isActive   = <?= $event['is_active'] ? 'true' : 'false' ?>;
let capacity   = <?= $event['capacity'] !== null ? (int)$event['capacity'] : 'null' ?>;
let allowGuest = <?= !empty($event['allow_guest']) ? 'true' : 'false' ?>;

// ──────────────────────────────────
// 初期ロード
// ──────────────────────────────────
document.querySelector('a[href="#tabApplications"]').addEventListener('shown.bs.tab', loadApplications);
document.querySelector('a[href="#tabTeams"]').addEventListener('shown.bs.tab', loadTeams);
document.querySelector('a[href="#tabExpenses"]').addEventListener('shown.bs.tab', loadExpenses);
document.querySelector('a[href="#tabCalc"]').addEventListener('shown.bs.tab', loadCalc);
document.querySelector('a[href="#tabGuestFields"]').addEventListener('shown.bs.tab', loadGuestFields);

// 初期表示時に申込者数（会員＋ゲスト）を読み込み、タブのバッジを正しく表示する
loadApplications();

// ──────────────────────────────────
// 基本情報（自動保存）
// ──────────────────────────────────
const INFO_INPUT_IDS = [
    'infoTitle', 'infoDate', 'infoTime', 'infoLocation', 'infoDescription',
    'infoFee', 'infoCapacity', 'infoDeadline',
    'infoAllowWaitlist', 'infoIsActive', 'infoAllowGuest', 'infoGuestType', 'infoIncludeGuests',
];

// 入力・切替を監視して自動保存
INFO_INPUT_IDS.forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    // テキスト系は入力が落ち着いてから、チェックボックス・選択は即時に保存
    const evt = (el.type === 'checkbox' || el.tagName === 'SELECT') ? 'change' : 'input';
    el.addEventListener(evt, scheduleInfoSave);
});

// ゲスト受付ONのときだけ種別選択を表示
document.getElementById('infoAllowGuest').addEventListener('change', function () {
    document.getElementById('infoGuestTypeWrap').style.display = this.checked ? '' : 'none';
});

function setInfoSaveStatus(text, cls) {
    const el = document.getElementById('infoSaveStatus');
    if (el) el.innerHTML = `<span class="${cls || 'text-muted'}">${esc(text)}</span>`;
}

let infoSaveTimer = null;

function scheduleInfoSave() {
    setInfoSaveStatus('未保存の変更があります…', 'text-muted');
    if (infoSaveTimer) clearTimeout(infoSaveTimer);
    infoSaveTimer = setTimeout(saveInfo, 800);
}

async function saveInfo() {
    if (infoSaveTimer) { clearTimeout(infoSaveTimer); infoSaveTimer = null; }

    const title = document.getElementById('infoTitle').value.trim();
    const date  = document.getElementById('infoDate').value;
    // タイトル・開催日は必須。未入力のうちは保存しない（壊れた状態の保存を防ぐ）
    if (!title || !date) {
        setInfoSaveStatus('タイトルと開催日は必須です（未保存）', 'text-danger');
        return;
    }

    const cap = document.getElementById('infoCapacity').value;
    const payload = {
        title:             title,
        event_date:        date,
        event_time:        document.getElementById('infoTime').value        || null,
        location:          document.getElementById('infoLocation').value.trim() || null,
        description:       document.getElementById('infoDescription').value.trim() || null,
        participation_fee: parseInt(document.getElementById('infoFee').value) || 0,
        capacity:          cap !== '' ? parseInt(cap) : null,
        deadline:          document.getElementById('infoDeadline').value || null,
        allow_waitlist:    document.getElementById('infoAllowWaitlist').checked ? 1 : 0,
        allow_guest:       document.getElementById('infoAllowGuest').checked ? 1 : 0,
        guest_type:        document.getElementById('infoAllowGuest').checked
                               ? document.getElementById('infoGuestType').value : null,
        include_guests_in_calc: document.getElementById('infoIncludeGuests').checked ? 1 : 0,
        is_active:         document.getElementById('infoIsActive').checked ? 1 : 0,
    };

    setInfoSaveStatus('保存中…', 'text-muted');
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.success) {
            isActive   = !!payload.is_active;
            capacity   = payload.capacity;
            allowGuest = !!payload.allow_guest;
            // 申込URLタブを開いていればゲストURLの表示を更新
            if (lastToken !== undefined) renderToken(lastToken);
            document.getElementById('toggleActiveBtn').textContent =
                isActive ? '非公開にする' : '会員ページに公開する';
            setInfoSaveStatus('✓ 自動保存しました', 'text-success');
        } else {
            setInfoSaveStatus(data.error?.message || '保存に失敗しました', 'text-danger');
        }
    } catch (e) {
        setInfoSaveStatus('通信エラー（未保存）', 'text-danger');
    }
}

// ──────────────────────────────────
// 公開切り替え
// ──────────────────────────────────
async function toggleActive() {
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/toggle-active`, { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            isActive = !!data.data.is_active;
            document.getElementById('toggleActiveBtn').textContent =
                isActive ? '非公開にする' : '会員ページに公開する';
            document.getElementById('infoIsActive').checked = isActive;
            showToast(data.message, 'success');
        }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

// ──────────────────────────────────
// 申込者一覧（取得・絞り込み・並び替え）
// ──────────────────────────────────
let appData = {          // 取得した申込データのキャッシュ
    submitted: [], waitlist: [], guestSubmitted: [], guestWaitlist: [], guestFields: [], memberFields: [],
};
let appSortDir = 1;      // 1=昇順 / -1=降順

async function loadApplications() {
    const container = document.getElementById('applicationsContainer');
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/applications`);
        const data = await res.json();
        appData = {
            submitted:      data.data?.submitted      || [],
            waitlist:       data.data?.waitlist       || [],
            guestSubmitted: data.data?.guest_submitted|| [],
            guestWaitlist:  data.data?.guest_waitlist || [],
            guestFields:    data.data?.guest_fields   || [],
            memberFields:   data.data?.member_fields  || [],
        };
        populateGradeFilter();
        buildGuestFieldControls();
        renderApplications();
    } catch (e) {
        container.innerHTML = '<div class="text-center p-4 text-danger">読み込みに失敗しました</div>';
    }
}

// ゲストのテンプレ項目を、絞り込みドロップダウン＋並び替え選択肢として組み立てる
function buildGuestFieldControls() {
    const fields = appData.guestFields || [];

    // ── 並び替えキーにゲスト項目を追加（重複追加を避けて再構築） ──
    const sortSel = document.getElementById('appSortKey');
    const prevSort = sortSel.value;
    sortSel.querySelectorAll('option[data-guest-field]').forEach(o => o.remove());
    fields.forEach(f => {
        const opt = document.createElement('option');
        opt.value = `gf:${f.id}`;
        opt.dataset.guestField = '1';
        opt.textContent = `${f.label}（ゲスト項目）`;
        sortSel.appendChild(opt);
    });
    // 以前選んでいたゲスト項目が消えていなければ選択を維持
    if ([...sortSel.options].some(o => o.value === prevSort)) sortSel.value = prevSort;

    // ── 選択式項目（select/radio）を値での絞り込みドロップダウンに ──
    const wrap = document.getElementById('appGuestFieldFilters');
    const choiceFields = fields.filter(f => (f.type === 'select' || f.type === 'radio')
        && Array.isArray(f.options) && f.options.length);
    if (!choiceFields.length) { wrap.innerHTML = ''; return; }

    wrap.innerHTML = choiceFields.map(f => `
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1">${esc(f.label)}（ゲスト）</label>
            <select class="form-select form-select-sm app-gf-filter" data-fid="${f.id}" onchange="renderApplications()">
                <option value="">すべて</option>
                ${f.options.map(o => `<option value="${esc(o)}">${esc(o)}</option>`).join('')}
            </select>
        </div>`).join('');
}

// 会員の学年フィルタ選択肢を、実際の申込者の学年から組み立てる
function populateGradeFilter() {
    const sel = document.getElementById('appFilterGrade');
    if (!sel) return;
    const prev = sel.value;
    const grades = new Set();
    [...appData.submitted, ...appData.waitlist].forEach(a => { if (a.grade) grades.add(a.grade); });
    const sorted = [...grades].sort((a, b) => gradeToNumber(a) - gradeToNumber(b));
    sel.innerHTML = '<option value="">すべて</option>' +
        sorted.map(g => `<option value="${esc(g)}">${esc(gradeLabel(g))}</option>`).join('');
    sel.value = prev;   // 再読込時に選択を維持
}

function toggleAppSortDir() {
    appSortDir *= -1;
    document.getElementById('appSortDirBtn').textContent = appSortDir === 1 ? '↑' : '↓';
    renderApplications();
}

function resetAppFilters() {
    document.getElementById('appSearch').value        = '';
    document.getElementById('appFilterKind').value    = '';
    document.getElementById('appFilterGrade').value   = '';
    document.getElementById('appFilterGender').value  = '';
    document.querySelectorAll('.app-gf-filter').forEach(sel => { sel.value = ''; });
    document.getElementById('appSortKey').value       = 'created_at';
    appSortDir = 1;
    document.getElementById('appSortDirBtn').textContent = '↑';
    renderApplications();
}

// 会員申込が検索キーワードに一致するか
function memberMatchesSearch(a, q) {
    if (!q) return true;
    const hay = [a.name_kanji, a.name_kana, a.department, a.faculty, a.line_name, gradeLabel(a.grade)]
        .filter(Boolean).join(' ').toLowerCase();
    return hay.includes(q);
}

// ゲスト申込が検索キーワードに一致するか（カスタム項目の回答も対象）
function guestMatchesSearch(g, q) {
    if (!q) return true;
    const vals = g.values ? Object.values(g.values) : [];
    const hay = [g.name, g.name_kana, g.match_key, ...vals].filter(Boolean).join(' ').toLowerCase();
    return hay.includes(q);
}

// 並び替え比較（会員・ゲスト共通）。相手側に無い属性は末尾に寄せる
function appSortValue(item, key, isGuest) {
    // ゲストのテンプレ項目（gf:<field_id>）で並び替え
    if (key.startsWith('gf:')) {
        if (!isGuest) return '￿';   // 会員はこの項目を持たないので末尾へ
        const fid = parseInt(key.slice(3));
        const v = (item.values && item.values[fid] != null) ? String(item.values[fid]) : '';
        // 数値だけの回答（代など）は数値として比較
        return /^\d+$/.test(v) ? parseInt(v) : (v || '￿');
    }
    if (key === 'created_at') return item.created_at || '';
    if (key === 'name_kana')  return isGuest ? (item.name || '') : (item.name_kana || item.name_kanji || '');
    if (key === 'grade')      return isGuest ? Infinity : gradeToNumber(item.grade);
    if (key === 'gender')     return isGuest ? 3 : ({ male: 1, female: 2 }[item.gender] || 3);
    if (key === 'department') return isGuest ? '' : (item.department || '');
    return '';
}

function sortApps(list, isGuest) {
    const key = document.getElementById('appSortKey').value;
    return [...list].sort((a, b) => {
        const va = appSortValue(a, key, isGuest), vb = appSortValue(b, key, isGuest);
        let cmp;
        if (typeof va === 'number' && typeof vb === 'number') cmp = va - vb;
        else cmp = String(va).localeCompare(String(vb), 'ja');
        return cmp * appSortDir;
    });
}

function renderApplications() {
    const container = document.getElementById('applicationsContainer');

    const q      = document.getElementById('appSearch').value.trim().toLowerCase();
    const kind   = document.getElementById('appFilterKind').value;     // ''|member|guest
    const grade  = document.getElementById('appFilterGrade').value;
    const gender = document.getElementById('appFilterGender').value;

    // 会員フィルタ（学年・性別・キーワード）
    const memFilter = (a) =>
        (!grade  || a.grade === grade) &&
        (!gender || a.gender === gender) &&
        memberMatchesSearch(a, q);
    // ゲスト項目（select/radio）での絞り込み条件を集める
    const gfFilters = [...document.querySelectorAll('.app-gf-filter')]
        .map(sel => ({ fid: parseInt(sel.dataset.fid), value: sel.value }))
        .filter(f => f.value !== '');

    // ゲストフィルタ（学年・性別はゲストに無いため、指定時はゲストを除外）
    const guestFilter = (g) =>
        !grade && !gender &&
        guestMatchesSearch(g, q) &&
        gfFilters.every(f => (g.values && g.values[f.fid] != null ? String(g.values[f.fid]) : '') === f.value);

    const showMember = kind !== 'guest';
    const showGuest  = kind !== 'member';

    const submitted      = showMember ? sortApps(appData.submitted.filter(memFilter), false)      : [];
    const waitlist       = showMember ? sortApps(appData.waitlist.filter(memFilter), false)        : [];
    const guestSubmitted = showGuest  ? sortApps(appData.guestSubmitted.filter(guestFilter), true) : [];
    const guestWaitlist  = showGuest  ? sortApps(appData.guestWaitlist.filter(guestFilter), true)  : [];

    // バッジ・定員表示は「全申込（フィルタ前）」の確定数で出す
    const totalSubmitted = appData.submitted.length + appData.guestSubmitted.length;
    document.getElementById('appCountBadge').textContent = totalSubmitted;
    const capDisplay = document.getElementById('capacityDisplay');
    if (capacity !== null) {
        const isFull = totalSubmitted >= capacity;
        const cls    = isFull ? 'bg-danger' : (totalSubmitted / capacity >= 0.8 ? 'bg-warning text-dark' : 'bg-primary');
        capDisplay.innerHTML = `<span class="badge ${cls} fs-6">${totalSubmitted} / ${capacity}</span>`;
    } else {
        capDisplay.innerHTML = `<span class="text-muted">${totalSubmitted}人申込中</span>`;
    }

    // ゲスト再提出（内容変更）のページ内アラート
    const resubmitCount = [...appData.guestSubmitted, ...appData.guestWaitlist]
        .filter(g => g.resubmitted_at).length;
    const alertEl = document.getElementById('resubmitAlert');
    if (alertEl) {
        if (resubmitCount > 0) {
            document.getElementById('resubmitAlertText').textContent =
                `会員以外（ゲスト）の申し込み内容の変更が ${resubmitCount} 件あります。`;
            alertEl.classList.remove('d-none');
            alertEl.classList.add('d-flex');
        } else {
            alertEl.classList.add('d-none');
            alertEl.classList.remove('d-flex');
        }
    }

    // 絞り込み結果のサマリー
    const shown = submitted.length + waitlist.length + guestSubmitted.length + guestWaitlist.length;
    const totalAll = appData.submitted.length + appData.waitlist.length
                   + appData.guestSubmitted.length + appData.guestWaitlist.length;
    document.getElementById('appFilterSummary').textContent =
        (shown === totalAll) ? `全${totalAll}件` : `${totalAll}件中 ${shown}件を表示`;

    let html = '';
    html += renderMemberSection('参加確定', submitted, false);
    html += renderMemberSection('キャンセル待ち', waitlist, true);
    html += renderGuestSection('参加確定（会員以外）', guestSubmitted, appData.guestFields, false);
    html += renderGuestSection('キャンセル待ち（会員以外）', guestWaitlist, appData.guestFields, true);

    if (html === '') {
        html = '<div class="text-center p-4 text-muted">該当する申込者はいません</div>';
    }
    container.innerHTML = html;
}

// 会員セクションを描画
function renderMemberSection(title, members, isWaitlist) {
    if (!members.length) return '';
    const mFields = appData.memberFields || [];
    const headCols = mFields.map(f => `<th class="small">${esc(f.label)}</th>`).join('');

    const rows = members.map((a, i) => {
        const promoted = parseInt(a.promoted) === 1 && !isWaitlist;
        const rowCls   = isWaitlist ? 'table-secondary' : (promoted ? 'table-warning' : '');
        const badge    = promoted
            ? '<span class="badge bg-warning text-dark ms-1" title="キャンセル待ちから繰り上げ">繰り上げ</span>'
            : '';
        const valCols = mFields.map(f => {
            const v = (a.member_values && a.member_values[f.id] != null) ? a.member_values[f.id] : '';
            return `<td class="small text-muted" style="white-space:pre-wrap;">${esc(v) || '—'}</td>`;
        }).join('');
        return `
            <tr class="${rowCls}">
                <td>${i + 1}</td>
                <td class="fw-semibold">${esc(a.name_kanji)}${badge}</td>
                <td>${esc(gradeLabel(a.grade))}</td>
                <td>${esc(a.gender === 'male' ? '男' : '女')}</td>
                <td class="small text-muted">${esc(a.department || '—')}</td>
                <td class="small text-muted">${esc(a.line_name || '—')}</td>
                ${valCols}
                <td class="small text-muted" style="white-space:pre-wrap;">${a.note ? esc(a.note) : '—'}</td>
                <td class="small text-muted">${formatDateTime(a.created_at)}</td>
                <td class="text-nowrap">
                    <button class="btn btn-sm btn-outline-primary" onclick="openMemberAnswerEdit(${a.id})">編集</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="cancelApplication(${a.id})">取消</button>
                </td>
            </tr>`;
    }).join('');

    const badgeCls = isWaitlist ? 'bg-secondary' : 'bg-primary';
    const note     = isWaitlist ? '<small class="text-muted ms-2">参加確定者がキャンセルすると自動で繰り上がります</small>' : '';
    const numHead  = isWaitlist ? '順番' : '#';
    const dateHead = isWaitlist ? '登録日時' : '申込日時';
    return `
        <div class="px-3 pt-3 pb-1 border-top">
            <span class="fw-semibold ${isWaitlist ? 'text-secondary' : ''}">${esc(title)}</span>
            <span class="badge ${badgeCls} ms-1">${members.length}人</span>${note}
        </div>
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr><th>${numHead}</th><th>氏名</th><th>学年</th><th>性別</th><th>学科</th><th>LINE名</th>${headCols}<th>備考</th><th>${dateHead}</th><th></th></tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>`;
}

// ゲスト申込のセクションを描画（カスタム項目を動的に列にする）
function renderGuestSection(title, guests, fields, isWaitlist) {
    if (!guests || guests.length === 0) return '';

    const headCols = fields.map(f => `<th class="small">${esc(f.label)}</th>`).join('');
    const rowCls   = isWaitlist ? 'table-secondary' : '';
    // 種別に応じた本人特定キーの見出し（obog=代 / shinkan=学科）
    const ptype    = guests.find(g => g.person_type)?.person_type;
    const matchHead= ptype === 'obog' ? '代' : (ptype === 'shinkan' ? '学科' : '代/学科');

    const rows = guests.map((g, i) => {
        const promoted = parseInt(g.promoted) === 1 && !isWaitlist;
        const badge = promoted
            ? '<span class="badge bg-warning text-dark ms-1" title="キャンセル待ちから繰り上げ">繰り上げ</span>'
            : '';
        // 未確認の内容変更（再提出）バッジ
        const resubmitted = !!g.resubmitted_at;
        const resubmitBadge = resubmitted
            ? '<span class="badge bg-warning text-dark ms-1" title="申し込み内容が変更されました">再提出</span>'
            : '';
        const resubmitBtn = resubmitted
            ? `<button class="btn btn-sm btn-outline-warning" onclick="confirmGuestResubmit(${g.id})" title="変更を確認済みにする">確認</button>`
            : '';
        const matchVal = g.match_key ? (g.person_type === 'obog' ? esc(g.match_key) + '代' : esc(g.match_key)) : '—';
        const valCols = fields.map(f => {
            const v = (g.values && g.values[f.id] != null) ? g.values[f.id] : '';
            return `<td class="small text-muted">${esc(v) || '—'}</td>`;
        }).join('');
        return `
            <tr class="${resubmitted ? 'table-warning' : (promoted ? 'table-warning' : rowCls)}">
                <td>${i + 1}</td>
                <td class="fw-semibold">${esc(g.name)}<span class="badge bg-info text-dark ms-1">ゲスト</span>${badge}${resubmitBadge}</td>
                <td class="small text-muted">${esc(g.name_kana || '—')}</td>
                <td class="small text-muted">${matchVal}</td>
                ${valCols}
                <td class="small text-muted">${formatDateTime(g.created_at)}</td>
                <td class="text-nowrap">${resubmitBtn}
                    <button class="btn btn-sm btn-outline-primary" onclick="openGuestAnswerEdit(${g.id})">編集</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="cancelGuestApplication(${g.id})">取消</button></td>
            </tr>`;
    }).join('');

    const badgeCls = isWaitlist ? 'bg-secondary' : 'bg-info text-dark';
    return `
        <div class="px-3 pt-3 pb-1 border-top">
            <span class="fw-semibold">${esc(title)}</span>
            <span class="badge ${badgeCls} ms-1">${guests.length}人</span>
        </div>
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr><th>#</th><th>氏名</th><th>カナ</th><th>${matchHead}</th>${headCols}<th>申込日時</th><th></th></tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>`;
}

async function cancelGuestApplication(id) {
    if (!confirm('このゲスト申込をキャンセルしますか？')) return;
    try {
        const res  = await fetch(`/api/event-guest-applications/${id}/cancel`, { method: 'POST' });
        const data = await res.json();
        if (data.success) { loadApplications(); }
        else { alert(data.error?.message || 'キャンセルに失敗しました'); }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

// 再提出（内容変更）を確認済みにする
async function confirmGuestResubmit(id) {
    try {
        const res  = await fetch(`/api/event-guest-applications/${id}/confirm-resubmit`, { method: 'POST' });
        const data = await res.json();
        if (data.success) { loadApplications(); showToast('確認済みにしました', 'success'); }
        else { alert(data.error?.message || '更新に失敗しました'); }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

async function cancelApplication(id) {
    if (!confirm('この申込をキャンセルしますか？')) return;
    try {
        const res  = await fetch(`/api/event-applications/${id}/cancel`, { method: 'POST' });
        const data = await res.json();
        if (data.success) { loadApplications(); }
        else { alert(data.error?.message || 'キャンセルに失敗しました'); }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

// ──────────────────────────────────
// 申込回答の編集（会員・ゲスト共通モーダル）
// ──────────────────────────────────
let answerEditState = null;   // { kind:'member'|'guest', id, fields, values }

function findMemberApp(id) {
    return [...appData.submitted, ...appData.waitlist].find(a => parseInt(a.id) === parseInt(id));
}
function findGuestApp(id) {
    return [...appData.guestSubmitted, ...appData.guestWaitlist].find(g => parseInt(g.id) === parseInt(id));
}

function openMemberAnswerEdit(id) {
    const a = findMemberApp(id);
    if (!a) return;
    answerEditState = { kind: 'member', id, fields: appData.memberFields || [], values: a.member_values || {} };
    document.getElementById('answerEditWho').textContent = `${a.name_kanji} さん（会員）`;
    renderAnswerEditForm(a.note || '');
    getAnswerEditModal().show();
}

function openGuestAnswerEdit(id) {
    const g = findGuestApp(id);
    if (!g) return;
    answerEditState = { kind: 'guest', id, fields: appData.guestFields || [], values: g.values || {} };
    document.getElementById('answerEditWho').textContent = `${g.name} さん（ゲスト）`;
    renderAnswerEditForm(g.note || '');
    getAnswerEditModal().show();
}

let _answerEditModal = null;
function getAnswerEditModal() {
    if (!_answerEditModal) _answerEditModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('answerEditModal'));
    return _answerEditModal;
}

function renderAnswerEditForm(note) {
    const { fields, values } = answerEditState;
    document.getElementById('answerEditError').classList.add('d-none');
    document.getElementById('answerEditNote').value = note || '';
    const wrap = document.getElementById('answerEditFields');
    wrap.innerHTML = fields.map(f => {
        const fid = f.id;
        const cur = (values && values[fid] != null) ? String(values[fid]) : '';
        const required = parseInt(f.is_required) === 1;
        const opts = Array.isArray(f.options) ? f.options : [];
        const reqMark = required ? ' <span class="text-danger">*</span>' : '';
        let input;
        if (f.type === 'textarea') {
            input = `<textarea class="form-control form-control-sm ans-field" data-fid="${fid}" rows="2">${esc(cur)}</textarea>`;
        } else if (f.type === 'select') {
            input = `<select class="form-select form-select-sm ans-field" data-fid="${fid}"><option value="">選択してください</option>`
                  + opts.map(o => `<option value="${esc(o)}" ${o === cur ? 'selected' : ''}>${esc(o)}</option>`).join('') + `</select>`;
        } else if (f.type === 'radio') {
            input = '<div>' + opts.map((o, i) =>
                `<div class="form-check"><input class="form-check-input ans-field" type="radio" name="ans_${fid}" data-fid="${fid}" id="ans_${fid}_${i}" value="${esc(o)}" ${o === cur ? 'checked' : ''}>`
                + `<label class="form-check-label" for="ans_${fid}_${i}">${esc(o)}</label></div>`).join('') + '</div>';
        } else {
            input = `<input type="text" class="form-control form-control-sm ans-field" data-fid="${fid}" value="${esc(cur)}" maxlength="255">`;
        }
        return `<div class="mb-3"><label class="form-label small fw-semibold">${esc(f.label)}${reqMark}</label>${input}</div>`;
    }).join('') || '<div class="text-muted small mb-2">この申込に紐づく質問項目はありません。</div>';
}

async function saveAnswerEdit() {
    if (!answerEditState) return;
    const { kind, id } = answerEditState;
    const errEl = document.getElementById('answerEditError');
    errEl.classList.add('d-none');

    const values = {};
    document.querySelectorAll('#answerEditFields .ans-field').forEach(el => {
        const fid = el.dataset.fid;
        if (el.type === 'radio') { if (el.checked) values[fid] = el.value; }
        else { values[fid] = el.value.trim(); }
    });
    const note = document.getElementById('answerEditNote').value.trim();

    const url = kind === 'member'
        ? `/api/event-applications/${id}/answers`
        : `/api/event-guest-applications/${id}/answers`;
    const btn = document.getElementById('answerEditSaveBtn');
    btn.disabled = true;
    try {
        const res  = await fetch(url, {
            method: 'PUT', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ note, values }),
        });
        const data = await res.json();
        if (data.success) {
            getAnswerEditModal().hide();
            loadApplications();
            showToast('回答を更新しました', 'success');
        } else {
            errEl.textContent = data.error?.message || '更新に失敗しました';
            errEl.classList.remove('d-none');
        }
    } catch (e) {
        errEl.textContent = '通信エラーが発生しました';
        errEl.classList.remove('d-none');
    } finally {
        btn.disabled = false;
    }
}

// ──────────────────────────────────
// 雑費
// ──────────────────────────────────
async function loadExpenses() {
    const container = document.getElementById('expensesContainer');
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}`);
        const data = await res.json();
        const expenses = data.data?.expenses || [];

        if (expenses.length === 0) {
            container.innerHTML = '<div class="text-center p-4 text-muted">雑費はありません</div>';
            return;
        }

        const total = expenses.reduce((s, e) => s + parseInt(e.amount), 0);
        const rows  = expenses.map(e => `
            <tr>
                <td>${esc(e.name)}</td>
                <td class="text-end">${Number(e.amount).toLocaleString()}円</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteExpense(${e.id})">削除</button>
                </td>
            </tr>`).join('');

        container.innerHTML = `
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>項目名</th><th class="text-end">金額</th><th></th></tr>
                </thead>
                <tbody>${rows}</tbody>
                <tfoot class="table-light fw-semibold">
                    <tr><td>合計</td><td class="text-end">${total.toLocaleString()}円</td><td></td></tr>
                </tfoot>
            </table>`;
    } catch (e) {
        container.innerHTML = '<div class="text-center p-4 text-danger">読み込みに失敗しました</div>';
    }
}

async function addExpense() {
    const name   = document.getElementById('expenseName').value.trim();
    const amount = parseInt(document.getElementById('expenseAmount').value) || 0;
    if (!name) { alert('項目名を入力してください'); return; }

    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/expenses`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, amount }),
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('expenseName').value   = '';
            document.getElementById('expenseAmount').value = '0';
            loadExpenses();
        } else {
            alert(data.error?.message || '追加に失敗しました');
        }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

async function deleteExpense(id) {
    if (!confirm('この雑費を削除しますか？')) return;
    try {
        const res  = await fetch(`/api/event-expenses/${id}`, { method: 'DELETE' });
        const data = await res.json();
        if (data.success) { loadExpenses(); }
        else { alert(data.error?.message || '削除に失敗しました'); }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

// ──────────────────────────────────
// 費用計算
// ──────────────────────────────────
async function loadCalc() {
    const container = document.getElementById('calcResult');
    container.innerHTML = '<div class="text-center text-muted">計算中...</div>';
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/calculate`);
        const data = await res.json();
        const d    = data.data;

        if (!data.success) {
            container.innerHTML = '<div class="text-danger">計算に失敗しました</div>';
            return;
        }

        const expenseRows = (d.expenses || []).map(e => `
            <tr><td>${esc(e.name)}</td><td class="text-end">${Number(e.amount).toLocaleString()}円</td></tr>
        `).join('');

        container.innerHTML = `
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card text-center border-primary">
                        <div class="card-body py-3">
                            <div class="fs-4 fw-bold text-primary">${d.applicant_count}人</div>
                            <div class="small text-muted">申込人数${d.guest_count > 0 ? `<br><span class="text-info">（ゲスト${d.guest_count}人含む）</span>` : ''}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <div class="fs-4 fw-bold">${Number(d.participation_fee).toLocaleString()}円</div>
                            <div class="small text-muted">参加費</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <div class="fs-4 fw-bold">${Number(d.expense_per_person).toLocaleString()}円</div>
                            <div class="small text-muted">雑費負担（1人）</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card text-center border-success">
                        <div class="card-body py-3">
                            <div class="fs-4 fw-bold text-success">${Number(d.total_per_person).toLocaleString()}円</div>
                            <div class="small text-muted">1人あたり合計</div>
                        </div>
                    </div>
                </div>
            </div>
            ${d.expenses.length > 0 ? `
            <h6 class="mb-2">雑費内訳</h6>
            <table class="table table-sm">
                <thead class="table-light"><tr><th>項目</th><th class="text-end">金額</th></tr></thead>
                <tbody>${expenseRows}</tbody>
                <tfoot class="fw-semibold"><tr><td>合計</td><td class="text-end">${Number(d.total_expenses).toLocaleString()}円</td></tr></tfoot>
            </table>` : '<p class="text-muted">雑費は登録されていません</p>'}
            ${d.applicant_count === 0 ? '<div class="alert alert-warning mt-3">申込者がいないため1人あたりの費用は計算できません</div>' : ''}`;
    } catch (e) {
        container.innerHTML = '<div class="text-danger">通信エラーが発生しました</div>';
    }
}

// ──────────────────────────────────
// ユーティリティ
// ──────────────────────────────────
function esc(str) {
    return String(str ?? '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function gradeLabel(g) {
    if (g === 'OB' || g === 'OG') return g;
    return g + '年';
}

function formatDateTime(str) {
    if (!str) return '—';
    const d = new Date(str);
    return `${d.getFullYear()}/${d.getMonth()+1}/${d.getDate()} ${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`;
}

function showToast(message, type = 'success') {
    const t = document.createElement('div');
    t.className = `toast align-items-center text-bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
    t.setAttribute('role', 'alert');
    t.innerHTML = `<div class="d-flex"><div class="toast-body">${esc(message)}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
    document.body.appendChild(t);
    const toast = new bootstrap.Toast(t, { delay: 3000 });
    toast.show();
    t.addEventListener('hidden.bs.toast', () => t.remove());
}

// ──────────────────────────────────
// ゲストフォーム項目（カスタム質問）
// ──────────────────────────────────
let guestFieldsLoaded = false;

const GF_TYPE_LABELS = {
    text: '短文', textarea: '長文', select: 'プルダウン', radio: 'ラジオ',
};

function toggleGfOptions() {
    const type = document.getElementById('gfType').value;
    document.getElementById('gfOptionsWrap').style.display =
        (type === 'select' || type === 'radio') ? 'block' : 'none';
}

async function loadGuestFields() {
    const container = document.getElementById('guestFieldsContainer');
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/guest-fields`);
        const data = await res.json();
        renderGuestFields(data.data?.fields || []);
        guestFieldsLoaded = true;
    } catch (e) {
        container.innerHTML = '<div class="text-center p-4 text-danger">読み込みに失敗しました</div>';
    }
}

let guestFieldsCache = [];

function renderGuestFields(fields) {
    guestFieldsCache = fields || [];
    const container = document.getElementById('guestFieldsContainer');
    if (!fields.length) {
        container.innerHTML = '<div class="text-center p-4 text-muted">項目はまだありません。「お名前」のみのフォームになります。</div>';
        return;
    }
    const rows = fields.map((f, idx) => {
        // 編集中の項目はインライン編集フォームを表示
        if (parseInt(f.id) === editingGuestFieldId) return renderGuestFieldEditRow(f);

        const opts = Array.isArray(f.options) && f.options.length
            ? `<span class="small text-muted ms-2">選択肢${f.options.length}件</span>` : '';
        const req  = parseInt(f.is_required) === 1
            ? '<span class="badge bg-danger ms-1">必須</span>'
            : '<span class="badge bg-light text-dark border ms-1">任意</span>';
        const desc = f.description
            ? `<div class="small text-muted mt-1"><i class="bi bi-chat-left-text"></i> ${esc(f.description)}</div>` : '';
        const aud = f.audience === 'member'
            ? '<span class="badge bg-primary ms-1">会員</span>'
            : '<span class="badge bg-info text-dark ms-1">ゲスト</span>';
        const upDisabled   = idx === 0 ? 'disabled' : '';
        const downDisabled = idx === fields.length - 1 ? 'disabled' : '';
        return `
            <tr>
                <td class="text-nowrap" style="width:1%;">
                    <button class="btn btn-sm btn-light border" data-gf-action="up" data-gf-id="${f.id}" ${upDisabled} title="上へ">↑</button>
                    <button class="btn btn-sm btn-light border" data-gf-action="down" data-gf-id="${f.id}" ${downDisabled} title="下へ">↓</button>
                </td>
                <td class="fw-semibold">${esc(f.label)}${aud}${req}${opts}${desc}</td>
                <td><span class="badge bg-secondary">${esc(GF_TYPE_LABELS[f.type] || f.type)}</span></td>
                <td class="text-end text-nowrap">
                    <button class="btn btn-sm btn-outline-primary" data-gf-action="edit" data-gf-id="${f.id}">編集</button>
                    <button class="btn btn-sm btn-outline-primary" data-gf-action="desc" data-gf-id="${f.id}">説明文</button>
                    <button class="btn btn-sm btn-outline-secondary" data-gf-action="required" data-gf-id="${f.id}" data-gf-required="${parseInt(f.is_required) === 1 ? 0 : 1}">
                        ${parseInt(f.is_required) === 1 ? '任意にする' : '必須にする'}
                    </button>
                    <button class="btn btn-sm btn-outline-danger" data-gf-action="delete" data-gf-id="${f.id}">削除</button>
                </td>
            </tr>`;
    }).join('');
    container.innerHTML = `
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>順序</th><th>項目名</th><th>形式</th><th></th></tr></thead>
            <tbody>${rows}</tbody>
        </table>`;
}

let editingGuestFieldId = null;   // インライン編集中の項目ID

// 項目のインライン編集フォーム（項目名・入力形式・選択肢）
function renderGuestFieldEditRow(f) {
    const typeOpts = Object.entries({ text: '短文（1行）', textarea: '長文（複数行）', select: 'プルダウン選択', radio: 'ラジオ選択' })
        .map(([v, l]) => `<option value="${v}" ${f.type === v ? 'selected' : ''}>${l}</option>`).join('');
    const optsValue = Array.isArray(f.options) ? f.options.join(', ') : '';
    const showOpts  = (f.type === 'select' || f.type === 'radio');
    return `
        <tr class="table-active">
            <td colspan="3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small mb-1">項目名</label>
                        <input type="text" class="form-control form-control-sm" id="gfEditLabel" value="${esc(f.label)}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">入力形式</label>
                        <select class="form-select form-select-sm" id="gfEditType"
                                onchange="document.getElementById('gfEditOptionsWrap').style.display = (this.value==='select'||this.value==='radio') ? 'block' : 'none'">
                            ${typeOpts}
                        </select>
                    </div>
                    <div class="col-md-5" id="gfEditOptionsWrap" style="display:${showOpts ? 'block' : 'none'};">
                        <label class="form-label small mb-1">選択肢（カンマ区切り）</label>
                        <input type="text" class="form-control form-control-sm" id="gfEditOptions" value="${esc(optsValue)}" placeholder="例: 経済学科, 法学科, 工学科">
                    </div>
                </div>
                <div class="mt-2 d-flex gap-2">
                    <button class="btn btn-sm btn-primary" data-gf-action="save-edit" data-gf-id="${f.id}">保存</button>
                    <button class="btn btn-sm btn-outline-secondary" data-gf-action="cancel-edit" data-gf-id="${f.id}">キャンセル</button>
                </div>
            </td>
        </tr>`;
}

async function addGuestField() {
    const audience = document.getElementById('gfAudience').value;
    const label = document.getElementById('gfLabel').value.trim();
    const desc  = document.getElementById('gfDescription').value.trim();
    const type  = document.getElementById('gfType').value;
    const req   = document.getElementById('gfRequired').checked ? 1 : 0;
    if (!label) { alert('項目名を入力してください'); return; }

    let options = null;
    if (type === 'select' || type === 'radio') {
        options = document.getElementById('gfOptions').value
            .split(',').map(s => s.trim()).filter(s => s !== '');
        if (!options.length) { alert('選択肢をカンマ区切りで入力してください'); return; }
    }

    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/guest-fields`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ audience, label, description: desc, type, options, is_required: req }),
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('gfLabel').value       = '';
            document.getElementById('gfDescription').value = '';
            document.getElementById('gfOptions').value     = '';
            document.getElementById('gfRequired').checked  = false;
            renderGuestFields(data.data.fields || []);
            showToast('項目を追加しました', 'success');
        } else {
            alert(data.error?.message || '追加に失敗しました');
        }
    } catch (e) { alert('通信エラーが発生しました'); }
}

async function applyGuestFieldTemplate() {
    if (!confirm('定番の追加項目（LINE名・性別・テニスの経験・備考）を追加しますか？\n氏名・カナ・代/学科はフォーム標準項目のため含まれません。既存の項目はそのまま残り、末尾に追加されます。')) return;
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/guest-fields/template`, { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            renderGuestFields(data.data.fields || []);
            showToast('テンプレートを追加しました', 'success');
        } else {
            alert(data.error?.message || '追加に失敗しました');
        }
    } catch (e) { alert('通信エラーが発生しました'); }
}

// ゲスト項目一覧のボタンはイベント委譲で処理する
// （説明文に引用符・改行が含まれても onclick 文字列が壊れないようにするため）
document.getElementById('guestFieldsContainer').addEventListener('click', (e) => {
    const btn = e.target.closest('[data-gf-action]');
    if (!btn) return;
    const id     = parseInt(btn.dataset.gfId);
    const action = btn.dataset.gfAction;
    if (action === 'desc')        editGuestFieldDescription(id);
    if (action === 'required')    toggleGuestFieldRequired(id, parseInt(btn.dataset.gfRequired));
    if (action === 'delete')      deleteGuestField(id);
    if (action === 'edit')      { editingGuestFieldId = id; renderGuestFields(guestFieldsCache); }
    if (action === 'cancel-edit'){ editingGuestFieldId = null; renderGuestFields(guestFieldsCache); }
    if (action === 'save-edit')   saveGuestFieldEdit(id);
    if (action === 'up')          moveGuestField(id, -1);
    if (action === 'down')        moveGuestField(id, 1);
});

// 項目を1つ上/下へ移動して順番を保存
async function moveGuestField(id, dir) {
    const order = guestFieldsCache.map(f => parseInt(f.id));
    const i = order.indexOf(id);
    const j = i + dir;
    if (i < 0 || j < 0 || j >= order.length) return;
    [order[i], order[j]] = [order[j], order[i]];

    // 即座に並びを反映（楽観的更新）
    guestFieldsCache.sort((a, b) => order.indexOf(parseInt(a.id)) - order.indexOf(parseInt(b.id)));
    renderGuestFields(guestFieldsCache);

    // sort_order を 0..n で送信
    const payload = {};
    order.forEach((fid, idx) => { payload[fid] = idx; });
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/guest-fields/reorder`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order: payload }),
        });
        const data = await res.json();
        if (data.success) {
            renderGuestFields(data.data.fields || []);
        } else {
            alert(data.error?.message || '並び替えに失敗しました');
            loadGuestFields();
        }
    } catch (e) {
        alert('通信エラーが発生しました');
        loadGuestFields();
    }
}

async function saveGuestFieldEdit(id) {
    const label = document.getElementById('gfEditLabel').value.trim();
    const type  = document.getElementById('gfEditType').value;
    if (!label) { alert('項目名を入力してください'); return; }

    let options = null;
    if (type === 'select' || type === 'radio') {
        options = document.getElementById('gfEditOptions').value
            .split(',').map(s => s.trim()).filter(s => s !== '');
        if (!options.length) { alert('選択肢をカンマ区切りで入力してください'); return; }
    }

    try {
        const res  = await fetch(`/api/event-guest-fields/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ label, type, options }),
        });
        const data = await res.json();
        if (data.success) {
            editingGuestFieldId = null;
            renderGuestFields(data.data.fields || []);
            showToast('項目を更新しました', 'success');
        } else {
            alert(data.error?.message || '更新に失敗しました');
        }
    } catch (e) { alert('通信エラーが発生しました'); }
}

async function editGuestFieldDescription(id) {
    const field   = guestFieldsCache.find(f => parseInt(f.id) === id);
    const current = field ? (field.description || '') : '';
    const desc = prompt('この項目の説明文（質問文・補足）を入力してください。\n申込フォームで項目名の下に表示されます。空欄にすると非表示になります。', current);
    if (desc === null) return;   // キャンセル
    try {
        const res  = await fetch(`/api/event-guest-fields/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ description: desc }),
        });
        const data = await res.json();
        if (data.success) { renderGuestFields(data.data.fields || []); showToast('説明文を更新しました', 'success'); }
        else { alert(data.error?.message || '更新に失敗しました'); }
    } catch (e) { alert('通信エラーが発生しました'); }
}

async function toggleGuestFieldRequired(id, required) {
    try {
        const res  = await fetch(`/api/event-guest-fields/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ is_required: required }),
        });
        const data = await res.json();
        if (data.success) { renderGuestFields(data.data.fields || []); }
        else { alert(data.error?.message || '更新に失敗しました'); }
    } catch (e) { alert('通信エラーが発生しました'); }
}

async function deleteGuestField(id) {
    if (!confirm('この項目を削除しますか？既存のゲスト申込の回答も削除されます。')) return;
    try {
        const res  = await fetch(`/api/event-guest-fields/${id}`, { method: 'DELETE' });
        const data = await res.json();
        if (data.success) { renderGuestFields(data.data.fields || []); showToast('削除しました', 'success'); }
        else { alert(data.error?.message || '削除に失敗しました'); }
    } catch (e) { alert('通信エラーが発生しました'); }
}

// ──────────────────────────────────
// 班決め（グループ分け）
// ──────────────────────────────────
let teamMembers   = [];   // 参加確定者（team_no を保持）
let teamConstraints = [];  // 制約一覧
let teamBalanceFields = [];  // 班分けの基準に使えるゲスト項目（select/radio）
let teamsLoaded   = false;

let teamSortConfig = {
    primary:   { key: 'team_no',   direction: 1 },
    secondary: { key: 'name_kana', direction: 1 },
};

let teamView = 'teams';   // 'teams'（班ごとカード） / 'list'（一覧表）

// 班ビュー / 一覧ビュー切替
function setTeamView(view) {
    teamView = view;
    document.getElementById('teamViewBtn').className = view === 'teams' ? 'btn btn-primary' : 'btn btn-outline-primary';
    document.getElementById('listViewBtn').className = view === 'list'  ? 'btn btn-primary' : 'btn btn-outline-primary';
    // 並べ替えは一覧ビューのときだけ表示
    document.getElementById('teamSortControls').style.display = view === 'list' ? 'flex' : 'none';
    renderTeamTable();
}

function gradeToNumber(grade) {
    if (grade === 'OB' || grade === 'OG') return 99;
    if (grade === 'M1') return 5;
    if (grade === 'M2') return 6;
    const n = parseInt(grade);
    return isNaN(n) ? 98 : n;
}

function compareTeamMembers(a, b, key) {
    if (key === 'team_no') {
        // 未割り当て(null)は末尾へ
        const ta = (a.team_no === null || a.team_no === undefined || a.team_no === '') ? Infinity : parseInt(a.team_no);
        const tb = (b.team_no === null || b.team_no === undefined || b.team_no === '') ? Infinity : parseInt(b.team_no);
        return ta - tb;
    } else if (key === 'name_kana') {
        return (a.name_kana || '').localeCompare(b.name_kana || '', 'ja');
    } else if (key === 'grade') {
        return gradeToNumber(a.grade) - gradeToNumber(b.grade);
    } else if (key === 'gender') {
        const order = { 'male': 1, 'female': 2 };
        return (order[a.gender] || 3) - (order[b.gender] || 3);
    } else if (key === 'faculty') {
        return (a.faculty || '').localeCompare(b.faculty || '', 'ja');
    }
    return 0;
}

function applyTeamSorting() {
    teamSortConfig.primary.key   = document.getElementById('teamSortPrimary').value;
    teamSortConfig.secondary.key = document.getElementById('teamSortSecondary').value || '';
    renderTeamTable();
}

function toggleTeamSortDirection(level) {
    if (level === 'primary') {
        teamSortConfig.primary.direction *= -1;
        document.getElementById('teamSortPrimaryDir').textContent = teamSortConfig.primary.direction === 1 ? '↑' : '↓';
    } else {
        teamSortConfig.secondary.direction *= -1;
        document.getElementById('teamSortSecondaryDir').textContent = teamSortConfig.secondary.direction === 1 ? '↑' : '↓';
    }
    renderTeamTable();
}

async function loadTeams() {
    if (teamsLoaded) return;
    const container = document.getElementById('teamsContainer');
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/teams`);
        const data = await res.json();
        const members = (data.data?.members || []).map(m => ({
            ...m,
            is_guest: false,
            team_no: (m.team_no === null || m.team_no === undefined) ? null : parseInt(m.team_no),
        }));
        const guests = (data.data?.guests || []).map(g => ({
            ...g,
            is_guest: true,
            team_no: (g.team_no === null || g.team_no === undefined) ? null : parseInt(g.team_no),
        }));
        teamMembers     = members.concat(guests);
        teamConstraints = data.data?.constraints || [];
        teamBalanceFields = data.data?.balance_fields || [];
        teamsLoaded = true;

        renderBalanceFieldChecks();
        renderConstraintSelects();
        renderConstraints();
        renderTeamTable();
    } catch (e) {
        container.innerHTML = '<div class="text-center p-4 text-danger">読み込みに失敗しました</div>';
    }
}

// ゲスト項目（select/radio）を「均等にする基準」のチェックボックスとして表示
function renderBalanceFieldChecks() {
    const wrap = document.getElementById('balanceGuestFields');
    if (!wrap) return;
    wrap.innerHTML = teamBalanceFields.map(f => `
        <div class="form-check form-check-inline">
            <input class="form-check-input balance-gf" type="checkbox" id="balanceGf${f.id}" data-fid="${f.id}">
            <label class="form-check-label" for="balanceGf${f.id}">${esc(f.label)}を分散</label>
        </div>`).join('');
}

// 制約用セレクトボックスを更新
function renderConstraintSelects() {
    const opts = '<option value="">選択してください</option>' +
        teamMembers
            .filter(m => !m.is_guest && m.member_id != null)
            .sort((a, b) => (a.name_kana || '').localeCompare(b.name_kana || '', 'ja'))
            .map(m => `<option value="${m.member_id}">${esc(m.name_kanji)}（${esc(gradeLabel(m.grade))}）</option>`)
            .join('');
    document.getElementById('constraintMemberA').innerHTML = opts;
    document.getElementById('constraintMemberB').innerHTML = opts;
}

function renderConstraints() {
    const container = document.getElementById('constraintsContainer');
    if (teamConstraints.length === 0) {
        container.innerHTML = '<div class="text-muted small">制約はまだありません</div>';
        return;
    }
    container.innerHTML = teamConstraints.map(c => {
        const badge = c.type === 'together'
            ? '<span class="badge bg-success">絶対一緒</span>'
            : '<span class="badge bg-danger">絶対別</span>';
        return `
            <span class="badge bg-light text-dark border me-2 mb-2 p-2">
                ${badge}
                <span class="ms-1">${esc(c.member_a_name)} と ${esc(c.member_b_name)}</span>
                <button class="btn-close ms-2" style="font-size:.6rem;vertical-align:middle;" onclick="deleteConstraint(${c.id})" title="削除"></button>
            </span>`;
    }).join('');
}

async function addConstraint() {
    const memberA = document.getElementById('constraintMemberA').value;
    const memberB = document.getElementById('constraintMemberB').value;
    const type    = document.getElementById('constraintType').value;

    if (!memberA || !memberB) { alert('2名のメンバーを選択してください'); return; }
    if (memberA === memberB)  { alert('異なるメンバーを選択してください'); return; }

    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/constraints`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ member_a_id: parseInt(memberA), member_b_id: parseInt(memberB), type }),
        });
        const data = await res.json();
        if (data.success) {
            teamConstraints = data.data.constraints || [];
            renderConstraints();
            document.getElementById('constraintMemberA').value = '';
            document.getElementById('constraintMemberB').value = '';
            showToast('制約を追加しました', 'success');
        } else {
            alert(data.error?.message || '追加に失敗しました');
        }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

async function deleteConstraint(id) {
    if (!confirm('この制約を削除しますか？')) return;
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/constraints/${id}`, { method: 'DELETE' });
        const data = await res.json();
        if (data.success) {
            teamConstraints = teamConstraints.filter(c => parseInt(c.id) !== id);
            renderConstraints();
            showToast('制約を削除しました', 'success');
        } else {
            alert(data.error?.message || '削除に失敗しました');
        }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

// 自動振り分け（制約を尊重 + 選択基準でバランス）
function autoAssignTeams() {
    const teamCount = parseInt(document.getElementById('teamCount').value);
    if (!teamCount || teamCount < 1) { alert('班の数を正しく入力してください'); return; }
    if (teamMembers.length === 0) { alert('参加確定者がいません'); return; }

    const useGrade   = document.getElementById('balanceGrade').checked;
    const useGender  = document.getElementById('balanceGender').checked;
    const useFaculty = document.getElementById('balanceFaculty').checked;
    // チェックされたゲスト項目（select/radio）を分散基準に追加
    const useGuestFieldIds = [...document.querySelectorAll('.balance-gf:checked')]
        .map(cb => parseInt(cb.dataset.fid));

    // 制約は会員（member_id）同士のみ。クラスタ／apart のキーには各メンバーの一意な
    // 申込ID（m.id）を使い、会員→ID への対応表を介して制約を変換する。
    const memberIdToAppId = {};
    teamMembers.forEach(m => { if (!m.is_guest && m.member_id != null) memberIdToAppId[m.member_id] = m.id; });

    // ── 1. together 制約でユニオンファインド（グループ化） ──
    const parent = {};
    teamMembers.forEach(m => { parent[m.id] = m.id; });
    function find(x) { while (parent[x] !== x) { parent[x] = parent[parent[x]]; x = parent[x]; } return x; }
    function union(a, b) { const ra = find(a), rb = find(b); if (ra !== rb) parent[ra] = rb; }

    teamConstraints.forEach(c => {
        const a = memberIdToAppId[c.member_a_id], b = memberIdToAppId[c.member_b_id];
        if (c.type === 'together' && a !== undefined && b !== undefined) {
            union(a, b);
        }
    });

    // クラスタ（同じ班に入るべき人のまとまり）を作成
    const clusters = {};
    teamMembers.forEach(m => {
        const root = find(m.id);
        (clusters[root] = clusters[root] || []).push(m);
    });
    let clusterList = Object.values(clusters);

    // ── 2. apart 制約マップ（app_id -> Set(禁止相手 app_id)） ──
    const apartMap = {};
    teamConstraints.forEach(c => {
        if (c.type === 'apart') {
            const a = memberIdToAppId[c.member_a_id], b = memberIdToAppId[c.member_b_id];
            if (a === undefined || b === undefined) return;
            (apartMap[a] = apartMap[a] || new Set()).add(b);
            (apartMap[b] = apartMap[b] || new Set()).add(a);
        }
    });

    // ── 3. クラスタを大きい順に並べ、貪欲法で各班へ ──
    clusterList.sort((a, b) => b.length - a.length);

    const teams = Array.from({ length: teamCount }, () => []);

    // 各班ごとの属性カウント（バランス評価用）
    function teamHasApartConflict(teamIdx, cluster) {
        for (const m of teams[teamIdx]) {
            for (const cm of cluster) {
                if (apartMap[m.id] && apartMap[m.id].has(cm.id)) return true;
            }
        }
        return false;
    }

    // バランススコア: 配置後にどれだけ偏るか（小さいほど良い）
    function balanceCost(teamIdx, cluster) {
        let cost = teams[teamIdx].length + cluster.length; // 人数均等が基本
        // valFn が空文字/null を返す人は数えない（その属性を持たない人＝偏りに影響させない）
        const counts = (valFn) => {
            const c = {};
            const tally = m => { const v = valFn(m); if (v !== '' && v != null) c[v] = (c[v] || 0) + 1; };
            teams[teamIdx].forEach(tally);
            cluster.forEach(tally);
            // 同属性の重複が多いほどペナルティ
            return Object.values(c).reduce((s, n) => s + (n > 1 ? (n - 1) * 2 : 0), 0);
        };
        if (useGrade)   cost += counts(m => m.grade != null ? gradeToNumber(m.grade) : '');
        if (useGender)  cost += counts(m => m.gender || '');
        if (useFaculty) cost += counts(m => m.faculty || '');
        // ゲスト項目（回答値で分散）。会員は values を持たないので自然と無視される
        useGuestFieldIds.forEach(fid => {
            cost += counts(m => (m.values && m.values[fid] != null) ? String(m.values[fid]) : '');
        });
        return cost;
    }

    let unplaced = [];
    clusterList.forEach(cluster => {
        // apart 制約に違反しない班の中で、最もコストの低い班を選ぶ
        let best = -1, bestCost = Infinity;
        for (let t = 0; t < teamCount; t++) {
            if (teamHasApartConflict(t, cluster)) continue;
            const cost = balanceCost(t, cluster);
            if (cost < bestCost) { bestCost = cost; best = t; }
        }
        if (best === -1) {
            // どの班にも入れられない（apart制約が厳しすぎ）→ 一番人数の少ない班へ
            unplaced.push(cluster);
        } else {
            cluster.forEach(m => { m.team_no = best + 1; });
            teams[best].push(...cluster);
        }
    });

    // 配置できなかったクラスタは人数最少の班へ強制配置
    unplaced.forEach(cluster => {
        let min = 0;
        for (let t = 1; t < teamCount; t++) if (teams[t].length < teams[min].length) min = t;
        cluster.forEach(m => { m.team_no = min + 1; });
        teams[min].push(...cluster);
    });

    renderTeamTable();
    saveTeams();   // 自動振り分け結果を即保存

    if (unplaced.length > 0) {
        showToast('一部、絶対別の制約を満たせない班割りになりました', 'warning');
    } else {
        showToast('自動振り分けしました', 'success');
    }
}

function renderTeamTable() {
    const container = document.getElementById('teamsContainer');
    const summary   = document.getElementById('teamsSummary');

    if (teamMembers.length === 0) {
        summary.innerHTML = '';
        container.innerHTML = '<div class="text-center p-4 text-muted">参加確定者がいません</div>';
        return;
    }

    // サマリー（班ごとの人数）
    const teamCounts = {};
    teamMembers.forEach(m => {
        const key = (m.team_no === null) ? '未割り当て' : `${m.team_no}班`;
        teamCounts[key] = (teamCounts[key] || 0) + 1;
    });
    const summaryBadges = Object.keys(teamCounts)
        .sort((a, b) => {
            if (a === '未割り当て') return 1;
            if (b === '未割り当て') return -1;
            return parseInt(a) - parseInt(b);
        })
        .map(k => {
            const cls = k === '未割り当て' ? 'bg-secondary' : 'bg-primary';
            return `<span class="badge ${cls} me-1 mb-1">${esc(k)}: ${teamCounts[k]}人</span>`;
        }).join('');
    summary.innerHTML = `<div class="mb-2">${summaryBadges}</div>`;

    if (teamView === 'teams') {
        renderTeamCards(container);
    } else {
        renderTeamList(container);
    }
}

// ── 一覧ビュー（ソート可能な表） ──
function renderTeamList(container) {
    const sorted = [...teamMembers].sort((a, b) => {
        let cmp = compareTeamMembers(a, b, teamSortConfig.primary.key);
        if (cmp !== 0) return cmp * teamSortConfig.primary.direction;
        if (teamSortConfig.secondary.key) {
            cmp = compareTeamMembers(a, b, teamSortConfig.secondary.key);
            if (cmp !== 0) return cmp * teamSortConfig.secondary.direction;
        }
        return (a.name_kana || '').localeCompare(b.name_kana || '', 'ja');
    });

    const rows = sorted.map((m, i) => {
        const guestBadge = m.is_guest ? '<span class="badge bg-info text-dark ms-1">ゲスト</span>' : '';
        return `
        <tr>
            <td>${i + 1}</td>
            <td class="fw-semibold">${esc(m.name_kanji)}${guestBadge}</td>
            <td>${m.is_guest ? '—' : esc(gradeLabel(m.grade))}</td>
            <td>${m.is_guest ? '—' : esc(m.gender === 'male' ? '男' : '女')}</td>
            <td class="small text-muted">${esc(m.faculty || '—')}</td>
            <td class="small text-muted">${esc(m.department || '—')}</td>
            <td style="width:90px;">
                <input type="number" class="form-control form-control-sm" min="1" value="${m.team_no ?? ''}"
                       placeholder="—" onchange="updateTeamNo('${m.id}', this.value)">
            </td>
        </tr>`;
    }).join('');

    container.innerHTML = `
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr><th>#</th><th>氏名</th><th>学年</th><th>性別</th><th>学部</th><th>学科</th><th>班番号</th></tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>`;
}

// ── 班ビュー（班ごとのカード） ──
const TEAM_COLORS = [
    '#0d6efd', '#198754', '#dc3545', '#fd7e14', '#6f42c1',
    '#0dcaf0', '#d63384', '#20c997', '#ffc107', '#6610f2',
];

function genderLabel(g) { return g === 'male' ? '男' : (g === 'female' ? '女' : '—'); }

function renderTeamCards(container) {
    // 班番号ごとにグループ化
    const groups = {};        // team_no -> [members]
    const unassigned = [];
    teamMembers.forEach(m => {
        if (m.team_no === null) unassigned.push(m);
        else (groups[m.team_no] = groups[m.team_no] || []).push(m);
    });

    const teamNos = Object.keys(groups).map(Number).sort((a, b) => a - b);

    if (teamNos.length === 0 && unassigned.length === teamMembers.length) {
        container.innerHTML = `
            <div class="text-center p-4 text-muted">
                まだ班が割り当てられていません。<br>
                上の「班の自動振り分け」を実行するか、一覧ビューで班番号を入力してください。
            </div>`;
        return;
    }

    // 各メンバーカード内のミニ情報
    const memberChip = (m) => {
        const gColor = m.gender === 'male' ? 'text-primary' : (m.gender === 'female' ? 'text-danger' : 'text-muted');
        const meta = m.is_guest
            ? '<span class="badge bg-info text-dark ms-1">ゲスト</span>'
            : `<span class="small text-muted ms-1">${esc(gradeLabel(m.grade))}</span>
               <span class="small ${gColor} ms-1">${genderLabel(m.gender)}</span>`;
        return `
            <div class="d-flex justify-content-between align-items-center py-1 px-2 border-bottom team-member-row">
                <div class="text-truncate">
                    <span class="fw-semibold">${esc(m.name_kanji)}</span>
                    ${meta}
                </div>
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <span class="small text-muted text-truncate d-none d-md-inline" style="max-width:90px;">${esc(m.department || m.faculty || '')}</span>
                    <select class="form-select form-select-sm team-move-select" style="width:auto;" onchange="updateTeamNo('${m.id}', this.value)">
                        ${teamMoveOptions(m.team_no, teamNos)}
                    </select>
                </div>
            </div>`;
    };

    // 班ごとの属性内訳バッジ
    const breakdown = (members) => {
        const males   = members.filter(m => m.gender === 'male').length;
        const females = members.filter(m => m.gender === 'female').length;
        const guests  = members.filter(m => m.is_guest).length;
        const gradeCount = {};
        members.forEach(m => {
            if (m.is_guest) return;   // ゲストは学年・性別の内訳に含めない
            const g = gradeLabel(m.grade);
            gradeCount[g] = (gradeCount[g] || 0) + 1;
        });
        const gradeBadges = Object.keys(gradeCount)
            .sort((a, b) => gradeToNumber(a.replace('年','')) - gradeToNumber(b.replace('年','')))
            .map(g => `<span class="badge bg-light text-dark border me-1">${esc(g)}×${gradeCount[g]}</span>`)
            .join('');
        return `
            <div class="small mt-1">
                <span class="text-primary">男${males}</span> /
                <span class="text-danger">女${females}</span>
                ${guests > 0 ? `<span class="text-info ms-1">ゲスト${guests}</span>` : ''}
                <span class="ms-2">${gradeBadges}</span>
            </div>`;
    };

    const teamCard = (no, members) => {
        const color = TEAM_COLORS[(no - 1) % TEAM_COLORS.length];
        const sorted = [...members].sort((a, b) => (a.name_kana || '').localeCompare(b.name_kana || '', 'ja'));
        return `
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:${color};">
                        <span class="fw-bold fs-5">${no}班</span>
                        <span class="badge bg-light text-dark">${members.length}人</span>
                    </div>
                    <div class="card-body p-0">
                        ${sorted.map(memberChip).join('')}
                    </div>
                    <div class="card-footer py-2" style="border-top:2px solid ${color};">
                        ${breakdown(members)}
                    </div>
                </div>
            </div>`;
    };

    let html = '<div class="row g-3 p-3">';
    teamNos.forEach(no => { html += teamCard(no, groups[no]); });

    // 未割り当て
    if (unassigned.length > 0) {
        const sorted = [...unassigned].sort((a, b) => (a.name_kana || '').localeCompare(b.name_kana || '', 'ja'));
        html += `
            <div class="col-12">
                <div class="card border-secondary">
                    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                        <span class="fw-bold">未割り当て</span>
                        <span class="badge bg-light text-dark">${unassigned.length}人</span>
                    </div>
                    <div class="card-body p-0">
                        ${sorted.map(memberChip).join('')}
                    </div>
                </div>
            </div>`;
    }
    html += '</div>';
    container.innerHTML = html;
}

// 班移動用セレクトの選択肢
function teamMoveOptions(currentNo, teamNos) {
    let opts = `<option value="" ${currentNo === null ? 'selected' : ''}>未</option>`;
    teamNos.forEach(no => {
        opts += `<option value="${no}" ${currentNo === no ? 'selected' : ''}>${no}班</option>`;
    });
    return opts;
}

function updateTeamNo(appId, value) {
    const m = teamMembers.find(x => String(x.id) === String(appId));
    if (!m) return;
    m.team_no = (value === '' || value === null) ? null : parseInt(value);
    if (teamView === 'teams') {
        // 班ビューはメンバーが該当の班カードへ移動するよう全体を再描画
        renderTeamTable();
    } else {
        // 一覧ビューはサマリーだけ即時更新（入力中の体感を優先し並び替えはしない）
        renderTeamSummaryOnly();
    }
    scheduleAutoSave();
}

function renderTeamSummaryOnly() {
    const summary = document.getElementById('teamsSummary');
    const teamCounts = {};
    teamMembers.forEach(m => {
        const key = (m.team_no === null) ? '未割り当て' : `${m.team_no}班`;
        teamCounts[key] = (teamCounts[key] || 0) + 1;
    });
    const summaryBadges = Object.keys(teamCounts)
        .sort((a, b) => {
            if (a === '未割り当て') return 1;
            if (b === '未割り当て') return -1;
            return parseInt(a) - parseInt(b);
        })
        .map(k => {
            const cls = k === '未割り当て' ? 'bg-secondary' : 'bg-primary';
            return `<span class="badge ${cls} me-1 mb-1">${esc(k)}: ${teamCounts[k]}人</span>`;
        }).join('');
    summary.innerHTML = `<div class="mb-2">${summaryBadges}</div>`;
}

// ── 自動保存（デバウンス付き） ──
let autoSaveTimer = null;

function setSaveStatus(text, cls) {
    const el = document.getElementById('teamSaveStatus');
    if (el) el.innerHTML = `<span class="${cls || 'text-muted'}">${esc(text)}</span>`;
}

// 変更があってから少し待ってまとめて保存する
function scheduleAutoSave() {
    setSaveStatus('未保存の変更があります…', 'text-muted');
    if (autoSaveTimer) clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(saveTeams, 800);
}

async function saveTeams() {
    if (autoSaveTimer) { clearTimeout(autoSaveTimer); autoSaveTimer = null; }

    const assignments = {};
    teamMembers.forEach(m => { assignments[m.id] = m.team_no; });

    setSaveStatus('保存中…', 'text-muted');
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/teams`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ assignments }),
        });
        const data = await res.json();
        if (data.success) {
            setSaveStatus('✓ 自動保存しました', 'text-success');
        } else {
            setSaveStatus('保存に失敗しました', 'text-danger');
        }
    } catch (e) {
        setSaveStatus('通信エラー（未保存）', 'text-danger');
    }
}

// ──────────────────────────────────
// 申込URLトークン
// ──────────────────────────────────
document.querySelector('a[href="#tabToken"]').addEventListener('shown.bs.tab', loadToken);

async function loadToken() {
    const area = document.getElementById('tokenArea');
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/token`);
        const data = await res.json();
        const tok  = data.data?.token;
        renderToken(tok);
    } catch (e) {
        area.innerHTML = '<div class="text-danger">読み込みに失敗しました</div>';
    }
}

let lastToken;   // 直近に取得したトークン（saveInfo後の再描画用）

function renderToken(tok) {
    lastToken = tok;
    const area = document.getElementById('tokenArea');
    if (!tok) {
        area.innerHTML = '<div class="text-muted">申込URLは発行されていません</div>';
        return;
    }
    const memberUrl = `${location.origin}/apply/event/${tok.token}`;
    const guestUrl  = `${memberUrl}/guest`;
    const expired   = tok.expires_at && new Date(tok.expires_at) < new Date();

    // URL1件分の入力欄＋コピーボタン
    const urlBlock = (label, hint, url, inputId) => `
        <div class="mb-3">
            <div class="fw-semibold small">${label}</div>
            ${hint ? `<div class="form-text mt-0 mb-1">${hint}</div>` : ''}
            <div class="input-group">
                <input type="text" class="form-control form-control-sm" value="${esc(url)}" readonly id="${inputId}">
                <button class="btn btn-outline-secondary btn-sm" onclick="copyTokenUrl('${inputId}')">
                    <i class="bi bi-clipboard"></i> コピー
                </button>
            </div>
        </div>`;

    area.innerHTML = `
        <div class="border rounded p-3">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="small text-muted">有効期限: ${tok.expires_at ? tok.expires_at.substring(0, 10) : '無期限'}
                    ${expired ? '<span class="badge bg-danger ms-1">期限切れ</span>' : ''}
                </div>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteToken()">
                    <i class="bi bi-trash"></i> 無効化
                </button>
            </div>
            ${urlBlock('会員用URL', '学籍番号でログインして申し込みます。', memberUrl, 'tokenUrlInput')}
            ${allowGuest
                ? urlBlock('会員以外（ゲスト）用URL', 'ログイン不要。新歓・OB交流会の参加者に直接共有できます。', guestUrl, 'guestTokenUrlInput')
                : '<div class="form-text">会員以外（ゲスト）の申し込みを受け付けるには、基本情報タブで「会員以外（ゲスト）の申し込みを受け付ける」をONにしてください。ONにするとゲスト用URLがここに表示されます。</div>'}
        </div>`;
}

async function generateToken() {
    if (!confirm('申込URLを発行しますか？既存のURLは無効になります。')) return;
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/token`, { method: 'POST' });
        const data = await res.json();
        if (data.success) { renderToken(data.data.token); showToast('URLを発行しました', 'success'); }
        else { alert(data.error?.message || '発行に失敗しました'); }
    } catch (e) { alert('通信エラーが発生しました'); }
}

async function deleteToken() {
    if (!confirm('このURLを無効にしますか？')) return;
    try {
        const res  = await fetch(`/api/events/${EVENT_ID}/token`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ _method: 'DELETE' }),
        });
        const data = await res.json();
        if (data.success) { renderToken(null); showToast('URLを無効化しました', 'success'); }
        else { alert(data.error?.message || '削除に失敗しました'); }
    } catch (e) { alert('通信エラーが発生しました'); }
}

function copyTokenUrl(inputId = 'tokenUrlInput') {
    const input = document.getElementById(inputId);
    if (input) navigator.clipboard.writeText(input.value).then(() => showToast('コピーしました', 'success'));
}

// ── 班一覧を JPG 画像として出力 ──
// 画面上のカード（セレクトボックスや折り返しレイアウトを含む）ではなく、
// 出力専用のクリーンなレイアウトをオフスクリーンで組み立ててから html2canvas で描画する。
async function exportTeamsAsImage() {
    if (!teamsLoaded) { await loadTeams(); }

    // 班番号ごとにグループ化
    const groups = {};
    const unassigned = [];
    teamMembers.forEach(m => {
        if (m.team_no === null) unassigned.push(m);
        else (groups[m.team_no] = groups[m.team_no] || []).push(m);
    });
    const teamNos = Object.keys(groups).map(Number).sort((a, b) => a - b);

    if (teamNos.length === 0) {
        alert('出力できる班がありません。先に班を割り当ててください。');
        return;
    }

    const btn = document.getElementById('teamExportBtn');
    const original = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> 出力中...'; }

    // 出力専用のクリーンなカードを組み立てる
    const exMemberRow = (m) => {
        const gColor = m.gender === 'male' ? '#0d6efd' : (m.gender === 'female' ? '#dc3545' : '#6c757d');
        const meta = m.is_guest
            ? '<span style="font-size:11px;color:#0dcaf0;margin-left:6px;">ゲスト</span>'
            : `<span style="font-size:12px;color:#6c757d;margin-left:6px;">${esc(gradeLabel(m.grade))}</span>`
              + `<span style="font-size:12px;color:${gColor};margin-left:4px;">${genderLabel(m.gender)}</span>`;
        return `<div style="padding:4px 10px;border-bottom:1px solid #eee;font-size:14px;">
                    <span style="font-weight:600;">${esc(m.name_kanji)}</span>${meta}
                </div>`;
    };
    const exBreakdown = (members) => {
        const males   = members.filter(m => m.gender === 'male').length;
        const females = members.filter(m => m.gender === 'female').length;
        const guests  = members.filter(m => m.is_guest).length;
        return `<div style="padding:6px 10px;font-size:12px;color:#555;">
                    <span style="color:#0d6efd;">男${males}</span> /
                    <span style="color:#dc3545;">女${females}</span>
                    ${guests > 0 ? `<span style="color:#0dcaf0;margin-left:6px;">ゲスト${guests}</span>` : ''}
                    <span style="margin-left:8px;">計${members.length}人</span>
                </div>`;
    };
    const exCard = (label, members, color) => {
        const sorted = [...members].sort((a, b) => (a.name_kana || '').localeCompare(b.name_kana || '', 'ja'));
        return `<div style="width:240px;border:1px solid #ddd;border-radius:8px;overflow:hidden;background:#fff;">
                    <div style="background:${color};color:#fff;padding:8px 10px;display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-weight:700;font-size:16px;">${esc(label)}</span>
                        <span style="background:#fff;color:#333;border-radius:10px;padding:1px 8px;font-size:12px;font-weight:600;">${members.length}人</span>
                    </div>
                    <div>${sorted.map(exMemberRow).join('')}</div>
                    <div style="border-top:2px solid ${color};">${exBreakdown(members)}</div>
                </div>`;
    };

    let cards = '';
    teamNos.forEach(no => {
        const color = TEAM_COLORS[(no - 1) % TEAM_COLORS.length];
        cards += exCard(`${no}班`, groups[no], color);
    });
    if (unassigned.length > 0) {
        cards += exCard('未割り当て', unassigned, '#6c757d');
    }

    const now = new Date();
    const dateStr = `${now.getFullYear()}/${String(now.getMonth()+1).padStart(2,'0')}/${String(now.getDate()).padStart(2,'0')}`;

    const stage = document.createElement('div');
    stage.style.cssText = 'position:fixed;left:-99999px;top:0;background:#fff;padding:24px;width:max-content;font-family:sans-serif;';
    stage.innerHTML = `
        <div style="margin-bottom:16px;">
            <div style="font-size:22px;font-weight:700;color:#222;">${esc(EVENT_TITLE)} — 班一覧</div>
            <div style="font-size:13px;color:#888;margin-top:4px;">出力日: ${dateStr} ／ 全${teamNos.length}班・${teamMembers.length}人</div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:16px;max-width:1280px;align-items:flex-start;">${cards}</div>`;
    document.body.appendChild(stage);

    try {
        const canvas = await html2canvas(stage, { scale: 2, backgroundColor: '#ffffff' });
        const dataUrl = canvas.toDataURL('image/jpeg', 0.92);
        const a = document.createElement('a');
        const safeTitle = (EVENT_TITLE || '企画').replace(/[\\/:*?"<>|]/g, '_');
        a.href = dataUrl;
        a.download = `${safeTitle}_班一覧.jpg`;
        a.click();
    } catch (e) {
        console.error(e);
        alert('画像の生成に失敗しました');
    } finally {
        document.body.removeChild(stage);
        if (btn) { btn.disabled = false; btn.innerHTML = original; }
    }
}
</script>
