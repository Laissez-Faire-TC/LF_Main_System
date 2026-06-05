<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-2">会員名簿管理</h1>
        <div class="d-flex align-items-center">
            <label class="me-2 mb-0">年度:</label>
            <select class="form-select form-select-sm" id="academicYearSelect" style="width: 150px;" onchange="loadMembers()">
                <option value="">読み込み中...</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="showNewYearModal()">
                <i class="bi bi-plus-circle"></i> 新年度作成
            </button>
        </div>
    </div>
    <div>
        <button class="btn btn-outline-success me-2" onclick="exportExcel()">
            <i class="bi bi-file-earmark-excel"></i> Excel出力
        </button>
        <button class="btn btn-outline-primary me-2" onclick="showImportModal()">
            <i class="bi bi-upload"></i> インポート
        </button>
        <button class="btn btn-primary" onclick="showCreateModal()">
            + 会員を追加
        </button>
    </div>
</div>

<!-- 検索・フィルタ -->
<style>
.multi-select-wrapper { position: relative; }
.multi-select-btn {
    width: 100%; text-align: left; background: #fff; border: 1px solid #ced4da;
    border-radius: 0.375rem; padding: 0.375rem 2rem 0.375rem 0.75rem;
    font-size: 1rem; line-height: 1.5; color: #212529; cursor: pointer;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 16px 12px;
}
.multi-select-btn:focus { outline: 0; border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25); }
.multi-select-btn.has-selection { border-color: #0d6efd; color: #0d6efd; font-weight: 500; }
.multi-select-dropdown {
    position: absolute; top: calc(100% + 2px); left: 0; min-width: 100%;
    background: #fff; border: 1px solid #ced4da; border-radius: 0.375rem;
    z-index: 1050; max-height: 260px; overflow-y: auto; display: none;
    padding: 0.35rem 0.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.multi-select-dropdown.show { display: block; }
.multi-select-item {
    display: flex; align-items: center; gap: 6px; padding: 4px 4px;
    cursor: pointer; border-radius: 3px; font-size: 0.9rem; white-space: nowrap;
}
.multi-select-item:hover { background: #f0f4ff; }
.multi-select-item input[type="checkbox"] { cursor: pointer; flex-shrink: 0; }
.multi-select-group {
    font-size: 0.72rem; font-weight: 700; color: #6c757d; letter-spacing: 0.03em;
    padding: 5px 4px 2px; margin-top: 2px; border-top: 1px solid #e9ecef;
}
.multi-select-group:first-child { border-top: none; margin-top: 0; }
</style>
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">検索</label>
                <input type="text" class="form-control" id="searchQuery" placeholder="名前またはカナで検索..." oninput="debounceSearch()">
            </div>
            <div class="col-md-2">
                <label class="form-label">学年</label>
                <div class="multi-select-wrapper">
                    <button class="multi-select-btn" type="button" id="btn-grade" onclick="toggleMultiSelect('grade')">すべて</button>
                    <div class="multi-select-dropdown" id="dropdown-grade">
                        <label class="multi-select-item"><input type="checkbox" value="1" onchange="onFilterChange('grade')"> 1年</label>
                        <label class="multi-select-item"><input type="checkbox" value="2" onchange="onFilterChange('grade')"> 2年</label>
                        <label class="multi-select-item"><input type="checkbox" value="3" onchange="onFilterChange('grade')"> 3年</label>
                        <label class="multi-select-item"><input type="checkbox" value="4" onchange="onFilterChange('grade')"> 4年</label>
                        <label class="multi-select-item"><input type="checkbox" value="5" onchange="onFilterChange('grade')"> 5年</label>
                        <label class="multi-select-item"><input type="checkbox" value="6" onchange="onFilterChange('grade')"> 6年</label>
                        <label class="multi-select-item"><input type="checkbox" value="M1" onchange="onFilterChange('grade')"> M1</label>
                        <label class="multi-select-item"><input type="checkbox" value="M2" onchange="onFilterChange('grade')"> M2</label>
                        <label class="multi-select-item"><input type="checkbox" value="OB" onchange="onFilterChange('grade')"> OB</label>
                        <label class="multi-select-item"><input type="checkbox" value="OG" onchange="onFilterChange('grade')"> OG</label>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">学部</label>
                <div class="multi-select-wrapper">
                    <button class="multi-select-btn" type="button" id="btn-faculty" onclick="toggleMultiSelect('faculty')">すべて</button>
                    <div class="multi-select-dropdown" id="dropdown-faculty">
                        <label class="multi-select-item"><input type="checkbox" value="基幹理工学部" onchange="onFilterChange('faculty')"> 基幹理工学部</label>
                        <label class="multi-select-item"><input type="checkbox" value="創造理工学部" onchange="onFilterChange('faculty')"> 創造理工学部</label>
                        <label class="multi-select-item"><input type="checkbox" value="先進理工学部" onchange="onFilterChange('faculty')"> 先進理工学部</label>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">性別</label>
                <div class="multi-select-wrapper">
                    <button class="multi-select-btn" type="button" id="btn-gender" onclick="toggleMultiSelect('gender')">すべて</button>
                    <div class="multi-select-dropdown" id="dropdown-gender">
                        <label class="multi-select-item"><input type="checkbox" value="male" onchange="onFilterChange('gender')"> 男性</label>
                        <label class="multi-select-item"><input type="checkbox" value="female" onchange="onFilterChange('gender')"> 女性</label>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">ステータス</label>
                <div class="multi-select-wrapper">
                    <button class="multi-select-btn" type="button" id="btn-status" onclick="toggleMultiSelect('status')">すべて</button>
                    <div class="multi-select-dropdown" id="dropdown-status">
                        <label class="multi-select-item"><input type="checkbox" value="active" onchange="onFilterChange('status')"> 現役</label>
                        <label class="multi-select-item"><input type="checkbox" value="pending" onchange="onFilterChange('status')"> 承認待ち</label>
                        <label class="multi-select-item"><input type="checkbox" value="ob_og" onchange="onFilterChange('status')"> OB/OG</label>
                        <label class="multi-select-item"><input type="checkbox" value="withdrawn" onchange="onFilterChange('status')"> 退会</label>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">学科</label>
                <div class="multi-select-wrapper">
                    <button class="multi-select-btn" type="button" id="btn-department" onclick="toggleMultiSelect('department')">すべて</button>
                    <div class="multi-select-dropdown" id="dropdown-department">
                        <div class="multi-select-group">基幹理工学部</div>
                        <label class="multi-select-item"><input type="checkbox" value="数学科" onchange="onFilterChange('department')"> 数学科</label>
                        <label class="multi-select-item"><input type="checkbox" value="応用数理学科" onchange="onFilterChange('department')"> 応用数理学科</label>
                        <label class="multi-select-item"><input type="checkbox" value="機械科学・航空宇宙学科" onchange="onFilterChange('department')"> 機械科学・航空宇宙学科</label>
                        <label class="multi-select-item"><input type="checkbox" value="電子物理システム学科" onchange="onFilterChange('department')"> 電子物理システム学科</label>
                        <label class="multi-select-item"><input type="checkbox" value="情報理工学科" onchange="onFilterChange('department')"> 情報理工学科</label>
                        <label class="multi-select-item"><input type="checkbox" value="情報通信学科" onchange="onFilterChange('department')"> 情報通信学科</label>
                        <label class="multi-select-item"><input type="checkbox" value="表現工学科" onchange="onFilterChange('department')"> 表現工学科</label>
                        <div class="multi-select-group">創造理工学部</div>
                        <label class="multi-select-item"><input type="checkbox" value="建築学科" onchange="onFilterChange('department')"> 建築学科</label>
                        <label class="multi-select-item"><input type="checkbox" value="総合機械工学科" onchange="onFilterChange('department')"> 総合機械工学科</label>
                        <label class="multi-select-item"><input type="checkbox" value="経営システム工学科" onchange="onFilterChange('department')"> 経営システム工学科</label>
                        <label class="multi-select-item"><input type="checkbox" value="社会環境工学科" onchange="onFilterChange('department')"> 社会環境工学科</label>
                        <label class="multi-select-item"><input type="checkbox" value="環境資源工学科" onchange="onFilterChange('department')"> 環境資源工学科</label>
                        <label class="multi-select-item"><input type="checkbox" value="社会文化領域" onchange="onFilterChange('department')"> 社会文化領域</label>
                        <div class="multi-select-group">先進理工学部</div>
                        <label class="multi-select-item"><input type="checkbox" value="物理学科" onchange="onFilterChange('department')"> 物理学科</label>
                        <label class="multi-select-item"><input type="checkbox" value="応用物理学科" onchange="onFilterChange('department')"> 応用物理学科</label>
                        <label class="multi-select-item"><input type="checkbox" value="化学・生命化学科" onchange="onFilterChange('department')"> 化学・生命化学科</label>
                        <label class="multi-select-item"><input type="checkbox" value="応用化学科" onchange="onFilterChange('department')"> 応用化学科</label>
                        <label class="multi-select-item"><input type="checkbox" value="生命医科学科" onchange="onFilterChange('department')"> 生命医科学科</label>
                        <label class="multi-select-item"><input type="checkbox" value="電気・情報生命工学科" onchange="onFilterChange('department')"> 電気・情報生命工学科</label>
                    </div>
                </div>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                    リセット
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 集計情報・並べ替え -->
<div class="row mb-3 align-items-center">
    <div class="col-md-6">
        <span id="memberCount" class="text-muted">読み込み中...</span>
    </div>
    <div class="col-md-6 d-flex justify-content-end gap-2 flex-wrap">
        <!-- 第1ソート -->
        <div class="input-group input-group-sm" style="width: auto;">
            <span class="input-group-text">並べ替え</span>
            <select class="form-select form-select-sm" id="sortPrimary" onchange="applySorting()" style="width: auto;">
                <option value="name_kana" selected>カナ順</option>
                <option value="grade">学年</option>
                <option value="gender">性別</option>
                <option value="faculty">学部</option>
                <option value="enrollment_year">入学年度</option>
            </select>
            <button type="button" class="btn btn-outline-secondary" id="sortPrimaryDir" onclick="toggleSortDirection('primary')" title="昇順/降順切り替え">↑</button>
        </div>
        <!-- 第2ソート -->
        <div class="input-group input-group-sm" style="width: auto;">
            <span class="input-group-text">→</span>
            <select class="form-select form-select-sm" id="sortSecondary" onchange="applySorting()" style="width: auto;">
                <option value="">なし</option>
                <option value="name_kana">カナ順</option>
                <option value="grade" selected>学年</option>
                <option value="gender">性別</option>
                <option value="faculty">学部</option>
                <option value="enrollment_year">入学年度</option>
            </select>
            <button type="button" class="btn btn-outline-secondary" id="sortSecondaryDir" onclick="toggleSortDirection('secondary')" title="昇順/降順切り替え">↑</button>
        </div>
    </div>
</div>

<!-- 列表示設定 -->
<div class="card mb-2">
    <div class="card-body py-2">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <small class="text-muted me-1">表示列:</small>
            <div class="form-check form-check-inline mb-0"><input class="form-check-input col-toggle" type="checkbox" id="col_grade" value="grade" checked><label class="form-check-label" for="col_grade">学年</label></div>
            <div class="form-check form-check-inline mb-0"><input class="form-check-input col-toggle" type="checkbox" id="col_faculty" value="faculty" checked><label class="form-check-label" for="col_faculty">学部</label></div>
            <div class="form-check form-check-inline mb-0"><input class="form-check-input col-toggle" type="checkbox" id="col_department" value="department" checked><label class="form-check-label" for="col_department">学科</label></div>
            <div class="form-check form-check-inline mb-0"><input class="form-check-input col-toggle" type="checkbox" id="col_student_id" value="student_id" checked><label class="form-check-label" for="col_student_id">学籍番号</label></div>
            <div class="form-check form-check-inline mb-0"><input class="form-check-input col-toggle" type="checkbox" id="col_status" value="status" checked><label class="form-check-label" for="col_status">ステータス</label></div>
            <div class="form-check form-check-inline mb-0"><input class="form-check-input col-toggle" type="checkbox" id="col_phone" value="phone"><label class="form-check-label" for="col_phone">電話番号</label></div>
            <div class="form-check form-check-inline mb-0"><input class="form-check-input col-toggle" type="checkbox" id="col_email" value="email"><label class="form-check-label" for="col_email">メール</label></div>
            <div class="form-check form-check-inline mb-0"><input class="form-check-input col-toggle" type="checkbox" id="col_line_name" value="line_name"><label class="form-check-label" for="col_line_name">LINE名</label></div>
            <div class="form-check form-check-inline mb-0"><input class="form-check-input col-toggle" type="checkbox" id="col_address" value="address"><label class="form-check-label" for="col_address">住所</label></div>
            <div class="form-check form-check-inline mb-0"><input class="form-check-input col-toggle" type="checkbox" id="col_emergency_contact" value="emergency_contact"><label class="form-check-label" for="col_emergency_contact">緊急連絡先</label></div>
            <div class="form-check form-check-inline mb-0"><input class="form-check-input col-toggle" type="checkbox" id="col_birthdate" value="birthdate"><label class="form-check-label" for="col_birthdate">生年月日</label></div>
            <div class="form-check form-check-inline mb-0"><input class="form-check-input col-toggle" type="checkbox" id="col_allergy" value="allergy"><label class="form-check-label" for="col_allergy">アレルギー</label></div>
            <div class="form-check form-check-inline mb-0"><input class="form-check-input col-toggle" type="checkbox" id="col_sns_allowed" value="sns_allowed"><label class="form-check-label" for="col_sns_allowed">SNS許可</label></div>
            <div class="form-check form-check-inline mb-0"><input class="form-check-input col-toggle" type="checkbox" id="col_sports_registration_no" value="sports_registration_no"><label class="form-check-label" for="col_sports_registration_no">コート予約番号</label></div>
            <div class="form-check form-check-inline mb-0"><input class="form-check-input col-toggle" type="checkbox" id="col_enrollment_year" value="enrollment_year"><label class="form-check-label" for="col_enrollment_year">入学年度</label></div>
        </div>
    </div>
</div>

<!-- 会員一覧テーブル -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 table-sm" id="memberTable">
            <thead class="table-light">
                <tr>
                    <th style="white-space:nowrap">名前</th>
                    <th class="col-grade" style="white-space:nowrap; width:48px; min-width:48px;">学年</th>
                    <th class="col-faculty" style="white-space:nowrap">学部</th>
                    <th class="col-department" style="white-space:nowrap; width:180px; min-width:180px; max-width:180px;">学科/学系</th>
                    <th class="col-student_id" style="white-space:nowrap; min-width:9ch;">学籍番号</th>
                    <th class="col-status" style="white-space:nowrap">ステータス</th>
                    <th class="col-phone" style="white-space:nowrap">電話番号</th>
                    <th class="col-email" style="white-space:nowrap">メール</th>
                    <th class="col-line_name" style="white-space:nowrap">LINE名</th>
                    <th class="col-address" style="min-width:260px;">住所</th>
                    <th class="col-emergency_contact" style="white-space:nowrap">緊急連絡先</th>
                    <th class="col-birthdate" style="white-space:nowrap">生年月日</th>
                    <th class="col-allergy" style="white-space:nowrap">アレルギー</th>
                    <th class="col-sns_allowed" style="white-space:nowrap">SNS許可</th>
                    <th class="col-sports_registration_no" style="white-space:nowrap">コート予約番号</th>
                    <th class="col-enrollment_year" style="white-space:nowrap">入学年度</th>
                    <th style="white-space:nowrap" width="80">操作</th>
                </tr>
            </thead>
            <tbody id="memberTableBody">
                <tr>
                    <td colspan="17" class="text-center py-4">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">読み込み中...</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ページネーション -->
<nav class="mt-3" id="pagination"></nav>

<!-- 会員編集モーダル -->
<div class="modal fade" id="memberModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="memberModalTitle">会員情報編集</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="memberForm">
                    <input type="hidden" id="memberId">

                    <h6 class="border-bottom pb-2 mb-3">基本情報</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">名前（漢字）<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nameKanji" required placeholder="山田 太郎">
                            <small class="text-muted">全角スペース区切り</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">名前（カタカナ）<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nameKana" required placeholder="ヤマダ タロウ">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">性別 <span class="text-danger">*</span></label>
                            <select class="form-select" id="gender" required>
                                <option value="male">男性</option>
                                <option value="female">女性</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">学年 <span class="text-danger">*</span></label>
                            <select class="form-select" id="grade" required>
                                <option value="1">1年</option>
                                <option value="2">2年</option>
                                <option value="3">3年</option>
                                <option value="4">4年</option>
                    <option value="5">5年</option>
                    <option value="6">6年</option>
                                <option value="M1">M1</option>
                                <option value="M2">M2</option>
                                <option value="OB">OB</option>
                                <option value="OG">OG</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">生年月日 <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="birthdate" required>
                        </div>
                    </div>

                    <h6 class="border-bottom pb-2 mb-3 mt-4">所属情報</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">学籍番号 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="studentId" required placeholder="" oninput="normalizeStudentId(); parseStudentId()">
                            <small class="text-muted" id="studentIdExample">CDあり（例: ）</small>
                        </div>
                        <div class="col-md-6">
                            <div id="studentIdParseResult" class="alert alert-info mb-0 mt-4" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">学部 <span class="text-danger">*</span></label>
                            <select class="form-select" id="faculty" required onchange="updateDepartmentOptions()">
                                <option value="">選択してください</option>
                                <option value="基幹理工学部">基幹理工学部</option>
                                <option value="創造理工学部">創造理工学部</option>
                                <option value="先進理工学部">先進理工学部</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">学科/学系 <span class="text-danger">*</span></label>
                            <select class="form-select" id="department" required onchange="toggleDepartmentOther()">
                                <option value="">選択してください</option>
                            </select>
                            <input type="text" class="form-control mt-2" id="departmentOther" placeholder="学科名を入力" style="display:none;">
                            <small class="text-muted" id="departmentOtherHint" style="display:none;">例: Mathematical Sciences</small>
                        </div>
                    </div>

                    <h6 class="border-bottom pb-2 mb-3 mt-4">連絡先</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">電話番号 <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone" required placeholder="090-1234-5678">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">緊急連絡先 <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="emergencyContact" required placeholder="03-1234-5678">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">住所 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="address" required placeholder="東京都新宿区西早稲田1-2-3">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">メールアドレス</label>
                            <input type="email" class="form-control" id="email" placeholder="example@email.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">LINE名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="lineName" required placeholder="yamada_taro">
                        </div>
                    </div>

                    <h6 class="border-bottom pb-2 mb-3 mt-4">その他</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">アレルギー</label>
                            <input type="text" class="form-control" id="allergy" placeholder="なし">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">コート予約番号</label>
                            <input type="text" class="form-control" id="sportsRegistrationNo" placeholder="12345678">
                            <small class="text-muted">8桁の番号</small>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="snsAllowed" checked>
                                <label class="form-check-label" for="snsAllowed">SNSへの写真投稿を許可</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ステータス</label>
                            <select class="form-select" id="status">
                                <option value="pending">承認待ち</option>
                                <option value="active">現役</option>
                                <option value="ob_og">OB/OG</option>
                                <option value="withdrawn">退会</option>
                            </select>
                        </div>
                    </div>

                    <!-- OAuth連携状況（救済用・編集時のみ表示） -->
                    <div class="row mt-3" id="oauthRow" style="display:none;">
                        <div class="col-12">
                            <label class="form-label">ログイン方法（外部アカウント連携）</label>
                            <div class="border rounded p-2 bg-light d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <span id="oauthStatusText" class="small"></span>
                                <button type="button" class="btn btn-sm btn-outline-warning" id="oauthUnlinkBtn"
                                        onclick="adminUnlinkOauth()" style="display:none;">連携を解除（学籍番号ログインに戻す）</button>
                            </div>
                            <div class="form-text">連携中の会員は学籍番号でログインできません。本人がログインできなくなった場合はここで解除してください。</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger me-auto" id="deleteMemberBtn" onclick="deleteMember()" style="display:none;">削除</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" onclick="saveMember()">保存</button>
            </div>
        </div>
    </div>
</div>

<!-- インポートモーダル -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">会員一括インポート</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Excel/CSVファイルから会員を一括登録します</strong>
                    <p class="mb-0 mt-2">既存の学籍番号と重複する場合は上書きされます。</p>
                </div>

                <div class="mb-3">
                    <label class="form-label">ファイルを選択</label>
                    <input type="file" class="form-control" id="importFile" accept=".xlsx,.xls,.csv">
                </div>

                <h6>必須列（1行目にヘッダー）</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <tr>
                            <td>名前（漢字）</td>
                            <td>名前（カタカナ）</td>
                            <td>性別</td>
                            <td>学年</td>
                            <td>学部</td>
                        </tr>
                        <tr>
                            <td>学科</td>
                            <td>学籍番号</td>
                            <td>電話番号</td>
                            <td>住所</td>
                            <td>緊急連絡先</td>
                        </tr>
                        <tr>
                            <td>生年月日</td>
                            <td>アレルギー</td>
                            <td>LINE名</td>
                            <td>SNS投稿可否</td>
                            <td>コート予約番号</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" onclick="importMembers()">インポート</button>
            </div>
        </div>
    </div>
</div>


<!-- 新年度作成モーダル -->
<div class="modal fade" id="newYearModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新年度作成</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    新しい年度を作成します。継続入会や合宿申し込みなどで使用されます。
                </div>

                <div class="mb-3">
                    <label class="form-label">年度 <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="newYear" placeholder="例: 2026" min="2020" max="2100">
                    <small class="text-muted">例: 2026年度（2026年4月～2027年3月）</small>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="setAsCurrent">
                    <label class="form-check-label" for="setAsCurrent">
                        この年度を現在年度に設定する
                    </label>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="openEnrollment" checked>
                    <label class="form-check-label" for="openEnrollment">
                        入会受付を開始する
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" onclick="createNewYear()">作成</button>
            </div>
        </div>
    </div>
</div>


<!-- Excel出力モーダル -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-excel"></i> Excel出力設定</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- 絞り込み -->
                <h6 class="fw-bold mb-3">絞り込み条件</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">年度</label>
                        <select class="form-select" id="exportYear"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ステータス</label>
                        <div class="multi-select-wrapper">
                            <button class="multi-select-btn" type="button" id="btn-exportStatus" onclick="toggleMultiSelect('exportStatus')">すべて</button>
                            <div class="multi-select-dropdown" id="dropdown-exportStatus">
                                <label class="multi-select-item"><input type="checkbox" value="active" onchange="updateMultiSelectLabel('exportStatus')"> 現役</label>
                                <label class="multi-select-item"><input type="checkbox" value="pending" onchange="updateMultiSelectLabel('exportStatus')"> 承認待ち</label>
                                <label class="multi-select-item"><input type="checkbox" value="ob_og" onchange="updateMultiSelectLabel('exportStatus')"> OB/OG</label>
                                <label class="multi-select-item"><input type="checkbox" value="withdrawn" onchange="updateMultiSelectLabel('exportStatus')"> 退会</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">学年</label>
                        <div class="multi-select-wrapper">
                            <button class="multi-select-btn" type="button" id="btn-exportGrade" onclick="toggleMultiSelect('exportGrade')">すべて</button>
                            <div class="multi-select-dropdown" id="dropdown-exportGrade">
                                <label class="multi-select-item"><input type="checkbox" value="1" onchange="updateMultiSelectLabel('exportGrade')"> 1年</label>
                                <label class="multi-select-item"><input type="checkbox" value="2" onchange="updateMultiSelectLabel('exportGrade')"> 2年</label>
                                <label class="multi-select-item"><input type="checkbox" value="3" onchange="updateMultiSelectLabel('exportGrade')"> 3年</label>
                                <label class="multi-select-item"><input type="checkbox" value="4" onchange="updateMultiSelectLabel('exportGrade')"> 4年</label>
                                <label class="multi-select-item"><input type="checkbox" value="5" onchange="updateMultiSelectLabel('exportGrade')"> 5年</label>
                                <label class="multi-select-item"><input type="checkbox" value="6" onchange="updateMultiSelectLabel('exportGrade')"> 6年</label>
                                <label class="multi-select-item"><input type="checkbox" value="M1" onchange="updateMultiSelectLabel('exportGrade')"> M1</label>
                                <label class="multi-select-item"><input type="checkbox" value="M2" onchange="updateMultiSelectLabel('exportGrade')"> M2</label>
                                <label class="multi-select-item"><input type="checkbox" value="OB" onchange="updateMultiSelectLabel('exportGrade')"> OB</label>
                                <label class="multi-select-item"><input type="checkbox" value="OG" onchange="updateMultiSelectLabel('exportGrade')"> OG</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">性別</label>
                        <div class="multi-select-wrapper">
                            <button class="multi-select-btn" type="button" id="btn-exportGender" onclick="toggleMultiSelect('exportGender')">すべて</button>
                            <div class="multi-select-dropdown" id="dropdown-exportGender">
                                <label class="multi-select-item"><input type="checkbox" value="male" onchange="updateMultiSelectLabel('exportGender')"> 男性</label>
                                <label class="multi-select-item"><input type="checkbox" value="female" onchange="updateMultiSelectLabel('exportGender')"> 女性</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">学部</label>
                        <div class="multi-select-wrapper">
                            <button class="multi-select-btn" type="button" id="btn-exportFaculty" onclick="toggleMultiSelect('exportFaculty')">すべて</button>
                            <div class="multi-select-dropdown" id="dropdown-exportFaculty">
                                <label class="multi-select-item"><input type="checkbox" value="基幹理工学部" onchange="updateMultiSelectLabel('exportFaculty')"> 基幹理工学部</label>
                                <label class="multi-select-item"><input type="checkbox" value="創造理工学部" onchange="updateMultiSelectLabel('exportFaculty')"> 創造理工学部</label>
                                <label class="multi-select-item"><input type="checkbox" value="先進理工学部" onchange="updateMultiSelectLabel('exportFaculty')"> 先進理工学部</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">学科</label>
                        <div class="multi-select-wrapper">
                            <button class="multi-select-btn" type="button" id="btn-exportDepartment" onclick="toggleMultiSelect('exportDepartment')">すべて</button>
                            <div class="multi-select-dropdown" id="dropdown-exportDepartment">
                                <div class="multi-select-group">基幹理工学部</div>
                                <label class="multi-select-item"><input type="checkbox" value="数学科" onchange="updateMultiSelectLabel('exportDepartment')"> 数学科</label>
                                <label class="multi-select-item"><input type="checkbox" value="応用数理学科" onchange="updateMultiSelectLabel('exportDepartment')"> 応用数理学科</label>
                                <label class="multi-select-item"><input type="checkbox" value="機械科学・航空宇宙学科" onchange="updateMultiSelectLabel('exportDepartment')"> 機械科学・航空宇宙学科</label>
                                <label class="multi-select-item"><input type="checkbox" value="電子物理システム学科" onchange="updateMultiSelectLabel('exportDepartment')"> 電子物理システム学科</label>
                                <label class="multi-select-item"><input type="checkbox" value="情報理工学科" onchange="updateMultiSelectLabel('exportDepartment')"> 情報理工学科</label>
                                <label class="multi-select-item"><input type="checkbox" value="情報通信学科" onchange="updateMultiSelectLabel('exportDepartment')"> 情報通信学科</label>
                                <label class="multi-select-item"><input type="checkbox" value="表現工学科" onchange="updateMultiSelectLabel('exportDepartment')"> 表現工学科</label>
                                <div class="multi-select-group">創造理工学部</div>
                                <label class="multi-select-item"><input type="checkbox" value="建築学科" onchange="updateMultiSelectLabel('exportDepartment')"> 建築学科</label>
                                <label class="multi-select-item"><input type="checkbox" value="総合機械工学科" onchange="updateMultiSelectLabel('exportDepartment')"> 総合機械工学科</label>
                                <label class="multi-select-item"><input type="checkbox" value="経営システム工学科" onchange="updateMultiSelectLabel('exportDepartment')"> 経営システム工学科</label>
                                <label class="multi-select-item"><input type="checkbox" value="社会環境工学科" onchange="updateMultiSelectLabel('exportDepartment')"> 社会環境工学科</label>
                                <label class="multi-select-item"><input type="checkbox" value="環境資源工学科" onchange="updateMultiSelectLabel('exportDepartment')"> 環境資源工学科</label>
                                <label class="multi-select-item"><input type="checkbox" value="社会文化領域" onchange="updateMultiSelectLabel('exportDepartment')"> 社会文化領域</label>
                                <div class="multi-select-group">先進理工学部</div>
                                <label class="multi-select-item"><input type="checkbox" value="物理学科" onchange="updateMultiSelectLabel('exportDepartment')"> 物理学科</label>
                                <label class="multi-select-item"><input type="checkbox" value="応用物理学科" onchange="updateMultiSelectLabel('exportDepartment')"> 応用物理学科</label>
                                <label class="multi-select-item"><input type="checkbox" value="化学・生命化学科" onchange="updateMultiSelectLabel('exportDepartment')"> 化学・生命化学科</label>
                                <label class="multi-select-item"><input type="checkbox" value="応用化学科" onchange="updateMultiSelectLabel('exportDepartment')"> 応用化学科</label>
                                <label class="multi-select-item"><input type="checkbox" value="生命医科学科" onchange="updateMultiSelectLabel('exportDepartment')"> 生命医科学科</label>
                                <label class="multi-select-item"><input type="checkbox" value="電気・情報生命工学科" onchange="updateMultiSelectLabel('exportDepartment')"> 電気・情報生命工学科</label>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- 出力列の選択・並び替え -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h6 class="fw-bold mb-0">出力する項目・順番</h6>
                        <small class="text-muted">☰ をドラッグして順番を変更できます</small>
                    </div>
                    <div>
                        <button class="btn btn-outline-secondary btn-sm me-1" onclick="exportCheckAll(true)">すべて選択</button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="exportCheckAll(false)">すべて解除</button>
                    </div>
                </div>
                <style>
                .export-col-item {
                    display: flex; align-items: center; gap: 8px;
                    padding: 6px 10px; margin-bottom: 4px;
                    background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px;
                    cursor: default; user-select: none;
                }
                .export-col-item.dragging { opacity: 0.4; }
                .export-col-item.drag-over { border-color: #0d6efd; background: #e8f0fe; }
                .drag-handle { cursor: grab; color: #adb5bd; font-size: 1rem; flex-shrink: 0; }
                .drag-handle:active { cursor: grabbing; }
                #exportColumnList { list-style: none; padding: 0; margin: 0; }
                </style>
                <ul id="exportColumnList">
                    <li class="export-col-item" draggable="true" data-key="name_kanji"><span class="drag-handle">☰</span><input class="form-check-input export-col" type="checkbox" id="ecol_name_kanji" value="name_kanji" checked><label class="mb-0" for="ecol_name_kanji">氏名（漢字）</label></li>
                    <li class="export-col-item" draggable="true" data-key="name_kana"><span class="drag-handle">☰</span><input class="form-check-input export-col" type="checkbox" id="ecol_name_kana" value="name_kana" checked><label class="mb-0" for="ecol_name_kana">氏名（カナ）</label></li>
                    <li class="export-col-item" draggable="true" data-key="gender"><span class="drag-handle">☰</span><input class="form-check-input export-col" type="checkbox" id="ecol_gender" value="gender" checked><label class="mb-0" for="ecol_gender">性別</label></li>
                    <li class="export-col-item" draggable="true" data-key="grade"><span class="drag-handle">☰</span><input class="form-check-input export-col" type="checkbox" id="ecol_grade" value="grade" checked><label class="mb-0" for="ecol_grade">学年</label></li>
                    <li class="export-col-item" draggable="true" data-key="faculty"><span class="drag-handle">☰</span><input class="form-check-input export-col" type="checkbox" id="ecol_faculty" value="faculty" checked><label class="mb-0" for="ecol_faculty">学部</label></li>
                    <li class="export-col-item" draggable="true" data-key="department"><span class="drag-handle">☰</span><input class="form-check-input export-col" type="checkbox" id="ecol_department" value="department" checked><label class="mb-0" for="ecol_department">学科</label></li>
                    <li class="export-col-item" draggable="true" data-key="student_id"><span class="drag-handle">☰</span><input class="form-check-input export-col" type="checkbox" id="ecol_student_id" value="student_id" checked><label class="mb-0" for="ecol_student_id">学籍番号</label></li>
                    <li class="export-col-item" draggable="true" data-key="phone"><span class="drag-handle">☰</span><input class="form-check-input export-col" type="checkbox" id="ecol_phone" value="phone" checked><label class="mb-0" for="ecol_phone">電話番号</label></li>
                    <li class="export-col-item" draggable="true" data-key="email"><span class="drag-handle">☰</span><input class="form-check-input export-col" type="checkbox" id="ecol_email" value="email" checked><label class="mb-0" for="ecol_email">メールアドレス</label></li>
                    <li class="export-col-item" draggable="true" data-key="line_name"><span class="drag-handle">☰</span><input class="form-check-input export-col" type="checkbox" id="ecol_line_name" value="line_name" checked><label class="mb-0" for="ecol_line_name">LINE名</label></li>
                    <li class="export-col-item" draggable="true" data-key="status"><span class="drag-handle">☰</span><input class="form-check-input export-col" type="checkbox" id="ecol_status" value="status" checked><label class="mb-0" for="ecol_status">ステータス</label></li>
                    <li class="export-col-item" draggable="true" data-key="enrollment_year"><span class="drag-handle">☰</span><input class="form-check-input export-col" type="checkbox" id="ecol_enrollment_year" value="enrollment_year"><label class="mb-0" for="ecol_enrollment_year">入学年度</label></li>
                    <li class="export-col-item" draggable="true" data-key="academic_year"><span class="drag-handle">☰</span><input class="form-check-input export-col" type="checkbox" id="ecol_academic_year" value="academic_year"><label class="mb-0" for="ecol_academic_year">年度</label></li>
                    <li class="export-col-item" draggable="true" data-key="birthdate"><span class="drag-handle">☰</span><input class="form-check-input export-col" type="checkbox" id="ecol_birthdate" value="birthdate"><label class="mb-0" for="ecol_birthdate">生年月日</label></li>
                    <li class="export-col-item" draggable="true" data-key="allergy"><span class="drag-handle">☰</span><input class="form-check-input export-col" type="checkbox" id="ecol_allergy" value="allergy"><label class="mb-0" for="ecol_allergy">アレルギー</label></li>
                    <li class="export-col-item" draggable="true" data-key="emergency_contact"><span class="drag-handle">☰</span><input class="form-check-input export-col" type="checkbox" id="ecol_emergency_contact" value="emergency_contact"><label class="mb-0" for="ecol_emergency_contact">緊急連絡先</label></li>
                    <li class="export-col-item" draggable="true" data-key="address"><span class="drag-handle">☰</span><input class="form-check-input export-col" type="checkbox" id="ecol_address" value="address"><label class="mb-0" for="ecol_address">住所</label></li>
                    <li class="export-col-item" draggable="true" data-key="sns_allowed"><span class="drag-handle">☰</span><input class="form-check-input export-col" type="checkbox" id="ecol_sns_allowed" value="sns_allowed"><label class="mb-0" for="ecol_sns_allowed">SNS投稿可否</label></li>
                    <li class="export-col-item" draggable="true" data-key="sports_registration_no"><span class="drag-handle">☰</span><input class="form-check-input export-col" type="checkbox" id="ecol_sports_registration_no" value="sports_registration_no"><label class="mb-0" for="ecol_sports_registration_no">都営登録番号</label></li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-success" onclick="doExportExcel()">
                    <i class="bi bi-file-earmark-excel"></i> ダウンロード
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let memberModal, importModal, newYearModal, exportModal;
let currentPage = 1;
let searchTimeout;
let allLoadedMembers = [];
let sortConfig = {
    primary: { key: 'name_kana', direction: 1 },
    secondary: { key: 'grade', direction: 1 }
};

// 学科/学系のマッピング
const departmentOptions = {
    '基幹理工学部': {
        gakukei_old: ['学系I', '学系II', '学系III'],
        gakukei_new: ['学系I', '学系II', '学系III', '学系IV'],
        departments: ['数学科', '応用数理学科', '機械科学・航空宇宙学科', '電子物理システム学科', '情報理工学科', '情報通信学科', '表現工学科', 'その他（英語学位等）']
    },
    '創造理工学部': {
        departments: ['建築学科', '総合機械工学科', '経営システム工学科', '社会環境工学科', '環境資源工学科', '社会文化領域']
    },
    '先進理工学部': {
        departments: ['物理学科', '応用物理学科', '化学・生命化学科', '応用化学科', '生命医科学科', '電気・情報生命工学科']
    }
};

// 列表示設定をlocalStorageから復元・保存
const COL_STORAGE_KEY = 'memberTableColumns';

function loadColumnSettings() {
    const saved = localStorage.getItem(COL_STORAGE_KEY);
    if (!saved) return;
    const settings = JSON.parse(saved);
    document.querySelectorAll('.col-toggle').forEach(cb => {
        if (cb.value in settings) {
            cb.checked = settings[cb.value];
        }
    });
}

function saveColumnSettings() {
    const settings = {};
    document.querySelectorAll('.col-toggle').forEach(cb => {
        settings[cb.value] = cb.checked;
    });
    localStorage.setItem(COL_STORAGE_KEY, JSON.stringify(settings));
}

function applyColumnVisibility() {
    document.querySelectorAll('.col-toggle').forEach(cb => {
        const visible = cb.checked;
        document.querySelectorAll(`.col-${cb.value}`).forEach(el => {
            el.style.display = visible ? '' : 'none';
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    memberModal = new bootstrap.Modal(document.getElementById('memberModal'));
    importModal = new bootstrap.Modal(document.getElementById('importModal'));
    newYearModal = new bootstrap.Modal(document.getElementById('newYearModal'));
    exportModal = new bootstrap.Modal(document.getElementById('exportModal'));

    // 学籍番号の例示を現在年度の下2桁で生成
    const yy = String(new Date().getFullYear()).slice(-2);
    const exId = `1Y${yy}F158-5`;
    document.getElementById('studentId').placeholder = exId;
    document.getElementById('studentIdExample').textContent = `CDあり（例: ${exId}）`;

    // 列設定の復元と適用
    loadColumnSettings();
    applyColumnVisibility();

    // チェックボックス変更時に保存・適用
    document.querySelectorAll('.col-toggle').forEach(cb => {
        cb.addEventListener('change', () => {
            saveColumnSettings();
            applyColumnVisibility();
        });
    });

    loadAcademicYears();
    loadPendingCount();
});

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadMembers();
    }, 300);
}

// 日付から現在の年度を計算
function getCurrentAcademicYear() {
    const today = new Date();
    const year = today.getFullYear();
    const month = today.getMonth() + 1; // 0-indexed
    // 1〜3月は前年度、4〜12月は今年度
    return (month >= 4) ? year : year - 1;
}

// URLパラメータから年度を取得
function getYearFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    const year = urlParams.get('year');
    return year ? parseInt(year) : null;
}

async function loadAcademicYears() {
    try {
        const res = await fetch('/index.php?route=api/academic-years');
        const data = await res.json();

        if (data.success) {
            const select = document.getElementById('academicYearSelect');
            select.innerHTML = '';

            const currentYear = getCurrentAcademicYear();
            const urlYear = getYearFromUrl(); // URLパラメータから年度を取得
            const targetYear = urlYear || currentYear; // URLパラメータがあればそれを優先

            data.data.years.forEach(year => {
                const option = document.createElement('option');
                option.value = year.year;
                option.textContent = `${year.year}年度${year.year === currentYear ? ' (現在)' : ''}`;
                // URLパラメータまたは現在年度を選択
                if (year.year === targetYear) {
                    option.selected = true;
                }
                select.appendChild(option);
            });

            // 年度が読み込まれたら会員一覧を読み込む
            loadMembers();
        }
    } catch (err) {
        console.error(err);
        // エラー時はデフォルトで会員一覧を読み込む
        loadMembers();
    }
}

// --- 複数選択フィルタ ヘルパー ---
const MULTI_SELECT_LABELS = {
    grade:  { '1':'1年','2':'2年','3':'3年','4':'4年','5':'5年','6':'6年','M1':'M1','M2':'M2','OB':'OB','OG':'OG' },
    faculty: { '基幹理工学部':'基幹','創造理工学部':'創造','先進理工学部':'先進' },
    gender: { 'male':'男性','female':'女性' },
    status: { 'active':'現役','pending':'承認待ち','ob_og':'OB/OG','withdrawn':'退会' },
};

function getMultiSelectValues(name) {
    return Array.from(
        document.querySelectorAll(`#dropdown-${name} input[type="checkbox"]:checked`)
    ).map(cb => cb.value);
}

function updateMultiSelectLabel(name) {
    const values = getMultiSelectValues(name);
    const btn = document.getElementById('btn-' + name);
    if (values.length === 0) {
        btn.textContent = 'すべて';
        btn.classList.remove('has-selection');
    } else {
        const map = MULTI_SELECT_LABELS[name];
        const labels = map ? values.map(v => map[v] || v) : values;
        const text = labels.join(', ');
        btn.textContent = text.length > 18 ? values.length + '件選択中' : text;
        btn.classList.add('has-selection');
    }
}

function toggleMultiSelect(name) {
    const dropdown = document.getElementById('dropdown-' + name);
    const isOpen = dropdown.classList.contains('show');
    document.querySelectorAll('.multi-select-dropdown.show').forEach(d => d.classList.remove('show'));
    if (!isOpen) dropdown.classList.add('show');
}

function onFilterChange(name) {
    updateMultiSelectLabel(name);
    currentPage = 1;
    loadMembers();
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.multi-select-wrapper')) {
        document.querySelectorAll('.multi-select-dropdown.show').forEach(d => d.classList.remove('show'));
    }
});
// --- ここまで ---

async function loadMembers() {
    const academicYear = document.getElementById('academicYearSelect').value;

    const params = new URLSearchParams({
        page: 1,
        per_page: 1000,
        search: document.getElementById('searchQuery').value,
    });

    // 年度パラメータを追加（選択されている場合）
    if (academicYear) {
        params.append('academic_year', academicYear);
    }

    // 複数選択フィルタを配列として送信
    ['grade', 'faculty', 'gender', 'department', 'status'].forEach(name => {
        getMultiSelectValues(name).forEach(v => params.append(name + '[]', v));
    });

    try {
        const res = await fetch(`/index.php?route=api/members&${params}`);
        const data = await res.json();

        if (data.success) {
            allLoadedMembers = data.data.members;
            renderMembersSorted();
            document.getElementById('pagination').innerHTML = '';
            document.getElementById('memberCount').textContent = `全 ${data.data.pagination.total} 名`;
        }
    } catch (err) {
        console.error(err);
    }
}

async function loadPendingCount() {
    try {
        return;
        const res = await fetch('/index.php?route=api/members/pending/count');
        const data = await res.json();
    } catch (err) {
        console.error(err);
    }
}

function renderMembers(members) {
    const tbody = document.getElementById('memberTableBody');

    if (members.length === 0) {
        tbody.innerHTML = '<tr><td colspan="17" class="text-center py-4 text-muted">会員が見つかりません</td></tr>';
        return;
    }

    tbody.innerHTML = members.map(m => `
        <tr>
            <td style="white-space:nowrap">
                <strong>${escapeHtml(m.name_kanji)}</strong>
                <br><small class="text-muted">${escapeHtml(m.name_kana)}</small>
            </td>
            <td class="col-grade" style="white-space:nowrap">${formatGrade(m.grade, m.gender, m.enrollment_year)}</td>
            <td class="col-faculty" style="white-space:nowrap">${escapeHtml(m.faculty)}</td>
            <td class="col-department" style="white-space:nowrap; max-width:180px; overflow:hidden; text-overflow:ellipsis;" title="${escapeHtml(m.department)}">${escapeHtml(m.department)}${m.department_not_set ? '<span class="badge bg-warning ms-1">要選択</span>' : ''}</td>
            <td class="col-student_id" style="white-space:nowrap"><code>${escapeHtml(m.student_id)}</code></td>
            <td class="col-status">${formatStatus(m.status, m.grade, m.gender, m.enrollment_year)}</td>
            <td class="col-phone" style="white-space:nowrap">${escapeHtml(m.phone || '')}</td>
            <td class="col-email">${escapeHtml(m.email || '')}</td>
            <td class="col-line_name">${escapeHtml(m.line_name || '')}</td>
            <td class="col-address" style="min-width:260px; white-space:normal; word-break:break-word;">${escapeHtml(m.address || '')}</td>
            <td class="col-emergency_contact" style="white-space:nowrap">${escapeHtml(m.emergency_contact || '')}</td>
            <td class="col-birthdate" style="white-space:nowrap">${escapeHtml(m.birthdate || '')}</td>
            <td class="col-allergy">${escapeHtml(m.allergy || '')}</td>
            <td class="col-sns_allowed" style="text-align:center">${m.sns_allowed ? '○' : '×'}</td>
            <td class="col-sports_registration_no">${escapeHtml(m.sports_registration_no || '')}</td>
            <td class="col-enrollment_year" style="text-align:center">${escapeHtml(m.enrollment_year || '')}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="editMember(${m.id})">編集</button>
            </td>
        </tr>
    `).join('');

    applyColumnVisibility();
}

function isRetired(grade, gender, enrollmentYear) {
    if (grade === 'OB' || grade === 'OG') return true;
    if (grade !== '3') return false;
    // enrollment_year があれば入学年+2年後の10月1日を過ぎているか判定
    if (enrollmentYear) {
        const retirementDate = new Date(parseInt(enrollmentYear) + 2, 9, 1); // 10月1日(月は0始まり)
        return new Date() >= retirementDate;
    }
    // enrollment_year がない場合は今の月で判定（フォールバック）
    const month = new Date().getMonth();
    return month >= 9 || month <= 2;
}

function formatGrade(grade, gender, enrollmentYear) {
    if (isRetired(grade, gender, enrollmentYear)) {
        const obog = (grade === 'OG' || gender === 'female') ? 'OG' : 'OB';
        return `<span class="badge bg-secondary">${obog}</span>`;
    }

    const suffix = gender === 'male' ? '男' : '女';
    return `${grade}年${suffix}`;
}

function formatStatus(status, grade, gender, enrollmentYear) {
    if (status === 'active' && isRetired(grade, gender, enrollmentYear)) {
        return '<span class="badge bg-secondary">OB/OG</span>';
    }

    const statusMap = {
        'pending': '<span class="badge bg-warning">承認待ち</span>',
        'active': '<span class="badge bg-success">現役</span>',
        'ob_og': '<span class="badge bg-secondary">OB/OG</span>',
        'withdrawn': '<span class="badge bg-dark">退会</span>'
    };
    return statusMap[status] || status;
}

function renderPagination(pagination) {
    const nav = document.getElementById('pagination');
    if (pagination.total_pages <= 1) {
        nav.innerHTML = '';
        return;
    }

    let html = '<ul class="pagination justify-content-center">';

    // 前へ
    html += `<li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="goToPage(${pagination.current_page - 1})">前へ</a>
    </li>`;

    // ページ番号
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || Math.abs(i - pagination.current_page) <= 2) {
            html += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="goToPage(${i})">${i}</a>
            </li>`;
        } else if (Math.abs(i - pagination.current_page) === 3) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    // 次へ
    html += `<li class="page-item ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="goToPage(${pagination.current_page + 1})">次へ</a>
    </li>`;

    html += '</ul>';
    nav.innerHTML = html;
}

function goToPage(page) {
    currentPage = page;
    loadMembers();
    return false;
}

function resetFilters() {
    document.getElementById('searchQuery').value = '';
    ['grade', 'faculty', 'gender', 'department', 'status'].forEach(name => {
        document.querySelectorAll(`#dropdown-${name} input[type="checkbox"]`).forEach(cb => cb.checked = false);
        updateMultiSelectLabel(name);
    });
    currentPage = 1;
    loadMembers();
}

