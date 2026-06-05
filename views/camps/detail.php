<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= url('/camps') ?>" class="text-decoration-none">&larr; 戻る</a>
        <h1 class="mt-2" id="campTitle"><?= htmlspecialchars($camp['name']) ?></h1>
    </div>
    <a href="<?= url('/camps/' . $campId . '/result') ?>" class="btn btn-success">計算結果を見る</a>
</div>

<!-- タブナビゲーション -->
<ul class="nav nav-tabs mb-4" id="campTabs">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabBasic">基本情報</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabSchedule">日程設定</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabParticipants">参加者管理</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabExpenses">雑費管理</button>
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
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabTennis">テニス班分け</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPlans">企画班分け</button>
    </li>
</ul>

<div class="tab-content">
    <!-- 基本情報タブ -->
    <div class="tab-pane fade show active" id="tabBasic">
        <div id="basicInfo">読み込み中...</div>
    </div>

    <!-- 日程設定タブ -->
    <div class="tab-pane fade" id="tabSchedule">
        <div id="scheduleInfo">読み込み中...</div>
    </div>

    <!-- 参加者管理タブ -->
    <div class="tab-pane fade" id="tabParticipants">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>参加者一覧 <span class="badge bg-secondary" id="participantCount">0</span></h4>
            <div>
                <a class="btn btn-outline-success btn-sm me-2" href="/api/camps/<?= (int)$campId ?>/export/activity-meibo">参加者名簿</a>
                <button class="btn btn-outline-warning btn-sm me-2" onclick="showAllergyListModal()">アレルギーリスト</button>
                <button class="btn btn-outline-danger btn-sm me-2" onclick="deleteAllParticipants()">全員削除</button>
                <button class="btn btn-outline-secondary btn-sm me-2" onclick="showCsvImportModal()">CSVインポート</button>
                <button class="btn btn-primary btn-sm" onclick="showParticipantModal()">+ 参加者を追加</button>
            </div>
        </div>

        <!-- 検索・フィルタ -->
        <div class="row mb-3">
            <div class="col-md-4">
                <input type="text" class="form-control form-control-sm" id="participantSearch" placeholder="名前で検索..." oninput="filterParticipants()">
            </div>
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- 第1ソート -->
                    <div class="input-group input-group-sm" style="width: auto;">
                        <span class="input-group-text">並び替え</span>
                        <select class="form-select form-select-sm" id="sortPrimary" onchange="applySorting()" style="width: auto;">
                            <option value="id">登録順</option>
                            <option value="name">名前</option>
                            <option value="grade" selected>学年</option>
                            <option value="gender">性別</option>
                        </select>
                        <button type="button" class="btn btn-outline-secondary" id="sortPrimaryDir" onclick="toggleSortDirection('primary')" title="昇順/降順切り替え">↑</button>
                    </div>
                    <!-- 第2ソート -->
                    <div class="input-group input-group-sm" style="width: auto;">
                        <span class="input-group-text">→</span>
                        <select class="form-select form-select-sm" id="sortSecondary" onchange="applySorting()" style="width: auto;">
                            <option value="">なし</option>
                            <option value="id">登録順</option>
                            <option value="name">名前</option>
                            <option value="grade">学年</option>
                            <option value="gender" selected>性別</option>
                        </select>
                        <button type="button" class="btn btn-outline-secondary" id="sortSecondaryDir" onclick="toggleSortDirection('secondary')" title="昇順/降順切り替え">↑</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="participantList">読み込み中...</div>
    </div>

    <!-- 雑費管理タブ -->
    <div class="tab-pane fade" id="tabExpenses">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>雑費管理</h4>
            <button class="btn btn-primary btn-sm" onclick="showExpenseModal()">+ 全員対象の雑費を追加</button>
        </div>

        <!-- 時間割形式のUI -->
        <div class="card mb-4">
            <div class="card-header">
                <small class="text-muted">マスをクリックして、そのタイミングの参加者を対象とした雑費を追加できます</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0" id="expenseScheduleTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width:80px;"></th>
                                <!-- 日程は動的に生成 -->
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 時間割は動的に生成 -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 全員対象の雑費一覧 -->
        <h5 class="mt-4 mb-3">全員対象の雑費</h5>
        <div id="expenseListAll">読み込み中...</div>

        <!-- 建て替え総額集計 -->
        <h5 class="mt-4 mb-3">建て替え総額</h5>
        <div id="payerSummary">読み込み中...</div>
    </div>

    <!-- 申し込みURLタブ -->
    <div class="tab-pane fade" id="tabApplication">
        <div id="applicationUrlContent">読み込み中...</div>
    </div>

    <!-- 集金管理タブ -->
    <div class="tab-pane fade" id="tabCollection">
        <div id="collectionContent">読み込み中...</div>
    </div>

    <!-- ========== しおりタブ ========== -->
    <div class="tab-pane fade" id="tabBooklet">
        <div id="bookletArea">読み込み中...</div>
    </div>

    <!-- テニス班分けタブ -->
    <div class="tab-pane fade" id="tabTennis">
        <div id="tennisArea">読み込み中...</div>
    </div>

    <!-- 企画班分けタブ -->
    <div class="tab-pane fade" id="tabPlans">
        <div id="plansArea">読み込み中...</div>
    </div>
</div>

<!-- 参加者編集モーダル -->
<div class="modal fade" id="participantModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="participantModalTitle">参加者追加</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="participantForm">
                    <input type="hidden" id="participantId">

                    <div class="mb-3">
                        <label class="form-label">名前 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="participantName" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">学年</label>
                            <select class="form-select" id="participantGrade">
                                <option value="">未設定</option>
                                <option value="1">1年</option>
                                <option value="2">2年</option>
                                <option value="3">3年</option>
                                <option value="0">OB/OG</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">性別</label>
                            <select class="form-select" id="participantGender">
                                <option value="">未設定</option>
                                <option value="male">男</option>
                                <option value="female">女</option>
                            </select>
                            <small class="text-muted" id="obogHint" style="display:none;">OB/OGは性別で区別されます</small>
                        </div>
                    </div>

                    <h6 class="border-bottom pb-2 mb-3">参加期間</h6>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">参加開始</label>
                            <div class="input-group">
                                <select class="form-select" id="joinDay"></select>
                                <span class="input-group-text">日目</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">タイミング</label>
                            <select class="form-select" id="joinTiming" onchange="updateBusFromTiming()">
                                <!-- 選択肢はJavaScriptで動的に生成 -->
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">参加終了</label>
                            <div class="input-group">
                                <select class="form-select" id="leaveDay"></select>
                                <span class="input-group-text">日目</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">タイミング</label>
                            <select class="form-select" id="leaveTiming" onchange="updateBusFromTiming()">
                                <!-- 選択肢はJavaScriptで動的に生成 -->
                            </select>
                        </div>
                    </div>

                    <h6 class="border-bottom pb-2 mb-3">交通手段</h6>
                    <div class="row mb-3">
                        <div class="col-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="useOutboundBus" checked>
                                <label class="form-check-label" for="useOutboundBus">往路バス</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="useReturnBus" checked>
                                <label class="form-check-label" for="useReturnBus">復路バス</label>
                            </div>
                        </div>
                        <div class="col-4" id="rentalCarOption" style="display:none;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="useRentalCar">
                                <label class="form-check-label" for="useRentalCar">レンタカー</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">アレルギー</label>
                        <textarea class="form-control" id="participantAllergy" rows="2" placeholder="例: 卵, 乳, ピーナッツ"></textarea>
                        <small class="text-muted">アレルギーがある場合は記入してください</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger me-auto" id="deleteParticipantBtn" onclick="deleteParticipant()" style="display:none;">削除</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" onclick="saveParticipant()">保存</button>
            </div>
        </div>
    </div>
</div>

<!-- 雑費編集モーダル -->
<div class="modal fade" id="expenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="expenseModalTitle">雑費追加</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="expenseForm">
                    <input type="hidden" id="expenseId">

                    <div class="mb-3">
                        <label class="form-label">項目名 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="expenseName" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">金額（総額） <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="expenseAmount" required>
                            <span class="input-group-text">円</span>
                        </div>
                    </div>

                    <h6 class="border-bottom pb-2 mb-3">割り勘対象</h6>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="targetType" id="targetAll" value="all" checked onchange="toggleTargetSlot()">
                            <label class="form-check-label" for="targetAll">全参加者</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="targetType" id="targetSlot" value="slot" onchange="toggleTargetSlot()">
                            <label class="form-check-label" for="targetSlot">特定タイミングの参加者</label>
                        </div>
                    </div>

                    <div class="row mb-3" id="targetSlotInputs" style="display:none;">
                        <div class="col-6">
                            <select class="form-select" id="expenseTargetDay"></select>
                        </div>
                        <div class="col-6">
                            <select class="form-select" id="expenseTargetSlot">
                                <option value="morning">午前</option>
                                <option value="afternoon">午後</option>
                                <option value="banquet">宴会</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="border-bottom pb-2 mb-3">建て替え者</h6>
                    <div class="mb-3">
                        <select class="form-select" id="expensePayerId">
                            <option value="">なし（未指定）</option>
                            <!-- 参加者リストは動的に生成 -->
                        </select>
                        <small class="text-muted">この費用を立て替えた人を選択</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger me-auto" id="deleteExpenseBtn" onclick="deleteExpense()" style="display:none;">削除</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" onclick="saveExpense()">保存</button>
            </div>
        </div>
    </div>
</div>

<!-- CSVインポートモーダル -->
<div class="modal fade" id="csvImportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">CSVインポート</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>対応形式:</strong><br>
                    <strong>形式1:</strong> 氏名,学年性別（例: 田中太郎,1男）<br>
                    <strong>形式2:</strong> 氏名,性別,学年（例: 田中太郎,男,2）<br>
                    <small class="text-muted">※学年性別: 1男, 1女, 2男, 2女, 3男, 3女, OB, OG に対応</small><br>
                    <small class="text-muted">※ヘッダー行は不要です</small>
                </div>

                <!-- ファイルアップロード -->
                <div class="mb-3">
                    <label class="form-label">CSVファイルをアップロード</label>
                    <input type="file" class="form-control" id="csvFile" accept=".csv,.txt" onchange="loadCsvFile(this)">
                </div>

                <div class="text-center text-muted mb-3">または</div>

                <!-- テキスト貼り付け -->
                <div class="mb-3">
                    <label class="form-label">CSVデータを貼り付け</label>
                    <textarea class="form-control" id="csvData" rows="8" placeholder="田中太郎,1男&#10;鈴木花子,2女&#10;山田次郎,男,3&#10;高橋美咲,女,OG"></textarea>
                </div>
                <div id="csvImportResult" class="d-none">
                    <div class="alert alert-success" id="csvSuccessMsg"></div>
                    <div class="alert alert-danger d-none" id="csvErrorMsg"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                <button type="button" class="btn btn-primary" onclick="importCsv()">インポート</button>
            </div>
        </div>
    </div>
</div>

<!-- 申し込み情報修正確認モーダル -->
<div class="modal fade" id="appEditModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square text-warning"></i> 申し込み時の情報修正</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">参加者が申し込み時に修正した内容です。会員名簿への反映を確認してください。</p>
                <div id="appEditModalContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                <button type="button" class="btn btn-primary" id="appEditApplyBtn">
                    <i class="bi bi-check-circle"></i> 会員名簿に反映する
                </button>
            </div>
        </div>
    </div>
</div>

<!-- アレルギーリストモーダル -->
<div class="modal fade" id="allergyListModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">アレルギーのある参加者</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="allergyListContent"></div>
            </div>
            <div class="modal-footer">
                <a id="allergyListPdfBtn" href="#" target="_blank" class="btn btn-outline-primary me-auto">PDF出力</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>

<!-- 基本情報編集モーダル -->
<div class="modal fade" id="basicInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">基本情報を編集</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="basicInfoForm">
                    <h6 class="border-bottom pb-2 mb-3">基本情報</h6>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">合宿名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editCampName" required>
                        </div>
                    </div>

                    <h6 class="border-bottom pb-2 mb-3">施設利用料</h6>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">コート1面あたり料金</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" id="editCourtFeePerUnit" min="0">
                                <span class="input-group-text">/面</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">体育館1コマあたり料金</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" id="editGymFeePerUnit" min="0">
                                <span class="input-group-text">/コマ</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">宴会場料金（1人あたり）</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" id="editBanquetFeePerPerson" min="0">
                                <span class="input-group-text">/人</span>
                            </div>
                        </div>
                    </div>

                    <h6 class="border-bottom pb-2 mb-3">宿泊費用</h6>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">1泊料金</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" id="editLodgingFee" min="0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">入湯税（1泊あたり）</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" id="editHotSpringTax" min="0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">保険料</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" id="editInsuranceFee" min="0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">1日目昼食</label>
                            <select class="form-select" id="editFirstDayLunch">
                                <option value="1">対象</option>
                                <option value="0">対象外</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="border-bottom pb-2 mb-3">食事単価</h6>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">朝食追加</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" id="editBreakfastAdd" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">昼食追加</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" id="editLunchAdd" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">夕食追加</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" id="editDinnerAdd" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">朝食削除</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" id="editBreakfastRemove" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">昼食削除</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" id="editLunchRemove" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">夕食削除</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" id="editDinnerRemove" min="0">
                            </div>
                        </div>
                    </div>

                    <h6 class="border-bottom pb-2 mb-3">交通費（バス）</h6>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="editBusFeeSeparate" onchange="toggleEditBusFeeFields()">
                            <label class="form-check-label" for="editBusFeeSeparate">往路/復路を別々に設定</label>
                        </div>
                    </div>
                    <div class="row mb-3" id="editBusFeeRoundTripField">
                        <div class="col-md-6">
                            <label class="form-label">バス代（往復）</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" id="editBusFeeRoundTrip" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3" id="editBusFeeSeparateFields" style="display:none;">
                        <div class="col-md-6">
                            <label class="form-label">往路バス代</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" id="editBusFeeOutbound" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">復路バス代</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" id="editBusFeeReturn" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">往路高速代</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" id="editHighwayFeeOutbound" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">復路高速代</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" id="editHighwayFeeReturn" min="0">
                            </div>
                        </div>
                    </div>

                    <h6 class="border-bottom pb-2 mb-3">レンタカー</h6>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="editUseRentalCar" onchange="toggleEditRentalCarFields()">
                            <label class="form-check-label" for="editUseRentalCar">レンタカーを使用</label>
                        </div>
                    </div>
                    <div id="editRentalCarFields" style="display:none;">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">レンタカー代</label>
                                <div class="input-group">
                                    <span class="input-group-text">¥</span>
                                    <input type="number" class="form-control" id="editRentalCarFee" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">高速代</label>
                                <div class="input-group">
                                    <span class="input-group-text">¥</span>
                                    <input type="number" class="form-control" id="editRentalCarHighwayFee" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">定員</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="editRentalCarCapacity" min="1">
                                    <span class="input-group-text">人</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" onclick="saveBasicInfo()">保存</button>
            </div>
        </div>
    </div>
</div>

<script>
const campId = <?= $campId ?>;
let campData = null;
let participantModal, expenseModal, csvImportModal, basicInfoModal, allergyListModal, appEditModal;
// 並び替え設定
let sortConfig = {
    primary: { key: 'grade', direction: 1 },
    secondary: { key: 'gender', direction: 1 }
};

document.addEventListener('DOMContentLoaded', () => {
    participantModal = new bootstrap.Modal(document.getElementById('participantModal'));
    expenseModal = new bootstrap.Modal(document.getElementById('expenseModal'));
    csvImportModal = new bootstrap.Modal(document.getElementById('csvImportModal'));
    basicInfoModal = new bootstrap.Modal(document.getElementById('basicInfoModal'));
    allergyListModal = new bootstrap.Modal(document.getElementById('allergyListModal'));
    appEditModal = new bootstrap.Modal(document.getElementById('appEditModal'));
    loadCampData();

    // タブが完全に表示された後に描画（初回の空白・タブ往復時の空白を防ぐ）
    const tennisBtn = document.querySelector('[data-bs-target="#tabTennis"]');
    if (tennisBtn) tennisBtn.addEventListener('shown.bs.tab', () => loadTennis());
    const plansBtn = document.querySelector('[data-bs-target="#tabPlans"]');
    if (plansBtn) plansBtn.addEventListener('shown.bs.tab', () => loadPlans());
});

async function loadCampData() {
    try {
        const res = await fetch(`/index.php?route=api/camps/${campId}`);
        const data = await res.json();

        if (data.success) {
            campData = data.data;
            renderBasicInfo();
            renderSchedule();
            renderParticipants();
            renderExpenses();
            setupDaySelectors();
        }
    } catch (err) {
        console.error(err);
    }
}

function renderBasicInfo() {
    const c = campData;
    document.getElementById('basicInfo').innerHTML = `
        <div class="d-flex justify-content-end mb-3 gap-2">
            <a href="/pdf/upload?camp_id=${c.id}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-pdf"></i> PDFから読み込む
            </a>
            <button class="btn btn-outline-primary btn-sm" onclick="showEditBasicInfoModal()">基本情報を編集</button>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span>基本情報</span>
            </div>
            <div class="card-body">
                <p><strong>日程:</strong> ${c.start_date} ～ ${c.end_date}（${c.nights}泊${parseInt(c.nights)+1}日）</p>
                <div class="row">
                    <div class="col-md-4"><strong>コート単価:</strong> ¥${Number(c.court_fee_per_unit || 0).toLocaleString()}/面</div>
                    <div class="col-md-4"><strong>体育館単価:</strong> ¥${Number(c.gym_fee_per_unit || 0).toLocaleString()}/コマ</div>
                    <div class="col-md-4"><strong>宴会場単価:</strong> ¥${Number(c.banquet_fee_per_person || 0).toLocaleString()}/人</div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">宿泊費用</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3"><strong>1泊料金:</strong> ¥${Number(c.lodging_fee_per_night).toLocaleString()}</div>
                    <div class="col-md-3"><strong>入湯税:</strong> ¥${Number(c.hot_spring_tax || 0).toLocaleString()}/泊</div>
                    <div class="col-md-3"><strong>保険料:</strong> ¥${Number(c.insurance_fee).toLocaleString()}</div>
                    <div class="col-md-3"><strong>1日目昼食:</strong> ${c.first_day_lunch_included ? '対象' : '対象外'}</div>
                </div>
                <hr>
                <p class="mb-1"><strong>食事単価:</strong></p>
                <div class="row">
                    <div class="col-md-4">朝食: +¥${c.breakfast_add_price} / -¥${c.breakfast_remove_price}</div>
                    <div class="col-md-4">昼食: +¥${c.lunch_add_price} / -¥${c.lunch_remove_price}</div>
                    <div class="col-md-4">夕食: +¥${c.dinner_add_price} / -¥${c.dinner_remove_price}</div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">交通費（バス）</div>
            <div class="card-body">
                <div class="row">
                    ${c.bus_fee_separate ?
                        `<div class="col-md-3"><strong>往路バス:</strong> ¥${Number(c.bus_fee_outbound || 0).toLocaleString()}</div>
                         <div class="col-md-3"><strong>復路バス:</strong> ¥${Number(c.bus_fee_return || 0).toLocaleString()}</div>` :
                        `<div class="col-md-6"><strong>バス代（往復）:</strong> ¥${Number(c.bus_fee_round_trip || 0).toLocaleString()}</div>`
                    }
                    <div class="col-md-3"><strong>往路高速:</strong> ¥${Number(c.highway_fee_outbound || 0).toLocaleString()}</div>
                    <div class="col-md-3"><strong>復路高速:</strong> ¥${Number(c.highway_fee_return || 0).toLocaleString()}</div>
                </div>
            </div>
        </div>

        ${c.use_rental_car ? `
        <div class="card">
            <div class="card-header">レンタカー</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4"><strong>レンタカー代:</strong> ¥${Number(c.rental_car_fee || 0).toLocaleString()}</div>
                    <div class="col-md-4"><strong>高速代:</strong> ¥${Number(c.rental_car_highway_fee || 0).toLocaleString()}</div>
                    <div class="col-md-4"><strong>定員:</strong> ${c.rental_car_capacity || '-'}人</div>
                </div>
            </div>
        </div>
        ` : ''}
    `;
}

function renderSchedule() {
    const slots = campData.time_slots || [];
    const days = parseInt(campData.nights) + 1;
    const slotNames = { outbound: '往路', morning: '午前', afternoon: '午後', banquet: '宴会', return: '復路' };
    const courtFeePerUnit = campData.court_fee_per_unit || 0;
    const gymFeePerUnit = campData.gym_fee_per_unit || 0;
    const banquetFeePerPerson = campData.banquet_fee_per_person || 0;

    let html = `
        <div class="alert alert-info mb-3">
            <small>各スロットの施設種別を設定できます。コート料金は1面あたり¥${courtFeePerUnit.toLocaleString()}、体育館は1コマ¥${gymFeePerUnit.toLocaleString()}、宴会場は1人あたり¥${banquetFeePerPerson.toLocaleString()}で計算されます。変更後は「保存」ボタンをクリックしてください。</small>
        </div>
        <div class="card">
            <div class="card-body">
    `;

    for (let day = 1; day <= days; day++) {
        const daySlots = slots.filter(s => s.day_number == day);
        html += `<h6 class="mt-3 mb-2">${day}日目</h6>`;
        html += '<div class="table-responsive"><table class="table table-sm table-bordered mb-3">';
        html += '<thead class="table-light"><tr><th style="width:100px;">時間帯</th><th>施設種別</th><th style="width:100px;">面数</th><th style="width:120px;">小計</th></tr></thead><tbody>';

        for (const slot of daySlots) {
            const isTransport = slot.slot_type === 'outbound' || slot.slot_type === 'return';

            if (isTransport) {
                // 往路・復路は固定表示
                html += `<tr>
                    <td><strong>${slotNames[slot.slot_type]}</strong></td>
                    <td>バス移動</td>
                    <td>-</td>
                    <td>-</td>
                </tr>`;
            } else {
                const isTennis = slot.activity_type === 'tennis';
                const isGym = slot.activity_type === 'gym';
                const isBanquet = slot.activity_type === 'banquet';
                const courtCount = slot.court_count || 1;
                // テニス: 面数×単価、体育館: 1コマ単価、宴会場: 1人あたり単価（自動適用）、その他: 直接入力値
                let calculatedFee = 0;
                let feeDisplayText = '';
                if (isTennis) {
                    calculatedFee = courtFeePerUnit * courtCount;
                    feeDisplayText = `¥${calculatedFee.toLocaleString()}`;
                } else if (isGym) {
                    calculatedFee = gymFeePerUnit;
                    feeDisplayText = `¥${calculatedFee.toLocaleString()}`;
                } else if (isBanquet) {
                    calculatedFee = banquetFeePerPerson;
                    feeDisplayText = `¥${calculatedFee.toLocaleString()}/人`;
                } else {
                    calculatedFee = slot.facility_fee || 0;
                    feeDisplayText = `¥${calculatedFee.toLocaleString()}`;
                }
                // 金額直接入力が必要なのはその他・なしの場合のみ
                const needsDirectFeeInput = !isTennis && !isGym && !isBanquet;

                // 午前・午後・宴会は編集可能
                html += `<tr>
                    <td><strong>${slotNames[slot.slot_type]}</strong></td>
                    <td>
                        <select class="form-select form-select-sm slot-activity" data-slot-id="${slot.id}" onchange="updateCourtCountVisibility(${slot.id})">
                            <option value="">なし</option>
                            <option value="tennis" ${slot.activity_type === 'tennis' ? 'selected' : ''}>テニスコート</option>
                            <option value="gym" ${slot.activity_type === 'gym' ? 'selected' : ''}>体育館</option>
                            <option value="banquet" ${slot.activity_type === 'banquet' ? 'selected' : ''}>宴会場</option>
                            <option value="other" ${slot.activity_type === 'other' ? 'selected' : ''}>その他</option>
                        </select>
                    </td>
                    <td>
                        <div class="slot-court-count-container" data-slot-id="${slot.id}" style="display: ${isTennis ? 'block' : 'none'};">
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control slot-court-count" data-slot-id="${slot.id}" value="${courtCount}" min="1" onchange="updateCalculatedFee(${slot.id}, true)">
                                <span class="input-group-text">面</span>
                            </div>
                        </div>
                        <div class="slot-fee-container" data-slot-id="${slot.id}" style="display: ${needsDirectFeeInput ? 'block' : 'none'};">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control slot-fee" data-slot-id="${slot.id}" value="${slot.facility_fee || 0}" min="0" onchange="updateCalculatedFee(${slot.id}, true)">
                            </div>
                        </div>
                        <div class="slot-auto-fee-label" data-slot-id="${slot.id}" style="display: ${isGym ? 'block' : 'none'};">
                            <small class="text-muted">単価自動適用</small>
                        </div>
                        <div class="slot-banquet-fee-label" data-slot-id="${slot.id}" style="display: ${isBanquet ? 'block' : 'none'};">
                            <small class="text-muted">1人あたり単価適用</small>
                        </div>
                    </td>
                    <td class="slot-calculated-fee" data-slot-id="${slot.id}">
                        ${feeDisplayText}
                    </td>
                </tr>`;
            }
        }

        // 宴会スロットがない場合は追加オプションを表示
        const hasBanquetSlot = daySlots.some(s => s.slot_type === 'banquet');
        if (!hasBanquetSlot && day < days) {
            html += `<tr class="table-secondary">
                <td><strong>宴会</strong></td>
                <td colspan="3">
                    <button class="btn btn-outline-primary btn-sm" onclick="addBanquetSlot(${day})">
                        + 宴会スロットを追加
                    </button>
                </td>
            </tr>`;
        }

        html += '</tbody></table></div>';
    }

    html += `
            </div>
            <div class="card-footer">
                <small class="text-muted">変更は自動的に保存されます</small>
            </div>
        </div>
    `;

    document.getElementById('scheduleInfo').innerHTML = html;
}

