<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>使い方ガイド</h1>
        <a href="/dashboard" class="btn btn-outline-secondary">
            ← ダッシュボードに戻る
        </a>
    </div>

    <!-- 目次 -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <strong>目次</strong>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <ol>
                        <li><a href="#overview">アプリ概要</a></li>
                        <li><a href="#flow">基本的な流れ</a></li>
                        <li><a href="#create-camp">合宿を作成する</a></li>
                        <li><a href="#participants">参加者を登録する</a></li>
                        <li><a href="#partial">途中参加・途中抜けの設定</a></li>
                        <li><a href="#schedule">日程設定</a></li>
                        <li><a href="#expenses">雑費の登録</a></li>
                    </ol>
                </div>
                <div class="col-md-4">
                    <ol start="8">
                        <li><a href="#calculation">計算結果の確認</a></li>
                        <li><a href="#export">PDF/Excel出力</a></li>
                        <li><a href="#members">会員名簿管理</a></li>
                        <li><a href="#enrollment">入会フォーム</a></li>
                        <li><a href="#application">合宿申し込みフォーム</a></li>
                        <li><a href="#academic-years" class="text-primary"><strong>【NEW】年度管理</strong></a></li>
                    </ol>
                </div>
                <div class="col-md-4">
                    <ol start="14">
                        <li><a href="#events" class="text-primary"><strong>【NEW】企画管理</strong></a></li>
                        <li><a href="#collection" class="text-primary"><strong>【NEW】集金管理</strong></a></li>
                        <li><a href="#portal" class="text-primary"><strong>【NEW】会員ポータル</strong></a></li>
                        <li><a href="#expeditions" class="text-primary"><strong>【NEW】遠征管理</strong></a></li>
                        <li><a href="#faq">よくある質問</a></li>
                        <li><a href="#contact">お問い合わせ</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- 1. アプリ概要 -->
    <div class="card mb-4" id="overview">
        <div class="card-header">
            <h5 class="mb-0">1. アプリ概要</h5>
        </div>
        <div class="card-body">
            <p>このアプリは、サークルの合宿における費用計算を自動化するためのツールです。</p>

            <h6>主な機能</h6>
            <ul>
                <li><strong>参加者管理:</strong> CSV一括登録、学年・性別管理、途中参加・途中抜け対応</li>
                <li><strong>費用自動計算:</strong> 宿泊費、食事調整、バス代、施設利用料、雑費を自動計算</li>
                <li><strong>割り勘計算:</strong> 参加タイミングに応じた公平な割り勘</li>
                <li><strong>出力機能:</strong> PDF/Excel形式で精算表を出力</li>
            </ul>

            <h6>対応する費用項目</h6>
            <table class="table table-bordered table-sm">
                <thead class="table-light">
                    <tr>
                        <th>カテゴリ</th>
                        <th>項目</th>
                        <th>計算方法</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td rowspan="4">宿泊関連</td>
                        <td>宿泊費</td>
                        <td>1泊あたり × 宿泊数</td>
                    </tr>
                    <tr>
                        <td>入湯税</td>
                        <td>1泊あたり × 宿泊数</td>
                    </tr>
                    <tr>
                        <td>保険料</td>
                        <td>1人あたり固定</td>
                    </tr>
                    <tr>
                        <td>食事調整</td>
                        <td>追加/欠食を自動計算</td>
                    </tr>
                    <tr>
                        <td rowspan="2">交通費</td>
                        <td>バス代</td>
                        <td>利用者で均等割り</td>
                    </tr>
                    <tr>
                        <td>高速代</td>
                        <td>利用者で均等割り</td>
                    </tr>
                    <tr>
                        <td rowspan="3">施設利用料</td>
                        <td>コート代</td>
                        <td>参加者で均等割り</td>
                    </tr>
                    <tr>
                        <td>体育館代</td>
                        <td>参加者で均等割り</td>
                    </tr>
                    <tr>
                        <td>宴会場代</td>
                        <td>参加者で均等割り</td>
                    </tr>
                    <tr>
                        <td>その他</td>
                        <td>雑費</td>
                        <td>指定タイミングの参加者で割り勘</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. 基本的な流れ -->
    <div class="card mb-4" id="flow">
        <div class="card-header">
            <h5 class="mb-0">2. 基本的な流れ</h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-2">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">1</div>
                    <p class="mt-2 mb-0"><strong>合宿作成</strong></p>
                    <small class="text-muted">基本情報・費用設定</small>
                </div>
                <div class="col-md-1 d-flex align-items-center justify-content-center">
                    <span class="text-muted">→</span>
                </div>
                <div class="col-md-2">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">2</div>
                    <p class="mt-2 mb-0"><strong>参加者登録</strong></p>
                    <small class="text-muted">CSV一括登録</small>
                </div>
                <div class="col-md-1 d-flex align-items-center justify-content-center">
                    <span class="text-muted">→</span>
                </div>
                <div class="col-md-2">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">3</div>
                    <p class="mt-2 mb-0"><strong>途参途抜設定</strong></p>
                    <small class="text-muted">個別に編集</small>
                </div>
                <div class="col-md-1 d-flex align-items-center justify-content-center">
                    <span class="text-muted">→</span>
                </div>
                <div class="col-md-2">
                    <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">4</div>
                    <p class="mt-2 mb-0"><strong>計算・出力</strong></p>
                    <small class="text-muted">PDF/Excel</small>
                </div>
            </div>
            <hr>
            <p class="text-muted mb-0"><small>※ 日程設定・雑費登録はオプションです。必要に応じて設定してください。</small></p>
        </div>
    </div>

    <!-- 3. 合宿を作成する -->
    <div class="card mb-4" id="create-camp">
        <div class="card-header">
            <h5 class="mb-0">3. 合宿を作成する</h5>
        </div>
        <div class="card-body">
            <h6>3.1 新規作成</h6>
            <ol>
                <li>トップページの「+ 新規合宿作成」ボタンをクリック</li>
                <li>以下の項目を入力:
                    <ul>
                        <li><strong>合宿名:</strong> 例）2024年度春合宿</li>
                        <li><strong>開始日・終了日:</strong> 合宿の日程</li>
                        <li><strong>泊数:</strong> 宿泊数（3泊なら「3」）</li>
                    </ul>
                </li>
                <li>費用設定を入力（下記参照）</li>
                <li>「保存」をクリック</li>
            </ol>

            <h6 class="mt-4">3.2 費用設定項目</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>項目</th>
                            <th>説明</th>
                            <th>例</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1泊料金（3食付）</td>
                            <td>1泊あたりの宿泊費（朝・昼・夕食込み）</td>
                            <td>8,000円</td>
                        </tr>
                        <tr>
                            <td>入湯税</td>
                            <td>温泉地の場合、1泊あたりの入湯税</td>
                            <td>150円</td>
                        </tr>
                        <tr>
                            <td>保険料</td>
                            <td>1人あたりの保険料（宿泊数に関わらず固定）</td>
                            <td>500円</td>
                        </tr>
                        <tr>
                            <td>1日目昼食</td>
                            <td>1日目の昼食が費用計算に含まれるか</td>
                            <td>対象外（各自調達）</td>
                        </tr>
                        <tr>
                            <td>コート1面あたり料金</td>
                            <td>テニスコート1面1コマの料金</td>
                            <td>5,000円</td>
                        </tr>
                        <tr>
                            <td>体育館1コマあたり料金</td>
                            <td>体育館1コマの料金</td>
                            <td>3,000円</td>
                        </tr>
                        <tr>
                            <td>宴会場料金</td>
                            <td>宴会場使用時の1人あたり料金</td>
                            <td>500円</td>
                        </tr>
                        <tr>
                            <td>食事単価（追加/欠食）</td>
                            <td>食事を追加する場合と欠食する場合の金額</td>
                            <td>昼食: +990円 / -440円</td>
                        </tr>
                        <tr>
                            <td>バス代</td>
                            <td>大型バスの往復料金（総額）</td>
                            <td>160,000円</td>
                        </tr>
                        <tr>
                            <td>高速代</td>
                            <td>往路・復路それぞれの高速料金</td>
                            <td>往路15,000円 / 復路15,000円</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="alert alert-info mt-3">
                <strong>ポイント:</strong> 過去の合宿を「複製」すると、費用設定がコピーされます。日程と名前を変更するだけで新しい合宿を作成できます。
            </div>
        </div>
    </div>

    <!-- 4. 参加者を登録する -->
    <div class="card mb-4" id="participants">
        <div class="card-header">
            <h5 class="mb-0">4. 参加者を登録する</h5>
        </div>
        <div class="card-body">
            <h6>4.1 CSV一括登録（推奨）</h6>
            <p>多くの参加者を一度に登録できます。</p>
            <ol>
                <li>合宿詳細画面で「参加者管理」タブを選択</li>
                <li>「CSV一括登録」ボタンをクリック</li>
                <li>以下の形式でテキストを入力またはペースト</li>
                <li>「登録」をクリック</li>
            </ol>

            <h6 class="mt-3">CSV形式</h6>
            <pre class="bg-light p-3 border rounded"><code>山田太郎,1男