function exportExcel() {
    // 年度セレクトの選択肢をExcelモーダル側にコピー
    const srcSelect = document.getElementById('academicYearSelect');
    const destSelect = document.getElementById('exportYear');
    destSelect.innerHTML = '<option value="">すべての年度</option>';
    Array.from(srcSelect.options).forEach(opt => {
        const o = document.createElement('option');
        o.value = opt.value;
        o.textContent = opt.textContent;
        if (opt.selected) o.selected = true;
        destSelect.appendChild(o);
    });
    // Excelフィルタをリセット
    ['exportStatus', 'exportGrade', 'exportGender', 'exportFaculty', 'exportDepartment'].forEach(name => {
        document.querySelectorAll(`#dropdown-${name} input[type="checkbox"]`).forEach(cb => cb.checked = false);
        updateMultiSelectLabel(name);
    });
    exportModal.show();
}

function exportCheckAll(checked) {
    document.querySelectorAll('.export-col').forEach(cb => cb.checked = checked);
}

function doExportExcel() {
    const params = new URLSearchParams();
    const yearVal = document.getElementById('exportYear').value;
    if (yearVal) params.append('academic_year', yearVal);

    // 複数選択フィルタ
    const exportFilterMap = {
        exportStatus: 'status', exportGrade: 'grade',
        exportGender: 'gender', exportFaculty: 'faculty', exportDepartment: 'department'
    };
    Object.entries(exportFilterMap).forEach(([dropdownName, paramName]) => {
        getMultiSelectValues(dropdownName).forEach(v => params.append(paramName + '[]', v));
    });

    // リストの現在の順番でチェック済み項目を取得
    const cols = Array.from(document.querySelectorAll('#exportColumnList .export-col:checked')).map(cb => cb.value);
    if (cols.length === 0) { alert('出力する項目を1つ以上選択してください'); return; }
    params.append('columns', cols.join(','));

    exportModal.hide();
    window.location.href = `/index.php?route=api/members/export&${params}`;
}