// デバウンス用タイマー
let scheduleAutoSaveTimer = null;

function scheduleAutoSave() {
    // 既存のタイマーをクリア
    if (scheduleAutoSaveTimer) {
        clearTimeout(scheduleAutoSaveTimer);
    }
    // 500ms後に保存を実行
    scheduleAutoSaveTimer = setTimeout(() => {
        saveSchedule(true); // 自動保存フラグを渡す
    }, 500);
}

function updateCourtCountVisibility(slotId) {
    const activitySelect = document.querySelector(`.slot-activity[data-slot-id="${slotId}"]`);
    const courtCountContainer = document.querySelector(`.slot-court-count-container[data-slot-id="${slotId}"]`);
    const feeContainer = document.querySelector(`.slot-fee-container[data-slot-id="${slotId}"]`);
    const autoFeeLabel = document.querySelector(`.slot-auto-fee-label[data-slot-id="${slotId}"]`);
    const banquetFeeLabel = document.querySelector(`.slot-banquet-fee-label[data-slot-id="${slotId}"]`);

    if (activitySelect && courtCountContainer && feeContainer) {
        const activityType = activitySelect.value;
        const isTennis = activityType === 'tennis';
        const isGym = activityType === 'gym';
        const isBanquet = activityType === 'banquet';
        const needsDirectFeeInput = !isTennis && !isGym && !isBanquet;

        courtCountContainer.style.display = isTennis ? 'block' : 'none';
        feeContainer.style.display = needsDirectFeeInput ? 'block' : 'none';
        if (autoFeeLabel) {
            autoFeeLabel.style.display = isGym ? 'block' : 'none';
        }
        if (banquetFeeLabel) {
            banquetFeeLabel.style.display = isBanquet ? 'block' : 'none';
        }
        updateCalculatedFee(slotId);
        // 自動保存をスケジュール
        scheduleAutoSave();
    }
}

function updateCalculatedFee(slotId, triggerAutoSave = false) {
    const activitySelect = document.querySelector(`.slot-activity[data-slot-id="${slotId}"]`);
    const courtCountInput = document.querySelector(`.slot-court-count[data-slot-id="${slotId}"]`);
    const feeInput = document.querySelector(`.slot-fee[data-slot-id="${slotId}"]`);
    const calculatedFeeCell = document.querySelector(`.slot-calculated-fee[data-slot-id="${slotId}"]`);

    if (!calculatedFeeCell) return;

    const courtFeePerUnit = campData.court_fee_per_unit || 0;
    const gymFeePerUnit = campData.gym_fee_per_unit || 0;
    const banquetFeePerPerson = campData.banquet_fee_per_person || 0;
    const activityType = activitySelect ? activitySelect.value : '';

    let fee = 0;
    let displayText = '';
    if (activityType === 'tennis') {
        const courtCount = courtCountInput ? parseInt(courtCountInput.value) || 1 : 1;
        fee = courtFeePerUnit * courtCount;
        displayText = `¥${fee.toLocaleString()}`;
    } else if (activityType === 'gym') {
        fee = gymFeePerUnit;
        displayText = `¥${fee.toLocaleString()}`;
    } else if (activityType === 'banquet') {
        fee = banquetFeePerPerson;
        displayText = `¥${fee.toLocaleString()}/人`;
    } else {
        fee = feeInput ? parseInt(feeInput.value) || 0 : 0;
        displayText = `¥${fee.toLocaleString()}`;
    }

    calculatedFeeCell.textContent = displayText;

    // 自動保存をスケジュール（triggerAutoSaveがtrueの場合のみ）
    if (triggerAutoSave) {
        scheduleAutoSave();
    }
}

async function addBanquetSlot(dayNumber) {
    // 現在のスロットデータを取得
    const slots = [...(campData.time_slots || [])];

    // 新しい宴会スロットを追加
    slots.push({
        day_number: dayNumber,
        slot_type: 'banquet',
        activity_type: 'banquet',
        facility_fee: 0,
        court_count: 1,
        description: '宴会場'
    });

    // APIで保存
    try {
        const slotsToSave = slots.map(s => ({
            day_number: s.day_number,
            slot_type: s.slot_type,
            activity_type: s.activity_type,
            facility_fee: s.facility_fee || 0,
            court_count: s.court_count || 1,
            description: s.description || ''
        }));

        const res = await fetch(`/index.php?route=api/camps/${campId}/time-slots`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ slots: slotsToSave })
        });

        const result = await res.json();

        if (result.success) {
            loadCampData();
            showToast('宴会スロットを追加しました');
        } else {
            alert(result.error?.message || '追加に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

async function saveSchedule(isAutoSave = false) {
    const slots = [];
    const existingSlots = campData.time_slots || [];
    const courtFeePerUnit = campData.court_fee_per_unit || 0;
    const gymFeePerUnit = campData.gym_fee_per_unit || 0;
    const banquetFeePerPerson = campData.banquet_fee_per_person || 0;

    // 既存スロットのデータを収集
    for (const slot of existingSlots) {
        const activitySelect = document.querySelector(`.slot-activity[data-slot-id="${slot.id}"]`);
        const feeInput = document.querySelector(`.slot-fee[data-slot-id="${slot.id}"]`);
        const courtCountInput = document.querySelector(`.slot-court-count[data-slot-id="${slot.id}"]`);

        const activityType = activitySelect ? activitySelect.value : slot.activity_type;
        const courtCount = courtCountInput ? parseInt(courtCountInput.value) || 1 : (slot.court_count || 1);

        // テニス: 面数×単価、体育館: 1コマ単価、宴会場: 1人あたり単価、それ以外: 直接入力
        let facilityFee;
        if (activityType === 'tennis') {
            facilityFee = courtFeePerUnit * courtCount;
        } else if (activityType === 'gym') {
            facilityFee = gymFeePerUnit;
        } else if (activityType === 'banquet') {
            facilityFee = banquetFeePerPerson;
        } else {
            facilityFee = feeInput ? parseInt(feeInput.value) || 0 : (slot.facility_fee || 0);
        }

        slots.push({
            day_number: slot.day_number,
            slot_type: slot.slot_type,
            activity_type: activityType,
            facility_fee: facilityFee,
            court_count: courtCount,
            description: slot.description || ''
        });
    }

    try {
        const res = await fetch(`/index.php?route=api/camps/${campId}/time-slots`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ slots })
        });

        const result = await res.json();

        if (result.success) {
            // 自動保存の場合はUIを更新せずにデータだけ更新（画面のちらつき防止）
            if (isAutoSave) {
                // campDataのtime_slotsを更新（サーバーからの応答を反映）
                if (result.data) {
                    campData.time_slots = result.data;
                }
                showToast('自動保存しました', 'success', 1500);
            } else {
                loadCampData();
                showToast('日程設定を保存しました');
            }
        } else {
            if (!isAutoSave) {
                alert(result.error?.message || '保存に失敗しました');
            }
        }
    } catch (err) {
        if (!isAutoSave) {
            alert('通信エラーが発生しました');
        }
    }
}

function isRetiredAsOB(grade) {
    // 10月引退ルール: 3年生は10月以降（10月〜3月）OB扱い
    if (grade !== 3) return false;

    const now = new Date();
    const month = now.getMonth(); // 0-11
    return month >= 9 || month <= 2; // 10月〜12月 または 1月〜3月
}

function getGradeGenderLabel(grade, gender) {
    if (grade === null && gender === null) return '';

    // 10月引退ルール: 3年生は10月以降OB扱い
    if (grade === 3 && isRetiredAsOB(grade)) {
        if (gender === 'male') return 'OB';
        if (gender === 'female') return 'OG';
        return 'OB/OG';
    }

    if (grade === 0) {
        // OB/OGを性別で区別
        if (gender === 'male') return 'OB';
        if (gender === 'female') return 'OG';
        return 'OB/OG'; // 性別未設定の場合
    }
    const gradeStr = grade ? grade : '';
    const genderStr = gender === 'male' ? '男' : (gender === 'female' ? '女' : '');
    return gradeStr + genderStr;
}

function getSortedFilteredParticipants() {
    let participants = [...(campData.participants || [])];

    // 検索フィルタ
    const searchText = document.getElementById('participantSearch').value.toLowerCase();
    if (searchText) {
        participants = participants.filter(p => p.name.toLowerCase().includes(searchText));
    }

    // ソート（複合ソート対応）
    participants.sort((a, b) => {
        // 第1ソート
        let cmp = compareByKey(a, b, sortConfig.primary.key);
        if (cmp !== 0) return cmp * sortConfig.primary.direction;

        // 第2ソート
        if (sortConfig.secondary.key) {
            cmp = compareByKey(a, b, sortConfig.secondary.key);
            if (cmp !== 0) return cmp * sortConfig.secondary.direction;
        }

        // 最終的に名前でソート
        return (a.name || '').localeCompare(b.name || '', 'ja');
    });

    return participants;
}

// ソート用の実効学年を取得（10月引退ルール適用）
function getEffectiveGradeForSort(grade) {
    if (grade === null) return 999;
    if (grade === 0) return 99; // OB/OG
    if (grade === 3 && isRetiredAsOB(grade)) return 99; // 3年生で10月以降はOB扱い
    return grade;
}

// ソートキーによる比較関数
function compareByKey(a, b, key) {
    if (key === 'id') {
        // 登録順（ID順）
        return (a.id || 0) - (b.id || 0);
    } else if (key === 'name') {
        return (a.name || '').localeCompare(b.name || '', 'ja');
    } else if (key === 'grade') {
        const gradeA = getEffectiveGradeForSort(a.grade);
        const gradeB = getEffectiveGradeForSort(b.grade);
        return gradeA - gradeB;
    } else if (key === 'gender') {
        const genderOrder = { 'male': 1, 'female': 2, null: 3 };
        return (genderOrder[a.gender] || 3) - (genderOrder[b.gender] || 3);
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
    sortConfig.primary.key = document.getElementById('sortPrimary').value;
    sortConfig.secondary.key = document.getElementById('sortSecondary').value || '';
    renderParticipants();
}

function renderParticipants() {
    const allParticipants = campData.participants || [];
    const duplicateIds = campData.duplicate_participant_ids || [];
    document.getElementById('participantCount').textContent = allParticipants.length;

    const participants = getSortedFilteredParticipants();

    if (allParticipants.length === 0) {
        document.getElementById('participantList').innerHTML = '<p class="text-muted">参加者がいません</p>';
        return;
    }

    if (participants.length === 0) {
        document.getElementById('participantList').innerHTML = '<p class="text-muted">該当する参加者がいません</p>';
        return;
    }

    const days = parseInt(campData.nights) + 1;
    const joinTimingLabels = {
        outbound_bus: '往路バス', breakfast: '朝食', morning: '午前イベント', lunch: '昼食',
        afternoon: '午後イベント', dinner: '夕食', night: '夜', lodging: '宿泊'
    };
    const leaveTimingLabels = {
        return_bus: '復路バス', before_breakfast: '朝食前', breakfast: '朝食', morning: '午前イベント',
        lunch: '昼食', afternoon: '午後イベント', dinner: '夕食', night: '夜'
    };

    // 重複警告があれば表示
    let warningHtml = '';
    if (duplicateIds.length > 0) {
        warningHtml = `<div class="alert alert-warning mb-3">
            <i class="bi bi-exclamation-triangle"></i> 同姓同名・同学年の参加者が${duplicateIds.length}名います（<span class="text-warning fw-bold">※</span>マーク）
        </div>`;
    }

    let html = warningHtml + '<table class="table table-hover"><thead><tr><th>名前</th><th>学年</th><th>参加期間</th><th>交通</th><th>アレルギー</th><th></th></tr></thead><tbody>';

    for (const p of participants) {
        const joinLabel = `${p.join_day}日目${joinTimingLabels[p.join_timing] || ''}`;
        const leaveLabel = `${p.leave_day}日目${leaveTimingLabels[p.leave_timing] || ''}`;

        let transportLabel = '';
        if (p.use_rental_car) {
            transportLabel = 'レンタカー';
        } else if (p.use_outbound_bus && p.use_return_bus) {
            transportLabel = 'バス往復';
        } else if (p.use_outbound_bus) {
            transportLabel = 'バス往路のみ';
        } else if (p.use_return_bus) {
            transportLabel = 'バス復路のみ';
        } else {
            transportLabel = 'なし';
        }

        const isFullParticipation = (p.join_day == 1 && p.join_timing == 'outbound_bus' && p.leave_day == days && p.leave_timing == 'return_bus');
        const gradeGenderLabel = getGradeGenderLabel(p.grade, p.gender);
        const isDuplicate = duplicateIds.includes(p.id);
        const duplicateMark = isDuplicate ? '<span class="text-warning fw-bold" title="同姓同名・同学年の参加者がいます">※</span> ' : '';
        const allergyBadge = p.allergy ? `<span class="badge bg-warning text-dark" title="${escapeHtml(p.allergy)}">あり</span>` : '';

        html += `<tr${isDuplicate ? ' class="table-warning"' : ''}>
            <td>${duplicateMark}${escapeHtml(p.name)}</td>
            <td>${gradeGenderLabel}</td>
            <td>${isFullParticipation ? 'フル参加' : `${joinLabel}～${leaveLabel}`}</td>
            <td>${transportLabel}</td>
            <td>${allergyBadge}</td>
            <td><button class="btn btn-outline-primary btn-sm" onclick="editParticipant(${p.id})">編集</button></td>
        </tr>`;
    }

    html += '</tbody></table>';
    document.getElementById('participantList').innerHTML = html;
}

function filterParticipants() {
    renderParticipants();
}

function showAllergyListModal() {
    const participants = campData.participants || [];
    const withAllergy = participants.filter(p => p.allergy && p.allergy.trim() !== '');

    let content;
    if (withAllergy.length === 0) {
        content = '<p class="text-muted">アレルギーのある参加者はいません。</p>';
    } else {
        content = `<p class="text-muted mb-3">アレルギー情報が登録されている参加者: <strong>${withAllergy.length}名</strong></p>`;
        content += '<table class="table table-bordered"><thead><tr><th>名前</th><th>学年</th><th>アレルギー内容</th></tr></thead><tbody>';
        for (const p of withAllergy) {
            const gradeGenderLabel = getGradeGenderLabel(p.grade, p.gender);
            content += `<tr>
                <td>${escapeHtml(p.name)}</td>
                <td>${gradeGenderLabel}</td>
                <td>${escapeHtml(p.allergy)}</td>
            </tr>`;
        }
        content += '</tbody></table>';
    }

    document.getElementById('allergyListContent').innerHTML = content;
    document.getElementById('allergyListPdfBtn').href = `/index.php?route=api/camps/${campId}/export/allergy-list`;
    allergyListModal.show();
}

function showCsvImportModal() {
    document.getElementById('csvData').value = '';
    document.getElementById('csvFile').value = '';
    document.getElementById('csvImportResult').classList.add('d-none');
    csvImportModal.show();
}

function loadCsvFile(input) {
    const file = input.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('csvData').value = e.target.result;
    };
    reader.onerror = function() {
        alert('ファイルの読み込みに失敗しました');
    };
    // Shift-JISも考慮してテキストとして読み込む
    reader.readAsText(file, 'UTF-8');
}