佐藤花子,2女
鈴木一郎,3男
田中美咲,4女
高橋健太,OB
渡辺さくら,OG</code></pre>

            <div class="row mt-3">
                <div class="col-md-6">
                    <h6>対応する形式</h6>
                    <ul>
                        <li><code>1男</code>, <code>2女</code>, <code>3男</code>, <code>4女</code></li>
                        <li><code>１男</code>, <code>２女</code>（全角数字）</li>
                        <li><code>1年男</code>, <code>2年女</code></li>
                        <li><code>OB</code>, <code>OG</code></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>登録時のデフォルト</h6>
                    <ul>
                        <li>参加開始: 1日目・往路バスから</li>
                        <li>離脱: 最終日・復路バスまで</li>
                        <li>バス利用: 往復とも利用</li>
                    </ul>
                </div>
            </div>

            <div class="alert alert-warning mt-3">
                <strong>注意:</strong> CSV登録後、途中参加・途中抜けの人は個別に編集してください。
            </div>

            <h6 class="mt-4">4.2 個別登録</h6>
            <p>1人ずつ登録する場合は「+ 参加者を追加」ボタンから登録できます。</p>

            <h6 class="mt-4">4.3 並び替え・検索</h6>
            <ul>
                <li><strong>検索:</strong> 名前で絞り込み</li>
                <li><strong>並び替え:</strong> 学年・性別・名前で複合ソート可能</li>
            </ul>
        </div>
    </div>

    <!-- 5. 途中参加・途中抜けの設定 -->
    <div class="card mb-4" id="partial">
        <div class="card-header">
            <h5 class="mb-0">5. 途中参加・途中抜けの設定</h5>
        </div>
        <div class="card-body">
            <h6>5.1 設定方法</h6>
            <ol>
                <li>参加者一覧で該当者の「編集」ボタンをクリック</li>
                <li>「参加開始」と「離脱」のタイミングを変更</li>
                <li>バス利用の有無を設定</li>
                <li>「保存」をクリック</li>
            </ol>

            <h6 class="mt-4">5.2 参加タイミングの選択肢</h6>
            <div class="row">
                <div class="col-md-6">
                    <h6>参加開始タイミング</h6>
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>選択肢</th>
                                <th>意味</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>往路バスから</td><td>1日目のみ選択可</td></tr>
                            <tr><td>午前から</td><td>午前の活動から参加</td></tr>
                            <tr><td>昼食から</td><td>昼食を食べてから参加</td></tr>
                            <tr><td>午後から</td><td>昼食後の活動から参加</td></tr>
                            <tr><td>夕食から</td><td>夕食を食べてから参加</td></tr>
                            <tr><td>夜から</td><td>夕食後から参加</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>離脱タイミング</h6>
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>選択肢</th>
                                <th>意味</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>朝食後</td><td>朝食を食べて離脱</td></tr>
                            <tr><td>午前まで</td><td>午前の活動後に離脱</td></tr>
                            <tr><td>昼食まで</td><td>昼食を食べて離脱</td></tr>
                            <tr><td>午後まで</td><td>午後の活動後に離脱</td></tr>
                            <tr><td>夕食まで</td><td>夕食を食べて離脱</td></tr>
                            <tr><td>夜まで</td><td>夜の活動後に離脱</td></tr>
                            <tr><td>復路バスまで</td><td>最終日のみ選択可</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <h6 class="mt-4">5.3 自動計算される項目</h6>
            <ul>
                <li><strong>宿泊数:</strong> 参加期間から自動計算</li>
                <li><strong>食事の追加/欠食:</strong> タイミングに応じて自動計算</li>
                <li><strong>施設利用料:</strong> 参加しているコマのみ対象</li>
            </ul>

            <div class="alert alert-info">
                <h6>食事計算のルール</h6>
                <p class="mb-0">1泊に含まれる食事は「<strong>その日の夕食 + 翌日の朝食 + 翌日の昼食</strong>」です。</p>
                <ul class="mb-0 mt-2">
                    <li>宿泊するのに該当の食事を食べない → <span class="text-danger">欠食（減額）</span></li>
                    <li>宿泊しないのに該当の食事を食べる → <span class="text-success">追加（加算）</span></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- 6. 日程設定 -->
    <div class="card mb-4" id="schedule">
        <div class="card-header">
            <h5 class="mb-0">6. 日程設定（オプション）</h5>
        </div>
        <div class="card-body">
            <p>施設利用料を計算する場合は、日程設定が必要です。</p>

            <h6>6.1 設定方法</h6>
            <ol>
                <li>合宿詳細画面で「日程設定」タブを選択</li>
                <li>各日・各時間帯の活動を設定:
                    <ul>
                        <li><strong>午前・午後:</strong> テニスコート / 体育館 / なし</li>
                        <li><strong>コート面数:</strong> 使用する面数（複数面の場合）</li>
                        <li><strong>宴会:</strong> 宴会場を使用するかどうか</li>
                    </ul>
                </li>
                <li>「保存」をクリック</li>
            </ol>

            <h6 class="mt-4">6.2 施設利用料の計算</h6>
            <ul>
                <li><strong>コート代:</strong> 1面あたり料金 × 面数 ÷ 参加者数</li>
                <li><strong>体育館代:</strong> 1コマあたり料金 ÷ 参加者数</li>
                <li><strong>宴会場代:</strong> 1人あたり料金（参加者のみ）</li>
            </ul>

            <div class="alert alert-secondary">
                <strong>注:</strong> 日程設定をしない場合、施設利用料は0円として計算されます。
            </div>
        </div>
    </div>

    <!-- 7. 雑費の登録 -->
    <div class="card mb-4" id="expenses">
        <div class="card-header">
            <h5 class="mb-0">7. 雑費の登録（オプション）</h5>
        </div>
        <div class="card-body">
            <p>飲み物代、買い出し費用など、その他の費用を登録できます。</p>

            <h6>7.1 登録方法</h6>
            <ol>
                <li>合宿詳細画面で「雑費管理」タブを選択</li>
                <li>タイムテーブル上で、該当する日・時間帯のセルをクリック</li>
                <li>項目名と金額を入力</li>
                <li>「追加」をクリック</li>
            </ol>

            <h6 class="mt-4">7.2 割り勘対象</h6>
            <p>雑費は、<strong>その時間帯に参加していた人</strong>で自動的に割り勘されます。</p>
            <div class="alert alert-info">
                <strong>例:</strong> 2日目の夜に飲み物代3,000円を登録した場合<br>
                → 2日目の夜に参加していた20人で割り勘 = 1人あたり150円
            </div>
        </div>
    </div>

    <!-- 8. 計算結果の確認 -->
    <div class="card mb-4" id="calculation">
        <div class="card-header">
            <h5 class="mb-0">8. 計算結果の確認</h5>
        </div>
        <div class="card-body">
            <h6>8.1 計算結果画面</h6>
            <ol>
                <li>合宿詳細画面で「計算結果を見る」ボタンをクリック</li>
                <li>以下の情報が表示されます:
                    <ul>
                        <li>合計金額・参加者数・平均金額</li>
                        <li>フル参加者の負担額と内訳</li>
                        <li>途中参加・途中抜けの個別負担額と内訳</li>
                    </ul>
                </li>
            </ol>

            <h6 class="mt-4">8.2 途参途抜一覧</h6>
            <p>「途参途抜一覧」ボタンをクリックすると、各参加者のスケジュールを一覧で確認できます。</p>
            <ul>
                <li>各日のスロット（往路、午前、午後、宴会、復路）ごとに○×で表示</li>
                <li>合計行には全参加者の参加人数を表示</li>
            </ul>
        </div>
    </div>

    <!-- 9. PDF/Excel出力 -->
    <div class="card mb-4" id="export">
        <div class="card-header">
            <h5 class="mb-0">9. PDF/Excel出力</h5>
        </div>
        <div class="card-body">
            <h6>9.1 出力方法</h6>
            <ol>
                <li>計算結果画面で「PDF出力」または「Excel出力」をクリック</li>
                <li>ファイルがダウンロードされます</li>
            </ol>

            <h6 class="mt-4">9.2 出力形式</h6>
            <div class="row">
                <div class="col-md-6">
                    <h6>PDF出力</h6>
                    <ul>
                        <li>印刷用のフォーマット</li>
                        <li>精算表として配布可能</li>
                        <li>途参途抜一覧も含む</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Excel出力（xlsx形式）</h6>
                    <ul>
                        <li>Excelで直接開けるxlsx形式</li>
                        <li>シート1: 精算表（フル参加・途中参加）</li>
                        <li>シート2: 途参途抜一覧（スケジュール表）</li>
                    </ul>
                </div>
            </div>

            <h6 class="mt-4">9.3 ファイル名</h6>
            <p>出力ファイルは以下の形式で命名されます：</p>
            <code>{合宿名}_参加費_{日時}.xlsx</code>
            <p class="text-muted mt-1"><small>例：2026春合宿_参加費_20260214_153045.xlsx</small></p>

            <h6 class="mt-4">9.4 出力内容</h6>
            <table class="table table-bordered table-sm">
                <thead class="table-light">
                    <tr>
                        <th>セクション</th>
                        <th>内容</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>フル参加者</td>
                        <td>1行にまとめて表示（負担額・カテゴリ別内訳）</td>
                    </tr>
                    <tr>
                        <td>途中参加・途中抜け</td>
                        <td>各人の参加期間・負担額・内訳を個別表示</td>
                    </tr>
                    <tr>
                        <td>途参途抜スケジュール一覧</td>
                        <td>各スロットの参加状況を○×で表示（Excel出力では別シート）</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 10. 会員名簿管理（NEW） -->
    <div class="card mb-4" id="members">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">10. 会員名簿管理 <span class="badge bg-light text-success">NEW</span></h5>
        </div>
        <div class="card-body">
            <p class="lead">会員情報を一元管理し、合宿申し込みと連携できます。</p>

            <h6 class="mt-4">会員の追加方法</h6>
            <ol>
                <li><strong>手動追加</strong>: 「会員を追加」ボタンから1人ずつ登録</li>
                <li><strong>CSVインポート</strong>: Excel/CSVファイルから一括登録</li>
                <li><strong>入会フォーム</strong>: 新入生が自分で申請（承認制）</li>
            </ol>

            <h6 class="mt-4">学籍番号の自動判定</h6>
            <p>学籍番号を入力すると、学部・入学年度・学科が自動で判定されます。</p>
            <div class="alert alert-info">
                <strong>例:</strong> 1Y23F158-5 を入力<br>
                → 先進理工学部、2023年入学、電気・情報生命工学科
            </div>

            <h6 class="mt-4">基幹理工学部の学系</h6>
            <ul>
                <li>2024年以前入学: 学系I、II、III</li>
                <li>2025年以降入学: 学系I、II、III、IV</li>
                <li>2年生以降は進振り後の学科を選択</li>
            </ul>

            <h6 class="mt-4">10月引退処理</h6>
            <p>10月に「10月引退処理」ボタンからB3（3年生）全員をOB/OGに変更できます。</p>
            <ul>
                <li>3年（男性）→ OB、3年（女性）→ OG</li>
            </ul>
            <div class="alert alert-warning">
                <strong>注意:</strong> この操作は元に戻せません。実行前にプレビューで確認してください。
            </div>
        </div>
    </div>

    <!-- 11. 入会フォーム（NEW） -->
    <div class="card mb-4" id="enrollment">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">11. 入会フォーム（新入生向け） <span class="badge bg-light text-success">NEW</span></h5>
        </div>
        <div class="card-body">
            <p class="lead">新入生が自分で会員情報を登録できる公開フォームです。</p>

            <h6 class="mt-4">URL</h6>
            <div class="alert alert-info">
                <code>/enroll</code>（認証不要、誰でもアクセス可能）
            </div>

            <h6 class="mt-4">申請の流れ</h6>
            <ol>
                <li>入会フォームにアクセス</li>
                <li>全項目を入力（学籍番号から学部・学科が自動判定）</li>
                <li>確認画面で内容を確認</li>
                <li>申請送信 → 「承認待ち」として登録</li>
                <li>管理者が <code>/members/pending</code> で承認</li>
                <li>ステータスが「現役」に変更され、合宿申し込みが可能に</li>
            </ol>
        </div>
    </div>

    <!-- 12. 合宿申し込みフォーム（NEW） -->
    <div class="card mb-4" id="application">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">12. 合宿申し込みフォーム <span class="badge bg-light text-success">NEW</span></h5>
        </div>
        <div class="card-body">
            <p class="lead">会員が専用URLから合宿に申し込むことができます。</p>

            <h6 class="mt-4">管理者：申し込みURL発行</h6>
            <ol>
                <li>合宿詳細画面で「申し込みURL」タブをクリック</li>
                <li>「申し込みURLを発行」ボタンをクリック</li>
                <li>発行されたURLを会員に共有（LINE、メール等）</li>
                <li>締切日時を設定可能</li>
                <li>有効/無効の切り替えが可能</li>
            </ol>

            <h6 class="mt-4">会員：申し込みの流れ</h6>
            <ol>
                <li>共有されたURLにアクセス（認証不要）</li>
                <li>名前で自分を検索</li>
                <li>自動入力された情報を確認</li>
                <li>参加日程を選択（全日程/途中参加/途中抜け）</li>
                <li>交通手段を選択（往路バス、復路バス）</li>
                <li>最終確認画面で内容を確認</li>
                <li>申し込み完了 → 参加者一覧に自動登録</li>
            </ol>

            <div class="alert alert-success">
                <strong>メリット:</strong> Google Formを使わずに直接参加者登録ができます。<br>
                従来の「Form → スプレッドシート → CSV → インポート」の手間が不要になります。
            </div>

            <h6 class="mt-4">重複申し込み</h6>
            <p>同じ会員が再度申し込むと、前の申し込みが上書きされます。</p>

            <h6 class="mt-4">名簿に登録されていない場合</h6>
            <p>「管理者に連絡してください」と表示されます。<br>
            入会フォームで申請し、承認後に申し込みが可能になります。</p>
        </div>
    </div>

    <!-- 13. 年度管理（NEW） -->
    <div class="card mb-4" id="academic-years">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">13. 年度管理 <span class="badge bg-light text-primary">NEW</span></h5>
        </div>
        <div class="card-body">
            <p class="lead">年度の作成・切り替え、入会受付の開閉を管理します。</p>

            <h6>13.1 年度の仕組み</h6>
            <ul>
                <li><strong>現在年度:</strong> 会員名簿やシステム全体で参照される「今の年度」。1つだけ設定可能。</li>
                <li><strong>入会受付中年度:</strong> 新規・継続入会フォームから申請を受け付ける年度。1つだけ設定可能。</li>
            </ul>

            <h6 class="mt-4">13.2 基本操作</h6>
            <ol>
                <li><strong>次年度の作成:</strong> 「次年度を作成」ボタンから新しい年度レコードを追加</li>
                <li><strong>現在年度の切り替え:</strong> 「現在の年度に設定」ボタンで切り替え</li>
                <li><strong>入会受付の開閉:</strong> 「入会受付を開始/終了」ボタンで切り替え</li>
            </ol>

            <div class="alert alert-warning">
                <strong>注意:</strong> 年度の切り替えは慎重に行ってください。現在年度が変わると、会員の学年表示などに影響が出る場合があります。
            </div>

            <h6 class="mt-4">13.3 年度と入会申請の関係</h6>
            <p>「入会受付中」に設定した年度に対して、入会フォーム・継続入会フォームからの申請が紐づきます。受付を開始する前に必ず設定してください。</p>
        </div>
    </div>

    <!-- 14. 企画管理（NEW） -->
    <div class="card mb-4" id="events">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">14. 企画管理 <span class="badge bg-light text-primary">NEW</span></h5>
        </div>
        <div class="card-body">
            <p class="lead">テニス大会・歓迎会など、合宿以外のイベントを管理します。</p>

            <h6>14.1 企画の作成</h6>
            <ol>
                <li>ダッシュボードまたはナビから「企画管理」をクリック</li>
                <li>「+ 新規企画作成」ボタンをクリック</li>
                <li>以下の項目を入力:
                    <ul>
                        <li><strong>タイトル</strong>（必須）</li>
                        <li><strong>開催日・開始時刻</strong></li>
                        <li><strong>場所・概要</strong></li>
                        <li><strong>参加費</strong>（0円でも入力可）</li>
                        <li><strong>定員</strong>（空欄 = 制限なし）</li>
                        <li><strong>申込期限</strong>（期限を過ぎると会員ページから自動的に非表示）</li>
                        <li><strong>キャンセル待ちを受け付けるか</strong></li>
                        <li><strong>公開するか</strong>（後から切り替え可）</li>
                    </ul>
                </li>
            </ol>

            <h6 class="mt-4">14.2 会員への公開</h6>
            <p>企画詳細ページで「会員ページに公開する」をONにすると、会員ホームページに表示されます。</p>
            <div class="alert alert-info">
                <strong>申込期限の動作:</strong> 期限日を過ぎると、公開設定に関わらず会員ページから自動で非表示になります。管理者画面では引き続き確認できます。
            </div>

            <h6 class="mt-4">14.3 定員とキャンセル待ち</h6>
            <table class="table table-bordered table-sm">
                <thead class="table-light">
                    <tr><th>状況</th><th>キャンセル待ちなし</th><th>キャンセル待ちあり</th></tr>
                </thead>
                <tbody>
                    <tr><td>定員に空きあり</td><td colspan="2" class="text-center">「申し込む」ボタンが表示</td></tr>
                    <tr><td>定員満員</td><td>「定員締め切り」と表示（申込不可）</td><td>「キャンセル待ちで申し込む」ボタンが表示</td></tr>
                </tbody>
            </table>

            <h6 class="mt-4">14.4 繰り上げ処理</h6>
            <p>参加確定者がキャンセルすると、キャンセル待ちの先頭の人が自動的に参加確定に繰り上がります。管理者の申込者一覧では<span class="badge bg-warning text-dark">繰り上げ</span>バッジで強調表示されます。</p>

            <h6 class="mt-4">14.5 費用計算（雑費管理）</h6>
            <p>企画詳細の「雑費」タブで費用を登録し、「費用計算」タブで確認できます。</p>
            <ul>
                <li>雑費の合計 ÷ 申込人数 = 1人あたりの雑費負担</li>
                <li>雑費負担 + 参加費 = 1人あたり合計</li>
            </ul>
        </div>
    </div>

    <!-- 15. 集金管理（NEW） -->
    <div class="card mb-4" id="collection">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">15. 集金管理 <span class="badge bg-light text-primary">NEW</span></h5>
        </div>
        <div class="card-body">
            <p class="lead">合宿費用の振り込み確認を、会員ポータルを通じてオンラインで管理できます。</p>

            <h6>15.1 集金開始の手順（管理者）</h6>
            <ol>
                <li>合宿詳細画面で「集金管理」タブをクリック</li>
                <li>「集金を開始する」セクションで以下を入力:
                    <ul>
                        <li><strong>基本金額:</strong> 全員への請求額（円）</li>
                        <li><strong>入金期限:</strong> 振り込みの締め切り日</li>
                    </ul>
                </li>
                <li>「集金開始」ボタンをクリック</li>
                <li>申し込み済み会員に自動でフォームが割り当てられます</li>
            </ol>

            <h6 class="mt-4">15.2 個別金額の設定</h6>
            <p>途中参加・途中抜けなど、基本金額と異なる請求額の参加者には個別金額を設定できます。</p>
            <ol>
                <li>集金管理タブの金額一覧テーブルで該当者の「金額」欄を入力</li>
                <li>空欄のままだと基本金額が適用されます</li>
            </ol>

            <h6 class="mt-4">15.3 振り込み確認フォーム（会員）</h6>
            <p>集金が開始されると、対象の会員の<strong>会員ホームページ</strong>に「振込確認フォーム」の通知カードが表示されます。</p>
            <ol>
                <li>「振込確認」ボタンをクリック</li>
                <li>振込金額・期限を確認</li>
                <li>振り込みを完了したら「振り込みを完了しました」チェックを入れる</li>
                <li>「提出する」ボタンをクリック</li>
            </ol>
            <div class="alert alert-warning mt-2">
                <strong>期限超過の場合:</strong> 期限を過ぎてから初めてフォームを開くと、「入金遅れの理由」入力欄が必須で表示されます。理由を入力の上、提出してください。
            </div>

            <h6 class="mt-4">15.4 通帳照合（管理者）</h6>
            <p>集金管理タブの「提出済みリスト」に、提出した会員のカタカナ氏名・金額・提出日時が表示されます。</p>
            <ul>
                <li>実際の通帳と照合しながら「確認済み」チェックボックスを入れる</li>
                <li>チェックを入れると、会員のフォーム画面に「通帳確認済み」と表示されます</li>
            </ul>

            <div class="alert alert-info">
                <strong>未提出リスト:</strong> 期限を過ぎても提出していない会員は「未提出リスト」に赤く表示されます。直接連絡してください。
            </div>
        </div>
    </div>

    <!-- 16. 会員ポータル（NEW） -->
    <div class="card mb-4" id="portal">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">16. 会員ポータル <span class="badge bg-light text-primary">NEW</span></h5>
        </div>
        <div class="card-body">
            <p class="lead">部員が自分でログインして使える会員向けページです。</p>

            <h6>16.1 アクセス方法</h6>
            <p>URL: <code>/portal</code>（認証不要。部員に共有してください）</p>
            <p>ポータルページから「会員ログイン」を選択し、<strong>学籍番号</strong>でログインします。</p>
            <div class="alert alert-info">
                ログインできるのは、管理者が承認した<strong>現役</strong>会員のみです。
            </div>

            <h6 class="mt-4">16.2 会員ホームで確認できること</h6>
            <table class="table table-bordered table-sm">
                <thead class="table-light">
                    <tr><th>セクション</th><th>内容</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="badge bg-danger">振込確認</span></td>
                        <td>未提出の集金フォームがある場合に表示。振り込み完了を報告する。</td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-warning text-dark">合宿申込受付中</span></td>
                        <td>現在申込受付中の合宿一覧。「申し込む」から申込フォームへ。</td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-primary">企画・イベント</span></td>
                        <td>公開中の企画一覧。ボタン1回で申し込み完了（名前検索不要）。</td>
                    </tr>
                </tbody>
            </table>

            <h6 class="mt-4">16.3 企画のワンクリック申込</h6>
            <p>合宿申込と異なり、企画への申し込みはログイン済みの学籍番号から個人を自動判別するため、<strong>名前を検索する手間なし</strong>でボタン1つで申し込みが完了します。</p>

            <h6 class="mt-4">16.4 継続入会フォーム</h6>
            <p>URL: <code>/renew</code>（認証不要）<br>
            昨年度会員が今年度の継続登録をするためのフォームです。学籍番号で自分を検索して情報を確認・更新します。</p>
        </div>
    </div>

    <!-- 17. 遠征管理（NEW） -->
    <div class="card mb-4" id="expeditions">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">17. 遠征管理 <span class="badge bg-light text-primary">NEW</span></h5>
        </div>
        <div class="card-body">
            <p class="lead">espajio OPENなどの対外試合遠征を管理します。参加者・車割・チーム分け・集金・しおりをまとめて管理できます。</p>

            <h6>17.1 遠征の作成</h6>
            <ol>
                <li>サイドバーまたはナビから「遠征管理」をクリック</li>
                <li>「+ 新規遠征作成」ボタンをクリック</li>
                <li>以下の項目を入力:
                    <ul>
                        <li><strong>遠征名</strong>（例：espajio OPEN 2025秋）</li>
                        <li><strong>開始日・終了日</strong></li>
                        <li><strong>参加費・前泊費用</strong>（円）</li>
                        <li><strong>場所・説明</strong></li>
                    </ul>
                </li>
                <li>「保存」をクリック</li>
            </ol>

            <h6 class="mt-4">17.2 タブ構成</h6>
            <table class="table table-bordered table-sm">
                <thead class="table-light">
                    <tr><th>タブ</th><th>内容</th></tr>
                </thead>
                <tbody>
                    <tr><td>基本情報</td><td>遠征の基本設定・編集</td></tr>
                    <tr><td>参加者管理</td><td>参加者の追加・編集・削除、Excel/PDF/名簿/エスパジオ登録ファイルの出力</td></tr>
                    <tr><td>車割</td><td>往路・復路の車割を手動または自動作成。住所から推奨下車駅を解析</td></tr>
                    <tr><td>チーム分け</td><td>対戦チームの編成・表示</td></tr>
                    <tr><td>申し込みURL</td><td>会員向け申し込みURLの発行・管理</td></tr>
                    <tr><td>集金管理</td><td>参加費の振り込み確認（合宿の集金管理と同様）</td></tr>
                    <tr><td>しおり</td><td>遠征しおりの自動生成・公開URL発行</td></tr>
                    <tr><td>レンタカー清算</td><td>レンタカー・ガソリン代の精算</td></tr>
                </tbody>
            </table>

            <h6 class="mt-4">17.3 車割</h6>
            <p>「往路を自動作成」「復路を自動作成」ボタンで、参加者の住所をもとに最適な車割を自動生成できます。</p>
            <ul>
                <li><strong>推奨下車駅の解析:</strong> 「解析」ボタンで各参加者の最寄り駅を自動判定</li>
                <li><strong>手動調整:</strong> ドラッグ＆ドロップで乗車者を変更可能</li>
                <li><strong>往路・復路別:</strong> 往路・復路それぞれ独立して設定可能</li>
            </ul>

            <h6 class="mt-4">17.4 しおり</h6>
            <p>遠征しおりを自動生成して参加者に公開URLで共有できます。</p>
            <ul>
                <li>基本情報・スケジュール・参加者名簿・車割が自動でまとめられます</li>
                <li>「公開URL」をコピーしてLINE等で参加者に共有</li>
            </ul>

            <h6 class="mt-4">17.5 遠征申し込みフォーム</h6>
            <p>合宿申し込みと同様に、会員が専用URLから申し込みできます。「申し込みURL」タブでURLを発行し、会員に共有してください。</p>
        </div>
    </div>

    <!-- 18. よくある質問 -->
    <div class="card mb-4" id="faq">
        <div class="card-header">
            <h5 class="mb-0">18. よくある質問</h5>
        </div>
        <div class="card-body">
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            Q: 食事の追加/欠食はどのように計算されますか？
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p><strong>A:</strong> 1泊に含まれる食事は「その日の夕食、翌日の朝食、翌日の昼食」です。</p>
                            <ul>
                                <li>宿泊するのに該当の食事を食べない → 欠食として減額</li>
                                <li>宿泊しないのに該当の食事を食べる → 追加として加算</li>
                            </ul>
                            <p class="mb-0">参加タイミングに応じて自動計算されます。</p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            Q: 端数はどうなりますか？
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p><strong>A:</strong> 割り勘計算は四捨五入されます。</p>
                            <p class="mb-0">合計の端数は会計担当が調整してください。</p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            Q: 過去の合宿を参考に新しい合宿を作りたい
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p><strong>A:</strong> 合宿一覧で「複製」ボタンをクリックしてください。</p>
                            <ul class="mb-0">
                                <li>費用設定・日程設定がコピーされます</li>
                                <li>参加者はコピーされません（個人情報保護のため）</li>
                                <li>複製後、合宿名と日程を編集してください</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                            Q: 参加者を間違えて登録してしまった
                        </button>
                    </h2>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p><strong>A:</strong> 参加者一覧で該当者の「編集」ボタンをクリックして修正できます。</p>
                            <p class="mb-0">削除する場合は、編集画面の「削除」ボタンをクリックしてください。</p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                            Q: バスを利用しない人がいる場合は？
                        </button>
                    </h2>
                    <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p><strong>A:</strong> 参加者編集画面で「往路バス利用」「復路バス利用」のチェックを外してください。</p>
                            <p class="mb-0">バス代・高速代はバス利用者のみで割り勘されます。</p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                            Q: レンタカーを出す場合は？
                        </button>
                    </h2>
                    <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p><strong>A:</strong> 合宿作成/編集画面で「レンタカーを追加する」にチェックを入れてください。</p>
                            <ul class="mb-0">
                                <li>レンタカー代・高速代を入力</li>
                                <li>参加者編集画面で「レンタカー利用」にチェックを入れた人で割り勘</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                            Q: OB/OGの区別はどうなっていますか？
                        </button>
                    </h2>
                    <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p><strong>A:</strong> 学年が「0」（OBOG）の場合、性別で区別されます。</p>
                            <ul class="mb-0">
                                <li>男性 → OB</li>
                                <li>女性 → OG</li>
                            </ul>
                            <p class="mt-2 mb-0">CSV登録時は「OB」「OG」と入力すれば自動で設定されます。</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- お問い合わせ -->
    <div class="card mb-4" id="contact">
        <div class="card-header">
            <h5 class="mb-0">お問い合わせ・トラブル発生時</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>開発・管理者</h6>
                    <p class="mb-1"><strong>Laissez-Faire T.C. 11th 幹事長 渡邉光悦</strong></p>
                    <ul class="list-unstyled text-muted">
                        <li><i class="bi bi-envelope"></i> <a href="mailto:kohetsu.watanabe@gmail.com">kohetsu.watanabe@gmail.com</a></li>
                        <li><i class="bi bi-envelope"></i> <a href="mailto:kohetsu.watanabe@etsu-dx.com">kohetsu.watanabe@etsu-dx.com</a></li>
                        <li><i class="bi bi-telephone"></i> 080-2671-9571</li>
                        <li><i class="bi bi-geo-alt"></i> 〒103-0014 東京都中央区日本橋蛎殻町1-22-1<br>
                            <span class="ms-4">デュークスカーラ日本橋1205号</span></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>ソースコード</h6>
                    <p class="text-muted">
                        このアプリはオープンソースで公開されています。<br>
                        バグ報告や機能リクエストはGitHub、メールの両方からお願いします。
                    </p>
                    <p>
                        <a href="https://github.com/Etsu5082/CampCalculator" target="_blank" class="btn btn-outline-dark btn-sm">
                            <i class="bi bi-github"></i> GitHub - CampCalculator
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- フッター -->
    <div class="text-center text-muted mb-4">
        <a href="/index.php?route=camps" class="btn btn-primary">合宿一覧に戻る</a>
    </div>