// ドラッグ&ドロップで列の順番を並び替え
(function () {
    let draggingEl = null;

    document.addEventListener('dragstart', e => {
        const item = e.target.closest('.export-col-item');
        if (!item) return;
        draggingEl = item;
        setTimeout(() => item.classList.add('dragging'), 0);
    });

    document.addEventListener('dragend', e => {
        const item = e.target.closest('.export-col-item');
        if (!item) return;
        item.classList.remove('dragging');
        document.querySelectorAll('.export-col-item').forEach(el => el.classList.remove('drag-over'));
        draggingEl = null;
    });

    document.addEventListener('dragover', e => {
        e.preventDefault();
        const item = e.target.closest('.export-col-item');
        if (!item || item === draggingEl) return;
        document.querySelectorAll('.export-col-item').forEach(el => el.classList.remove('drag-over'));
        item.classList.add('drag-over');

        const list = item.parentNode;
        const items = Array.from(list.children);
        const draggingIdx = items.indexOf(draggingEl);
        const targetIdx   = items.indexOf(item);
        if (draggingIdx < targetIdx) {
            list.insertBefore(draggingEl, item.nextSibling);
        } else {
            list.insertBefore(draggingEl, item);
        }
    });

    document.addEventListener('drop', e => {
        e.preventDefault();
        document.querySelectorAll('.export-col-item').forEach(el => el.classList.remove('drag-over'));
    });
})();