async function deleteAllParticipants() {
    const participants = campData.participants || [];
    if (participants.length === 0) {
        alert('削除する参加者がいません');
        return;
    }

    if (!confirm(`参加者${participants.length}名を全員削除しますか？\n\n※この操作は取り消せません。`)) return;

    try {
        const res = await fetch(`/index.php?route=api/camps/${campId}/participants/deleteAll`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' }
        });

        const data = await res.json();

        if (data.success) {
            showToast('参加者を全員削除しました');
            loadCampData();
        } else {
            alert(data.error?.message || '削除に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

async function importCsv() {
    const csvData = document.getElementById('csvData').value.trim();

    if (!csvData) {
        alert('CSVデータを入力してください');
        return;
    }

    try {
        const res = await fetch(`/index.php?route=api/camps/${campId}/participants/import`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ csv_data: csvData })
        });

        const result = await res.json();

        const resultDiv = document.getElementById('csvImportResult');
        const successDiv = document.getElementById('csvSuccessMsg');
        const errorDiv = document.getElementById('csvErrorMsg');

        resultDiv.classList.remove('d-none');

        if (result.success) {
            let successMsg = `${result.data.success_count}名の参加者を登録しました`;
            if (result.data.has_duplicates) {
                successMsg += `\n⚠️ 同姓同名・同学年の参加者が${result.data.duplicate_count}名います`;
            }
            successDiv.textContent = successMsg;
            successDiv.classList.remove('d-none');

            if (result.data.errors && result.data.errors.length > 0) {
                errorDiv.innerHTML = result.data.errors.join('<br>');
                errorDiv.classList.remove('d-none');
            } else {
                errorDiv.classList.add('d-none');
            }

            // 重複警告がある場合はアラートも表示
            if (result.data.has_duplicates) {
                alert(`⚠️ 同姓同名・同学年の参加者が${result.data.duplicate_count}名います。\n参加者一覧で確認してください。`);
            }

            loadCampData();
        } else {
            successDiv.classList.add('d-none');
            errorDiv.textContent = result.error?.message || 'インポートに失敗しました';
            errorDiv.classList.remove('d-none');
        }

    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

function renderExpenses() {
    const expenses = campData.expenses || [];
    const days = parseInt(campData.nights) + 1;
    const slotTypes = ['morning', 'afternoon', 'banquet'];
    const slotNames = { morning: '午前', afternoon: '午後', banquet: '宴会' };

    // 時間割テーブルのヘッダーを生成
    let headerHtml = '<tr><th style="width:80px;"></th>';
    for (let day = 1; day <= days; day++) {
        headerHtml += `<th class="text-center">${day}日目</th>`;
    }
    headerHtml += '</tr>';
    document.querySelector('#expenseScheduleTable thead').innerHTML = headerHtml;

    // 時間割テーブルの本体を生成
    let bodyHtml = '';
    for (const slotType of slotTypes) {
        bodyHtml += `<tr><th class="table-light">${slotNames[slotType]}</th>`;
        for (let day = 1; day <= days; day++) {
            // このスロットの雑費を取得
            const slotExpenses = expenses.filter(e =>
                e.target_type === 'slot' && e.target_day == day && e.target_slot === slotType
            );

            let cellContent = '';
            if (slotExpenses.length > 0) {
                cellContent = slotExpenses.map(e =>
                    `<div class="expense-item mb-1" onclick="editExpense(${e.id}); event.stopPropagation();">
                        <small class="text-primary" style="cursor:pointer;">
                            ${escapeHtml(e.name)}<br>
                            <span class="text-muted">¥${Number(e.amount).toLocaleString()}</span>
                            ${e.payer_name ? `<br><span class="text-info">[${escapeHtml(e.payer_name)}]</span>` : ''}
                        </small>
                    </div>`
                ).join('');
            }

            bodyHtml += `<td class="expense-cell" style="cursor:pointer; min-height:60px; vertical-align:top; padding:8px;"
                            onclick="showExpenseModalForSlot(${day}, '${slotType}')"
                            data-day="${day}" data-slot="${slotType}">
                ${cellContent}
                <div class="add-expense-hint text-muted" style="font-size:0.75rem;">
                    ${slotExpenses.length === 0 ? '<i>+ クリックで追加</i>' : '<i>+ 追加</i>'}
                </div>
            </td>`;
        }
        bodyHtml += '</tr>';
    }
    document.querySelector('#expenseScheduleTable tbody').innerHTML = bodyHtml;

    // 全員対象の雑費一覧
    const allExpenses = expenses.filter(e => e.target_type === 'all' || !e.target_type);

    if (allExpenses.length === 0) {
        document.getElementById('expenseListAll').innerHTML = '<p class="text-muted">全員対象の雑費はありません</p>';
    } else {
        let html = '<table class="table table-hover"><thead><tr><th>項目名</th><th>金額</th><th>建て替え</th><th></th></tr></thead><tbody>';
        for (const e of allExpenses) {
            html += `<tr>
                <td>${escapeHtml(e.name)}</td>
                <td>¥${Number(e.amount).toLocaleString()}</td>
                <td>${e.payer_name ? escapeHtml(e.payer_name) : '<span class="text-muted">-</span>'}</td>
                <td><button class="btn btn-outline-primary btn-sm" onclick="editExpense(${e.id})">編集</button></td>
            </tr>`;
        }
        html += '</tbody></table>';
        document.getElementById('expenseListAll').innerHTML = html;
    }

    // 建て替え総額集計を表示
    renderPayerSummary();
}

function renderPayerSummary() {
    const expenses = campData.expenses || [];

    // 建て替え者ごとに集計
    const payerTotals = {};
    for (const e of expenses) {
        if (e.payer_id && e.payer_name) {
            if (!payerTotals[e.payer_id]) {
                payerTotals[e.payer_id] = {
                    name: e.payer_name,
                    total: 0,
                    items: []
                };
            }
            payerTotals[e.payer_id].total += Number(e.amount);
            payerTotals[e.payer_id].items.push(e);
        }
    }

    const payerIds = Object.keys(payerTotals);

    if (payerIds.length === 0) {
        document.getElementById('payerSummary').innerHTML = '<p class="text-muted">建て替えが登録されている雑費はありません</p>';
        return;
    }

    // 合計金額でソート（降順）
    payerIds.sort((a, b) => payerTotals[b].total - payerTotals[a].total);

    let html = '<div class="card"><div class="card-body p-0"><table class="table table-hover mb-0">';
    html += '<thead class="table-light"><tr><th>建て替え者</th><th class="text-end">建て替え総額</th><th>内訳</th></tr></thead><tbody>';

    for (const id of payerIds) {
        const payer = payerTotals[id];
        const itemsDetail = payer.items.map(e => `${escapeHtml(e.name)}(¥${Number(e.amount).toLocaleString()})`).join('、');

        html += `<tr>
            <td><strong>${escapeHtml(payer.name)}</strong></td>
            <td class="text-end"><strong>¥${payer.total.toLocaleString()}</strong></td>
            <td><small class="text-muted">${itemsDetail}</small></td>
        </tr>`;
    }

    // 合計行
    const grandTotal = payerIds.reduce((sum, id) => sum + payerTotals[id].total, 0);
    html += `<tr class="table-secondary">
        <td><strong>合計</strong></td>
        <td class="text-end"><strong>¥${grandTotal.toLocaleString()}</strong></td>
        <td></td>
    </tr>`;

    html += '</tbody></table></div></div>';
    document.getElementById('payerSummary').innerHTML = html;
}

function showExpenseModalForSlot(day, slotType) {
    const slotNames = { morning: '午前', afternoon: '午後', banquet: '宴会' };
    document.getElementById('expenseModalTitle').textContent = `雑費追加（${day}日目${slotNames[slotType]}）`;
    document.getElementById('expenseId').value = '';
    document.getElementById('expenseForm').reset();
    document.getElementById('deleteExpenseBtn').style.display = 'none';

    // 特定タイミングを選択
    document.getElementById('targetSlot').checked = true;
    toggleTargetSlot();
    setupDaySelectors();

    // 日程とスロットを事前設定
    document.getElementById('expenseTargetDay').value = day;
    document.getElementById('expenseTargetSlot').value = slotType;

    expenseModal.show();
}

function setupDaySelectors() {
    const days = parseInt(campData.nights) + 1;
    const selectors = ['joinDay', 'leaveDay', 'expenseTargetDay'];

    for (const id of selectors) {
        const el = document.getElementById(id);
        if (el) {
            el.innerHTML = '';
            for (let i = 1; i <= days; i++) {
                el.innerHTML += `<option value="${i}">${i}</option>`;
            }
            if (id === 'leaveDay') el.value = days;
        }
    }

    // 日程変更時にタイミング選択肢を更新
    document.getElementById('joinDay').addEventListener('change', updateJoinTimingOptions);
    document.getElementById('leaveDay').addEventListener('change', updateLeaveTimingOptions);

    // 初期設定
    updateJoinTimingOptions();
    updateLeaveTimingOptions();

    // 建て替え者セレクタを更新
    setupPayerSelector();
}

// 参加開始タイミングの選択肢を更新
function updateJoinTimingOptions() {
    const joinDay = parseInt(document.getElementById('joinDay').value);
    const joinTimingSelect = document.getElementById('joinTiming');
    const currentValue = joinTimingSelect.value;
    const days = parseInt(campData.nights) + 1;

    // 選択肢を生成
    // 食事と活動それぞれの境界で区別
    let options = [];

    // 1日目は往路バス到着後なので、朝食・午前イベント・昼食はない
    if (joinDay === 1) {
        options.push({ value: 'outbound_bus', label: '往路バスから' });
        options.push({ value: 'afternoon', label: '午後イベントから' });   // 昼食を食べずに午後イベントから参加
        options.push({ value: 'dinner', label: '夕食から' });              // 夕食から参加
        options.push({ value: 'night', label: '夜から' });                 // 夕食を食べずに夜イベントから参加
        options.push({ value: 'lodging', label: '宿泊から' });             // 宿泊のみ参加
    } else {
        // 2日目以降は朝食からの選択肢も表示
        options.push({ value: 'breakfast', label: '朝食から' });           // 朝食から参加
        options.push({ value: 'morning', label: '午前イベントから' });     // 朝食を食べずに午前イベントから参加
        options.push({ value: 'lunch', label: '昼食から' });               // 昼食から参加
        options.push({ value: 'afternoon', label: '午後イベントから' });   // 昼食を食べずに午後イベントから参加
        options.push({ value: 'dinner', label: '夕食から' });              // 夕食から参加
        options.push({ value: 'night', label: '夜から' });                 // 夕食を食べずに夜イベントから参加
        options.push({ value: 'lodging', label: '宿泊から' });             // 宿泊のみ参加
    }

    // セレクトボックスを更新
    joinTimingSelect.innerHTML = options.map(opt =>
        `<option value="${opt.value}">${opt.label}</option>`
    ).join('');

    // 可能であれば以前の値を復元
    const validValues = options.map(opt => opt.value);
    if (validValues.includes(currentValue)) {
        joinTimingSelect.value = currentValue;
    } else if (joinDay === 1) {
        joinTimingSelect.value = 'outbound_bus'; // 1日目のデフォルトは往路バス
    } else {
        joinTimingSelect.value = 'breakfast'; // 途中参加のデフォルトは朝食
    }

    // バス設定を自動更新
    updateBusFromTiming();
}

// 参加終了タイミングの選択肢を更新
function updateLeaveTimingOptions() {
    const leaveDay = parseInt(document.getElementById('leaveDay').value);
    const leaveTimingSelect = document.getElementById('leaveTiming');
    const currentValue = leaveTimingSelect.value;
    const days = parseInt(campData.nights) + 1;

    // 選択肢を生成
    // 食事と活動それぞれの境界で区別
    let options = [];

    // 共通の選択肢（最終日以外）
    options.push({ value: 'before_breakfast', label: '朝食前まで' });  // 朝食を食べずに帰る
    options.push({ value: 'breakfast', label: '朝食まで' });           // 朝食を食べて午前イベントに参加せず帰る
    options.push({ value: 'morning', label: '午前イベントまで' });     // 午前イベントに参加して昼食を食べずに帰る
    options.push({ value: 'lunch', label: '昼食まで' });               // 昼食を食べて午後イベントに参加せず帰る

    // 最終日以外のみ午後イベント・夕食・夜を表示（最終日は昼食後バスで帰るため）
    // 「宿泊まで」は翌日の「朝食前まで」と同義なので選択肢から削除
    if (leaveDay < days) {
        options.push({ value: 'afternoon', label: '午後イベントまで' });   // 午後イベントに参加して夕食を食べずに帰る
        options.push({ value: 'dinner', label: '夕食まで' });          // 夕食を食べて夜イベントに参加せず帰る
        options.push({ value: 'night', label: '夜まで' });             // 夜イベントに参加して宿泊せずに帰る
    }

    // 最終日のみ「復路バスまで」を表示
    if (leaveDay === days) {
        options.push({ value: 'return_bus', label: '復路バスまで' });
    }

    // セレクトボックスを更新
    leaveTimingSelect.innerHTML = options.map(opt =>
        `<option value="${opt.value}">${opt.label}</option>`
    ).join('');

    // 可能であれば以前の値を復元
    const validValues = options.map(opt => opt.value);
    if (validValues.includes(currentValue)) {
        leaveTimingSelect.value = currentValue;
    } else if (leaveDay === days) {
        leaveTimingSelect.value = 'return_bus'; // 最終日のデフォルトは復路バス
    } else {
        leaveTimingSelect.value = 'night'; // 途中抜けのデフォルトは夜まで
    }

    // バス設定を自動更新
    updateBusFromTiming();
}

// タイミング変更時にバス使用を自動設定
function updateBusFromTiming() {
    const joinDay = parseInt(document.getElementById('joinDay').value);
    const joinTiming = document.getElementById('joinTiming').value;
    const leaveDay = parseInt(document.getElementById('leaveDay').value);
    const leaveTiming = document.getElementById('leaveTiming').value;
    const days = parseInt(campData.nights) + 1;

    // 往路バス: 1日目で「往路バスから」を選択した場合はON、それ以外はOFF
    const useOutbound = (joinDay === 1 && joinTiming === 'outbound_bus');
    document.getElementById('useOutboundBus').checked = useOutbound;

    // 復路バス: 最終日で「復路バスまで」を選択した場合はON、それ以外はOFF
    const useReturn = (leaveDay === days && leaveTiming === 'return_bus');
    document.getElementById('useReturnBus').checked = useReturn;
}

function setupPayerSelector() {
    const payerSelect = document.getElementById('expensePayerId');
    if (!payerSelect) return;

    const participants = campData.participants || [];

    payerSelect.innerHTML = '<option value="">なし（未指定）</option>';
    for (const p of participants) {
        payerSelect.innerHTML += `<option value="${p.id}">${escapeHtml(p.name)}</option>`;
    }
}

function showParticipantModal() {
    document.getElementById('participantModalTitle').textContent = '参加者追加';
    document.getElementById('participantId').value = '';
    document.getElementById('participantForm').reset();
    document.getElementById('deleteParticipantBtn').style.display = 'none';
    setupDaySelectors();

    // デフォルト値を設定（フル参加）
    const days = parseInt(campData.nights) + 1;
    document.getElementById('joinDay').value = 1;
    document.getElementById('leaveDay').value = days;

    // タイミング選択肢を更新してデフォルト値を設定
    updateJoinTimingOptions();
    updateLeaveTimingOptions();

    // フル参加のデフォルト: 1日目往路バスから、最終日復路バスまで
    document.getElementById('joinTiming').value = 'outbound_bus';
    document.getElementById('leaveTiming').value = 'return_bus';

    // バス設定も更新
    document.getElementById('useOutboundBus').checked = true;
    document.getElementById('useReturnBus').checked = true;

    // アレルギーをリセット
    document.getElementById('participantAllergy').value = '';

    // レンタカーオプションの表示/非表示
    const rentalCarOption = document.getElementById('rentalCarOption');
    if (campData.use_rental_car) {
        rentalCarOption.style.display = 'block';
    } else {
        rentalCarOption.style.display = 'none';
    }

    participantModal.show();
}

function editParticipant(id) {
    const p = campData.participants.find(x => x.id == id);
    if (!p) return;

    document.getElementById('participantModalTitle').textContent = '参加者編集';
    document.getElementById('participantId').value = p.id;
    document.getElementById('participantName').value = p.name;
    document.getElementById('participantGrade').value = p.grade !== null ? p.grade : '';
    document.getElementById('participantGender').value = p.gender || '';

    // 日程セレクタをセットアップ（イベントリスナーなしで）
    const days = parseInt(campData.nights) + 1;
    const joinDaySelect = document.getElementById('joinDay');
    const leaveDaySelect = document.getElementById('leaveDay');
    const expenseTargetDaySelect = document.getElementById('expenseTargetDay');

    for (const el of [joinDaySelect, leaveDaySelect, expenseTargetDaySelect]) {
        if (el) {
            el.innerHTML = '';
            for (let i = 1; i <= days; i++) {
                el.innerHTML += `<option value="${i}">${i}</option>`;
            }
        }
    }

    // 日程を設定
    joinDaySelect.value = p.join_day;
    leaveDaySelect.value = p.leave_day;

    // タイミング選択肢を更新（バス自動設定なしで）
    updateJoinTimingOptionsWithoutBusUpdate();
    updateLeaveTimingOptionsWithoutBusUpdate();

    // タイミングを設定
    document.getElementById('joinTiming').value = p.join_timing;
    document.getElementById('leaveTiming').value = p.leave_timing;

    // 既存のバス設定を復元（自動設定を上書き）
    document.getElementById('useOutboundBus').checked = p.use_outbound_bus == 1;
    document.getElementById('useReturnBus').checked = p.use_return_bus == 1;
    document.getElementById('useRentalCar').checked = p.use_rental_car == 1;
    document.getElementById('participantAllergy').value = p.allergy || '';
    document.getElementById('deleteParticipantBtn').style.display = 'block';

    // イベントリスナーを再設定
    joinDaySelect.onchange = function() {
        updateJoinTimingOptions();
    };
    leaveDaySelect.onchange = function() {
        updateLeaveTimingOptions();
    };

    // レンタカーオプションの表示/非表示
    const rentalCarOption = document.getElementById('rentalCarOption');
    if (campData.use_rental_car) {
        rentalCarOption.style.display = 'block';
    } else {
        rentalCarOption.style.display = 'none';
    }

    // 建て替え者セレクタを更新
    setupPayerSelector();

    participantModal.show();
}

// タイミング選択肢を更新（バス自動設定なし - 編集時用）
function updateJoinTimingOptionsWithoutBusUpdate() {
    const joinDay = parseInt(document.getElementById('joinDay').value);
    const joinTimingSelect = document.getElementById('joinTiming');

    // 食事と活動それぞれの境界で区別
    // 1日目は往路バス到着後なので、朝食・午前イベント・昼食はない
    let options = [];
    if (joinDay === 1) {
        options.push({ value: 'outbound_bus', label: '往路バスから' });
        options.push({ value: 'afternoon', label: '午後イベントから' });
        options.push({ value: 'dinner', label: '夕食から' });
        options.push({ value: 'night', label: '夜から' });
        options.push({ value: 'lodging', label: '宿泊から' });
    } else {
        options.push({ value: 'breakfast', label: '朝食から' });
        options.push({ value: 'morning', label: '午前イベントから' });
        options.push({ value: 'lunch', label: '昼食から' });
        options.push({ value: 'afternoon', label: '午後イベントから' });
        options.push({ value: 'dinner', label: '夕食から' });
        options.push({ value: 'night', label: '夜から' });
        options.push({ value: 'lodging', label: '宿泊から' });
    }

    joinTimingSelect.innerHTML = options.map(opt =>
        `<option value="${opt.value}">${opt.label}</option>`
    ).join('');
}

function updateLeaveTimingOptionsWithoutBusUpdate() {
    const leaveDay = parseInt(document.getElementById('leaveDay').value);
    const leaveTimingSelect = document.getElementById('leaveTiming');
    const days = parseInt(campData.nights) + 1;

    // 食事と活動それぞれの境界で区別
    // 「宿泊まで」は翌日の「朝食前まで」と同義なので選択肢から削除
    // 最終日は昼食後にバスで帰るため、午後イベント以降の選択肢は不要
    let options = [];
    options.push({ value: 'before_breakfast', label: '朝食前まで' });
    options.push({ value: 'breakfast', label: '朝食まで' });
    options.push({ value: 'morning', label: '午前イベントまで' });
    options.push({ value: 'lunch', label: '昼食まで' });
    if (leaveDay < days) {
        options.push({ value: 'afternoon', label: '午後イベントまで' });
        options.push({ value: 'dinner', label: '夕食まで' });
        options.push({ value: 'night', label: '夜まで' });
    }
    if (leaveDay === days) {
        options.push({ value: 'return_bus', label: '復路バスまで' });
    }

    leaveTimingSelect.innerHTML = options.map(opt =>
        `<option value="${opt.value}">${opt.label}</option>`
    ).join('');
}

async function saveParticipant(skipDuplicateCheck = false) {
    const id = document.getElementById('participantId').value;
    const gradeVal = document.getElementById('participantGrade').value;
    const genderVal = document.getElementById('participantGender').value;
    const name = document.getElementById('participantName').value;
    const grade = gradeVal !== '' ? parseInt(gradeVal) : null;

    const allergyVal = document.getElementById('participantAllergy').value.trim();
    const data = {
        name: name,
        grade: grade,
        gender: genderVal || null,
        allergy: allergyVal || null,
        join_day: parseInt(document.getElementById('joinDay').value),
        join_timing: document.getElementById('joinTiming').value,
        leave_day: parseInt(document.getElementById('leaveDay').value),
        leave_timing: document.getElementById('leaveTiming').value,
        use_outbound_bus: document.getElementById('useOutboundBus').checked ? 1 : 0,
        use_return_bus: document.getElementById('useReturnBus').checked ? 1 : 0,
        use_rental_car: document.getElementById('useRentalCar').checked ? 1 : 0,
    };

    // 重複チェック（スキップフラグが立っていない場合）
    if (!skipDuplicateCheck) {
        try {
            const checkRes = await fetch(`/index.php?route=api/camps/${campId}/participants/check-duplicate`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: name,
                    grade: grade,
                    exclude_id: id || null
                })
            });
            const checkResult = await checkRes.json();

            if (checkResult.success && checkResult.data.has_duplicates) {
                const gradeLabel = getGradeLabel(grade, genderVal);
                if (!confirm(`同姓同名・同学年の参加者「${name}（${gradeLabel}）」が既に登録されています。\n\nそれでも登録しますか？`)) {
                    return;
                }
            }
        } catch (err) {
            console.error('重複チェックエラー:', err);
        }
    }

    const url = id ? `/index.php?route=api/participants/${id}` : `/index.php?route=api/camps/${campId}/participants`;
    const method = id ? 'PUT' : 'POST';

    try {
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await res.json();

        if (result.success) {
            participantModal.hide();
            loadCampData();
            showToast('保存しました');
        } else {
            alert(result.error?.message || '保存に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

function getGradeLabel(grade, gender) {
    if (grade === null || grade === undefined) return '学年未設定';

    // 10月引退ルール: 3年生は10月以降OB扱い
    if (grade === 3 && isRetiredAsOB(grade)) {
        if (gender === 'male') return 'OB';
        if (gender === 'female') return 'OG';
        return 'OB/OG';
    }

    if (grade === 0) {
        if (gender === 'male') return 'OB';
        if (gender === 'female') return 'OG';
        return 'OB/OG';
    }
    const genderLabel = gender === 'male' ? '男' : (gender === 'female' ? '女' : '');
    return `${grade}${genderLabel}`;
}

async function deleteParticipant() {
    const id = document.getElementById('participantId').value;
    if (!id || !confirm('この参加者を削除しますか？')) return;

    try {
        const res = await fetch(`/index.php?route=api/participants/${id}`, { method: 'DELETE' });
        const result = await res.json();

        if (result.success) {
            participantModal.hide();
            loadCampData();
            showToast('削除しました');
        } else {
            alert(result.error?.message || '削除に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

function showExpenseModal() {
    document.getElementById('expenseModalTitle').textContent = '雑費追加';
    document.getElementById('expenseId').value = '';
    document.getElementById('expenseForm').reset();
    document.getElementById('deleteExpenseBtn').style.display = 'none';
    document.getElementById('targetAll').checked = true;
    toggleTargetSlot();
    setupDaySelectors();
    expenseModal.show();
}

function editExpense(id) {
    const e = campData.expenses.find(x => x.id == id);
    if (!e) return;

    document.getElementById('expenseModalTitle').textContent = '雑費編集';
    document.getElementById('expenseId').value = e.id;
    document.getElementById('expenseName').value = e.name;
    document.getElementById('expenseAmount').value = e.amount;
    setupDaySelectors();

    if (e.target_type === 'slot') {
        document.getElementById('targetSlot').checked = true;
        document.getElementById('expenseTargetDay').value = e.target_day;
        document.getElementById('expenseTargetSlot').value = e.target_slot;
    } else {
        document.getElementById('targetAll').checked = true;
    }
    toggleTargetSlot();

    // 建て替え者を設定
    document.getElementById('expensePayerId').value = e.payer_id || '';

    document.getElementById('deleteExpenseBtn').style.display = 'block';
    expenseModal.show();
}

function toggleTargetSlot() {
    const isSlot = document.getElementById('targetSlot').checked;
    document.getElementById('targetSlotInputs').style.display = isSlot ? 'flex' : 'none';
}

async function saveExpense() {
    const id = document.getElementById('expenseId').value;
    const targetType = document.querySelector('input[name="targetType"]:checked').value;
    const payerIdValue = document.getElementById('expensePayerId').value;

    const data = {
        name: document.getElementById('expenseName').value,
        amount: parseInt(document.getElementById('expenseAmount').value),
        target_type: targetType,
        target_day: targetType === 'slot' ? parseInt(document.getElementById('expenseTargetDay').value) : null,
        target_slot: targetType === 'slot' ? document.getElementById('expenseTargetSlot').value : null,
        payer_id: payerIdValue ? parseInt(payerIdValue) : null,
    };

    const url = id ? `/index.php?route=api/expenses/${id}` : `/index.php?route=api/camps/${campId}/expenses`;
    const method = id ? 'PUT' : 'POST';

    try {
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await res.json();

        if (result.success) {
            expenseModal.hide();
            loadCampData();
            showToast('保存しました');
        } else {
            alert(result.error?.message || '保存に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

async function deleteExpense() {
    const id = document.getElementById('expenseId').value;
    if (!id || !confirm('この雑費を削除しますか？')) return;

    try {
        const res = await fetch(`/index.php?route=api/expenses/${id}`, { method: 'DELETE' });
        const result = await res.json();

        if (result.success) {
            expenseModal.hide();
            loadCampData();
            showToast('削除しました');
        } else {
            alert(result.error?.message || '削除に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showEditBasicInfoModal() {
    const c = campData;

    // フォームに現在の値を設定
    document.getElementById('editCampName').value = c.name || '';
    document.getElementById('editCourtFeePerUnit').value = c.court_fee_per_unit || '';
    document.getElementById('editGymFeePerUnit').value = c.gym_fee_per_unit || '';
    document.getElementById('editBanquetFeePerPerson').value = c.banquet_fee_per_person || '';
    document.getElementById('editLodgingFee').value = c.lodging_fee_per_night || 0;
    document.getElementById('editHotSpringTax').value = c.hot_spring_tax || 0;
    document.getElementById('editInsuranceFee').value = c.insurance_fee || 0;
    document.getElementById('editFirstDayLunch').value = c.first_day_lunch_included ? '1' : '0';

    // 食事単価
    document.getElementById('editBreakfastAdd').value = c.breakfast_add_price || 0;
    document.getElementById('editBreakfastRemove').value = c.breakfast_remove_price || 0;
    document.getElementById('editLunchAdd').value = c.lunch_add_price || 0;
    document.getElementById('editLunchRemove').value = c.lunch_remove_price || 0;
    document.getElementById('editDinnerAdd').value = c.dinner_add_price || 0;
    document.getElementById('editDinnerRemove').value = c.dinner_remove_price || 0;

    // バス代
    document.getElementById('editBusFeeSeparate').checked = c.bus_fee_separate == 1;
    document.getElementById('editBusFeeRoundTrip').value = c.bus_fee_round_trip || 0;
    document.getElementById('editBusFeeOutbound').value = c.bus_fee_outbound || 0;
    document.getElementById('editBusFeeReturn').value = c.bus_fee_return || 0;
    document.getElementById('editHighwayFeeOutbound').value = c.highway_fee_outbound || 0;
    document.getElementById('editHighwayFeeReturn').value = c.highway_fee_return || 0;
    toggleEditBusFeeFields();

    // レンタカー
    document.getElementById('editUseRentalCar').checked = c.use_rental_car == 1;
    document.getElementById('editRentalCarFee').value = c.rental_car_fee || 0;
    document.getElementById('editRentalCarHighwayFee').value = c.rental_car_highway_fee || 0;
    document.getElementById('editRentalCarCapacity').value = c.rental_car_capacity || '';
    toggleEditRentalCarFields();

    basicInfoModal.show();
}

function toggleEditBusFeeFields() {
    const isSeparate = document.getElementById('editBusFeeSeparate').checked;
    document.getElementById('editBusFeeRoundTripField').style.display = isSeparate ? 'none' : 'flex';
    document.getElementById('editBusFeeSeparateFields').style.display = isSeparate ? 'flex' : 'none';
}

function toggleEditRentalCarFields() {
    const useRentalCar = document.getElementById('editUseRentalCar').checked;
    document.getElementById('editRentalCarFields').style.display = useRentalCar ? 'block' : 'none';
}

async function saveBasicInfo() {
    const name = document.getElementById('editCampName').value.trim();
    if (!name) {
        alert('合宿名を入力してください');
        return;
    }

    const data = {
        name: name,
        court_fee_per_unit: parseInt(document.getElementById('editCourtFeePerUnit').value) || null,
        gym_fee_per_unit: parseInt(document.getElementById('editGymFeePerUnit').value) || null,
        banquet_fee_per_person: parseInt(document.getElementById('editBanquetFeePerPerson').value) || null,
        lodging_fee_per_night: parseInt(document.getElementById('editLodgingFee').value) || 0,
        hot_spring_tax: parseInt(document.getElementById('editHotSpringTax').value) || 0,
        insurance_fee: parseInt(document.getElementById('editInsuranceFee').value) || 0,
        first_day_lunch_included: parseInt(document.getElementById('editFirstDayLunch').value),
        breakfast_add_price: parseInt(document.getElementById('editBreakfastAdd').value) || 0,
        breakfast_remove_price: parseInt(document.getElementById('editBreakfastRemove').value) || 0,
        lunch_add_price: parseInt(document.getElementById('editLunchAdd').value) || 0,
        lunch_remove_price: parseInt(document.getElementById('editLunchRemove').value) || 0,
        dinner_add_price: parseInt(document.getElementById('editDinnerAdd').value) || 0,
        dinner_remove_price: parseInt(document.getElementById('editDinnerRemove').value) || 0,
        bus_fee_separate: document.getElementById('editBusFeeSeparate').checked ? 1 : 0,
        bus_fee_round_trip: parseInt(document.getElementById('editBusFeeRoundTrip').value) || null,
        bus_fee_outbound: parseInt(document.getElementById('editBusFeeOutbound').value) || null,
        bus_fee_return: parseInt(document.getElementById('editBusFeeReturn').value) || null,
        highway_fee_outbound: parseInt(document.getElementById('editHighwayFeeOutbound').value) || null,
        highway_fee_return: parseInt(document.getElementById('editHighwayFeeReturn').value) || null,
        use_rental_car: document.getElementById('editUseRentalCar').checked ? 1 : 0,
        rental_car_fee: parseInt(document.getElementById('editRentalCarFee').value) || null,
        rental_car_highway_fee: parseInt(document.getElementById('editRentalCarHighwayFee').value) || null,
        rental_car_capacity: parseInt(document.getElementById('editRentalCarCapacity').value) || null,
    };

    try {
        const res = await fetch(`/index.php?route=api/camps/${campId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await res.json();

        if (result.success) {
            basicInfoModal.hide();
            // ページタイトルも更新
            document.getElementById('campTitle').textContent = name;
            loadCampData();
            showToast('基本情報を保存しました');
        } else {
            alert(result.error?.message || '保存に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

// ==============================
// 集金管理
// ==============================
async function loadCollection() {
    const content = document.getElementById('collectionContent');
    try {
        const res  = await fetch(`/api/camps/${campId}/collection`);
        const data = await res.json();
        if (data.success) {
            renderCollection(data.data);
        } else {
            content.innerHTML = '<div class="alert alert-danger">読み込みに失敗しました</div>';
        }
    } catch (err) {
        console.error(err);
        content.innerHTML = '<div class="alert alert-danger">読み込みに失敗しました</div>';
    }
}

function renderCollection(data) {
    const content = document.getElementById('collectionContent');

    if (!data.collection) {
        // 集金未作成 → 作成フォームを表示
        const suggested = data.suggested_amount || '';
        const hint = suggested
            ? `<small class="text-muted">計算結果の平均負担額: ¥${Number(suggested).toLocaleString()}</small>`
            : '';
        content.innerHTML = `
            <h4 class="mb-4">集金管理</h4>
            <div class="card">
                <div class="card-body">
                    <p class="text-muted mb-3">この合宿の集金はまだ作成されていません。</p>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">デフォルト金額（円）</label>
                            <input type="number" class="form-control" id="newDefaultAmount" min="0"
                                   value="${suggested}" placeholder="例: 3000">
                            ${hint}
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">入金期限</label>
                            <input type="date" class="form-control" id="newDeadline">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary w-100" onclick="startCollection()">
                                集金を開始する
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        return;
    }

    const col            = data.collection;
    const submitted      = data.submitted   || [];
    const unsubmitted    = data.unsubmitted || [];
    const suggestedAmount = data.suggested_amount || null;
    const allItems    = [...submitted, ...unsubmitted].sort((a, b) =>
        (a.name_kana || '').localeCompare(b.name_kana || '', 'ja')
    );
    const today = new Date().toISOString().split('T')[0];

    // 全員の個別金額編集テーブル
    let allItemsHtml = '';
    if (allItems.length === 0) {
        allItemsHtml = '<p class="text-muted">対象会員がいません</p>';
    } else {
        allItemsHtml = `
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>氏名（カナ）</th>
                            <th>学年</th>
                            <th style="width:180px;">個別金額（円）</th>
                            <th>状態</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${allItems.map(item => {
                            const amount = item.custom_amount !== null
                                ? parseInt(item.custom_amount)
                                : parseInt(col.default_amount);
                            const statusBadge = parseInt(item.submitted) === 1
                                ? '<span class="badge bg-success">提出済</span>'
                                : '<span class="badge bg-secondary">未提出</span>';
                            return `<tr>
                                <td>${escapeHtml(item.name_kana || '')}</td>
                                <td>${item.grade !== null ? item.grade + '年' : '-'}</td>
                                <td>
                                    <input type="number" class="form-control form-control-sm"
                                           value="${item.custom_amount !== null ? item.custom_amount : ''}"
                                           placeholder="デフォルト(¥${Number(col.default_amount).toLocaleString()})"
                                           min="0"
                                           onchange="updateItemAmount(${item.id}, this.value)">
                                </td>
                                <td>${statusBadge}</td>
                            </tr>`;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    // 提出済みリスト
    let submittedHtml = '';
    if (submitted.length === 0) {
        submittedHtml = '<p class="text-muted">まだ提出がありません</p>';
    } else {
        submittedHtml = `
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>氏名（カナ）</th>
                            <th class="text-end">金額</th>
                            <th>提出日時</th>
                            <th class="text-center">通帳確認</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${submitted.map(item => {
                            const amount = item.custom_amount !== null
                                ? parseInt(item.custom_amount)
                                : parseInt(col.default_amount);
                            const checked = parseInt(item.admin_confirmed) === 1 ? 'checked' : '';
                            return `<tr>
                                <td>${escapeHtml(item.name_kana || '')}</td>
                                <td class="text-end">¥${Number(amount).toLocaleString()}</td>
                                <td><small>${item.submitted_at || ''}</small></td>
                                <td class="text-center">
                                    <input type="checkbox" ${checked}
                                           onchange="toggleConfirm(${item.id}, this)">
                                </td>
                            </tr>`;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    // 未提出リスト
    let unsubmittedHtml = '';
    if (unsubmitted.length === 0) {
        unsubmittedHtml = '<p class="text-muted">全員提出済みです</p>';
    } else {
        unsubmittedHtml = `
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>氏名（カナ）</th>
                            <th class="text-end">金額</th>
                            <th>期限状態</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${unsubmitted.map(item => {
                            const amount = item.custom_amount !== null
                                ? parseInt(item.custom_amount)
                                : parseInt(col.default_amount);
                            const overdue = col.deadline < today;
                            return `<tr class="${overdue ? 'table-danger' : ''}">
                                <td>${escapeHtml(item.name_kana || '')}</td>
                                <td class="text-end">¥${Number(amount).toLocaleString()}</td>
                                <td>${overdue
                                    ? '<span class="badge bg-danger">期限超過</span>'
                                    : '<span class="text-muted small">期限内</span>'}</td>
                            </tr>`;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    content.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">集金管理</h4>
            <span class="badge bg-secondary fs-6">${submitted.length} / ${submitted.length + unsubmitted.length} 提出</span>
        </div>

        <!-- 個別金額設定 -->
        <div class="card mb-4">
            <div class="card-header">個別金額設定（空欄 = デフォルト ¥${Number(col.default_amount).toLocaleString()}）</div>
            <div class="card-body p-2">
                ${allItemsHtml}
            </div>
        </div>

        <!-- 提出済みリスト -->
        <div class="card mb-4">
            <div class="card-header">提出済み（${submitted.length}名）</div>
            <div class="card-body p-2">
                ${submittedHtml}
            </div>
        </div>

        <!-- 未提出リスト -->
        <div class="card mb-4">
            <div class="card-header">未提出（${unsubmitted.length}名）</div>
            <div class="card-body p-2">
                ${unsubmittedHtml}
            </div>
        </div>

        <!-- 設定編集 -->
        <div class="card">
            <div class="card-header">集金設定の変更</div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">デフォルト金額（円）</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="editDefaultAmount"
                                   value="${col.default_amount}" min="0">
                            ${suggestedAmount ? `<button class="btn btn-outline-secondary" type="button"
                                title="計算結果の平均負担額 ¥${Number(suggestedAmount).toLocaleString()} を反映"
                                onclick="document.getElementById('editDefaultAmount').value = ${suggestedAmount}">
                                計算結果を反映
                            </button>` : ''}
                        </div>
                        ${suggestedAmount ? `<small class="text-muted">計算結果の平均負担額: ¥${Number(suggestedAmount).toLocaleString()}</small>` : ''}
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">入金期限</label>
                        <input type="date" class="form-control" id="editDeadline" value="${col.deadline}">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary me-2" onclick="updateCollection()">保存</button>
                        <button class="btn btn-outline-danger" onclick="deleteCollection()">集金を削除</button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

async function startCollection() {
    const amount   = document.getElementById('newDefaultAmount').value;
    const deadline = document.getElementById('newDeadline').value;

    if (!amount || parseInt(amount) < 0) {
        alert('金額を入力してください');
        return;
    }
    if (!deadline) {
        alert('入金期限を選択してください');
        return;
    }

    try {
        const res  = await fetch(`/api/camps/${campId}/collection`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ default_amount: parseInt(amount), deadline }),
        });
        const data = await res.json();
        if (data.success) {
            showToast('集金を作成しました');
            renderCollection(data.data);
        } else {
            alert(data.error?.message || '作成に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

async function updateItemAmount(itemId, value) {
    const customAmount = value === '' ? null : parseInt(value);
    try {
        const res  = await fetch(`/api/collection-items/${itemId}`, {
            method:  'PUT',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ custom_amount: customAmount }),
        });
        const data = await res.json();
        if (!data.success) {
            alert(data.error?.message || '更新に失敗しました');
        } else {
            showToast('金額を更新しました', 'success', 1500);
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

async function toggleConfirm(itemId, cb) {
    try {
        const res  = await fetch(`/api/collection-items/${itemId}/confirm`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    '{}',
        });
        const data = await res.json();
        if (data.success) {
            cb.checked = data.data.admin_confirmed === 1;
            showToast('確認状態を更新しました', 'success', 1500);
        } else {
            cb.checked = !cb.checked; // 元に戻す
            alert(data.error?.message || '更新に失敗しました');
        }
    } catch (err) {
        cb.checked = !cb.checked;
        alert('通信エラーが発生しました');
    }
}

async function updateCollection() {
    const amount   = document.getElementById('editDefaultAmount').value;
    const deadline = document.getElementById('editDeadline').value;

    if (!amount || !deadline) {
        alert('金額と期限を入力してください');
        return;
    }

    try {
        const res  = await fetch(`/api/camps/${campId}/collection`, {
            method:  'PUT',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ default_amount: parseInt(amount), deadline }),
        });
        const data = await res.json();
        if (data.success) {
            showToast('設定を保存しました');
            loadCollection();
        } else {
            alert(data.error?.message || '保存に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

async function deleteCollection() {
    if (!confirm('集金を削除しますか？\n提出済みデータも全て削除されます。')) return;

    try {
        const res  = await fetch(`/api/camps/${campId}/collection`, { method: 'DELETE' });
        const data = await res.json();
        if (data.success) {
            showToast('削除しました');
            loadCollection();
        } else {
            alert(data.error?.message || '削除に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}

function showToast(message, type = 'info', duration = 3000) {
    // 簡易的なトースト表示（既存のものがあればそれを使う）
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
// 申し込みURL管理
// ==============================
async function loadApplicationUrl() {
    const content = document.getElementById('applicationUrlContent');

    try {
        const response = await fetch(`/api/camps/${campId}/application-url`);
        const data = await response.json();

        if (data.success) {
            renderApplicationUrl(data.data);
        } else {
            content.innerHTML = '<div class="alert alert-danger">読み込みに失敗しました</div>';
        }
    } catch (error) {
        console.error('Load application URL error:', error);
        content.innerHTML = '<div class="alert alert-danger">読み込みに失敗しました</div>';
    }
}

function renderApplicationUrl(data) {
    const content = document.getElementById('applicationUrlContent');

    let html = '<h4 class="mb-3">申し込みURL管理</h4>';

    if (data.has_token) {
        const token = data.token;
        const isActive = token.is_active == 1;
        const hasDeadline = token.deadline !== null;
        const isExpired = hasDeadline && new Date(token.deadline) < new Date();

        html += `
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <strong>現在の申し込みURL</strong>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">URL:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="${data.url}" id="applicationUrlInput" readonly>
                            <button class="btn btn-outline-secondary" onclick="copyApplicationUrl()">
                                <i class="bi bi-clipboard"></i> コピー
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ステータス:</label>
                        <div>
                            ${isActive && !isExpired ?
                                '<span class="badge bg-success">有効</span>' :
                                '<span class="badge bg-secondary">無効</span>'}
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">締切日時:</label>
                        <input type="datetime-local" class="form-control" id="deadlineInput"
                               value="${token.deadline ? token.deadline.replace(' ', 'T').substring(0, 16) : ''}">
                        <small class="text-muted">未設定の場合は無期限</small>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="isActiveInput"
                               ${isActive ? 'checked' : ''}>
                        <label class="form-check-label" for="isActiveInput">
                            URLを有効にする（無効にすると申し込みを停止）
                        </label>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" onclick="updateApplicationUrl()">
                            <i class="bi bi-save"></i> 設定を保存
                        </button>
                        <button class="btn btn-outline-warning" onclick="generateNewApplicationUrl()">
                            <i class="bi bi-arrow-clockwise"></i> 新しいURLを発行
                        </button>
                    </div>
                </div>
            </div>

            <div class="alert alert-warning">
                <strong><i class="bi bi-exclamation-triangle"></i> 注意</strong><br>
                新しいURLを発行すると、古いURLは無効になります。
            </div>
        `;

        // 申し込み一覧も表示
        html += '<div id="applicationsList" class="mt-4"></div>';

    } else {
        html += `
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-link-45deg" style="font-size: 3rem; color: #6c757d;"></i>
                    <h5 class="mt-3">申し込みURLが未発行です</h5>
                    <p class="text-muted">URLを発行すると、会員が合宿に申し込めるようになります。</p>
                    <button class="btn btn-primary" onclick="generateNewApplicationUrl()">
                        <i class="bi bi-plus-circle"></i> 申し込みURLを発行
                    </button>
                </div>
            </div>
        `;
    }

    content.innerHTML = html;

    // 申し込み一覧も読み込む
    if (data.has_token) {
        loadApplicationsList();
    }
}

function copyApplicationUrl() {
    const input = document.getElementById('applicationUrlInput');
    input.select();
    document.execCommand('copy');
    showToast('URLをコピーしました');
}

async function updateApplicationUrl() {
    const deadline = document.getElementById('deadlineInput').value || null;
    const isActive = document.getElementById('isActiveInput').checked ? 1 : 0;

    try {
        const response = await fetch(`/api/camps/${campId}/application-url`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                deadline: deadline ? deadline.replace('T', ' ') + ':00' : null,
                is_active: isActive
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast('設定を更新しました');
            loadApplicationUrl();
        } else {
            alert('更新に失敗しました: ' + (data.error?.message || ''));
        }
    } catch (error) {
        console.error('Update error:', error);
        alert('更新に失敗しました');
    }
}

async function generateNewApplicationUrl() {
    if (!confirm('新しいURLを発行しますか？\n古いURLは無効になります。')) {
        return;
    }

    try {
        const response = await fetch(`/api/camps/${campId}/application-url`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({})
        });

        const data = await response.json();

        if (data.success) {
            showToast('申し込みURLを発行しました');
            loadApplicationUrl();
        } else {
            alert('発行に失敗しました: ' + (data.error?.message || ''));
        }
    } catch (error) {
        console.error('Generate error:', error);
        alert('発行に失敗しました');
    }
}

async function loadApplicationsList() {
    const container = document.getElementById('applicationsList');

    try {
        const response = await fetch(`/api/camps/${campId}/applications`);
        const data = await response.json();

        if (data.success) {
            renderApplicationsList(data.data.applications);
        }
    } catch (error) {
        console.error('Load applications error:', error);
    }
}

function renderApplicationsList(applications) {
    const container = document.getElementById('applicationsList');

    let html = '<h5 class="mb-3">申し込み状況</h5>';

    if (applications.length === 0) {
        html += '<div class="alert alert-info">まだ申し込みはありません</div>';
    } else {
        // 情報修正ありで未反映の件数を確認
        const pendingEdits = applications.filter(a => a.info_edited == 1 && a.member_updated == 0);
        if (pendingEdits.length > 0) {
            html += `<div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>${pendingEdits.length}名</strong>が申し込み時に情報を修正しています。内容を確認して会員名簿に反映してください。
            </div>`;
        }

        html += `
            <div class="card">
                <div class="card-body">
                    <p class="mb-3"><strong>申し込み済み: ${applications.length}名</strong></p>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>名前</th>
                                    <th>学年</th>
                                    <th>参加期間</th>
                                    <th>交通</th>
                                    <th>申し込み日時</th>
                                    <th>情報修正</th>
                                </tr>
                            </thead>
                            <tbody>
        `;

        applications.forEach(app => {
            const busInfo = [];
            if (app.use_outbound_bus) busInfo.push('往');
            if (app.use_return_bus) busInfo.push('復');

            let editCell = '';
            if (app.info_edited == 1) {
                if (app.member_updated == 1) {
                    editCell = '<span class="badge bg-success">名簿反映済</span>';
                } else {
                    editCell = `<button class="btn btn-warning btn-sm py-0" onclick="showApplicationEditModal(${app.id})">
                        <i class="bi bi-pencil"></i> 修正あり
                    </button>`;
                }
            }

            html += `
                <tr>
                    <td>${escapeHtml(app.name_kanji)}</td>
                    <td>${escapeHtml(String(app.member_grade))}年</td>
                    <td>${app.join_day}日目〜${app.leave_day}日目</td>
                    <td>${busInfo.length > 0 ? busInfo.join('/') : '車'}</td>
                    <td>${new Date(app.created_at).toLocaleString('ja-JP')}</td>
                    <td>${editCell}</td>
                </tr>
            `;
        });

        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    // 情報修正モーダルのデータをキャッシュ
    window._applicationsCache = applications;

    container.innerHTML = html;
}

function showApplicationEditModal(applicationId) {
    const app = (window._applicationsCache || []).find(a => a.id == applicationId);
    if (!app) return;

    const gradeLabel = v => v ? String(v) + '年' : '未設定';
    const genderLabel = v => v === 'male' ? '男性' : v === 'female' ? '女性' : '未設定';

    const rows = [
        { label: '名前',   orig: app.name_kanji,        edited: app.edited_name_kanji },
        { label: '学年',   orig: gradeLabel(app.member_grade), edited: app.edited_grade ? gradeLabel(app.edited_grade) : null },
        { label: '性別',   orig: genderLabel(app.gender),      edited: app.edited_gender ? genderLabel(app.edited_gender) : null },
        { label: '学部',   orig: app.member_faculty,    edited: app.edited_faculty },
        { label: '学科',   orig: app.member_department, edited: app.edited_department },
    ];

    let tableHtml = '<table class="table table-sm table-bordered"><thead><tr><th>項目</th><th>会員名簿（現在）</th><th>申し込み時の修正</th></tr></thead><tbody>';
    rows.forEach(r => {
        const hasChange = r.edited && r.edited !== r.orig;
        tableHtml += `<tr${hasChange ? ' class="table-warning"' : ''}>
            <td>${escapeHtml(r.label)}</td>
            <td>${escapeHtml(r.orig || '')}</td>
            <td>${r.edited ? `<strong>${escapeHtml(r.edited)}</strong>` : '<span class="text-muted">変更なし</span>'}</td>
        </tr>`;
    });
    tableHtml += '</tbody></table>';

    document.getElementById('appEditModalContent').innerHTML = tableHtml;
    document.getElementById('appEditApplyBtn').onclick = () => applyMemberEdit(applicationId);
    appEditModal.show();
}

async function applyMemberEdit(applicationId) {
    if (!confirm('申し込み時の修正内容を会員名簿に反映しますか？')) return;

    try {
        const res = await fetch(`/index.php?route=api/applications/${applicationId}/apply-member-edit`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
        });
        const result = await res.json();
        if (result.success) {
            appEditModal.hide();
            showToast('会員名簿に反映しました');
            loadApplicationsList();
        } else {
            alert(result.error?.message || '反映に失敗しました');
        }
    } catch (err) {
        alert('通信エラーが発生しました');
    }
}
</script>

<style>
.expense-cell:hover {
    background-color: #f8f9fa;
}
.expense-cell .add-expense-hint {
    opacity: 0.5;
    transition: opacity 0.2s;
}
.expense-cell:hover .add-expense-hint {
    opacity: 1;
}
.expense-item:hover {
    background-color: #e9ecef;
    border-radius: 4px;
}
#expenseScheduleTable td {
    min-width: 120px;
}
</style>

<!-- 参加者選択モーダル -->
<div class="modal fade" id="participantPickerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pickerModalTitle">参加者を選択</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <input type="text" class="form-control form-control-sm" id="pickerSearch" placeholder="名前で絞り込み..." oninput="filterPicker()">
                </div>
                <div class="row g-1 mb-2">
                    <div class="col-auto">
                        <select class="form-select form-select-sm" id="pickerGradeFilter" onchange="filterPicker()">
                            <option value="">全学年</option>
                            <option value="1">1年</option>
                            <option value="2">2年</option>
                            <option value="3">3年</option>
                            <option value="4">4年以上</option>
                            <option value="0">OB/OG</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select class="form-select form-select-sm" id="pickerGenderFilter" onchange="filterPicker()">
                            <option value="">全性別</option>
                            <option value="male">男</option>
                            <option value="female">女</option>
                        </select>
                    </div>
                    <div class="col-auto ms-auto">
                        <button class="btn btn-outline-secondary btn-sm" onclick="selectAllVisible()">表示中を全選択</button>
                        <button class="btn btn-outline-secondary btn-sm ms-1" onclick="clearAllPicker()">全解除</button>
                    </div>
                </div>
                <div id="pickerList" style="max-height:350px;overflow-y:auto;"></div>
            </div>
            <div class="modal-footer">
                <span class="text-muted small me-auto" id="pickerSelectedCount">0人選択中</span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" onclick="applyPickerSelection()">選択を反映</button>
            </div>
        </div>
    </div>
</div>

<script>
/* ---- しおり管理 ---- */
let _bookletData = null;
let _allParticipants = null;
let _pickerCallback  = null;
let _pickerSelected  = new Set();

async function loadBooklet() {
    if (_bookletData !== null) { renderBookletEditor(_bookletData); return; }
    try {
        const [bRes, pRes] = await Promise.all([
            fetch(`/api/camps/<?= (int)$campId ?>/booklet`),
            fetch(`/api/camps/<?= (int)$campId ?>/booklet/participants`),
        ]);
        const bData = await bRes.json();
        const pData = await pRes.json();
        if (bData.success) {
            _bookletData = bData.data;
            _allParticipants = pData.success ? pData.data.participants : [];
            renderBookletEditor(_bookletData);
        } else {
            document.getElementById('bookletArea').innerHTML = '<div class="alert alert-danger">読み込みに失敗しました</div>';
        }
    } catch(e) {
        document.getElementById('bookletArea').innerHTML = '<div class="alert alert-danger">通信エラー</div>';
    }
}

function renderBookletEditor(b) {
    const publicToken = b.public_token || '';
    const baseUrl = window.location.origin;
    const publicUrl = publicToken ? `${baseUrl}/booklet/${publicToken}` : '';

    document.getElementById('bookletArea').innerHTML = `
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">合宿しおり編集</h5>
    <div class="d-flex gap-2 flex-wrap">
        ${publicUrl ? `<a href="/member/camp/<?= (int)$campId ?>/booklet" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye"></i> 会員向けプレビュー</a>` : ''}
        <span id="bookletSaveStatus" class="small text-muted ms-2"></span>
        <button class="btn btn-outline-secondary btn-sm" onclick="saveBooklet(false)"><i class="bi bi-save"></i> 今すぐ保存</button>
    </div>
</div>

<!-- 公開設定 -->
<div class="card mb-3">
    <div class="card-header fw-bold">公開設定</div>
    <div class="card-body">
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" id="bIsPublic" ${b.is_public ? 'checked' : ''} onchange="scheduleBookletSave()">
            <label class="form-check-label" for="bIsPublic">会員ログイン後に表示する</label>
        </div>
        ${publicUrl ? `
        <div class="mb-2">
            <label class="form-label small">公開URL（ログイン不要でアクセス可能）</label>
            <div class="input-group input-group-sm">
                <input type="text" class="form-control" value="${publicUrl}" readonly id="bookletPublicUrl">
                <button class="btn btn-outline-secondary" onclick="copyBookletUrl()"><i class="bi bi-clipboard"></i></button>
                <a href="${publicUrl}" target="_blank" class="btn btn-outline-primary"><i class="bi bi-box-arrow-up-right"></i></a>
            </div>
        </div>` : ''}
        <button class="btn btn-outline-secondary btn-sm" onclick="regenBookletToken()">
            <i class="bi bi-arrow-clockwise"></i> ${publicToken ? '公開URLを再発行' : '公開URLを発行'}
        </button>
    </div>
</div>

<!-- 集合情報 -->
<div class="card mb-3">
    <div class="card-header fw-bold">集合情報</div>
    <div class="card-body">
        <div class="row g-2 mb-2">
            <div class="col-md-3">
                <label class="form-label small">集合時間</label>
                <input type="text" class="form-control form-control-sm" id="bMeetingTime" value="${h(b.meeting_time||'8:40')}" placeholder="8:40" oninput="scheduleBookletSave()">
            </div>
            <div class="col-md-5">
                <label class="form-label small">集合場所</label>
                <input type="text" class="form-control form-control-sm" id="bMeetingPlace" value="${h(b.meeting_place||'新宿センタービル（地上）')}" oninput="scheduleBookletSave()">
            </div>
            <div class="col-md-4">
                <label class="form-label small">帰着場所（任意）</label>
                <input type="text" class="form-control form-control-sm" id="bReturnPlace" value="${h(b.return_place||'')}" oninput="scheduleBookletSave()">
            </div>
        </div>
        <div>
            <label class="form-label small">備考（地図の説明など）</label>
            <textarea class="form-control form-control-sm" id="bMeetingNote" rows="2" oninput="scheduleBookletSave()">${h(b.meeting_note||'')}</textarea>
        </div>
    </div>
</div>

<!-- 持ち物 -->
<div class="card mb-3">
    <div class="card-header fw-bold d-flex justify-content-between">
        持ち物
        <button class="btn btn-outline-secondary btn-sm" onclick="addBringItem()">＋ 追加</button>
    </div>
    <div class="card-body p-2" id="bringItemList">
        ${renderBringItems(b.items_to_bring||[])}
    </div>
</div>

<!-- タイムスケジュール -->
<div class="card mb-3">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center">
        タイムスケジュール（日別）
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm" onclick="importScheduleFromSlots()" title="日程設定タブのタイムスロットから自動生成します">
                <i class="bi bi-arrow-repeat"></i> 日程設定から取り込む
            </button>
            <button class="btn btn-outline-secondary btn-sm" onclick="addScheduleDay()">＋ 日追加</button>
        </div>
    </div>
    <div class="card-body p-2" id="schedulesDayList">
        ${renderScheduleDays(b.schedules||[])}
    </div>
</div>

<!-- 部屋割り -->
<div class="card mb-3">
    <div class="card-header fw-bold d-flex justify-content-between">
        部屋割り
        <button class="btn btn-outline-secondary btn-sm" onclick="addRoomCategory()">＋ カテゴリ追加</button>
    </div>
    <div class="card-body p-2" id="roomAssignmentList">
        ${renderRoomAssignments(b.room_assignments||[])}
    </div>
</div>

<!-- 宿内平面図 -->
<div class="card mb-3">
    <div class="card-header fw-bold">宿内平面図</div>
    <div class="card-body">
        <div class="d-flex gap-2 align-items-center mb-2">
            <label class="btn btn-outline-secondary btn-sm mb-0" style="cursor:pointer;">
                <i class="bi bi-upload"></i> 画像をアップロード
                <input type="file" accept="image/*" style="display:none;" onchange="uploadFloorPlan(this)">
            </label>
            <span id="floorPlanUploadStatus" class="small text-muted"></span>
        </div>
        <div id="floorPlanPreview">
            ${b.floor_plan_image
                ? `<img src="${h(b.floor_plan_image)}" class="img-fluid rounded" style="max-height:300px;" alt="平面図">
                   <div class="mt-1">
                       <button class="btn btn-outline-danger btn-sm py-0" onclick="removeFloorPlan()"><i class="bi bi-trash"></i> 削除</button>
                   </div>`
                : '<p class="text-muted small mb-0">画像が設定されていません</p>'}
        </div>
        <input type="hidden" id="bFloorPlanImage" value="${h(b.floor_plan_image||'')}">
    </div>
</div>

<!-- 配膳当番 -->
<div class="card mb-3">
    <div class="card-header fw-bold d-flex justify-content-between">
        配膳当番
        <button class="btn btn-outline-secondary btn-sm" onclick="addMealDutyRow()">＋ 行追加</button>
    </div>
    <div class="card-body p-2" id="mealDutyList">
        ${renderMealDuty(b.meal_duty||[])}
    </div>
</div>

<div class="text-end mb-4">
    <span class="text-muted small me-2">編集内容は自動的に保存されます</span>
    <button class="btn btn-outline-secondary" onclick="saveBooklet(false)"><i class="bi bi-save"></i> 今すぐ保存</button>
</div>
`;
}

/* ---- ヘルパー ---- */
function h(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ---- 持ち物 ---- */
function renderBringItems(items) {
    if (!items.length) return '<p class="text-muted small p-2 mb-0">まだ登録されていません</p>';
    return items.map((it, i) => `
    <div class="d-flex gap-2 align-items-center mb-1 bring-item" data-idx="${i}">
        <input type="checkbox" class="form-check-input" ${it.highlight ? 'checked' : ''} onchange="updateBringItem(${i},'highlight',this.checked)" title="強調">
        <input type="text" class="form-control form-control-sm" value="${h(it.text||'')}" oninput="updateBringItem(${i},'text',this.value)" placeholder="持ち物">
        <input type="text" class="form-control form-control-sm" value="${h(it.note||'')}" oninput="updateBringItem(${i},'note',this.value)" placeholder="補足（任意）" style="max-width:180px;">
        <button class="btn btn-outline-danger btn-sm py-0 px-1" onclick="removeBringItem(${i})">×</button>
    </div>`).join('');
}
function addBringItem() {
    _bookletData.items_to_bring = _bookletData.items_to_bring || [];
    _bookletData.items_to_bring.push({text:'', note:'', highlight: false});
    document.getElementById('bringItemList').innerHTML = renderBringItems(_bookletData.items_to_bring);
    scheduleBookletSave();
}
function updateBringItem(i, key, val) {
    _bookletData.items_to_bring[i][key] = val;
    scheduleBookletSave();
}
function removeBringItem(i) {
    _bookletData.items_to_bring.splice(i, 1);
    document.getElementById('bringItemList').innerHTML = renderBringItems(_bookletData.items_to_bring);
    scheduleBookletSave();
}

/* ---- タイムスケジュール ---- */
function renderScheduleDays(days) {
    if (!days.length) return '<p class="text-muted small p-2 mb-0">まだ登録されていません</p>';
    return days.map((day, di) => `
    <div class="border rounded mb-2">
        <div class="d-flex gap-2 align-items-center p-2" style="cursor:pointer;" onclick="toggleScheduleDay(${di})">
            <span class="text-muted" id="scheduleChevron_${di}" style="font-size:.8rem;">▼</span>
            <input type="text" class="form-control form-control-sm" value="${h(day.label||`${di+1}日目の予定`)}" oninput="updateScheduleDay(${di},'label',this.value)" onclick="event.stopPropagation()" placeholder="${di+1}日目の予定" style="max-width:200px;">
            <span class="text-muted small ms-1">${(day.rows||[]).length}行</span>
            <button class="btn btn-outline-secondary btn-sm py-0 ms-auto" onclick="event.stopPropagation();addScheduleRow(${di})">＋ 行追加</button>
            <button class="btn btn-outline-danger btn-sm py-0" onclick="event.stopPropagation();removeScheduleDay(${di})">この日を削除</button>
        </div>
        <div id="scheduleCollapse_${di}">
            <div class="px-2 pb-2">
                <table class="table table-sm mb-0">
                    <thead><tr><th style="width:80px">時間</th><th>予定</th><th>備考</th><th style="width:40px"></th></tr></thead>
                    <tbody id="scheduleRows_${di}">
                        ${renderScheduleRows(day.rows||[], di)}
                    </tbody>
                </table>
            </div>
        </div>
    </div>`).join('');
}

function toggleScheduleDay(di) {
    const el = document.getElementById(`scheduleCollapse_${di}`);
    const ch = document.getElementById(`scheduleChevron_${di}`);
    if (!el) return;
    const collapsed = el.style.display === 'none';
    el.style.display = collapsed ? '' : 'none';
    ch.textContent = collapsed ? '▼' : '▶';
}
function renderScheduleRows(rows, di) {
    return rows.map((row, ri) => `
    <tr>
        <td><input type="text" class="form-control form-control-sm" value="${h(row.time||'')}" oninput="updateScheduleRow(${di},${ri},'time',this.value)" placeholder="8:00"></td>
        <td><input type="text" class="form-control form-control-sm" value="${h(row.activity||'')}" oninput="updateScheduleRow(${di},${ri},'activity',this.value)"></td>
        <td><input type="text" class="form-control form-control-sm" value="${h(row.note||'')}" oninput="updateScheduleRow(${di},${ri},'note',this.value)" placeholder="配膳当番など（赤字）"></td>
        <td><button class="btn btn-outline-danger btn-sm py-0 px-1" onclick="removeScheduleRow(${di},${ri})">×</button></td>
    </tr>`).join('');
}
function addScheduleDay() {
    _bookletData.schedules = _bookletData.schedules || [];
    _bookletData.schedules.push({label:`${_bookletData.schedules.length+1}日目の予定`, rows:[]});
    document.getElementById('schedulesDayList').innerHTML = renderScheduleDays(_bookletData.schedules);
    scheduleBookletSave();
}
function removeScheduleDay(di) {
    _bookletData.schedules.splice(di, 1);
    document.getElementById('schedulesDayList').innerHTML = renderScheduleDays(_bookletData.schedules);
    scheduleBookletSave();
}
function updateScheduleDay(di, key, val) {
    _bookletData.schedules[di][key] = val;
    scheduleBookletSave();
}
function addScheduleRow(di) {
    _bookletData.schedules[di].rows = _bookletData.schedules[di].rows || [];
    _bookletData.schedules[di].rows.push({time:'', activity:'', note:''});
    document.getElementById(`scheduleRows_${di}`).innerHTML = renderScheduleRows(_bookletData.schedules[di].rows, di);
    // 折りたたみ中なら開く
    const col = document.getElementById(`scheduleCollapse_${di}`);
    const ch  = document.getElementById(`scheduleChevron_${di}`);
    if (col && col.style.display === 'none') { col.style.display = ''; if (ch) ch.textContent = '▼'; }
    _updateScheduleRowCount(di);
    scheduleBookletSave();
}
function removeScheduleRow(di, ri) {
    _bookletData.schedules[di].rows.splice(ri, 1);
    document.getElementById(`scheduleRows_${di}`).innerHTML = renderScheduleRows(_bookletData.schedules[di].rows, di);
    _updateScheduleRowCount(di);
    scheduleBookletSave();
}
function _updateScheduleRowCount(di) {
    const el = document.querySelector(`#scheduleCollapse_${di}`)?.closest('.border.rounded')?.querySelector('.text-muted.small.ms-1');
    if (el) el.textContent = `${(_bookletData.schedules[di]?.rows||[]).length}行`;
}
function updateScheduleRow(di, ri, key, val) {
    _bookletData.schedules[di].rows[ri][key] = val;
    scheduleBookletSave();
}

/* ---- 団体戦チーム ---- */
function renderTeamBattleTeams(teams) {
    if (!teams.length) return '<p class="text-muted small p-2 mb-0">まだ登録されていません</p>';
    return `<div class="row g-2">${teams.map((team, ti) => `
    <div class="col-md-3">
        <div class="border rounded p-2 h-100">
            <div class="d-flex gap-1 mb-1">
                <input type="text" class="form-control form-control-sm" value="${h(team.team_name||'')}" oninput="updateTeamBattle(${ti},'team_name',this.value)" placeholder="チーム1">
                <button class="btn btn-outline-danger btn-sm py-0 px-1" onclick="removeTeamBattle(${ti})">×</button>
            </div>
            <div id="teamMembers_${ti}">${renderTeamMembers(team.members||[], ti)}</div>
            <div class="mt-1">
                <button class="btn btn-outline-primary btn-sm w-100 py-0" onclick="openPickerForTeamMember(${ti})"><i class="bi bi-people"></i> 名簿から選択</button>
            </div>
        </div>
    </div>`).join('')}</div>`;
}
function renderTeamMembers(members, ti) {
    if (!members.length) return '<p class="text-muted small mb-1">（未選択）</p>';
    return members.map((m, mi) => `
    <div class="d-flex gap-1 align-items-center mb-1">
        <input type="checkbox" class="form-check-input flex-shrink-0" ${m.is_leader ? 'checked' : ''} onchange="updateTeamMember(${ti},${mi},'is_leader',this.checked)" title="リーダー">
        <span class="small flex-fill">${h(m.name||'')}</span>
        <button class="btn btn-outline-danger btn-sm py-0 px-1" onclick="removeTeamMember(${ti},${mi})">×</button>
    </div>`).join('');
}
function addTeamBattleTeam() {
    _bookletData.team_battle_teams = _bookletData.team_battle_teams || [];
    _bookletData.team_battle_teams.push({team_name:`チーム${_bookletData.team_battle_teams.length+1}`, members:[]});
    document.getElementById('teamBattleTeamList').innerHTML = renderTeamBattleTeams(_bookletData.team_battle_teams);
    scheduleBookletSave();
}
function removeTeamBattle(ti) {
    _bookletData.team_battle_teams.splice(ti, 1);
    document.getElementById('teamBattleTeamList').innerHTML = renderTeamBattleTeams(_bookletData.team_battle_teams);
    scheduleBookletSave();
}
function updateTeamBattle(ti, key, val) {
    _bookletData.team_battle_teams[ti][key] = val;
    scheduleBookletSave();
}
function removeTeamMember(ti, mi) {
    _bookletData.team_battle_teams[ti].members.splice(mi, 1);
    document.getElementById(`teamMembers_${ti}`).innerHTML = renderTeamMembers(_bookletData.team_battle_teams[ti].members, ti);
    scheduleBookletSave();
}
function updateTeamMember(ti, mi, key, val) {
    _bookletData.team_battle_teams[ti].members[mi][key] = val;
    scheduleBookletSave();
}

/* ---- 紅白戦メンバー ---- */
function renderKohakuMembers(members, color) {
    if (!members.length) return '<p class="text-muted small mb-1">（未選択）</p>';

    // _allParticipants から性別を引く
    const genderMap = {};
    (_allParticipants || []).forEach(p => { genderMap[p.name] = p.gender; });

    const males   = members.map((m, mi) => ({...m, mi})).filter(m => genderMap[m.name] !== 'female');
    const females = members.map((m, mi) => ({...m, mi})).filter(m => genderMap[m.name] === 'female');

    const renderRow = (m) => `
        <div class="d-flex gap-1 align-items-center mb-1">
            <span class="small flex-fill">${h(m.name||'')}</span>
            <button class="btn btn-outline-danger btn-sm py-0 px-1" onclick="removeKohakuMember('${color}',${m.mi})">×</button>
        </div>`;

    return `<div class="row g-2">
        <div class="col-6">
            <div class="text-muted small fw-bold mb-1">男子</div>
            ${males.length ? males.map(renderRow).join('') : '<p class="text-muted small">—</p>'}
        </div>
        <div class="col-6">
            <div class="text-muted small fw-bold mb-1">女子</div>
            ${females.length ? females.map(renderRow).join('') : '<p class="text-muted small">—</p>'}
        </div>
    </div>`;
}
function removeKohakuMember(color, mi) {
    _bookletData.kohaku_teams[color].splice(mi, 1);
    document.getElementById(`kohaku${color.charAt(0).toUpperCase()+color.slice(1)}List`).innerHTML = renderKohakuMembers(_bookletData.kohaku_teams[color], color);
    scheduleBookletSave();
}

/* ---- 紅白戦対戦表 ---- */
const MATCH_TYPES = [{v:'男子D',l:'男子ダブルス'},{v:'女子D',l:'女子ダブルス'},{v:'混合D',l:'ミックスダブルス'}];

function renderKohakuMatches(matches) {
    if (!matches.length) return '<p class="text-muted small p-2 mb-0">まだ登録されていません</p>';
    return matches.map((round, ri) => `
    <div class="border rounded mb-2 p-2">
        <div class="d-flex gap-2 align-items-center mb-2">
            <input type="text" class="form-control form-control-sm" value="${h(round.round||`${ri+1}試合目`)}" oninput="updateKohakuRound(${ri},'round',this.value)" style="max-width:120px;">
            <button class="btn btn-outline-secondary btn-sm py-0" onclick="addKohakuCourt(${ri})">＋ 試合追加</button>
            <button class="btn btn-outline-danger btn-sm py-0 ms-auto" onclick="removeKohakuRound(${ri})">削除</button>
        </div>
        <div id="kohakuCourts_${ri}">
            ${renderKohakuCourts(round.courts||[], ri)}
        </div>
    </div>`).join('');
}

function renderKohakuCourts(courts, ri) {
    if (!courts.length) return '<p class="text-muted small mb-0">試合がありません</p>';
    return courts.map((c, ci) => {
        const typeOpts = MATCH_TYPES.map(t => `<option value="${t.v}" ${c.type===t.v?'selected':''}>${t.l}</option>`).join('');
        const fmt = (n1, n2) => {
            if (!n1 && !n2) return '<span class="text-muted">未選択</span>';
            return `${h(n1||'？')} ／ ${h(n2||'？')}`;
        };
        return `
        <div class="border rounded p-2 mb-1 small">
            <div class="d-flex gap-2 align-items-center mb-2">
                <span class="text-muted fw-bold">${ci+1}番コート</span>
                <select class="form-select form-select-sm" style="max-width:160px;" onchange="updateKohakuCourt(${ri},${ci},'type',this.value)">
                    ${typeOpts}
                </select>
                <button class="btn btn-outline-danger btn-sm py-0 ms-auto px-2" onclick="removeKohakuCourt(${ri},${ci})">×</button>
            </div>
            <div class="row g-1">
                <div class="col-6">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold"><span class="badge me-1" style="background:#dc2626;">赤</span>赤組ペア</span>
                        <button class="btn btn-outline-secondary btn-sm py-0 px-1" onclick="pickKohakuPair(${ri},${ci},'red')" style="font-size:.75rem;"><i class="bi bi-people"></i> 選択</button>
                    </div>
                    <div class="ps-1">${fmt(c.red1, c.red2)}</div>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold"><span class="badge me-1 bg-secondary">白</span>白組ペア</span>
                        <button class="btn btn-outline-secondary btn-sm py-0 px-1" onclick="pickKohakuPair(${ri},${ci},'white')" style="font-size:.75rem;"><i class="bi bi-people"></i> 選択</button>
                    </div>
                    <div class="ps-1">${fmt(c.white1, c.white2)}</div>
                </div>
            </div>
        </div>`;
    }).join('');
}

function addKohakuRound() {
    _bookletData.kohaku_matches = _bookletData.kohaku_matches || [];
    const ri = _bookletData.kohaku_matches.length;
    _bookletData.kohaku_matches.push({round:`${ri+1}試合目`, courts:[]});
    document.getElementById('kohakuMatchList').innerHTML = renderKohakuMatches(_bookletData.kohaku_matches);
    scheduleBookletSave();
}
function removeKohakuRound(ri) {
    _bookletData.kohaku_matches.splice(ri, 1);
    document.getElementById('kohakuMatchList').innerHTML = renderKohakuMatches(_bookletData.kohaku_matches);
    scheduleBookletSave();
}
function updateKohakuRound(ri, key, val) {
    _bookletData.kohaku_matches[ri][key] = val;
    scheduleBookletSave();
}
function addKohakuCourt(ri) {
    _bookletData.kohaku_matches[ri].courts.push({type:'男子D', red1:'', red2:'', white1:'', white2:''});
    document.getElementById(`kohakuCourts_${ri}`).innerHTML = renderKohakuCourts(_bookletData.kohaku_matches[ri].courts, ri);
    scheduleBookletSave();
}
function removeKohakuCourt(ri, ci) {
    _bookletData.kohaku_matches[ri].courts.splice(ci, 1);
    document.getElementById(`kohakuCourts_${ri}`).innerHTML = renderKohakuCourts(_bookletData.kohaku_matches[ri].courts, ri);
    scheduleBookletSave();
}
function updateKohakuCourt(ri, ci, key, val) {
    _bookletData.kohaku_matches[ri].courts[ci][key] = val;
    document.getElementById(`kohakuCourts_${ri}`).innerHTML = renderKohakuCourts(_bookletData.kohaku_matches[ri].courts, ri);
    scheduleBookletSave();
}

/* 紅白戦：ペア2名同時選択ピッカー */
function pickKohakuPair(ri, ci, color) {
    const round  = _bookletData.kohaku_matches[ri];
    const court  = round.courts[ci];
    const isRed  = color === 'red';
    const matchType = court.type || '男子D';

    // 種別による性別制限
    let genderFilter = null;
    if (matchType === '男子D') genderFilter = 'male';
    else if (matchType === '女子D') genderFilter = 'female';

    // 選択可能：同じ組のメンバー × 性別フィルター
    const teamNames = isRed
        ? (_bookletData.kohaku_teams?.red   || []).map(m => m.name).filter(n => n)
        : (_bookletData.kohaku_teams?.white || []).map(m => m.name).filter(n => n);

    // 同じ round 内で既に使われている名前（自分のペア枠は除く）
    const usedInRound = new Set();
    round.courts.forEach((c, idx) => {
        ['red1','red2','white1','white2'].forEach(slot => {
            const name = c[slot] || '';
            if (!name) return;
            // 自コートの自分の色枠は除外（上書き可）
            if (idx === ci && slot.startsWith(color)) return;
            usedInRound.add(name);
        });
    });

    const typeLabel = {男子D:'男子ダブルス', 女子D:'女子ダブルス', 混合D:'ミックスダブルス'}[matchType] || matchType;
    const title = `${isRed?'赤組':'白組'}ペアを選択（${typeLabel}・2名まで）`;

    // 現在選択中のペア
    const preSelected = [court[`${color}1`], court[`${color}2`]].filter(n => n);

    const origParticipants = _allParticipants;
    _allParticipants = (_allParticipants || []).filter(p =>
        teamNames.includes(p.name) &&
        (genderFilter === null || p.gender === genderFilter)
    );

    openPicker(title, preSelected, (names) => {
        _allParticipants = origParticipants;
        _bookletData.kohaku_matches[ri].courts[ci][`${color}1`] = names[0] || '';
        _bookletData.kohaku_matches[ri].courts[ci][`${color}2`] = names[1] || '';
        document.getElementById(`kohakuCourts_${ri}`).innerHTML = renderKohakuCourts(round.courts, ri);
        scheduleBookletSave();
    }, [...usedInRound], false, 2);

    _allParticipants = origParticipants;
}

let _pickerSingleMode = false;
function togglePicker(name, checked) {
    if (checked) {
        if (_pickerSingleMode) {
            // シングルモード：他を全解除してから追加
            _pickerSelected.clear();
            document.querySelectorAll('#pickerList input[type=checkbox]').forEach(cb => { cb.checked = false; });
            _pickerSelected.add(name);
            const cb = document.querySelector(`#pickerList input[value="${CSS.escape(name)}"]`);
            if (cb) cb.checked = true;
        } else if (_pickerMaxSelect > 0 && _pickerSelected.size >= _pickerMaxSelect) {
            // 上限超え：チェックを戻す
            const cb = document.querySelector(`#pickerList input[value="${CSS.escape(name)}"]`);
            if (cb) cb.checked = false;
            return;
        } else {
            _pickerSelected.add(name);
        }
    } else {
        _pickerSelected.delete(name);
    }
    document.getElementById('pickerSelectedCount').textContent = _pickerMaxSelect > 0 ? `${_pickerSelected.size}／${_pickerMaxSelect}名選択中` : `${_pickerSelected.size}人選択中`;
}

/* ---- 夜レク班 ---- */
function renderNightRecGroups(groups) {
    if (!groups.length) return '<p class="text-muted small p-2 mb-0">まだ登録されていません</p>';
    const groupNames = groups.map(g => g.group_name || '');
    return `<div class="row g-2">${groups.map((g, gi) => `
    <div class="col-md-3">
        <div class="border rounded p-2 h-100">
            <div class="d-flex gap-1 mb-1">
                <input type="text" class="form-control form-control-sm" value="${h(g.group_name||'')}" oninput="updateNightRecGroup(${gi},'group_name',this.value)" placeholder="1班">
                <button class="btn btn-outline-danger btn-sm py-0 px-1" onclick="removeNightRecGroup(${gi})">×</button>
            </div>
            <div id="nrecMembers_${gi}">${renderNightRecMembers(g.members||[], gi, groups.length)}</div>
            <div class="mt-1">
                <button class="btn btn-outline-primary btn-sm w-100 py-0" onclick="openPickerForNightRec(${gi})"><i class="bi bi-people"></i> 名簿から選択</button>
            </div>
        </div>
    </div>`).join('')}</div>`;
}
function renderNightRecMembers(members, gi, groupCount) {
    if (!members.length) return '<p class="text-muted small mb-1">（未選択）</p>';
    const gc = groupCount ?? (_bookletData.night_rec_groups||[]).length;
    // 移動先選択肢：自分以外の班
    const moveOpts = Array.from({length: gc}, (_, i) => i)
        .filter(i => i !== gi)
        .map(i => {
            const gname = (_bookletData.night_rec_groups||[])[i]?.group_name || `${i+1}班`;
            return `<option value="${i}">${h(gname)}</option>`;
        }).join('');
    return members.map((m, mi) => `
    <div class="d-flex gap-1 align-items-center mb-1">
        <span class="small flex-fill">${h(m.name||'')}</span>
        ${gc > 1 ? `<select class="form-select form-select-sm py-0" style="width:4.5rem;font-size:.7rem;" title="移動先" onchange="moveNightRecMember(${gi},${mi},parseInt(this.value));this.value=''">
            <option value="">移動</option>${moveOpts}
        </select>` : ''}
        <button class="btn btn-outline-danger btn-sm py-0 px-1" onclick="removeNightRecMember(${gi},${mi})">×</button>
    </div>`).join('');
}

/* 夜レク：別班へ移動 */
function moveNightRecMember(fromGi, mi, toGi) {
    const member = _bookletData.night_rec_groups[fromGi].members.splice(mi, 1)[0];
    _bookletData.night_rec_groups[toGi].members.push(member);
    document.getElementById('nightRecGroupList').innerHTML = renderNightRecGroups(_bookletData.night_rec_groups);
    scheduleBookletSave();
}

/* 夜レク：自動振り分けモーダルを開く */
function openNightRecAutoModal() {
    const modal = new bootstrap.Modal(document.getElementById('nightRecAutoModal'));
    const countEl = document.getElementById('nightRecGroupCount');
    const preview = document.getElementById('nightRecAutoPreview');
    countEl.oninput = () => {
        const n = parseInt(countEl.value) || 0;
        const total = (_allParticipants||[]).length;
        preview.textContent = n > 0 && total > 0 ? `参加者 ${total} 名を ${n} 班に振り分けます（1班あたり約 ${Math.ceil(total/n)} 名）` : '';
    };
    countEl.oninput();
    modal.show();
}

/* 夜レク：自動振り分け実行 */
function execNightRecAuto() {
    const n = parseInt(document.getElementById('nightRecGroupCount').value) || 0;
    if (n < 2) { alert('班数は2以上を指定してください'); return; }

    const participants = _allParticipants || [];
    if (!participants.length) { alert('参加者情報が読み込まれていません'); return; }

    // 学年・性別でソートしてからラウンドロビン配分
    // グループ順序: 学年昇順 → 同学年内は男→女の交互配置で均等化
    const sorted = [...participants].sort((a, b) => {
        if (a.grade !== b.grade) return a.grade - b.grade;
        // 同学年内では男女交互になるよう gender でソート
        const ga = a.gender === 'female' ? 1 : 0;
        const gb = b.gender === 'female' ? 1 : 0;
        return ga - gb;
    });

    // 班を初期化
    const groups = Array.from({length: n}, (_, i) => ({
        group_name: `${i+1}班`,
        members: [],
    }));

    // スネーク配分（1→n→1→...）で学年・性別を均等に散らす
    let dir = 1, gi = 0;
    sorted.forEach(p => {
        groups[gi].members.push({name: p.name});
        gi += dir;
        if (gi >= n) { gi = n - 1; dir = -1; }
        else if (gi < 0) { gi = 0; dir = 1; }
    });

    _bookletData.night_rec_groups = groups;
    document.getElementById('nightRecGroupList').innerHTML = renderNightRecGroups(groups);
    bootstrap.Modal.getInstance(document.getElementById('nightRecAutoModal')).hide();
    scheduleBookletSave();
}
function addNightRecGroup() {
    _bookletData.night_rec_groups = _bookletData.night_rec_groups || [];
    _bookletData.night_rec_groups.push({group_name:`${_bookletData.night_rec_groups.length+1}班`, members:[]});
    document.getElementById('nightRecGroupList').innerHTML = renderNightRecGroups(_bookletData.night_rec_groups);
    scheduleBookletSave();
}
function removeNightRecGroup(gi) {
    _bookletData.night_rec_groups.splice(gi, 1);
    document.getElementById('nightRecGroupList').innerHTML = renderNightRecGroups(_bookletData.night_rec_groups);
    scheduleBookletSave();
}
function updateNightRecGroup(gi, key, val) {
    _bookletData.night_rec_groups[gi][key] = val;
    scheduleBookletSave();
}
function removeNightRecMember(gi, mi) {
    _bookletData.night_rec_groups[gi].members.splice(mi, 1);
    document.getElementById(`nrecMembers_${gi}`).innerHTML = renderNightRecMembers(_bookletData.night_rec_groups[gi].members, gi, _bookletData.night_rec_groups.length);
    scheduleBookletSave();
}

/* ---- 部屋割り ---- */
function renderRoomAssignments(cats) {
    if (!cats.length) return '<p class="text-muted small p-2 mb-0">まだ登録されていません</p>';
    return cats.map((cat, ci) => `
    <div class="border rounded mb-2 p-2">
        <div class="d-flex gap-2 align-items-center mb-1">
            <input type="text" class="form-control form-control-sm" value="${h(cat.category||'')}" oninput="updateRoomCat(${ci},'category',this.value)" placeholder="1男" style="max-width:120px;">
            <button class="btn btn-outline-secondary btn-sm py-0" onclick="addRoom(${ci})">＋ 部屋追加</button>
            <button class="btn btn-outline-danger btn-sm py-0 ms-auto" onclick="removeRoomCat(${ci})">削除</button>
        </div>
        <div id="rooms_${ci}">
            ${(cat.rooms||[]).map((r, ri) => `
            <div class="d-flex gap-2 align-items-center mb-1">
                <input type="text" class="form-control form-control-sm" value="${h(r.room_no||'')}" oninput="updateRoom(${ci},${ri},'room_no',this.value)" placeholder="401号室" style="max-width:100px;">
                <input type="text" class="form-control form-control-sm" value="${h(r.capacity||'')}" oninput="updateRoom(${ci},${ri},'capacity',this.value)" placeholder="5人" style="max-width:80px;">
                <button class="btn btn-outline-danger btn-sm py-0 px-1" onclick="removeRoom(${ci},${ri})">×</button>
            </div>`).join('')}
        </div>
    </div>`).join('');
}
function addRoomCategory() {
    _bookletData.room_assignments = _bookletData.room_assignments || [];
    _bookletData.room_assignments.push({category:'', rooms:[]});
    document.getElementById('roomAssignmentList').innerHTML = renderRoomAssignments(_bookletData.room_assignments);
    scheduleBookletSave();
}
function removeRoomCat(ci) {
    _bookletData.room_assignments.splice(ci, 1);
    document.getElementById('roomAssignmentList').innerHTML = renderRoomAssignments(_bookletData.room_assignments);
    scheduleBookletSave();
}
function updateRoomCat(ci, key, val) {
    _bookletData.room_assignments[ci][key] = val;
    scheduleBookletSave();
}
function addRoom(ci) {
    _bookletData.room_assignments[ci].rooms.push({room_no:'', capacity:''});
    document.getElementById('roomAssignmentList').innerHTML = renderRoomAssignments(_bookletData.room_assignments);
    scheduleBookletSave();
}
function removeRoom(ci, ri) {
    _bookletData.room_assignments[ci].rooms.splice(ri, 1);
    document.getElementById('roomAssignmentList').innerHTML = renderRoomAssignments(_bookletData.room_assignments);
    scheduleBookletSave();
}
function updateRoom(ci, ri, key, val) {
    _bookletData.room_assignments[ci].rooms[ri][key] = val;
    scheduleBookletSave();
}

/* ---- 配膳当番 ---- */
function renderMealDuty(duties) {
    if (!duties.length) return '<p class="text-muted small p-2 mb-0">まだ登録されていません</p>';
    return duties.map((md, mi) => `
    <div class="border rounded mb-2 p-2">
        <div class="d-flex gap-2 align-items-center mb-1">
            <input type="text" class="form-control form-control-sm" value="${h(md.meal||'')}" oninput="updateMealDuty(${mi},'meal',this.value)" placeholder="朝 / 昼 / 夜" style="max-width:80px;">
            <button class="btn btn-outline-secondary btn-sm py-0" onclick="addMealDutyDay(${mi})">＋ 日追加</button>
            <button class="btn btn-outline-danger btn-sm py-0 ms-auto" onclick="removeMealDutyRow(${mi})">削除</button>
        </div>
        <div id="mealDays_${mi}" class="row g-1">
            ${(md.days||[]).map((d, di) => `
            <div class="col-md-3">
                <div class="border rounded p-1 small">
                    <input type="text" class="form-control form-control-sm mb-1" value="${h(d.day||'')}" oninput="updateMealDay(${mi},${di},'day',this.value)" placeholder="2日目">
                    <input type="text" class="form-control form-control-sm" value="${h(d.group||'')}" oninput="updateMealDay(${mi},${di},'group',this.value)" placeholder="1男405号室">
                    <button class="btn btn-outline-danger btn-sm py-0 w-100 mt-1" onclick="removeMealDay(${mi},${di})">×</button>
                </div>
            </div>`).join('')}
        </div>
    </div>`).join('');
}
function addMealDutyRow() {
    _bookletData.meal_duty = _bookletData.meal_duty || [];
    _bookletData.meal_duty.push({meal:'', days:[]});
    document.getElementById('mealDutyList').innerHTML = renderMealDuty(_bookletData.meal_duty);
    scheduleBookletSave();
}
function removeMealDutyRow(mi) {
    _bookletData.meal_duty.splice(mi, 1);
    document.getElementById('mealDutyList').innerHTML = renderMealDuty(_bookletData.meal_duty);
    scheduleBookletSave();
}
function updateMealDuty(mi, key, val) {
    _bookletData.meal_duty[mi][key] = val;
    scheduleBookletSave();
}
function addMealDutyDay(mi) {
    _bookletData.meal_duty[mi].days.push({day:'', group:''});
    document.getElementById('mealDutyList').innerHTML = renderMealDuty(_bookletData.meal_duty);
    scheduleBookletSave();
}
function removeMealDay(mi, di) {
    _bookletData.meal_duty[mi].days.splice(di, 1);
    document.getElementById('mealDutyList').innerHTML = renderMealDuty(_bookletData.meal_duty);
    scheduleBookletSave();
}
function updateMealDay(mi, di, key, val) {
    _bookletData.meal_duty[mi].days[di][key] = val;
    scheduleBookletSave();
}

/* ---- 保存 ---- */
/* ---- 自動保存 ---- */
let _bookletSaveTimer = null;
let _bookletSaving    = false;

function scheduleBookletSave() {
    clearTimeout(_bookletSaveTimer);
    setBookletSaveStatus('unsaved');
    _bookletSaveTimer = setTimeout(() => saveBooklet(true), 1500);
}

function setBookletSaveStatus(state) {
    const el = document.getElementById('bookletSaveStatus');
    if (!el) return;
    if (state === 'saving')  { el.textContent = '保存中…';  el.className = 'small text-muted ms-2'; }
    if (state === 'saved')   { el.textContent = '保存済み'; el.className = 'small text-success ms-2'; }
    if (state === 'unsaved') { el.textContent = '未保存';   el.className = 'small text-warning ms-2'; }
    if (state === 'error')   { el.textContent = '保存失敗'; el.className = 'small text-danger ms-2'; }
}

async function saveBooklet(auto = false) {
    if (_bookletSaving) return;
    _bookletSaving = true;
    setBookletSaveStatus('saving');

    const payload = {
        meeting_time:      document.getElementById('bMeetingTime')?.value     || '8:40',
        meeting_place:     document.getElementById('bMeetingPlace')?.value    || '新宿センタービル（地上）',
        meeting_note:      document.getElementById('bMeetingNote')?.value     || '',
        return_place:      document.getElementById('bReturnPlace')?.value     || '',
        is_public:         document.getElementById('bIsPublic')?.checked ? 1 : 0,
        floor_plan_image:  document.getElementById('bFloorPlanImage')?.value  || '',
        items_to_bring:    _bookletData.items_to_bring    || [],
        schedules:         _bookletData.schedules         || [],
        room_assignments:  _bookletData.room_assignments  || [],
        meal_duty:         _bookletData.meal_duty         || [],
    };

    try {
        const res  = await fetch(`/api/camps/<?= (int)$campId ?>/booklet`, {
            method: 'PUT',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.success) {
            _bookletData = data.data;
            setBookletSaveStatus('saved');
            if (!auto) showToast('しおりを保存しました', 'success');
        } else {
            setBookletSaveStatus('error');
            if (!auto) alert(data.error?.message || '保存に失敗しました');
        }
    } catch(e) {
        setBookletSaveStatus('error');
        if (!auto) alert('通信エラーが発生しました');
    } finally {
        _bookletSaving = false;
    }
}

/* ---- 公開URL ---- */
/* ---- 宿内平面図アップロード ---- */
async function uploadFloorPlan(input) {
    const file = input.files[0];
    if (!file) return;
    const status = document.getElementById('floorPlanUploadStatus');
    status.textContent = 'アップロード中…';
    status.className = 'small text-muted';

    const fd = new FormData();
    fd.append('image', file);
    try {
        const res  = await fetch('/api/hp/upload', {method: 'POST', body: fd});
        const data = await res.json();
        if (data.success) {
            document.getElementById('bFloorPlanImage').value = data.data.path;
            document.getElementById('floorPlanPreview').innerHTML =
                `<img src="${data.data.path}" class="img-fluid rounded" style="max-height:300px;" alt="平面図">
                 <div class="mt-1"><button class="btn btn-outline-danger btn-sm py-0" onclick="removeFloorPlan()"><i class="bi bi-trash"></i> 削除</button></div>`;
            status.textContent = 'アップロード完了';
            status.className = 'small text-success';
            scheduleBookletSave();
        } else {
            status.textContent = data.error?.message || 'アップロードに失敗しました';
            status.className = 'small text-danger';
        }
    } catch(e) {
        status.textContent = '通信エラーが発生しました';
        status.className = 'small text-danger';
    }
    input.value = '';
}
function removeFloorPlan() {
    document.getElementById('bFloorPlanImage').value = '';
    document.getElementById('floorPlanPreview').innerHTML = '<p class="text-muted small mb-0">画像が設定されていません</p>';
    scheduleBookletSave();
}

function copyBookletUrl() {
    const el = document.getElementById('bookletPublicUrl');
    if (el) { navigator.clipboard.writeText(el.value); alert('URLをコピーしました'); }
}
async function regenBookletToken() {
    if (!confirm('公開URLを新しく発行しますか？（古いURLは無効になります）')) return;
    try {
        const res  = await fetch(`/api/camps/<?= (int)$campId ?>/booklet/token`, {method:'POST'});
        const data = await res.json();
        if (data.success) {
            // サーバー側で is_public=1 にもなるので最新データで上書き
            if (data.data.booklet) {
                _bookletData = data.data.booklet;
            } else {
                _bookletData.public_token = data.data.token;
                _bookletData.is_public = 1;
            }
            renderBookletEditor(_bookletData);
        } else {
            alert(data.error?.message || '発行に失敗しました');
        }
    } catch(e) {
        alert('通信エラーが発生しました');
    }
}

/* ---- 日程設定から取り込む ---- */
async function importScheduleFromSlots() {
    if (_bookletData.schedules && _bookletData.schedules.length > 0) {
        if (!confirm('現在のタイムスケジュールを日程設定から上書きします。よろしいですか？')) return;
    }
    try {
        const res  = await fetch(`/api/camps/<?= (int)$campId ?>/booklet/import-schedule`);
        const data = await res.json();
        if (data.success) {
            _bookletData.schedules = data.data.schedules;
            document.getElementById('schedulesDayList').innerHTML = renderScheduleDays(_bookletData.schedules);
            // 取り込んだことを一瞬示す
            const btn = document.querySelector('[onclick="importScheduleFromSlots()"]');
            if (btn) { btn.textContent = '✓ 取り込み完了'; setTimeout(() => btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> 日程設定から取り込む', 2000); }
        } else {
            alert(data.error?.message || '取り込みに失敗しました');
        }
    } catch(e) {
        alert('通信エラーが発生しました');
    }
}

/* ---- 参加者選択モーダル ---- */
let _pickerDisabled = new Set();

let _pickerMaxSelect = 0; // 0 = 無制限

function openPicker(title, preSelected, onApply, disabledNames = [], singleMode = false, maxSelect = 0) {
    _pickerCallback = onApply;
    _pickerSingleMode = singleMode;
    _pickerMaxSelect  = maxSelect;
    _pickerSelected = new Set(preSelected.map(n => String(n)));
    _pickerDisabled = new Set(disabledNames.map(n => String(n)));
    document.getElementById('pickerModalTitle').textContent = title;
    document.getElementById('pickerSearch').value = '';
    document.getElementById('pickerGradeFilter').value = '';
    document.getElementById('pickerGenderFilter').value = '';
    renderPickerList();
    new bootstrap.Modal(document.getElementById('participantPickerModal')).show();
}

function renderPickerList() {
    const search  = document.getElementById('pickerSearch').value.toLowerCase();
    const grade   = document.getElementById('pickerGradeFilter').value;
    const gender  = document.getElementById('pickerGenderFilter').value;
    const gradeLabels = {0:'OB/OG',1:'1年',2:'2年',3:'3年',4:'4年',5:'5年'};

    const filtered = (_allParticipants || []).filter(p => {
        if (search  && !p.name.includes(search)) return false;
        if (gender  && p.gender !== gender) return false;
        if (grade !== '') {
            if (grade === '4') { if (p.grade < 4) return false; }
            else if (String(p.grade) !== grade) return false;
        }
        return true;
    });

    const gradeOrder = {};
    filtered.forEach(p => { gradeOrder[p.grade] = gradeOrder[p.grade] || []; gradeOrder[p.grade].push(p); });

    let html = '';
    Object.keys(gradeOrder).sort((a,b) => Number(a)-Number(b)).forEach(g => {
        const gLabel = gradeLabels[g] || `${g}年`;
        html += `<div class="text-muted small fw-bold px-1 mt-2 mb-1">${gLabel}</div>`;
        html += '<div class="row g-1">';
        gradeOrder[g].forEach(p => {
            const disabled = _pickerDisabled.has(p.name);
            const chk = _pickerSelected.has(p.name) ? 'checked' : '';
            const genderBadge = p.gender === 'female' ? '<span class="badge bg-pink text-dark" style="background:#fce4ec!important;">女</span>' : '<span class="badge bg-light text-dark border">男</span>';
            if (disabled) {
                html += `<div class="col-md-4 col-6">
                    <label class="d-flex align-items-center gap-1 p-1 border rounded picker-item" style="cursor:not-allowed;opacity:.4;background:#f8f9fa;">
                        <input type="checkbox" class="form-check-input flex-shrink-0" disabled>
                        ${genderBadge} <span class="small">${h(p.name)}</span>
                    </label>
                </div>`;
            } else {
                html += `<div class="col-md-4 col-6">
                    <label class="d-flex align-items-center gap-1 p-1 border rounded picker-item" style="cursor:pointer;">
                        <input type="checkbox" class="form-check-input flex-shrink-0" value="${h(p.name)}" ${chk} onchange="togglePicker('${h(p.name)}',this.checked)">
                        ${genderBadge} <span class="small">${h(p.name)}</span>
                    </label>
                </div>`;
            }
        });
        html += '</div>';
    });

    if (!html) html = '<p class="text-muted text-center small p-3">該当する参加者がいません</p>';
    document.getElementById('pickerList').innerHTML = html;
    document.getElementById('pickerSelectedCount').textContent = _pickerMaxSelect > 0 ? `${_pickerSelected.size}／${_pickerMaxSelect}名選択中` : `${_pickerSelected.size}人選択中`;
}

function togglePicker(name, checked) {
    if (checked) _pickerSelected.add(name); else _pickerSelected.delete(name);
    document.getElementById('pickerSelectedCount').textContent = _pickerMaxSelect > 0 ? `${_pickerSelected.size}／${_pickerMaxSelect}名選択中` : `${_pickerSelected.size}人選択中`;
}

function filterPicker() { renderPickerList(); }

function selectAllVisible() {
    document.querySelectorAll('#pickerList input[type=checkbox]:not(:disabled)').forEach(cb => {
        cb.checked = true; _pickerSelected.add(cb.value);
    });
    document.getElementById('pickerSelectedCount').textContent = _pickerMaxSelect > 0 ? `${_pickerSelected.size}／${_pickerMaxSelect}名選択中` : `${_pickerSelected.size}人選択中`;
}

function clearAllPicker() {
    _pickerSelected.clear();
    document.querySelectorAll('#pickerList input[type=checkbox]').forEach(cb => cb.checked = false);
    document.getElementById('pickerSelectedCount').textContent = _pickerMaxSelect > 0 ? `0／${_pickerMaxSelect}名選択中` : '0人選択中';
}

function applyPickerSelection() {
    bootstrap.Modal.getInstance(document.getElementById('participantPickerModal'))?.hide();
    if (_pickerCallback) _pickerCallback([..._pickerSelected]);
}

/* ---- 団体戦：参加者から選択 ---- */
function openPickerForTeamMember(ti) {
    let preSelected = [];
    let disabledNames = [];
    if (ti !== null) {
        preSelected = (_bookletData.team_battle_teams[ti]?.members || []).map(m => m.name);
        (_bookletData.team_battle_teams || []).forEach((t, idx) => {
            if (idx !== ti) t.members?.forEach(m => { if (m.name) disabledNames.push(m.name); });
        });
    } else {
        (_bookletData.team_battle_teams || []).forEach(t => t.members?.forEach(m => preSelected.push(m.name)));
    }

    const title = ti !== null
        ? `「${_bookletData.team_battle_teams[ti]?.team_name || 'チーム'}」のメンバーを選択`
        : '団体戦チーム分け：参加者を選択';

    openPicker(title, preSelected, (names) => {
        if (ti !== null) {
            const existing = new Map((_bookletData.team_battle_teams[ti].members || []).map(m => [m.name, m]));
            _bookletData.team_battle_teams[ti].members = names.map(n => existing.get(n) || {name: n, is_leader: false});
            document.getElementById(`teamMembers_${ti}`).innerHTML = renderTeamMembers(_bookletData.team_battle_teams[ti].members, ti);
        } else {
            const allExisting = new Set((_bookletData.team_battle_teams || []).flatMap(t => (t.members||[]).map(m=>m.name)));
            names.filter(n => !allExisting.has(n)).forEach(n => {
                if (!_bookletData.team_battle_teams.length) _bookletData.team_battle_teams.push({team_name:'チーム1',members:[]});
                const target = _bookletData.team_battle_teams.reduce((a,b) => (a.members||[]).length <= (b.members||[]).length ? a : b);
                target.members.push({name: n, is_leader: false});
            });
            document.getElementById('teamBattleTeamList').innerHTML = renderTeamBattleTeams(_bookletData.team_battle_teams);
        }
        scheduleBookletSave();
    }, disabledNames);
}

/* ---- 紅白戦：参加者から選択 ---- */
function openPickerForKohaku() {
    const preSelected = (_bookletData.kohaku_teams?.red || []).map(m => m.name);
    const disabledNames = (_bookletData.kohaku_teams?.white || []).map(m => m.name).filter(n => n);
    openPicker('紅白戦 赤組のメンバーを選択', preSelected, (names) => {
        _bookletData.kohaku_teams = _bookletData.kohaku_teams || {red:[],white:[]};
        const existing = new Map((_bookletData.kohaku_teams.red||[]).map(m=>[m.name,m]));
        _bookletData.kohaku_teams.red = names.map(n => existing.get(n) || {name:n});
        document.getElementById('kohakuRedList').innerHTML = renderKohakuMembers(_bookletData.kohaku_teams.red, 'red');
        scheduleBookletSave();
    }, disabledNames);
}
function openPickerForKohakuWhite() {
    const preSelected = (_bookletData.kohaku_teams?.white || []).map(m => m.name);
    const disabledNames = (_bookletData.kohaku_teams?.red || []).map(m => m.name).filter(n => n);
    openPicker('紅白戦 白組のメンバーを選択', preSelected, (names) => {
        _bookletData.kohaku_teams = _bookletData.kohaku_teams || {red:[],white:[]};
        const existing = new Map((_bookletData.kohaku_teams.white||[]).map(m=>[m.name,m]));
        _bookletData.kohaku_teams.white = names.map(n => existing.get(n) || {name:n});
        document.getElementById('kohakuWhiteList').innerHTML = renderKohakuMembers(_bookletData.kohaku_teams.white, 'white');
        scheduleBookletSave();
    }, disabledNames);
}

/* ---- 夜レク：参加者から選択 ---- */
function openPickerForNightRec(gi) {
    let preSelected = [];
    let disabledNames = [];
    if (gi !== null) {
        preSelected = (_bookletData.night_rec_groups[gi]?.members || []).map(m => m.name);
        (_bookletData.night_rec_groups || []).forEach((g, idx) => {
            if (idx !== gi) g.members?.forEach(m => { if (m.name) disabledNames.push(m.name); });
        });
    } else {
        (_bookletData.night_rec_groups || []).forEach(g => g.members?.forEach(m => preSelected.push(m.name)));
    }

    const title = gi !== null
        ? `「${_bookletData.night_rec_groups[gi]?.group_name || '班'}」のメンバーを選択`
        : '夜レク班分け：参加者を選択';

    openPicker(title, preSelected, (names) => {
        if (gi !== null) {
            const existing = new Map((_bookletData.night_rec_groups[gi].members || []).map(m => [m.name, m]));
            _bookletData.night_rec_groups[gi].members = names.map(n => existing.get(n) || {name:n});
            document.getElementById(`nrecMembers_${gi}`).innerHTML = renderNightRecMembers(_bookletData.night_rec_groups[gi].members, gi);
        } else {
            const allExisting = new Set((_bookletData.night_rec_groups || []).flatMap(g => (g.members||[]).map(m=>m.name)));
            names.filter(n => !allExisting.has(n)).forEach(n => {
                if (!_bookletData.night_rec_groups.length) _bookletData.night_rec_groups.push({group_name:'1班',members:[]});
                const target = _bookletData.night_rec_groups.reduce((a,b) => (a.members||[]).length <= (b.members||[]).length ? a : b);
                target.members.push({name:n});
            });
            document.getElementById('nightRecGroupList').innerHTML = renderNightRecGroups(_bookletData.night_rec_groups);
        }
        scheduleBookletSave();
    }, disabledNames);
}
</script>

<!-- 対戦表 自動生成モーダル -->
<div class="modal fade" id="matchAutoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">対戦表を自動生成</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="small text-muted mb-3">紅白戦チーム分けで設定した赤組・白組から対戦表を作成します。同じコマに同一人物は出ず、なるべく試合間隔が空くように、連続して出る場合は同じコートになるよう配置します。<br>現在の対戦表は上書きされます。</p>
                <div class="row g-2">
                    <div class="col-4"><label class="form-label small">コマ数（試合数）</label><input type="number" class="form-control" id="maRounds" min="1" max="30" value="4"></div>
                    <div class="col-4"><label class="form-label small">コート数</label><input type="number" class="form-control" id="maCourts" min="1" max="10" value="2"></div>
                    <div class="col-4"><label class="form-label small">種別</label>
                        <select class="form-select" id="maType">
                            <option value="男子D">男子ダブルス</option>
                            <option value="女子D">女子ダブルス</option>
                            <option value="混合D">ミックスD</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-success" onclick="execMatchAuto()">生成する</button>
            </div>
        </div>
    </div>
</div>

<script>
/* ============================================================
   テニス班分け（団体戦チーム / 紅白戦チーム / 紅白戦対戦表）
   ============================================================ */
const CAMP_ID = <?= (int)$campId ?>;
let _tennisData = null;
let _tennisParticipants = [];
let _tennisLoaded = false;

function tEsc(s){ if(!s) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

async function loadTennis() {
    // 2回目以降（タブを開き直したとき）は読み込み済みデータで再描画する
    if (_tennisLoaded) { renderTennisEditor(); return; }
    try {
        const res  = await fetch(`/api/camps/${CAMP_ID}/tennis`);
        const data = await res.json();
        if (!data.success) { document.getElementById('tennisArea').innerHTML = '<div class="alert alert-danger">読み込みに失敗しました</div>'; return; }
        _tennisData = data.data;
        _tennisData.team_battle_teams = _tennisData.team_battle_teams || [];
        _tennisData.kohaku_teams      = _tennisData.kohaku_teams || {red:[],white:[]};
        _tennisData.kohaku_matches    = _tennisData.kohaku_matches || [];
        _tennisParticipants = data.data.participants || [];
        _allParticipants = _tennisParticipants;
        _tennisLoaded = true;
        renderTennisEditor();
    } catch(e) {
        document.getElementById('tennisArea').innerHTML = '<div class="alert alert-danger">通信エラー</div>';
    }
}

/* ---- 保存（自動保存） ---- */
let _tennisSaveTimer = null, _tennisSaving = false;
function setTennisSaveStatus(state){
    const el = document.getElementById('tennisSaveStatus'); if(!el) return;
    el.textContent = {saving:'保存中…',saved:'保存済み',unsaved:'未保存',error:'保存失敗'}[state] || '';
    el.className = 'small ms-2 ' + ({saving:'text-muted',saved:'text-success',unsaved:'text-warning',error:'text-danger'}[state]||'text-muted');
}
function scheduleTennisSave(){ clearTimeout(_tennisSaveTimer); setTennisSaveStatus('unsaved'); _tennisSaveTimer = setTimeout(()=>saveTennis(true),1200); }
async function saveTennis(auto=false){
    if(_tennisSaving) return; _tennisSaving = true; setTennisSaveStatus('saving');
    const payload = {
        team_battle_teams: _tennisData.team_battle_teams || [],
        team_battle_rules: document.getElementById('tTeamBattleRules')?.value || '',
        kohaku_teams:      _tennisData.kohaku_teams || {red:[],white:[]},
        kohaku_rules:      document.getElementById('tKohakuRules')?.value || '',
        kohaku_matches:    _tennisData.kohaku_matches || [],
    };
    try {
        const res = await fetch(`/api/camps/${CAMP_ID}/tennis`, {method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
        const data = await res.json();
        if(data.success){ setTennisSaveStatus('saved'); if(!auto) showToast('テニス班分けを保存しました','success'); }
        else { setTennisSaveStatus('error'); if(!auto) alert(data.error?.message||'保存に失敗しました'); }
    } catch(e){ setTennisSaveStatus('error'); if(!auto) alert('通信エラーが発生しました'); }
    finally { _tennisSaving = false; }
}

function renderTennisEditor() {
    const b = _tennisData;
    document.getElementById('tennisArea').innerHTML = `
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">テニス班分け</h5>
    <div>
        <span id="tennisSaveStatus" class="small text-muted ms-2"></span>
        <button class="btn btn-outline-secondary btn-sm ms-2" onclick="saveTennis(false)"><i class="bi bi-save"></i> 今すぐ保存</button>
    </div>
</div>

<!-- 団体戦チーム分け -->
<div class="card mb-3">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center">
        団体戦チーム分け
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm" onclick="openPickerForTBattle(null)"><i class="bi bi-people"></i> 参加者から選択</button>
            <button class="btn btn-outline-secondary btn-sm" onclick="addTBattleTeam()">＋ チーム追加</button>
        </div>
    </div>
    <div class="card-body p-2" id="tBattleTeamList">${renderTBattleTeams(b.team_battle_teams)}</div>
    <div class="card-footer">
        <label class="form-label small">団体戦ルール</label>
        <textarea class="form-control form-control-sm" id="tTeamBattleRules" rows="4" oninput="scheduleTennisSave()">${tEsc(b.team_battle_rules||'')}</textarea>
    </div>
</div>

<!-- 紅白戦チーム分け -->
<div class="card mb-3">
    <div class="card-header fw-bold">紅白戦チーム分け</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label small mb-0 text-danger fw-bold">赤組</label>
                    <button class="btn btn-outline-primary btn-sm py-0" onclick="openPickerForKohakuColor('red')"><i class="bi bi-people"></i> 選択</button>
                </div>
                <div id="tKohakuRedList">${renderKohakuColorList(b.kohaku_teams?.red||[],'red')}</div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label small mb-0 fw-bold">白組</label>
                    <button class="btn btn-outline-primary btn-sm py-0" onclick="openPickerForKohakuColor('white')"><i class="bi bi-people"></i> 選択</button>
                </div>
                <div id="tKohakuWhiteList">${renderKohakuColorList(b.kohaku_teams?.white||[],'white')}</div>
            </div>
        </div>
        <div class="mt-3">
            <label class="form-label small">紅白戦ルール</label>
            <textarea class="form-control form-control-sm" id="tKohakuRules" rows="3" oninput="scheduleTennisSave()">${tEsc(b.kohaku_rules||'')}</textarea>
        </div>
    </div>
</div>

<!-- 紅白戦対戦表 -->
<div class="card mb-3">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center flex-wrap gap-2">
        紅白戦対戦表
        <div class="d-flex gap-2">
            <button class="btn btn-outline-success btn-sm" onclick="openMatchAutoModal()"><i class="bi bi-magic"></i> 対戦表を自動生成</button>
            <button class="btn btn-outline-secondary btn-sm" onclick="addMatchRound()">＋ コマ追加</button>
        </div>
    </div>
    <div class="card-body p-2" id="matchList">${renderMatches(b.kohaku_matches)}</div>
</div>
`;
}

/* ---- 団体戦チーム ---- */
function renderTBattleTeams(teams){
    if(!teams.length) return '<p class="text-muted small p-2 mb-0">まだ登録されていません</p>';
    return `<div class="row g-2">${teams.map((team,ti)=>`
    <div class="col-md-3">
        <div class="border rounded p-2 h-100">
            <div class="d-flex gap-1 mb-1">
                <input type="text" class="form-control form-control-sm" value="${tEsc(team.team_name||'')}" oninput="updTBattle(${ti},'team_name',this.value)" placeholder="チーム${ti+1}">
                <button class="btn btn-outline-danger btn-sm py-0 px-1" onclick="rmTBattle(${ti})">×</button>
            </div>
            <div id="tBattleMembers_${ti}">${renderTBattleMembers(team.members||[],ti)}</div>
            <div class="mt-1"><button class="btn btn-outline-primary btn-sm w-100 py-0" onclick="openPickerForTBattle(${ti})"><i class="bi bi-people"></i> 名簿から選択</button></div>
        </div>
    </div>`).join('')}</div>`;
}
function renderTBattleMembers(members,ti){
    if(!members.length) return '<p class="text-muted small mb-1">（未選択）</p>';
    return members.map((m,mi)=>`
    <div class="d-flex gap-1 align-items-center mb-1">
        <input type="checkbox" class="form-check-input flex-shrink-0" ${m.is_leader?'checked':''} onchange="updTBattleMember(${ti},${mi},'is_leader',this.checked)" title="リーダー">
        <span class="small flex-fill">${tEsc(m.name||'')}</span>
        <button class="btn btn-outline-danger btn-sm py-0 px-1" onclick="rmTBattleMember(${ti},${mi})">×</button>
    </div>`).join('');
}
function addTBattleTeam(){ _tennisData.team_battle_teams.push({team_name:`チーム${_tennisData.team_battle_teams.length+1}`,members:[]}); document.getElementById('tBattleTeamList').innerHTML=renderTBattleTeams(_tennisData.team_battle_teams); scheduleTennisSave(); }
function rmTBattle(ti){ _tennisData.team_battle_teams.splice(ti,1); document.getElementById('tBattleTeamList').innerHTML=renderTBattleTeams(_tennisData.team_battle_teams); scheduleTennisSave(); }
function updTBattle(ti,k,v){ _tennisData.team_battle_teams[ti][k]=v; scheduleTennisSave(); }
function rmTBattleMember(ti,mi){ _tennisData.team_battle_teams[ti].members.splice(mi,1); document.getElementById(`tBattleMembers_${ti}`).innerHTML=renderTBattleMembers(_tennisData.team_battle_teams[ti].members,ti); scheduleTennisSave(); }
function updTBattleMember(ti,mi,k,v){ _tennisData.team_battle_teams[ti].members[mi][k]=v; scheduleTennisSave(); }
function openPickerForTBattle(ti){
    _allParticipants = _tennisParticipants;
    let preSelected=[], disabledNames=[];
    if(ti!==null){
        preSelected=(_tennisData.team_battle_teams[ti]?.members||[]).map(m=>m.name);
        _tennisData.team_battle_teams.forEach((t,idx)=>{ if(idx!==ti) (t.members||[]).forEach(m=>{ if(m.name) disabledNames.push(m.name); }); });
    } else {
        _tennisData.team_battle_teams.forEach(t=>(t.members||[]).forEach(m=>preSelected.push(m.name)));
    }
    openPicker('団体戦メンバーを選択', preSelected, (names)=>{
        if(ti!==null){
            _tennisData.team_battle_teams[ti].members = names.map(n=>{ const ex=(_tennisData.team_battle_teams[ti].members||[]).find(m=>m.name===n); return {name:n,is_leader:ex?ex.is_leader:false}; });
            document.getElementById('tBattleTeamList').innerHTML=renderTBattleTeams(_tennisData.team_battle_teams);
        } else {
            const existing=new Set(); _tennisData.team_battle_teams.forEach(t=>(t.members||[]).forEach(m=>existing.add(m.name)));
            names.filter(n=>!existing.has(n)).forEach(n=>{
                if(!_tennisData.team_battle_teams.length) _tennisData.team_battle_teams.push({team_name:'チーム1',members:[]});
                const tgt=_tennisData.team_battle_teams.reduce((a,b)=>(a.members||[]).length<=(b.members||[]).length?a:b);
                tgt.members.push({name:n,is_leader:false});
            });
            document.getElementById('tBattleTeamList').innerHTML=renderTBattleTeams(_tennisData.team_battle_teams);
        }
        scheduleTennisSave();
    }, disabledNames);
}

/* ---- 紅白戦チーム ---- */
function renderKohakuColorList(members,color){
    if(!members.length) return '<p class="text-muted small mb-1">（未選択）</p>';
    const gmap={}; (_tennisParticipants||[]).forEach(p=>{gmap[p.name]=p.gender;});
    const males=members.map((m,mi)=>({...m,mi})).filter(m=>gmap[m.name]!=='female');
    const females=members.map((m,mi)=>({...m,mi})).filter(m=>gmap[m.name]==='female');
    const row=(m)=>`<div class="d-flex gap-1 align-items-center mb-1"><span class="small flex-fill">${tEsc(m.name||'')}</span><button class="btn btn-outline-danger btn-sm py-0 px-1" onclick="rmKohakuColor('${color}',${m.mi})">×</button></div>`;
    return `<div class="row g-2">
        <div class="col-6"><div class="text-muted small fw-bold mb-1">男子</div>${males.length?males.map(row).join(''):'<p class="text-muted small">—</p>'}</div>
        <div class="col-6"><div class="text-muted small fw-bold mb-1">女子</div>${females.length?females.map(row).join(''):'<p class="text-muted small">—</p>'}</div>
    </div>`;
}
function rmKohakuColor(color,mi){ _tennisData.kohaku_teams[color].splice(mi,1); refreshKohakuColor(color); scheduleTennisSave(); }
function refreshKohakuColor(color){ const id = color==='red'?'tKohakuRedList':'tKohakuWhiteList'; document.getElementById(id).innerHTML=renderKohakuColorList(_tennisData.kohaku_teams[color],color); }
function openPickerForKohakuColor(color){
    _allParticipants=_tennisParticipants;
    const other = color==='red'?'white':'red';
    const pre = (_tennisData.kohaku_teams[color]||[]).map(m=>m.name);
    const disabled = (_tennisData.kohaku_teams[other]||[]).map(m=>m.name);
    openPicker(`${color==='red'?'赤組':'白組'}を選択`, pre, (names)=>{
        _tennisData.kohaku_teams[color]=names.map(n=>({name:n}));
        refreshKohakuColor(color); scheduleTennisSave();
    }, disabled);
}

/* ---- 紅白戦対戦表 ---- */
const T_MATCH_TYPES=[{v:'男子D',l:'男子ダブルス'},{v:'女子D',l:'女子ダブルス'},{v:'混合D',l:'ミックスD'}];
function renderMatches(matches){
    if(!matches.length) return '<p class="text-muted small p-2 mb-0">まだ登録されていません。「自動生成」または「コマ追加」で作成してください。</p>';
    return matches.map((round,ri)=>`
    <div class="border rounded mb-2 p-2">
        <div class="d-flex gap-2 align-items-center mb-2">
            <input type="text" class="form-control form-control-sm" value="${tEsc(round.round||`${ri+1}試合目`)}" oninput="updMatchRound(${ri},this.value)" style="max-width:130px;">
            <button class="btn btn-outline-secondary btn-sm py-0" onclick="addMatchCourt(${ri})">＋ コート追加</button>
            <button class="btn btn-outline-danger btn-sm py-0 ms-auto" onclick="rmMatchRound(${ri})">コマ削除</button>
        </div>
        <div id="matchCourts_${ri}">${renderMatchCourts(round.courts||[],ri)}</div>
    </div>`).join('');
}
function renderMatchCourts(courts,ri){
    if(!courts.length) return '<p class="text-muted small mb-0">コートがありません</p>';
    return courts.map((c,ci)=>{
        const typeOpts=T_MATCH_TYPES.map(t=>`<option value="${t.v}" ${c.type===t.v?'selected':''}>${t.l}</option>`).join('');
        const fmt=(n1,n2)=> (!n1&&!n2)?'<span class="text-muted">未選択</span>':`${tEsc(n1||'？')} ／ ${tEsc(n2||'？')}`;
        return `
        <div class="border rounded p-2 mb-1 small">
            <div class="d-flex gap-2 align-items-center mb-2">
                <span class="text-muted fw-bold">${ci+1}番コート</span>
                <select class="form-select form-select-sm" style="max-width:150px;" onchange="updMatchCourt(${ri},${ci},'type',this.value)">${typeOpts}</select>
                <button class="btn btn-outline-danger btn-sm py-0 ms-auto px-2" onclick="rmMatchCourt(${ri},${ci})">×</button>
            </div>
            <div class="row g-1">
                <div class="col-6">
                    <div class="d-flex justify-content-between align-items-center mb-1"><span class="fw-bold"><span class="badge me-1" style="background:#dc2626;">赤</span>赤組</span>
                    <button class="btn btn-outline-secondary btn-sm py-0 px-1" onclick="pickMatchPair(${ri},${ci},'red')"><i class="bi bi-people"></i></button></div>
                    <div class="ps-1">${fmt(c.red1,c.red2)}</div>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-between align-items-center mb-1"><span class="fw-bold"><span class="badge me-1 bg-secondary">白</span>白組</span>
                    <button class="btn btn-outline-secondary btn-sm py-0 px-1" onclick="pickMatchPair(${ri},${ci},'white')"><i class="bi bi-people"></i></button></div>
                    <div class="ps-1">${fmt(c.white1,c.white2)}</div>
                </div>
            </div>
        </div>`;
    }).join('');
}
function addMatchRound(){ const ri=_tennisData.kohaku_matches.length; _tennisData.kohaku_matches.push({round:`${ri+1}試合目`,courts:[]}); document.getElementById('matchList').innerHTML=renderMatches(_tennisData.kohaku_matches); scheduleTennisSave(); }
function rmMatchRound(ri){ _tennisData.kohaku_matches.splice(ri,1); document.getElementById('matchList').innerHTML=renderMatches(_tennisData.kohaku_matches); scheduleTennisSave(); }
function updMatchRound(ri,v){ _tennisData.kohaku_matches[ri].round=v; scheduleTennisSave(); }
function addMatchCourt(ri){ _tennisData.kohaku_matches[ri].courts.push({type:'男子D',red1:'',red2:'',white1:'',white2:''}); document.getElementById(`matchCourts_${ri}`).innerHTML=renderMatchCourts(_tennisData.kohaku_matches[ri].courts,ri); scheduleTennisSave(); }
function rmMatchCourt(ri,ci){ _tennisData.kohaku_matches[ri].courts.splice(ci,1); document.getElementById(`matchCourts_${ri}`).innerHTML=renderMatchCourts(_tennisData.kohaku_matches[ri].courts,ri); scheduleTennisSave(); }
function updMatchCourt(ri,ci,k,v){ _tennisData.kohaku_matches[ri].courts[ci][k]=v; document.getElementById(`matchCourts_${ri}`).innerHTML=renderMatchCourts(_tennisData.kohaku_matches[ri].courts,ri); scheduleTennisSave(); }

function pickMatchPair(ri,ci,color){
    _allParticipants=_tennisParticipants;
    const round=_tennisData.kohaku_matches[ri], court=round.courts[ci];
    const mt=court.type||'男子D';
    let genderFilter=null; if(mt==='男子D')genderFilter='male'; else if(mt==='女子D')genderFilter='female';
    const teamNames=(color==='red'?(_tennisData.kohaku_teams?.red||[]):(_tennisData.kohaku_teams?.white||[])).map(m=>m.name).filter(n=>n);
    const usedInRound=new Set();
    round.courts.forEach((c,idx)=>['red1','red2','white1','white2'].forEach(slot=>{ const n=c[slot]||''; if(!n)return; if(idx===ci&&slot.startsWith(color))return; usedInRound.add(n); }));
    const typeLabel={男子D:'男子ダブルス',女子D:'女子ダブルス',混合D:'ミックスD'}[mt]||mt;
    const pre=[court[`${color}1`],court[`${color}2`]].filter(n=>n);
    const orig=_allParticipants;
    _allParticipants=(_tennisParticipants||[]).filter(p=>teamNames.includes(p.name)&&(genderFilter===null||p.gender===genderFilter));
    openPicker(`${color==='red'?'赤組':'白組'}ペアを選択（${typeLabel}・2名まで）`, pre, (names)=>{
        _allParticipants=orig;
        court[`${color}1`]=names[0]||''; court[`${color}2`]=names[1]||'';
        document.getElementById(`matchCourts_${ri}`).innerHTML=renderMatchCourts(round.courts,ri); scheduleTennisSave();
    }, [...usedInRound], false, 2);
    _allParticipants=orig;
}

/* ---- 対戦表 自動生成 ---- */
function openMatchAutoModal(){
    const red=_tennisData.kohaku_teams?.red||[], white=_tennisData.kohaku_teams?.white||[];
    if(red.length<2||white.length<2){ alert('先に紅白戦チーム分けで赤組・白組を各2名以上設定してください'); return; }
    new bootstrap.Modal(document.getElementById('matchAutoModal')).show();
}
function genderOf(name){ const p=(_tennisParticipants||[]).find(x=>x.name===name); return p?p.gender:'male'; }

/*
 対戦表自動生成
  - 各コマ（試合）に複数コート。コートは指定種別。
  - 同一人物が同じコマで複数コートに出ない。
  - 連続コマに出る人は「同じ番号のコート」になるよう優先（2連続を同じコートで）。
  - なるべく各人の試合間隔を空ける（休養が長い人を優先選出）。
*/
function execMatchAuto(){
    const rounds=parseInt(document.getElementById('maRounds').value)||0;
    const courts=parseInt(document.getElementById('maCourts').value)||0;
    const type=document.getElementById('maType').value;
    if(rounds<1||courts<1){ alert('コマ数・コート数を正しく入力してください'); return; }

    const redAll=(_tennisData.kohaku_teams.red||[]).map(m=>m.name);
    const whiteAll=(_tennisData.kohaku_teams.white||[]).map(m=>m.name);
    const filt=(names)=>{ if(type==='男子D') return names.filter(n=>genderOf(n)==='male'); if(type==='女子D') return names.filter(n=>genderOf(n)==='female'); return names; };

    const buildPool=(names)=>{
        if(type==='混合D'){
            return {males:names.filter(n=>genderOf(n)==='male'), females:names.filter(n=>genderOf(n)==='female'), mixed:true};
        }
        return {pool:filt(names), mixed:false};
    };
    const red=buildPool(redAll), white=buildPool(whiteAll);

    const stats={}; [...redAll,...whiteAll].forEach(n=>stats[n]={count:0,last:-99,lastCourt:-1});

    function pick(candidates, usedThisRound, ri, courtIdx, need){
        const avail=(candidates||[]).filter(n=>!usedThisRound.has(n));
        const score=(n)=>{
            const rest=ri-stats[n].last;
            let s=rest*10 - stats[n].count*3;
            if(stats[n].last===ri-1 && stats[n].lastCourt===courtIdx) s+=1000; // 連続コマ同コート優先
            return s;
        };
        avail.sort((a,b)=>score(b)-score(a));
        return avail.slice(0,need);
    }

    const matches=[];
    for(let ri=0; ri<rounds; ri++){
        const round={round:`${ri+1}試合目`,courts:[]};
        const usedThisRound=new Set();
        for(let ci=0; ci<courts; ci++){
            let r1='',r2='',w1='',w2='';
            if(type==='混合D'){
                r1=pick(red.males,usedThisRound,ri,ci,1)[0]||'';
                r2=pick(red.females,usedThisRound,ri,ci,1)[0]||'';
                w1=pick(white.males,usedThisRound,ri,ci,1)[0]||'';
                w2=pick(white.females,usedThisRound,ri,ci,1)[0]||'';
            } else {
                const rp=pick(red.pool,usedThisRound,ri,ci,2); r1=rp[0]||''; r2=rp[1]||'';
                const wp=pick(white.pool,usedThisRound,ri,ci,2); w1=wp[0]||''; w2=wp[1]||'';
            }
            [r1,r2,w1,w2].forEach(n=>{ if(n){ usedThisRound.add(n); stats[n].count++; stats[n].last=ri; stats[n].lastCourt=ci; } });
            round.courts.push({type,red1:r1,red2:r2,white1:w1,white2:w2});
        }
        matches.push(round);
    }

    _tennisData.kohaku_matches=matches;
    document.getElementById('matchList').innerHTML=renderMatches(matches);
    bootstrap.Modal.getInstance(document.getElementById('matchAutoModal'))?.hide();
    scheduleTennisSave();
    showToast('対戦表を自動生成しました','success');
}
</script>

<script>
/* ============================================================
   企画班分け（複数企画・各々に名前・会計アプリ同等のフル機能）
   ============================================================ */
let _planDivisions = [];
let _planParticipants = [];
let _planLoaded = false;
let _planCurrentId = null;        // 選択中の企画ID
let _planMembers = [];            // 選択中企画のメンバー（team_no含む）
let _planConstraints = [];        // 選択中企画の制約
let _planView = 'teams';          // 'teams' | 'list'
let _planSort = { primary:{key:'team_no',dir:1}, secondary:{key:'name_kana',dir:1} };

const PLAN_COLORS = ['#0d6efd','#198754','#dc3545','#fd7e14','#6f42c1','#0dcaf0','#d63384','#20c997','#ffc107','#6610f2'];

let _planTimeSlots = [];   // 合宿のタイムスロット一覧（開催タイミング選択用）
const PLAN_SLOT_LABELS = { outbound:'往路', morning:'午前', afternoon:'午後', banquet:'宴会', return:'復路' };
function planSlotLabel(slot){
    if(!slot || !slot.day_number) return '未設定';
    return `${slot.day_number}日目 ${PLAN_SLOT_LABELS[slot.slot_type]||slot.slot_type||''}`;
}

function pEsc(s){ if(!s) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function planGradeNum(g){ if(g===null||g===undefined) return 98; const n=parseInt(g); if(isNaN(n)) return 98; if(n===0) return 99; return n; }
function planGradeLabel(g){ if(g===0||g==='0') return 'OB/OG'; if(g===null||g===undefined||g==='') return '—'; return g+'年'; }
function planGenderLabel(g){ return g==='male'?'男':(g==='female'?'女':'—'); }

async function loadPlans(){
    // 2回目以降（タブを開き直したとき）は読み込み済みデータで再描画する
    if(_planLoaded){
        renderPlansShell();
        if(_planCurrentId) renderPlanDetail();
        else if(_planDivisions.length) selectPlanDivision(_planDivisions[0].id);
        return;
    }
    try{
        const res=await fetch(`/api/camps/${CAMP_ID}/plans`);
        const data=await res.json();
        if(!data.success){ document.getElementById('plansArea').innerHTML='<div class="alert alert-danger">読み込みに失敗しました</div>'; return; }
        _planDivisions=data.data.divisions||[];
        _planParticipants=data.data.participants||[];
        _planTimeSlots=data.data.time_slots||[];
        _planLoaded=true;
        renderPlansShell();
        if(_planDivisions.length){ selectPlanDivision(_planDivisions[0].id); }
    }catch(e){ document.getElementById('plansArea').innerHTML='<div class="alert alert-danger">通信エラー</div>'; }
}

function renderPlansShell(){
    document.getElementById('plansArea').innerHTML=`
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">企画班分け</h5>
</div>
<div class="card mb-3">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center">
        企画一覧
        <button class="btn btn-outline-primary btn-sm" onclick="addPlanDivision()">＋ 企画を追加</button>
    </div>
    <div class="card-body">
        <div id="planTabs" class="d-flex gap-2 flex-wrap mb-2"></div>
        <p class="text-muted small mb-0" id="planEmptyHint" style="display:none;">企画がありません。「＋ 企画を追加」で作成してください（例：夜レク、BBQ）。</p>
    </div>
</div>
<div id="planDetailArea"></div>
`;
    renderPlanTabs();
}

function renderPlanTabs(){
    const wrap=document.getElementById('planTabs');
    document.getElementById('planEmptyHint').style.display = _planDivisions.length?'none':'block';
    wrap.innerHTML=_planDivisions.map(d=>{
        const active=d.id===_planCurrentId;
        const timing = d.day_number ? ` <span class="badge ${active?'bg-light text-dark':'bg-secondary'}">${pEsc(planSlotLabel(d))}</span>` : '';
        return `<button class="btn btn-sm ${active?'btn-primary':'btn-outline-secondary'}" onclick="selectPlanDivision(${d.id})">${pEsc(d.name)}${timing}</button>`;
    }).join('');
}

async function addPlanDivision(){
    const name=prompt('企画名を入力してください（例：夜レク、BBQ班）','新しい企画');
    if(name===null) return;
    try{
        const res=await fetch(`/api/camps/${CAMP_ID}/plans`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({name:name.trim()||'新しい企画'})});
        const data=await res.json();
        if(data.success){ _planDivisions.push(data.data); renderPlanTabs(); selectPlanDivision(data.data.id); }
        else alert(data.error?.message||'作成に失敗しました');
    }catch(e){ alert('通信エラーが発生しました'); }
}

async function renamePlanDivision(){
    if(!_planCurrentId) return;
    const cur=_planDivisions.find(d=>d.id===_planCurrentId);
    const name=prompt('企画名を変更',cur?cur.name:'');
    if(name===null||!name.trim()) return;
    try{
        const res=await fetch(`/api/plans/${_planCurrentId}`,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({name:name.trim()})});
        const data=await res.json();
        if(data.success){ if(cur) cur.name=name.trim(); renderPlanTabs(); renderPlanDetail(); }
        else alert(data.error?.message||'変更に失敗しました');
    }catch(e){ alert('通信エラーが発生しました'); }
}

async function deletePlanDivision(){
    if(!_planCurrentId) return;
    const cur=_planDivisions.find(d=>d.id===_planCurrentId);
    if(!confirm(`企画「${cur?cur.name:''}」を削除しますか？班分けも削除されます。`)) return;
    try{
        const res=await fetch(`/api/plans/${_planCurrentId}`,{method:'DELETE'});
        const data=await res.json();
        if(data.success){
            _planDivisions=_planDivisions.filter(d=>d.id!==_planCurrentId);
            _planCurrentId=null; _planMembers=[]; _planConstraints=[];
            renderPlanTabs();
            if(_planDivisions.length) selectPlanDivision(_planDivisions[0].id);
            else document.getElementById('planDetailArea').innerHTML='';
        } else alert(data.error?.message||'削除に失敗しました');
    }catch(e){ alert('通信エラーが発生しました'); }
}

async function selectPlanDivision(id){
    _planCurrentId=id;
    renderPlanTabs();
    document.getElementById('planDetailArea').innerHTML='<div class="text-center p-4 text-muted">読み込み中...</div>';
    try{
        const res=await fetch(`/api/plans/${id}`);
        const data=await res.json();
        if(!data.success){ document.getElementById('planDetailArea').innerHTML='<div class="alert alert-danger">読み込みに失敗しました</div>'; return; }
        _planMembers=(data.data.members||[]).map(m=>({...m, team_no:(m.team_no===null||m.team_no===undefined)?null:parseInt(m.team_no)}));
        _planConstraints=data.data.constraints||[];
        if(data.data.participants) _planParticipants=data.data.participants;
        // 最新の division（time_slot 情報付き）を反映
        if(data.data.division){ const i=_planDivisions.findIndex(d=>d.id===id); if(i>=0) _planDivisions[i]=data.data.division; }
        renderPlanDetail();
    }catch(e){ document.getElementById('planDetailArea').innerHTML='<div class="alert alert-danger">通信エラー</div>'; }
}

function renderPlanDetail(){
    const cur=_planDivisions.find(d=>d.id===_planCurrentId);
    if(!cur){ document.getElementById('planDetailArea').innerHTML=''; return; }
    document.getElementById('planDetailArea').innerHTML=`
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="fw-bold">${pEsc(cur.name)}</span>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm" onclick="renamePlanDivision()"><i class="bi bi-pencil"></i> 名前変更</button>
            <button class="btn btn-outline-danger btn-sm" onclick="deletePlanDivision()"><i class="bi bi-trash"></i> 企画削除</button>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small mb-1">開催タイミング（いつ）</label>
                <select class="form-select form-select-sm" id="planTimeSlotSelect" onchange="changePlanTimeSlot(this.value)">
                    <option value="">未設定</option>
                    ${_planTimeSlots.map(ts=>`<option value="${ts.id}" ${parseInt(cur.time_slot_id)===parseInt(ts.id)?'selected':''}>${pEsc(planSlotLabel(ts))}</option>`).join('')}
                </select>
            </div>
            <div class="col-md-7">
                <span class="text-muted small">
                    その時点で合宿に参加している人（${_planMembers.length}名）を自動的に対象にします。
                    <button class="btn btn-outline-secondary btn-sm py-0 ms-1" onclick="resyncPlanMembers()" title="参加者情報を変更した場合に最新化"><i class="bi bi-arrow-repeat"></i> 参加者を最新に</button>
                </span>
                ${_planTimeSlots.length===0?'<div class="text-danger small mt-1">タイムスロットが未設定です。先に「日程設定」タブでコマを設定してください。</div>':''}
            </div>
        </div>
    </div>
</div>

<!-- 自動振り分け -->
<div class="card mb-3">
    <div class="card-header fw-bold">班の自動振り分け</div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3"><label class="form-label small">班の数</label><input type="number" class="form-control form-control-sm" id="planTeamCount" value="4" min="1" max="50"></div>
            <div class="col-md-6">
                <label class="form-label small d-block">均等にする基準</label>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" id="planBalGrade" checked><label class="form-check-label small" for="planBalGrade">学年</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" id="planBalGender" checked><label class="form-check-label small" for="planBalGender">性別</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" id="planBalFaculty"><label class="form-check-label small" for="planBalFaculty">学科</label></div>
            </div>
            <div class="col-md-3 d-grid"><button class="btn btn-primary btn-sm" onclick="autoAssignPlan()">自動振り分け</button></div>
        </div>
        <div class="form-text mt-1">制約（絶対一緒／絶対別）を守りつつ、選択した基準が各班でばらけるよう割り当てます。学科データのない参加者は学科分散の対象外です。</div>
    </div>
</div>

<!-- 制約 -->
<div class="card mb-3">
    <div class="card-header fw-bold">組み合わせの制約</div>
    <div class="card-body">
        <div class="row g-2 align-items-end mb-3">
            <div class="col-md-4"><label class="form-label small">メンバー1</label><select class="form-select form-select-sm" id="planConA"></select></div>
            <div class="col-md-4"><label class="form-label small">メンバー2</label><select class="form-select form-select-sm" id="planConB"></select></div>
            <div class="col-md-2"><label class="form-label small">種別</label><select class="form-select form-select-sm" id="planConType"><option value="together">絶対一緒</option><option value="apart">絶対別</option></select></div>
            <div class="col-md-2 d-grid"><button class="btn btn-outline-primary btn-sm" onclick="addPlanConstraint()">追加</button></div>
        </div>
        <div id="planConstraintsList"></div>
    </div>
</div>

<!-- 班割り -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="fw-bold">班割り</span>
            <div class="btn-group btn-group-sm">
                <button class="btn ${_planView==='teams'?'btn-primary':'btn-outline-primary'}" id="planTeamViewBtn" onclick="setPlanView('teams')">班ビュー</button>
                <button class="btn ${_planView==='list'?'btn-primary':'btn-outline-primary'}" id="planListViewBtn" onclick="setPlanView('list')">一覧ビュー</button>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div id="planSortControls" class="align-items-center gap-2 flex-wrap" style="display:${_planView==='list'?'flex':'none'};">
                <div class="input-group input-group-sm" style="width:auto;">
                    <span class="input-group-text">並べ替え</span>
                    <select class="form-select form-select-sm" id="planSortPrimary" onchange="applyPlanSort()" style="width:auto;">
                        <option value="team_no">班番号</option><option value="name_kana">カナ順</option><option value="grade">学年</option><option value="gender">性別</option><option value="faculty">学部</option>
                    </select>
                    <button class="btn btn-outline-secondary" id="planSortPrimaryDir" onclick="togglePlanSortDir('primary')">↑</button>
                </div>
            </div>
            <span id="planSaveStatus" class="small text-muted"></span>
        </div>
    </div>
    <div class="card-body p-0">
        <div id="planSummary" class="px-3 pt-3"></div>
        <div id="planTeamsContainer"></div>
    </div>
</div>
`;
    // sort select の初期値反映
    const ps=document.getElementById('planSortPrimary'); if(ps) ps.value=_planSort.primary.key;
    renderPlanConstraints();
    renderPlanConstraintSelects();
    renderPlanTeams();
}

/* ---- 開催タイミング設定（参加者は自動同期） ---- */
async function changePlanTimeSlot(value){
    const timeSlotId = (value==='' || value===null) ? null : parseInt(value);
    try{
        const res=await fetch(`/api/plans/${_planCurrentId}`,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({time_slot_id:timeSlotId})});
        const data=await res.json();
        if(data.success){
            if(data.data.division){ const i=_planDivisions.findIndex(d=>d.id===_planCurrentId); if(i>=0) _planDivisions[i]=data.data.division; }
            _planMembers=(data.data.members||[]).map(m=>({...m, team_no:(m.team_no===null||m.team_no===undefined)?null:parseInt(m.team_no)}));
            renderPlanDetail();
            showToast('開催タイミングを設定し、参加者を更新しました','success');
        } else alert(data.error?.message||'更新に失敗しました');
    }catch(e){ alert('通信エラーが発生しました'); }
}

async function resyncPlanMembers(){
    if(!_planCurrentId) return;
    try{
        const res=await fetch(`/api/plans/${_planCurrentId}/resync`,{method:'POST'});
        const data=await res.json();
        if(data.success){
            _planMembers=(data.data.members||[]).map(m=>({...m, team_no:(m.team_no===null||m.team_no===undefined)?null:parseInt(m.team_no)}));
            renderPlanDetail();
            showToast('参加者を最新の状態に更新しました','success');
        } else alert(data.error?.message||'更新に失敗しました');
    }catch(e){ alert('通信エラーが発生しました'); }
}

/* ---- 制約 ---- */
function renderPlanConstraintSelects(){
    const opts='<option value="">選択してください</option>'+[..._planMembers]
        .sort((a,b)=>(a.name_kana||a.name||'').localeCompare(b.name_kana||b.name||'','ja'))
        .map(m=>`<option value="${m.participant_id}">${pEsc(m.name)}（${pEsc(planGradeLabel(m.grade))}）</option>`).join('');
    const a=document.getElementById('planConA'), b=document.getElementById('planConB');
    if(a) a.innerHTML=opts; if(b) b.innerHTML=opts;
}
function renderPlanConstraints(){
    const el=document.getElementById('planConstraintsList'); if(!el) return;
    if(!_planConstraints.length){ el.innerHTML='<div class="text-muted small">制約はまだありません</div>'; return; }
    el.innerHTML=_planConstraints.map(c=>{
        const badge=c.type==='together'?'<span class="badge bg-success">絶対一緒</span>':'<span class="badge bg-danger">絶対別</span>';
        return `<span class="badge bg-light text-dark border me-2 mb-2 p-2">${badge}<span class="ms-1">${pEsc(c.participant_a_name)} と ${pEsc(c.participant_b_name)}</span><button class="btn-close ms-2" style="font-size:.6rem;vertical-align:middle;" onclick="deletePlanConstraint(${c.id})"></button></span>`;
    }).join('');
}
async function addPlanConstraint(){
    const a=document.getElementById('planConA').value, b=document.getElementById('planConB').value, type=document.getElementById('planConType').value;
    if(!a||!b){ alert('2名のメンバーを選択してください'); return; }
    if(a===b){ alert('異なるメンバーを選択してください'); return; }
    try{
        const res=await fetch(`/api/plans/${_planCurrentId}/constraints`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({participant_a_id:parseInt(a),participant_b_id:parseInt(b),type})});
        const data=await res.json();
        if(data.success){ _planConstraints=data.data.constraints||[]; renderPlanConstraints(); showToast('制約を追加しました','success'); }
        else alert(data.error?.message||'追加に失敗しました');
    }catch(e){ alert('通信エラーが発生しました'); }
}
async function deletePlanConstraint(id){
    if(!confirm('この制約を削除しますか？')) return;
    try{
        const res=await fetch(`/api/plans/${_planCurrentId}/constraints/${id}`,{method:'DELETE'});
        const data=await res.json();
        if(data.success){ _planConstraints=_planConstraints.filter(c=>parseInt(c.id)!==id); renderPlanConstraints(); showToast('制約を削除しました','success'); }
        else alert(data.error?.message||'削除に失敗しました');
    }catch(e){ alert('通信エラーが発生しました'); }
}

/* ---- 自動振り分け ---- */
function autoAssignPlan(){
    const teamCount=parseInt(document.getElementById('planTeamCount').value);
    if(!teamCount||teamCount<1){ alert('班の数を正しく入力してください'); return; }
    if(!_planMembers.length){ alert('参加者がいません。上の「開催タイミング」を設定すると、その時点の参加者が自動で対象になります。'); return; }
    const useGrade=document.getElementById('planBalGrade').checked;
    const useGender=document.getElementById('planBalGender').checked;
    const useFaculty=document.getElementById('planBalFaculty').checked;

    const idToM={}; _planMembers.forEach(m=>{ idToM[m.participant_id]=m; });
    const parent={}; _planMembers.forEach(m=>{ parent[m.participant_id]=m.participant_id; });
    const find=(x)=>{ while(parent[x]!==x){ parent[x]=parent[parent[x]]; x=parent[x]; } return x; };
    const union=(a,b)=>{ const ra=find(a),rb=find(b); if(ra!==rb) parent[ra]=rb; };
    _planConstraints.forEach(c=>{ if(c.type==='together'&&idToM[c.participant_a_id]&&idToM[c.participant_b_id]) union(parseInt(c.participant_a_id),parseInt(c.participant_b_id)); });

    const clusters={}; _planMembers.forEach(m=>{ const r=find(m.participant_id); (clusters[r]=clusters[r]||[]).push(m); });
    let clusterList=Object.values(clusters);

    const apart={}; _planConstraints.forEach(c=>{ if(c.type==='apart'){ const a=parseInt(c.participant_a_id),b=parseInt(c.participant_b_id); (apart[a]=apart[a]||new Set()).add(b); (apart[b]=apart[b]||new Set()).add(a); } });

    clusterList.sort((a,b)=>b.length-a.length);
    const teams=Array.from({length:teamCount},()=>[]);
    const apartConflict=(t,cl)=>{ for(const m of teams[t]) for(const cm of cl){ if(apart[m.participant_id]&&apart[m.participant_id].has(cm.participant_id)) return true; } return false; };
    // バランス用コスト: 各基準値（性別なら male/female を別々に）が
    // 配置後に各班へ何人になるかの二乗和を最小化する。
    // 二乗和は特定の値が1つの班に偏るほど急増するため、
    // 少数派の値（例: 人数の少ない性別）も各班へ分散されやすい。
    const cost=(t,cl)=>{
        let c=Math.pow(teams[t].length+cl.length,2); // 人数均等
        const bal=(valFn)=>{ const o={}; teams[t].forEach(m=>{const v=valFn(m);o[v]=(o[v]||0)+1;}); cl.forEach(m=>{const v=valFn(m);o[v]=(o[v]||0)+1;}); return Object.values(o).reduce((s,n)=>s+n*n,0); };
        if(useGrade)   c+=bal(m=>planGradeNum(m.grade));
        if(useGender)  c+=bal(m=>m.gender||'__none__');
        if(useFaculty) c+=bal(m=>m.faculty||'__none__');
        return c;
    };
    let unplaced=[];
    clusterList.forEach(cl=>{
        let best=-1,bc=Infinity;
        for(let t=0;t<teamCount;t++){ if(apartConflict(t,cl)) continue; const co=cost(t,cl); if(co<bc){bc=co;best=t;} }
        if(best===-1) unplaced.push(cl); else { cl.forEach(m=>m.team_no=best+1); teams[best].push(...cl); }
    });
    unplaced.forEach(cl=>{ let mn=0; for(let t=1;t<teamCount;t++) if(teams[t].length<teams[mn].length) mn=t; cl.forEach(m=>m.team_no=mn+1); teams[mn].push(...cl); });

    renderPlanTeams();
    savePlanTeams();
    showToast(unplaced.length?'一部、絶対別の制約を満たせない班割りになりました':'自動振り分けしました','success');
}

/* ---- ビュー切替・ソート ---- */
function setPlanView(v){
    _planView=v;
    document.getElementById('planTeamViewBtn').className=v==='teams'?'btn btn-primary':'btn btn-outline-primary';
    document.getElementById('planListViewBtn').className=v==='list'?'btn btn-primary':'btn btn-outline-primary';
    document.getElementById('planSortControls').style.display=v==='list'?'flex':'none';
    renderPlanTeams();
}
function applyPlanSort(){ _planSort.primary.key=document.getElementById('planSortPrimary').value; renderPlanTeams(); }
function togglePlanSortDir(){ _planSort.primary.dir*=-1; document.getElementById('planSortPrimaryDir').textContent=_planSort.primary.dir===1?'↑':'↓'; renderPlanTeams(); }
function planCompare(a,b,key){
    if(key==='team_no'){ const ta=a.team_no===null?Infinity:a.team_no, tb=b.team_no===null?Infinity:b.team_no; return ta-tb; }
    if(key==='name_kana') return (a.name_kana||a.name||'').localeCompare(b.name_kana||b.name||'','ja');
    if(key==='grade') return planGradeNum(a.grade)-planGradeNum(b.grade);
    if(key==='gender'){ const o={male:1,female:2}; return (o[a.gender]||3)-(o[b.gender]||3); }
    if(key==='faculty') return (a.faculty||'').localeCompare(b.faculty||'','ja');
    return 0;
}

function planSummaryHtml(){
    const counts={}; _planMembers.forEach(m=>{ const k=m.team_no===null?'未割り当て':`${m.team_no}班`; counts[k]=(counts[k]||0)+1; });
    return '<div class="mb-2">'+Object.keys(counts).sort((a,b)=>{ if(a==='未割り当て')return 1; if(b==='未割り当て')return -1; return parseInt(a)-parseInt(b); })
        .map(k=>{ const cls=k==='未割り当て'?'bg-secondary':'bg-primary'; return `<span class="badge ${cls} me-1 mb-1">${pEsc(k)}: ${counts[k]}人</span>`; }).join('')+'</div>';
}

function renderPlanTeams(){
    const summary=document.getElementById('planSummary'), container=document.getElementById('planTeamsContainer');
    if(!container) return;
    if(!_planMembers.length){ summary.innerHTML=''; container.innerHTML='<div class="text-center p-4 text-muted">参加者がいません。上の「開催タイミング」を設定すると、その時点の参加者が自動で対象になります。</div>'; return; }
    summary.innerHTML=planSummaryHtml();
    if(_planView==='teams') renderPlanCards(container); else renderPlanList(container);
}

function renderPlanList(container){
    const sorted=[..._planMembers].sort((a,b)=>{
        let c=planCompare(a,b,_planSort.primary.key); if(c!==0) return c*_planSort.primary.dir;
        if(_planSort.secondary.key){ c=planCompare(a,b,_planSort.secondary.key); if(c!==0) return c*_planSort.secondary.dir; }
        return (a.name_kana||a.name||'').localeCompare(b.name_kana||b.name||'','ja');
    });
    const rows=sorted.map((m,i)=>`
    <tr>
        <td>${i+1}</td>
        <td class="fw-semibold">${pEsc(m.name)}</td>
        <td>${pEsc(planGradeLabel(m.grade))}</td>
        <td>${pEsc(planGenderLabel(m.gender))}</td>
        <td class="small text-muted">${pEsc(m.faculty||'—')}</td>
        <td class="small text-muted">${pEsc(m.department||'—')}</td>
        <td style="width:90px;"><input type="number" class="form-control form-control-sm" min="1" value="${m.team_no??''}" placeholder="—" onchange="updatePlanTeamNo(${m.id},this.value)"></td>
    </tr>`).join('');
    container.innerHTML=`<table class="table table-hover mb-0 align-middle"><thead class="table-light"><tr><th>#</th><th>氏名</th><th>学年</th><th>性別</th><th>学部</th><th>学科</th><th>班番号</th></tr></thead><tbody>${rows}</tbody></table>`;
}

function renderPlanCards(container){
    const groups={}, unassigned=[];
    _planMembers.forEach(m=>{ if(m.team_no===null) unassigned.push(m); else (groups[m.team_no]=groups[m.team_no]||[]).push(m); });
    const teamNos=Object.keys(groups).map(Number).sort((a,b)=>a-b);
    if(!teamNos.length){ container.innerHTML='<div class="text-center p-4 text-muted">まだ班が割り当てられていません。「自動振り分け」または一覧ビューで班番号を入力してください。</div>'; return; }
    const moveOpts=(cur)=>{ let o=`<option value="" ${cur===null?'selected':''}>未</option>`; teamNos.forEach(n=>{ o+=`<option value="${n}" ${cur===n?'selected':''}>${n}班</option>`; }); return o; };
    const chip=(m)=>{ const gc=m.gender==='male'?'text-primary':(m.gender==='female'?'text-danger':'text-muted'); return `
        <div class="d-flex justify-content-between align-items-center py-1 px-2 border-bottom">
            <div class="text-truncate"><span class="fw-semibold">${pEsc(m.name)}</span><span class="small text-muted ms-1">${pEsc(planGradeLabel(m.grade))}</span><span class="small ${gc} ms-1">${planGenderLabel(m.gender)}</span></div>
            <select class="form-select form-select-sm" style="width:auto;" onchange="updatePlanTeamNo(${m.id},this.value)">${moveOpts(m.team_no)}</select>
        </div>`; };
    const breakdown=(ms)=>{ const males=ms.filter(m=>m.gender==='male').length, females=ms.filter(m=>m.gender==='female').length; const gc={}; ms.forEach(m=>{const g=planGradeLabel(m.grade); gc[g]=(gc[g]||0)+1;}); const gb=Object.keys(gc).sort((a,b)=>planGradeNum(a.replace('年','').replace('OB/OG','0'))-planGradeNum(b.replace('年','').replace('OB/OG','0'))).map(g=>`<span class="badge bg-light text-dark border me-1">${pEsc(g)}×${gc[g]}</span>`).join(''); return `<div class="small mt-1"><span class="text-primary">男${males}</span> / <span class="text-danger">女${females}</span> <span class="ms-2">${gb}</span></div>`; };
    let html='<div class="row g-3 p-3">';
    teamNos.forEach(no=>{ const color=PLAN_COLORS[(no-1)%PLAN_COLORS.length]; const ms=[...groups[no]].sort((a,b)=>(a.name_kana||a.name||'').localeCompare(b.name_kana||b.name||'','ja'));
        html+=`<div class="col-12 col-md-6 col-lg-4"><div class="card h-100 shadow-sm">
            <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:${color};"><span class="fw-bold fs-5">${no}班</span><span class="badge bg-light text-dark">${ms.length}人</span></div>
            <div class="card-body p-0">${ms.map(chip).join('')}</div>
            <div class="card-footer py-2" style="border-top:2px solid ${color};">${breakdown(ms)}</div>
        </div></div>`; });
    if(unassigned.length){ const ms=[...unassigned].sort((a,b)=>(a.name_kana||a.name||'').localeCompare(b.name_kana||b.name||'','ja'));
        html+=`<div class="col-12"><div class="card border-secondary"><div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center"><span class="fw-bold">未割り当て</span><span class="badge bg-light text-dark">${unassigned.length}人</span></div><div class="card-body p-0">${ms.map(chip).join('')}</div></div></div>`; }
    html+='</div>';
    container.innerHTML=html;
}

function updatePlanTeamNo(memberId,value){
    const m=_planMembers.find(x=>parseInt(x.id)===parseInt(memberId)); if(!m) return;
    m.team_no=(value===''||value===null)?null:parseInt(value);
    if(_planView==='teams') renderPlanTeams(); else { document.getElementById('planSummary').innerHTML=planSummaryHtml(); }
    schedulePlanSave();
}

/* ---- 自動保存 ---- */
let _planSaveTimer=null;
function setPlanSaveStatus(t,cls){ const el=document.getElementById('planSaveStatus'); if(el) el.innerHTML=`<span class="${cls||'text-muted'}">${pEsc(t)}</span>`; }
function schedulePlanSave(){ setPlanSaveStatus('未保存の変更があります…','text-muted'); clearTimeout(_planSaveTimer); _planSaveTimer=setTimeout(savePlanTeams,800); }
async function savePlanTeams(){
    if(_planSaveTimer){ clearTimeout(_planSaveTimer); _planSaveTimer=null; }
    if(!_planCurrentId) return;
    const assignments={}; _planMembers.forEach(m=>{ assignments[m.id]=m.team_no; });
    setPlanSaveStatus('保存中…','text-muted');
    try{
        const res=await fetch(`/api/plans/${_planCurrentId}/teams`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({assignments})});
        const data=await res.json();
        setPlanSaveStatus(data.success?'✓ 自動保存しました':'保存に失敗しました', data.success?'text-success':'text-danger');
    }catch(e){ setPlanSaveStatus('通信エラー（未保存）','text-danger'); }
}
</script>