</div>

<!-- AIチャットボット（フローティング） -->
<div id="chatbot-container">
    <!-- 初回案内ツールチップ -->
    <div id="chatbot-tooltip" class="chatbot-tooltip">
        <span>使い方で困ったら質問してね！</span>
        <button type="button" class="chatbot-tooltip-close">&times;</button>
    </div>

    <!-- チャットボタン（ラベル付き） -->
    <div id="chatbot-toggle-wrapper" style="position: fixed; bottom: 20px; right: 20px; z-index: 1050;">
        <button id="chatbot-toggle" class="chatbot-button">
            <span class="chatbot-button-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M5 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
                    <path d="m2.165 15.803.02-.004c1.83-.363 2.948-.842 3.468-1.105A9.06 9.06 0 0 0 8 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6a10.437 10.437 0 0 1-.524 2.318l-.003.011a10.722 10.722 0 0 1-.244.637c-.079.186.074.394.273.362a21.673 21.673 0 0 0 .693-.125zm.8-3.108a1 1 0 0 0-.287-.801C1.618 10.83 1 9.468 1 8c0-3.192 3.004-6 7-6s7 2.808 7 6c0 3.193-3.004 6-7 6a8.06 8.06 0 0 1-2.088-.272 1 1 0 0 0-.711.074c-.387.196-1.24.57-2.634.893a10.97 10.97 0 0 0 .398-2z"/>
                </svg>
            </span>
            <span class="chatbot-button-label">AIに質問</span>
        </button>
    </div>

    <!-- チャットウィンドウ -->
    <div id="chatbot-window" class="card shadow-lg" style="display: none; position: fixed; bottom: 90px; right: 20px; width: 380px; height: 450px; z-index: 1050; min-width: 300px; min-height: 300px;">
        <!-- リサイズハンドル -->
        <div class="chatbot-resize-handle chatbot-resize-top"></div>
        <div class="chatbot-resize-handle chatbot-resize-left"></div>
        <div class="chatbot-resize-handle chatbot-resize-top-left"></div>
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span><strong>AIアシスタント</strong></span>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-light" id="chatbot-clear" title="会話履歴をクリア">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                    </svg>
                </button>
                <button type="button" class="btn-close btn-close-white" id="chatbot-close"></button>
            </div>
        </div>
        <div class="card-body p-0" style="flex: 1; overflow-y: auto;" id="chatbot-messages">
            <div class="p-3">
                <div class="alert alert-info mb-0" id="chatbot-welcome">
                    <small>
                        <strong>こんにちは！</strong><br>
                        合宿費用計算アプリについて質問があれば、お気軽にどうぞ。<br>
                        例：「食事の計算方法は？」「CSV登録の形式は？」
                    </small>
                </div>
            </div>
        </div>
        <div class="card-footer p-2">
            <div id="chatbot-disabled-notice" class="text-muted small text-center py-2" style="display: none;">
                AI機能は現在無効です
            </div>
            <form id="chatbot-form" class="d-flex gap-2">
                <input type="text" class="form-control" id="chatbot-input" placeholder="質問を入力..." maxlength="500" style="font-size: 16px;">
                <button type="submit" class="btn btn-primary" id="chatbot-submit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576 6.636 10.07Zm6.787-8.201L1.591 6.602l4.339 2.76 7.494-7.493Z"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<style>