function showCreateModal() {
    document.getElementById('memberModalTitle').textContent = '会員を追加';
    document.getElementById('memberId').value = '';
    document.getElementById('memberForm').reset();
    document.getElementById('deleteMemberBtn').style.display = 'none';
    document.getElementById('status').value = 'active';
    document.getElementById('snsAllowed').checked = true;
    document.getElementById('studentIdParseResult').style.display = 'none';
    document.getElementById('oauthRow').style.display = 'none';
    memberModal.show();
}

async function adminUnlinkOauth() {
    const id = document.getElementById('memberId').value;
    if (!id) return;
    if (!confirm('この会員の外部アカウント連携をすべて解除しますか？\n解除すると、その会員は学籍番号でログインできるようになります。')) return;
    try {
        const res  = await fetch(`/index.php?route=api/members/${id}/oauth`, { method: 'DELETE' });
        const json = await res.json();
        if (json.success) {
            alert(json.message || '連携を解除しました');
            editMember(id); // 表示を更新
        } else {
            alert(json.error?.message || '解除に失敗しました');
        }
    } catch (e) {
        alert('通信エラーが発生しました');
    }
}

async function editMember(id) {
    try {
        const res = await fetch(`/index.php?route=api/members/${id}`);
        const data = await res.json();

        if (data.success) {
            const m = data.data;
            document.getElementById('memberModalTitle').textContent = '会員情報編集';
            document.getElementById('memberId').value = m.id;
            document.getElementById('nameKanji').value = m.name_kanji;
            document.getElementById('nameKana').value = m.name_kana;
            document.getElementById('gender').value = m.gender;
            document.getElementById('grade').value = m.grade;
            document.getElementById('birthdate').value = m.birthdate;
            document.getElementById('studentId').value = m.student_id;
            document.getElementById('faculty').value = m.faculty;
            updateDepartmentOptions();

            // 学科/学系の設定（リストにない場合は「その他」扱い）
            const deptSelect = document.getElementById('department');
            const deptOptions = Array.from(deptSelect.options).map(opt => opt.value);

            if (deptOptions.includes(m.department)) {
                // リストに存在する場合
                deptSelect.value = m.department;
            } else if (m.department) {
                // リストにない場合は「その他」を選択して自由記述欄に入力
                deptSelect.value = 'その他（英語学位等）';
                document.getElementById('departmentOther').value = m.department;
                toggleDepartmentOther();
            }

            document.getElementById('phone').value = m.phone;
            document.getElementById('emergencyContact').value = m.emergency_contact;
            document.getElementById('address').value = m.address;
            document.getElementById('email').value = m.email || '';
            document.getElementById('lineName').value = m.line_name;
            document.getElementById('allergy').value = m.allergy || '';
            document.getElementById('sportsRegistrationNo').value = m.sports_registration_no || '';
            document.getElementById('snsAllowed').checked = m.sns_allowed == 1;
            document.getElementById('status').value = m.status;
            document.getElementById('deleteMemberBtn').style.display = 'block';
            document.getElementById('studentIdParseResult').style.display = 'none';

            // OAuth連携状況の表示
            const oauthRow = document.getElementById('oauthRow');
            const providers = m.oauth_providers || [];
            const labelMap = { google: 'Google', line: 'LINE' };
            oauthRow.style.display = 'block';
            if (providers.length > 0) {
                const names = providers.map(p => labelMap[p] || p).join('・');
                document.getElementById('oauthStatusText').innerHTML =
                    '<span class="badge bg-success">連携中</span> ' + names + '（学籍番号ログイン不可）';
                document.getElementById('oauthUnlinkBtn').style.display = 'inline-block';
            } else {
                document.getElementById('oauthStatusText').textContent = '連携なし（学籍番号でログイン）';
                document.getElementById('oauthUnlinkBtn').style.display = 'none';
            }

            memberModal.show();
        }
    } catch (err) {
        alert('会員情報の取得に失敗しました');
    }
}

function updateDepartmentOptions() {
    const faculty = document.getElementById('faculty').value;
    const grade = document.getElementById('grade').value;
    const deptSelect = document.getElementById('department');

    deptSelect.innerHTML = '<option value="">選択してください</option>';

    if (!faculty || !departmentOptions[faculty]) return;

    const opts = departmentOptions[faculty];

    if (faculty === '基幹理工学部') {
        // 基幹理工学部の場合、学年によって選択肢が異なる
        if (grade === '1') {
            // 1年生は学系を選択（入学年度で判断）
            const studentId = document.getElementById('studentId').value;
            const yearMatch = studentId.match(/^1W(\d{2})/);
            const enrollmentYear = yearMatch ? 2000 + parseInt(yearMatch[1]) : new Date().getFullYear();

            const gakukeiList = enrollmentYear >= 2025 ? opts.gakukei_new : opts.gakukei_old;
            gakukeiList.forEach(g => {
                deptSelect.innerHTML += `<option value="${g}">${g}</option>`;
            });
        } else {
            // 2年生以上は学科を選択
            opts.departments.forEach(d => {
                deptSelect.innerHTML += `<option value="${d}">${d}</option>`;
            });
        }
    } else {
        // 創造・先進は学科のみ
        opts.departments.forEach(d => {
            deptSelect.innerHTML += `<option value="${d}">${d}</option>`;
        });
    }
}