/* チャットボタン - 目立つデザイン */
.chatbot-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 50px;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    cursor: pointer;
    transition: all 0.3s ease;
    animation: pulse 2s infinite;
    font-weight: bold;
    font-size: 15px;
}
.chatbot-button:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}
.chatbot-button-icon {
    display: flex;
    align-items: center;
    justify-content: center;
}
.chatbot-button-label {
    white-space: nowrap;
}
@keyframes pulse {
    0% {
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
    50% {
        box-shadow: 0 4px 25px rgba(102, 126, 234, 0.7), 0 0 0 10px rgba(102, 126, 234, 0.1);
    }
    100% {
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
}

/* 初回案内ツールチップ */
.chatbot-tooltip {
    position: fixed;
    bottom: 80px;
    right: 20px;
    background: #333;
    color: white;
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 14px;
    z-index: 1051;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    animation: bounce 0.5s ease, fadeIn 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
}
.chatbot-tooltip::after {
    content: '';
    position: absolute;
    bottom: -8px;
    right: 30px;
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid #333;
}
.chatbot-tooltip-close {
    background: none;
    border: none;
    color: #999;
    font-size: 18px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}
.chatbot-tooltip-close:hover {
    color: white;
}
@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* メッセージスタイル */
#chatbot-messages .message {
    margin-bottom: 12px;
    padding: 8px 12px;
    border-radius: 12px;
    max-width: 85%;
    word-wrap: break-word;
}
#chatbot-messages .message.user {
    background-color: #0d6efd;
    color: white;
    margin-left: auto;
}
#chatbot-messages .message.assistant {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
}
#chatbot-messages .message.error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}
#chatbot-messages .typing-indicator {
    display: flex;
    gap: 4px;
    padding: 12px;
}
#chatbot-messages .typing-indicator span {
    width: 8px;
    height: 8px;
    background-color: #6c757d;
    border-radius: 50%;
    animation: typing 1.4s infinite ease-in-out;
}
#chatbot-messages .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
#chatbot-messages .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
@keyframes typing {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.5; }
    40% { transform: scale(1); opacity: 1; }
}