function toggleDepartmentOther() {
    const deptSelect = document.getElementById('department');
    const otherInput = document.getElementById('departmentOther');
    const otherHint = document.getElementById('departmentOtherHint');

    if (deptSelect.value === 'その他（英語学位等）') {
        otherInput.style.display = 'block';
        otherHint.style.display = 'block';
        otherInput.required = true;
    } else {
        otherInput.style.display = 'none';
        otherHint.style.display = 'none';
        otherInput.required = false;
        otherInput.value = '';
    }
}

function calcGradeFromEnrollmentYear(enrollmentYear) {
    const now = new Date();
    // 入学2年後の10月1日以降はOB/OG
    if (now >= new Date(enrollmentYear + 2, 9, 1)) {
        return 'OB'; // OB/OG は呼び出し側で性別により分岐
    }
    // 現在年度（4月始まり）
    const currentMonth = now.getMonth() + 1;
    const currentAcademicYear = currentMonth >= 4 ? now.getFullYear() : now.getFullYear() - 1;
    const grade = currentAcademicYear - enrollmentYear + 1;
    return String(Math.max(1, grade));
}

function normalizeStudentId() {
    const el = document.getElementById('studentId');
    const pos = el.selectionStart;
    el.value = el.value
        .replace(/[！-～]/g, s => String.fromCharCode(s.charCodeAt(0) - 0xFEE0))
        .replace(/　/g, ' ')
        .toUpperCase();
    el.setSelectionRange(pos, pos);
}

async function parseStudentId() {
    const studentId = document.getElementById('studentId').value;
    if (studentId.length < 5) {
        document.getElementById('studentIdParseResult').style.display = 'none';
        return;
    }

    try {
        const res = await fetch(`/index.php?route=api/members/parse-student-id&student_id=${encodeURIComponent(studentId)}`);
        const data = await res.json();

        const resultDiv = document.getElementById('studentIdParseResult');

        if (data.success && data.data.is_valid) {
            const d = data.data;

            // 学年を自動計算
            const autoGrade = calcGradeFromEnrollmentYear(d.enrollment_year);
            const gradeLabel = autoGrade === 'OB' || autoGrade === 'OG' ? autoGrade : autoGrade + '年';

            resultDiv.innerHTML = `
                <small>
                    <strong>自動判定:</strong> ${escapeHtml(d.faculty)} ${d.enrollment_year}年入学 → ${gradeLabel}
                    ${d.department ? `<br>学科: ${escapeHtml(d.department)}` : ''}
                </small>
            `;
            resultDiv.className = 'alert alert-success mb-0 mt-2 py-2';
            resultDiv.style.display = 'block';

            // フォームに自動入力
            document.getElementById('faculty').value = d.faculty;
            updateDepartmentOptions();
            if (d.department) {
                document.getElementById('department').value = d.department;
            }

            // 学年を自動セット
            const gradeSelect = document.getElementById('grade');
            if (autoGrade === 'OB' || autoGrade === 'OG') {
                const gender = document.getElementById('gender').value;
                gradeSelect.value = gender === 'female' ? 'OG' : 'OB';
            } else {
                gradeSelect.value = autoGrade;
            }
        } else {
            resultDiv.innerHTML = '<small>学籍番号の形式が正しくありません</small>';
            resultDiv.className = 'alert alert-warning mb-0 mt-2 py-2';
            resultDiv.style.display = 'block';
        }
    } catch (err) {
        console.error(err);
    }
}