/* チャットウィンドウのリサイズ */
#chatbot-window {
    display: flex;
    flex-direction: column;
}
.chatbot-resize-handle {
    position: absolute;
    z-index: 10;
}
.chatbot-resize-top {
    top: 0;
    left: 10px;
    right: 10px;
    height: 6px;
    cursor: n-resize;
}
.chatbot-resize-left {
    left: 0;
    top: 10px;
    bottom: 10px;
    width: 6px;
    cursor: w-resize;
}
.chatbot-resize-top-left {
    top: 0;
    left: 0;
    width: 12px;
    height: 12px;
    cursor: nw-resize;
}
.chatbot-resize-handle:hover {
    background: rgba(102, 126, 234, 0.3);
}

/* スマホ対応 */
@media (max-width: 480px) {
    #chatbot-window {
        width: calc(100vw - 20px) !important;
        right: 10px !important;
        bottom: 80px !important;
        max-height: 70vh !important;
    }
    .chatbot-resize-handle {
        display: none;
    }
    #chatbot-toggle-wrapper {
        right: 10px !important;
        bottom: 10px !important;
    }
    .chatbot-tooltip {
        right: 10px !important;
        bottom: 70px !important;
        max-width: calc(100vw - 40px);
    }
    .chatbot-button {
        padding: 10px 16px;
        font-size: 14px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('chatbot-toggle');
    const window_ = document.getElementById('chatbot-window');
    const close = document.getElementById('chatbot-close');
    const form = document.getElementById('chatbot-form');
    const input = document.getElementById('chatbot-input');
    const messages = document.getElementById('chatbot-messages');
    const disabledNotice = document.getElementById('chatbot-disabled-notice');
    const submitBtn = document.getElementById('chatbot-submit');
    const tooltip = document.getElementById('chatbot-tooltip');
    const tooltipClose = document.querySelector('.chatbot-tooltip-close');

    let isEnabled = false;
    const HISTORY_KEY = 'chatbot_history';
    const MAX_HISTORY = 50; // 最大保存メッセージ数
    const SIZE_KEY = 'chatbot_size'; // ウィンドウサイズ保存用

    // リサイズ機能
    const resizeTop = document.querySelector('.chatbot-resize-top');
    const resizeLeft = document.querySelector('.chatbot-resize-left');
    const resizeTopLeft = document.querySelector('.chatbot-resize-top-left');

    let isResizing = false;
    let resizeType = '';
    let startX, startY, startWidth, startHeight, startBottom, startRight;

    function saveWindowSize() {
        try {
            localStorage.setItem(SIZE_KEY, JSON.stringify({
                width: window_.offsetWidth,
                height: window_.offsetHeight
            }));
        } catch (e) {}
    }

    function loadWindowSize() {
        try {
            const saved = localStorage.getItem(SIZE_KEY);
            if (saved) {
                const size = JSON.parse(saved);
                window_.style.width = size.width + 'px';
                window_.style.height = size.height + 'px';
            }
        } catch (e) {}
    }

    function startResize(e, type) {
        isResizing = true;
        resizeType = type;
        startX = e.clientX;
        startY = e.clientY;
        startWidth = window_.offsetWidth;
        startHeight = window_.offsetHeight;
        startBottom = parseInt(window_.style.bottom) || 90;
        startRight = parseInt(window_.style.right) || 20;
        document.body.style.userSelect = 'none';
        e.preventDefault();
    }

    function doResize(e) {
        if (!isResizing) return;

        const minWidth = 300;
        const minHeight = 300;
        const maxWidth = window.innerWidth - 40;
        const maxHeight = window.innerHeight - 120;

        if (resizeType === 'left' || resizeType === 'top-left') {
            const deltaX = startX - e.clientX;
            let newWidth = startWidth + deltaX;
            newWidth = Math.max(minWidth, Math.min(maxWidth, newWidth));
            window_.style.width = newWidth + 'px';
        }

        if (resizeType === 'top' || resizeType === 'top-left') {
            const deltaY = startY - e.clientY;
            let newHeight = startHeight + deltaY;
            newHeight = Math.max(minHeight, Math.min(maxHeight, newHeight));
            window_.style.height = newHeight + 'px';
        }
    }

    function stopResize() {
        if (isResizing) {
            isResizing = false;
            document.body.style.userSelect = '';
            saveWindowSize();
        }
    }

    resizeTop.addEventListener('mousedown', (e) => startResize(e, 'top'));
    resizeLeft.addEventListener('mousedown', (e) => startResize(e, 'left'));
    resizeTopLeft.addEventListener('mousedown', (e) => startResize(e, 'top-left'));
    document.addEventListener('mousemove', doResize);
    document.addEventListener('mouseup', stopResize);

    // タッチデバイス対応
    resizeTop.addEventListener('touchstart', (e) => {
        const touch = e.touches[0];
        startResize({ clientX: touch.clientX, clientY: touch.clientY, preventDefault: () => {} }, 'top');
    });
    resizeLeft.addEventListener('touchstart', (e) => {
        const touch = e.touches[0];
        startResize({ clientX: touch.clientX, clientY: touch.clientY, preventDefault: () => {} }, 'left');
    });
    resizeTopLeft.addEventListener('touchstart', (e) => {
        const touch = e.touches[0];
        startResize({ clientX: touch.clientX, clientY: touch.clientY, preventDefault: () => {} }, 'top-left');
    });
    document.addEventListener('touchmove', (e) => {
        if (isResizing) {
            const touch = e.touches[0];
            doResize({ clientX: touch.clientX, clientY: touch.clientY });
        }
    });
    document.addEventListener('touchend', stopResize);

    // 会話履歴をローカルストレージから読み込む
    function loadHistory() {
        try {
            const saved = localStorage.getItem(HISTORY_KEY);
            return saved ? JSON.parse(saved) : [];
        } catch (e) {
            return [];
        }
    }

    // 会話履歴をローカルストレージに保存
    function saveHistory(history) {
        try {
            // 最大数を超えたら古いものから削除
            if (history.length > MAX_HISTORY) {
                history = history.slice(-MAX_HISTORY);
            }
            localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
        } catch (e) {
            console.error('Failed to save chat history:', e);
        }
    }

    // 履歴にメッセージを追加
    function addToHistory(text, type) {
        const history = loadHistory();
        history.push({
            text: text,
            type: type,
            timestamp: new Date().toISOString()
        });
        saveHistory(history);
    }

    // 履歴をクリア
    function clearHistory() {
        localStorage.removeItem(HISTORY_KEY);
    }

    // 保存された履歴からメッセージを復元
    function restoreMessages() {
        const history = loadHistory();
        if (history.length === 0) return;

        // ウェルカムメッセージを削除
        const welcome = document.getElementById('chatbot-welcome');
        if (welcome) welcome.remove();

        // 履歴からメッセージを表示
        history.forEach(item => {
            const div = document.createElement('div');
            div.className = 'message ' + item.type;
            div.innerHTML = item.text.replace(/\n/g, '<br>');

            const container = document.createElement('div');
            container.className = 'p-3 pt-0';
            container.appendChild(div);

            messages.appendChild(container);
        });

        messages.scrollTop = messages.scrollHeight;
    }

    // ツールチップの表示制御（初回のみ表示）
    if (sessionStorage.getItem('chatbot_tooltip_closed')) {
        tooltip.style.display = 'none';
    }

    // ツールチップを閉じる
    tooltipClose.addEventListener('click', function() {
        tooltip.style.display = 'none';
        sessionStorage.setItem('chatbot_tooltip_closed', 'true');
    });

    // チャットボットの状態を確認
    async function checkStatus() {
        try {
            const res = await fetch('/index.php?route=api/chatbot/status');
            const data = await res.json();
            isEnabled = data.success && data.data.enabled;

            if (!isEnabled) {
                disabledNotice.style.display = 'block';
                form.style.display = 'none';
            }
        } catch (e) {
            console.error('Chatbot status check failed:', e);
        }
    }

    // トグル
    toggle.addEventListener('click', function() {
        // ツールチップを非表示
        tooltip.style.display = 'none';
        sessionStorage.setItem('chatbot_tooltip_closed', 'true');

        const isVisible = window_.style.display !== 'none';
        window_.style.display = isVisible ? 'none' : 'flex';
        if (!isVisible) {
            loadWindowSize(); // 保存されたサイズを復元
            input.focus();
            checkStatus();
        }
    });

    // 閉じる
    close.addEventListener('click', function() {
        window_.style.display = 'none';
    });

    // 履歴クリア
    const clearBtn = document.getElementById('chatbot-clear');
    clearBtn.addEventListener('click', function() {
        if (confirm('会話履歴をクリアしますか？')) {
            clearHistory();
            // メッセージエリアをリセット
            messages.innerHTML = `
                <div class="p-3">
                    <div class="alert alert-info mb-0" id="chatbot-welcome">
                        <small>
                            <strong>こんにちは！</strong><br>
                            合宿費用計算アプリについて質問があれば、お気軽にどうぞ。<br>
                            例：「食事の計算方法は？」「CSV登録の形式は？」
                        </small>
                    </div>
                </div>
            `;
        }
    });

    // メッセージ追加
    function addMessage(text, type, saveToHistory = true) {
        const welcome = document.getElementById('chatbot-welcome');
        if (welcome) welcome.remove();

        const div = document.createElement('div');
        div.className = 'message ' + type;
        div.innerHTML = text.replace(/\n/g, '<br>');

        const container = document.createElement('div');
        container.className = 'p-3 pt-0';
        container.appendChild(div);

        messages.appendChild(container);
        messages.scrollTop = messages.scrollHeight;

        // 履歴に保存（エラーメッセージは保存しない）
        if (saveToHistory && type !== 'error') {
            addToHistory(text, type);
        }
    }

    // ローディング表示
    function showTyping() {
        const div = document.createElement('div');
        div.className = 'typing-indicator';
        div.id = 'typing-indicator';
        div.innerHTML = '<span></span><span></span><span></span>';

        const container = document.createElement('div');
        container.className = 'p-3 pt-0';
        container.appendChild(div);

        messages.appendChild(container);
        messages.scrollTop = messages.scrollHeight;
    }

    function hideTyping() {
        const indicator = document.getElementById('typing-indicator');
        if (indicator) indicator.parentElement.remove();
    }

    // 送信
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const question = input.value.trim();
        if (!question || !isEnabled) return;

        addMessage(question, 'user');
        input.value = '';
        input.disabled = true;
        submitBtn.disabled = true;
        showTyping();

        try {
            // 過去の会話履歴を取得（最新10件まで）
            const history = loadHistory().slice(-10).map(item => ({
                role: item.type === 'user' ? 'user' : 'assistant',
                content: item.text
            }));

            const res = await fetch('/index.php?route=api/chatbot/ask', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question, history })
            });

            const data = await res.json();
            hideTyping();

            if (data.success) {
                addMessage(data.data.answer, 'assistant');
            } else {
                addMessage(data.error || 'エラーが発生しました', 'error');
            }
        } catch (e) {
            hideTyping();
            addMessage('通信エラーが発生しました', 'error');
        }

        input.disabled = false;
        submitBtn.disabled = false;
        input.focus();
    });

    // 初期状態チェック
    checkStatus();

    // 保存された会話履歴を復元
    restoreMessages();
});
</script>