async function saveMember() {
    const id = document.getElementById('memberId').value;

    // 学科/学系の取得（「その他」の場合は自由記述を使用）
    let department = document.getElementById('department').value;
    if (department === 'その他（英語学位等）') {
        const otherValue = document.getElementById('departmentOther').value.trim();
        if (!otherValue) {
            alert('学科名を入力してください');
            return;
        }
        department = otherValue;
    }

    const data = {
        name_kanji: document.getElementById('nameKanji').value,
        name_kana: document.getElementById('nameKana').value,
        gender: document.getElementById('gender').value,
        grade: document.getElementById('grade').value,
        birthdate: document.getElementById('birthdate').value,
        student_id: document.getElementById('studentId').value,
        faculty: document.getElementById('faculty').value,
        department: department,
        phone: document.getElementById('phone').value,
        emergency_contact: document.getElementById('emergencyContact').value,
        address: document.getElementById('address').value,
        email: document.getElementById('email').value,
        line_name: document.getElementById('lineName').value,
        allergy: document.getElementById('allergy').value,
        sports_registration_no: document.getElementById('sportsRegistrationNo').value,
        sns_allowed: document.getElementById('snsAllowed').checked ? 1 : 0,
        status: document.getElementById('status').value
    };

    const url = id ? `/index.php?route=api/members/${id}` : '/index.php?route=api/members';
    const method = id ? 'PUT' : 'POST';

    try {
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await res.json();

        if (result.success) {
            memberModal.hide();
            loadMembers();
            showToast('保存しました');
        } else {
            alert(result.error?.message || '保存に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

async function deleteMember() {
    const id = document.getElementById('memberId').value;
    if (!confirm('この会員を削除しますか？\n\n※関連する申し込みデータも削除されます。')) return;

    try {
        const res = await fetch(`/index.php?route=api/members/${id}`, {
            method: 'DELETE'
        });

        const result = await res.json();

        if (result.success) {
            memberModal.hide();
            loadMembers();
            showToast('削除しました');
        } else {
            alert(result.error?.message || '削除に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

function showImportModal() {
    document.getElementById('importFile').value = '';
    importModal.show();
}

async function importMembers() {
    const fileInput = document.getElementById('importFile');
    if (!fileInput.files || !fileInput.files[0]) {
        alert('ファイルを選択してください');
        return;
    }

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);

    try {
        const res = await fetch('/index.php?route=api/members/import', {
            method: 'POST',
            body: formData
        });

        const result = await res.json();

        if (result.success) {
            importModal.hide();
            loadMembers();
            showToast(`${result.data.imported}名をインポートしました`);
        } else {
            alert(result.error?.message || 'インポートに失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

function showNewYearModal() {
    // 現在の年度を取得して+1を提案
    const currentSelect = document.getElementById('academicYearSelect');
    const currentYear = parseInt(currentSelect.value) || new Date().getFullYear();
    document.getElementById('newYear').value = currentYear + 1;

    newYearModal.show();
}

async function createNewYear() {
    const year = parseInt(document.getElementById('newYear').value);
    const setAsCurrent = document.getElementById('setAsCurrent').checked;
    const openEnrollment = document.getElementById('openEnrollment').checked;

    if (!year || year < 2020 || year > 2100) {
        alert('年度は2020～2100の範囲で指定してください');
        return;
    }

    try {
        const res = await fetch('/index.php?route=api/academic-years', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                year: year,
                is_current: setAsCurrent ? 1 : 0,
                enrollment_open: openEnrollment ? 1 : 0
            })
        });

        const result = await res.json();

        if (result.success) {
            newYearModal.hide();
            loadAcademicYears();
            showToast(`${year}年度を作成しました`);
        } else {
            alert(result.error?.message || '年度の作成に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

function gradeToNumber(grade) {
    if (grade === 'OB' || grade === 'OG') return 99;
    if (grade === 'M1') return 5;
    if (grade === 'M2') return 6;
    const n = parseInt(grade);
    return isNaN(n) ? 98 : n;
}

function compareMembers(a, b, key) {
    if (key === 'name_kana') {
        return (a.name_kana || '').localeCompare(b.name_kana || '', 'ja');
    } else if (key === 'grade') {
        return gradeToNumber(a.grade) - gradeToNumber(b.grade);
    } else if (key === 'gender') {
        const order = { 'male': 1, 'female': 2 };
        return (order[a.gender] || 3) - (order[b.gender] || 3);
    } else if (key === 'faculty') {
        return (a.faculty || '').localeCompare(b.faculty || '', 'ja');
    } else if (key === 'enrollment_year') {
        return (a.enrollment_year || 0) - (b.enrollment_year || 0);
    }
    return 0;
}

function renderMembersSorted() {
    const sorted = [...allLoadedMembers].sort((a, b) => {
        let cmp = compareMembers(a, b, sortConfig.primary.key);
        if (cmp !== 0) return cmp * sortConfig.primary.direction;
        if (sortConfig.secondary.key) {
            cmp = compareMembers(a, b, sortConfig.secondary.key);
            if (cmp !== 0) return cmp * sortConfig.secondary.direction;
        }
        return (a.name_kana || '').localeCompare(b.name_kana || '', 'ja');
    });
    renderMembers(sorted);
}

function toggleSortDirection(level) {
    if (level === 'primary') {
        sortConfig.primary.direction *= -1;
        document.getElementById('sortPrimaryDir').textContent = sortConfig.primary.direction === 1 ? '↑' : '↓';
    } else {
        sortConfig.secondary.direction *= -1;
        document.getElementById('sortSecondaryDir').textContent = sortConfig.secondary.direction === 1 ? '↑' : '↓';
    }
    renderMembersSorted();
}

function applySorting() {
    sortConfig.primary.key = document.getElementById('sortPrimary').value;
    sortConfig.secondary.key = document.getElementById('sortSecondary').value || '';
    renderMembersSorted();
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(message) {
    // トースト通知（簡易実装）
    alert(message);
}


</script>
