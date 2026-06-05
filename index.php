<?php
/**
 * 合宿費用計算アプリ - エントリーポイント
 */

// エラー表示設定（本番環境ではOFFに）
ini_set('display_errors', 1);
error_reporting(E_ALL);

// セッション開始
session_start();

// 文字コード設定
mb_internal_encoding('UTF-8');
header('Content-Type: text/html; charset=UTF-8');

// 定数定義
define('BASE_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . '/config');
define('SRC_PATH', BASE_PATH . '/src');
define('VIEWS_PATH', BASE_PATH . '/views');

// Composer オートロード（PHPMailer等）
require_once BASE_PATH . '/vendor/autoload.php';

// オートロード
spl_autoload_register(function ($class) {
    $paths = [
        SRC_PATH . '/Core/',
        SRC_PATH . '/Controllers/',
        SRC_PATH . '/Models/',
        SRC_PATH . '/Services/',
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// 設定ファイル読み込み
require_once CONFIG_PATH . '/database.php';
require_once CONFIG_PATH . '/app.php';

// データベース接続
$db = Database::getInstance();

// ルーター初期化・実行
$router = new Router();

// 認証ルート
$router->post('/api/auth/login', 'AuthController@login');
$router->post('/api/auth/logout', 'AuthController@logout');
$router->get('/api/auth/check', 'AuthController@check');

// 幹部Googleログイン
$router->get('/admin/oauth/{provider}/start', 'AdminPortalController@oauthStart');
$router->get('/admin/oauth/{provider}/callback', 'AdminPortalController@oauthCallback');

// 幹部・権限管理（Admin限定）
$router->get('/admin-users', 'AdminUserController@indexPage');
$router->get('/api/admin-users', 'AdminUserController@index');
$router->post('/api/admin-users', 'AdminUserController@store');
$router->put('/api/admin-users/{id}', 'AdminUserController@update');
$router->delete('/api/admin-users/{id}', 'AdminUserController@destroy');

// 合宿ルート
$router->get('/api/camps', 'CampController@index');
$router->post('/api/camps', 'CampController@store');
$router->get('/api/camps/{id}', 'CampController@show');
$router->put('/api/camps/{id}', 'CampController@update');
$router->delete('/api/camps/{id}', 'CampController@destroy');
$router->post('/api/camps/{id}/duplicate', 'CampController@duplicate');

// 合宿申し込みURL管理
$router->get('/api/camps/{id}/application-url', 'CampController@getApplicationUrl');
$router->post('/api/camps/{id}/application-url', 'CampController@generateApplicationUrl');
$router->put('/api/camps/{id}/application-url', 'CampController@updateApplicationUrl');
$router->get('/api/camps/{id}/applications', 'CampController@getApplications');
$router->post('/api/applications/{id}/apply-member-edit', 'CampController@applyMemberEdit');

// タイムスロットルート
$router->get('/api/camps/{id}/time-slots', 'TimeSlotController@index');
$router->put('/api/camps/{id}/time-slots', 'TimeSlotController@update');

// 参加者ルート
$router->get('/api/camps/{id}/participants', 'ParticipantController@index');
$router->post('/api/camps/{id}/participants', 'ParticipantController@store');
$router->post('/api/camps/{id}/participants/import', 'ParticipantController@importCsv');
$router->post('/api/camps/{id}/participants/check-duplicate', 'ParticipantController@checkDuplicate');
$router->delete('/api/camps/{id}/participants/deleteAll', 'ParticipantController@deleteAll');
$router->put('/api/participants/{id}', 'ParticipantController@update');
$router->delete('/api/participants/{id}', 'ParticipantController@destroy');

// 雑費ルート
$router->get('/api/camps/{id}/expenses', 'ExpenseController@index');
$router->post('/api/camps/{id}/expenses', 'ExpenseController@store');
$router->put('/api/expenses/{id}', 'ExpenseController@update');
$router->delete('/api/expenses/{id}', 'ExpenseController@destroy');

// 計算・出力ルート
$router->get('/api/camps/{id}/calculate', 'CalculationController@calculate');
$router->get('/api/camps/{id}/partial-schedule', 'CalculationController@partialSchedule');
$router->get('/api/camps/{id}/export/pdf', 'ExportController@pdf');
$router->get('/api/camps/{id}/export/excel', 'ExportController@excel');
$router->get('/api/camps/{id}/export/xlsx', 'ExportController@xlsx');
$router->get('/api/camps/{id}/export/insurance-roster', 'ExportController@insuranceRoster');
$router->get('/api/camps/{id}/export/participant-roster-cosmo', 'ExportController@participantRosterCosmo');
$router->get('/api/camps/{id}/export/headcount-report', 'ExportController@headcountReport');
$router->get('/api/camps/{id}/export/headcount-report-mycom', 'ExportController@headcountReportMycom');
$router->get('/api/camps/{id}/export/allergy-list', 'ExportController@allergyList');
$router->get('/api/camps/{id}/export/activity-meibo', 'ExportController@activityMeibo');

// チャットボットルート
$router->get('/api/chatbot/status', 'ChatbotController@status');
$router->post('/api/chatbot/ask', 'ChatbotController@ask');

// システム設定ルート
$router->get('/settings', 'SystemSettingsController@indexPage');
$router->get('/api/system-settings', 'SystemSettingsController@get');
$router->put('/api/system-settings', 'SystemSettingsController@update');

// パスワード変更ルート
$router->post('/api/auth/change-password', 'AuthController@changePassword');

// PDF読み取りルート
$router->get('/pdf/upload', 'PdfUploadController@index');
$router->post('/pdf/upload', 'PdfUploadController@upload');
$router->get('/pdf/review', 'PdfUploadController@review');
$router->post('/pdf/apply', 'PdfUploadController@apply');
$router->get('/pdf/cancel', 'PdfUploadController@cancel');

// 企画管理ルート（管理者用）
$router->get('/api/events', 'EventController@index');
$router->post('/api/events', 'EventController@store');
$router->get('/api/events/{id}', 'EventController@show');
$router->put('/api/events/{id}', 'EventController@update');
$router->delete('/api/events/{id}', 'EventController@destroy');
$router->post('/api/events/{id}/toggle-active', 'EventController@toggleActive');
$router->get('/api/events/{id}/applications', 'EventController@getApplications');
$router->post('/api/events/{id}/expenses', 'EventController@storeExpense');
$router->get('/api/events/{id}/calculate', 'EventController@calculate');
$router->post('/api/event-applications/{id}/cancel', 'EventController@cancelApplication');

// 班決め（グループ分け）API
$router->get('/api/events/{id}/teams', 'EventController@getTeams');
$router->post('/api/events/{id}/teams', 'EventController@saveTeams');
$router->post('/api/events/{id}/constraints', 'EventController@addTeamConstraint');
$router->delete('/api/events/{id}/constraints/{cid}', 'EventController@deleteTeamConstraint');

$router->put('/api/event-expenses/{id}', 'EventController@updateExpense');
$router->delete('/api/event-expenses/{id}', 'EventController@destroyExpense');

// 企画申込URLトークン管理API（固定パスを {id} ルートより前に登録）
$router->get('/api/events/{id}/token', 'EventController@getToken');
$router->post('/api/events/{id}/token', 'EventController@generateToken');
$router->delete('/api/events/{id}/token', 'EventController@deleteToken');

// 企画公開申込ページ（固定パスを先に登録）
$router->get('/apply/event/{token}/confirm', 'EventApplicationController@confirm');
$router->get('/apply/event/{token}/complete', 'EventApplicationController@complete');
$router->post('/api/apply/event/{token}', 'EventApplicationController@apply');
$router->get('/apply/event/{token}', 'EventApplicationController@form');

// 企画管理ページルート
$router->get('/events', 'EventController@indexPage');
$router->get('/events/{id}', 'EventController@detailPage');

// HP管理ルート（管理者用）
$router->get('/hp', 'HpController@indexPage');
$router->get('/api/hp/public', 'HpController@publicContent');
$router->put('/api/hp/settings', 'HpController@updateSettings');
$router->post('/api/hp/news', 'HpController@storeNews');
$router->put('/api/hp/news/{id}', 'HpController@updateNews');
$router->delete('/api/hp/news/{id}', 'HpController@destroyNews');
$router->put('/api/hp/schedule/{id}', 'HpController@updateSchedule');
$router->post('/api/hp/upload', 'HpController@uploadImage');

// 会員管理ルート（新規）
// 注意: 固定パスを先に登録（{id}パラメータより前）
$router->get('/api/members', 'MemberController@index');
$router->get('/api/members/pending', 'MemberController@pending');
$router->get('/api/members/pending/count', 'MemberController@pendingCount');
$router->get('/api/members/parse-student-id', 'EnrollmentController@parseStudentId');
$router->get('/api/members/export', 'MemberController@exportExcel');
$router->post('/api/members', 'MemberController@store');
$router->post('/api/members/import', 'MemberController@import');
// パラメータ付きルートは後ろに配置
$router->get('/api/members/{id}', 'MemberController@show');
$router->put('/api/members/{id}', 'MemberController@update');
$router->delete('/api/members/{id}', 'MemberController@destroy');
$router->post('/api/members/{id}/approve', 'MemberController@approve');
$router->post('/api/members/{id}/reject', 'MemberController@reject');
$router->delete('/api/members/{id}/oauth', 'MemberController@unlinkOauth');

// 年度管理ルート
$router->get('/api/academic-years', 'AcademicYearController@index');
$router->get('/api/academic-years/current', 'AcademicYearController@getCurrent');
$router->post('/api/academic-years', 'AcademicYearController@store');
$router->post('/api/academic-years/create-next', 'AcademicYearController@createNext');
$router->post('/api/academic-years/set-current', 'AcademicYearController@setCurrent');
$router->post('/api/academic-years/set-enrollment-open', 'AcademicYearController@setEnrollmentOpen');
$router->post('/api/academic-years/set-enrollment-deadline', 'AcademicYearController@setEnrollmentDeadline');
$router->post('/api/academic-years/set-enroll-open', 'AcademicYearController@setEnrollOpen');
$router->post('/api/academic-years/set-renew-open', 'AcademicYearController@setRenewOpen');

// ページルート（HTML表示）
$router->get('/', 'HomeController@index');
$router->get('/dashboard', 'PageController@index');
$router->get('/login', 'PageController@login');
$router->get('/camps', 'PageController@camps');
$router->get('/camps/{id}', 'PageController@campDetail');
$router->get('/camps/{id}/result', 'PageController@result');
$router->get('/camps/{id}/partial-schedule', 'PageController@partialSchedule');
$router->get('/guide', 'PageController@guide');
$router->get('/members', 'MemberController@indexPage');
$router->get('/members/pending', 'MemberController@pendingPage');
$router->get('/academic-years', 'AcademicYearController@indexPage');

// 会員ポータルルート（公開ページ）
$router->get('/portal', 'PortalController@index');

// 会員ログインルート
$router->get('/member/login', 'MemberPortalController@loginPage');
$router->post('/api/member/login', 'MemberPortalController@login');
$router->get('/member/home', 'MemberPortalController@home');
$router->post('/api/member/logout', 'MemberPortalController@logout');

// 会員OAuth2.0（希望者のみ・Google/LINE連携ログイン）
$router->get('/member/oauth/{provider}/start', 'MemberPortalController@oauthStart');
$router->get('/member/oauth/{provider}/callback', 'MemberPortalController@oauthCallback');
$router->post('/api/member/oauth/{provider}/unlink', 'MemberPortalController@oauthUnlink');

// 会員向け企画申し込みルート
$router->post('/api/member/events/{id}/apply', 'MemberPortalController@applyEvent');
$router->delete('/api/member/events/{id}/apply', 'MemberPortalController@cancelEvent');

// 入会フォームルート（公開ページ）
$router->get('/enroll', 'EnrollmentController@form');
$router->post('/enroll', 'EnrollmentController@submit');
$router->get('/enroll/confirm', 'EnrollmentController@confirm');
$router->get('/enroll/complete', 'EnrollmentController@complete');

// 継続入会フォームルート（公開ページ）
$router->get('/renew/confirm', 'RenewalController@confirm');
$router->get('/renew/review', 'RenewalController@review');
$router->post('/api/renew/submit', 'RenewalController@submit');
$router->get('/renew/complete', 'RenewalController@complete');

// 合宿申し込みルート（公開・認証不要）
$router->get('/apply/{token}', 'ApplicationController@form');
$router->get('/apply/{token}/search', 'ApplicationController@searchMembers');
$router->get('/apply/{token}/confirm', 'ApplicationController@confirmInfo');
$router->get('/apply/{token}/schedule', 'ApplicationController@schedule');
$router->get('/apply/{token}/review', 'ApplicationController@review');
$router->post('/apply/{token}/submit', 'ApplicationController@submit');
$router->get('/apply/{token}/complete', 'ApplicationController@complete');

// 集金管理ルート
$router->get('/api/camps/{id}/collection', 'CollectionController@get');
$router->post('/api/camps/{id}/collection', 'CollectionController@store');
$router->put('/api/camps/{id}/collection', 'CollectionController@update');
$router->delete('/api/camps/{id}/collection', 'CollectionController@destroy');
$router->put('/api/collection-items/{id}', 'CollectionController@updateItem');
$router->post('/api/collection-items/{id}/confirm', 'CollectionController@toggleConfirm');

// 会員プロフィール編集ルート
$router->get('/member/profile', 'MemberPortalController@profilePage');
$router->put('/api/member/profile', 'MemberPortalController@updateProfile');

// 会員変更通知既読ルート（管理者）
$router->post('/api/member-change-notifications/{id}/read', 'PageController@dismissChangeNotification');

// 会員向け集金ルート（合宿）
$router->get('/api/member/collections', 'MemberPortalController@myCollections');
$router->get('/member/collection/{id}', 'MemberPortalController@collectionForm');
$router->post('/api/member/collection-items/{id}/submit', 'MemberPortalController@submitCollection');

// 会員向け集金ルート（遠征）
$router->get('/member/expedition-collection/{id}', 'MemberPortalController@expeditionCollectionForm');
$router->post('/api/member/expedition-collection-items/{id}/submit', 'MemberPortalController@submitExpeditionCollection');

// 入会管理ページ（年度管理＋入会金管理＋新規入会者リスト統合）
$router->get('/enrollment-management', 'EnrollmentManagementController@index');

// 入会金管理ルート（管理者用）
$router->get('/membership-fees', 'MembershipFeeController@index');
$router->get('/api/membership-fees', 'MembershipFeeController@list');
$router->post('/api/membership-fees', 'MembershipFeeController@store');
$router->get('/api/membership-fees/{id}', 'MembershipFeeController@get');
$router->put('/api/membership-fees/{id}', 'MembershipFeeController@update');
$router->delete('/api/membership-fees/{id}', 'MembershipFeeController@destroy');
$router->put('/api/membership-fee-items/{id}', 'MembershipFeeController@updateItem');
$router->post('/api/membership-fee-items/{id}/confirm', 'MembershipFeeController@toggleConfirm');

// 会員向け入会金ルート
$router->get('/api/member/membership-fees', 'MemberPortalController@myFees');
$router->get('/member/membership-fee/{id}', 'MemberPortalController@membershipFeeForm');
$router->post('/api/member/membership-fee-items/{id}/submit', 'MemberPortalController@submitFee');

// 合宿しおりルート（管理者用API）
$router->get('/api/camps/{id}/booklet', 'CampBookletController@show');
$router->put('/api/camps/{id}/booklet', 'CampBookletController@upsert');
$router->post('/api/camps/{id}/booklet/token', 'CampBookletController@generateToken');
$router->get('/api/camps/{id}/booklet/import-schedule', 'CampBookletController@importSchedule');
$router->get('/api/camps/{id}/booklet/participants', 'CampBookletController@participants');

// テニス班分けルート（団体戦/紅白戦/対戦表）
$router->get('/api/camps/{id}/tennis', 'CampTennisController@show');
$router->put('/api/camps/{id}/tennis', 'CampTennisController@upsert');

// 企画班分けルート（複数企画・自動振り分け・制約）
$router->get('/api/camps/{id}/plans', 'CampPlanController@index');
$router->post('/api/camps/{id}/plans', 'CampPlanController@store');
// 固定パスを {id} ルートより前に登録
$router->get('/api/plans/{id}', 'CampPlanController@show');
$router->post('/api/plans/{id}/resync', 'CampPlanController@resyncMembers');
$router->post('/api/plans/{id}/teams', 'CampPlanController@saveTeams');
$router->post('/api/plans/{id}/constraints', 'CampPlanController@addConstraint');
$router->delete('/api/plans/{id}/constraints/{cid}', 'CampPlanController@deleteConstraint');
$router->put('/api/plans/{id}', 'CampPlanController@update');
$router->delete('/api/plans/{id}', 'CampPlanController@destroy');

// 合宿しおり閲覧（会員ログイン済み）
$router->get('/member/camp/{id}/booklet', 'MemberPortalController@booklet');

// 合宿しおり公開URL（ログイン不要）
$router->get('/booklet/{token}', 'MemberPortalController@bookletPublic');

// ===== 遠征管理 =====
// ページ
$router->get('/expeditions', 'PageController@expeditions');
$router->get('/expeditions/{id}', 'PageController@expeditionDetail');

// 遠征 CRUD
$router->get('/api/expeditions', 'ExpeditionController@index');
$router->post('/api/expeditions', 'ExpeditionController@store');
$router->get('/api/expeditions/{id}', 'ExpeditionController@show');
$router->put('/api/expeditions/{id}', 'ExpeditionController@update');
$router->delete('/api/expeditions/{id}', 'ExpeditionController@destroy');

// 参加者
$router->get('/api/expeditions/{id}/participants', 'ExpeditionController@getParticipants');
$router->post('/api/expeditions/{id}/participants', 'ExpeditionController@addParticipant');
$router->put('/api/expeditions/{id}/participants/{pid}', 'ExpeditionController@updateParticipant');
$router->delete('/api/expeditions/{id}/participants/{pid}', 'ExpeditionController@removeParticipant');

// 車割（固定パスを先に登録）
$router->get('/api/expeditions/{id}/cars/settlement', 'ExpeditionController@getSettlement');
$router->post('/api/expeditions/{id}/cars/auto-assign', 'ExpeditionController@autoAssignCars');
$router->post('/api/expeditions/{id}/cars/auto-assign-return', 'ExpeditionController@autoAssignReturnCars');
$router->post('/api/expeditions/{id}/cars/resolve-stations', 'ExpeditionController@resolveParticipantStations');
$router->get('/api/expeditions/{id}/cars', 'ExpeditionController@getCars');
$router->post('/api/expeditions/{id}/cars', 'ExpeditionController@addCar');
$router->put('/api/expeditions/{id}/cars/{cid}', 'ExpeditionController@updateCar');
$router->delete('/api/expeditions/{id}/cars/{cid}', 'ExpeditionController@removeCar');
$router->post('/api/expeditions/{id}/cars/{cid}/members', 'ExpeditionController@addCarMember');
$router->put('/api/expeditions/{id}/cars/{cid}/members/{mid}', 'ExpeditionController@updateCarMember');
$router->delete('/api/expeditions/{id}/cars/{cid}/members/{mid}', 'ExpeditionController@removeCarMember');
$router->post('/api/expeditions/{id}/cars/{cid}/payers', 'ExpeditionController@addCarPayer');
$router->delete('/api/expeditions/{id}/cars/{cid}/payers/{pid}', 'ExpeditionController@removeCarPayer');

// チーム分け（固定パスを先に登録）
$router->put('/api/expeditions/{id}/teams/order', 'ExpeditionController@updateTeamOrder');
$router->get('/api/expeditions/{id}/teams', 'ExpeditionController@getTeams');
$router->post('/api/expeditions/{id}/teams', 'ExpeditionController@addTeam');
$router->put('/api/expeditions/{id}/teams/{tid}', 'ExpeditionController@updateTeam');
$router->delete('/api/expeditions/{id}/teams/{tid}', 'ExpeditionController@removeTeam');
$router->post('/api/expeditions/{id}/teams/{tid}/members', 'ExpeditionController@addTeamMember');
$router->delete('/api/expeditions/{id}/teams/{tid}/members/{mid}', 'ExpeditionController@removeTeamMember');

// 集金
$router->get('/api/expeditions/{id}/collection', 'ExpeditionController@getCollection');
$router->post('/api/expeditions/{id}/collection/generate', 'ExpeditionController@generateCollection');
$router->put('/api/expeditions/{id}/collection/{cid}', 'ExpeditionController@updateCollection');
$router->put('/api/expeditions/{id}/collection/{cid}/items/{iid}', 'ExpeditionController@updateCollectionItem');

// 申し込みURL
$router->get('/api/expeditions/{id}/application-url', 'ExpeditionController@getApplicationUrl');
$router->post('/api/expeditions/{id}/application-url', 'ExpeditionController@generateApplicationUrl');

// 遠征申し込みページ（公開・会員ログイン使用）
$router->get('/apply/expedition/{token}', 'ExpeditionApplicationController@form');
$router->get('/apply/expedition/{token}/confirm', 'ExpeditionApplicationController@confirm');
$router->get('/apply/expedition/{token}/complete', 'ExpeditionApplicationController@complete');
$router->post('/api/apply/expedition/{token}', 'ExpeditionApplicationController@apply');

// しおり（固定パスを先に登録）
$router->post('/api/expeditions/{id}/booklet/publish', 'ExpeditionBookletController@publishBooklet');
$router->get('/api/expeditions/{id}/booklet', 'ExpeditionBookletController@getBooklet');
$router->post('/api/expeditions/{id}/booklet', 'ExpeditionBookletController@saveBooklet');
$router->get('/public/expedition-booklet/{token}', 'ExpeditionBookletController@viewPublicBooklet');

// エクスポート
$router->get('/api/expeditions/{id}/export/xlsx',           'ExpeditionExportController@xlsx');
$router->get('/api/expeditions/{id}/export/pdf',            'ExpeditionExportController@pdf');
$router->get('/api/expeditions/{id}/export/activity-meibo', 'ExpeditionExportController@activityMeibo');
$router->get('/api/expeditions/{id}/export/espajio',         'ExpeditionExportController@espajio');

// レンタカー清算（管理者）- 固定パスを先に登録
$router->get('/api/expeditions/{id}/car-expenses/settlement',    'ExpeditionCarExpenseController@settlement');
$router->get('/api/expeditions/{id}/car-expenses/export/xlsx',   'ExpeditionCarExpenseController@exportXlsx');
$router->get('/api/expeditions/{id}/car-expenses/export/pdf',    'ExpeditionCarExpenseController@exportPdf');
$router->get('/api/expeditions/{id}/car-expenses',               'ExpeditionCarExpenseController@index');
$router->delete('/api/expeditions/{id}/car-expenses/{eid}',      'ExpeditionCarExpenseController@destroy');

// レンタカー清算（会員）
$router->post('/api/member/expedition/{id}/car-expense', 'ExpeditionCarExpenseController@memberSubmit');

// ===== 物販管理 =====
// 管理画面ページ
$router->get('/merchandise', 'MerchandiseController@indexPage');
$router->get('/merchandise/{id}', 'MerchandiseController@detailPage');

// エクスポート（固定パスを {id} ルートより前に配置）
$router->get('/api/merchandise/{id}/export/xlsx', 'MerchandiseExportController@xlsx');
$router->get('/api/merchandise/{id}/export/pdf',  'MerchandiseExportController@pdf');

// 未マッチ注文（暫定購入者）
$router->get('/api/merchandise/pending-orders', 'MerchandiseController@pendingOrders');
$router->post('/api/merchandise/pending-orders/match-all', 'MerchandiseController@matchAllPending');
$router->post('/api/merchandise/orders/{id}/link-member', 'MerchandiseController@linkOrderToMember');

// 商品 CRUD（固定パスを先に登録）
$router->post('/api/merchandise/upload-image', 'MerchandiseController@uploadImage');
$router->get('/api/merchandise/{id}/tokens', 'MerchandiseController@getTokens');
$router->post('/api/merchandise/{id}/tokens', 'MerchandiseController@generateToken');
$router->delete('/api/merchandise/tokens/{id}', 'MerchandiseController@destroyToken');
$router->post('/api/merchandise/orders/{id}/toggle-paid', 'MerchandiseController@togglePaid');
$router->put('/api/merchandise/orders/{id}/status', 'MerchandiseController@updateOrderStatus');
$router->delete('/api/merchandise/orders/{id}', 'MerchandiseController@destroyOrder');
$router->get('/api/merchandise/orders/{id}', 'MerchandiseController@showOrder');
$router->get('/api/merchandise', 'MerchandiseController@index');
$router->post('/api/merchandise', 'MerchandiseController@store');
$router->get('/api/merchandise/{id}/orders', 'MerchandiseController@orders');
$router->get('/api/merchandise/{id}/summary', 'MerchandiseController@summary');
$router->put('/api/merchandise/{id}/colors', 'MerchandiseController@saveColors');
$router->put('/api/merchandise/{id}/sizes', 'MerchandiseController@saveSizes');
$router->get('/api/merchandise/{id}', 'MerchandiseController@show');
$router->put('/api/merchandise/{id}', 'MerchandiseController@update');
$router->delete('/api/merchandise/{id}', 'MerchandiseController@destroy');

// 物販ショップ（会員）
$router->get('/member/store', 'MerchandiseShopController@memberShop');
$router->post('/api/member/store/checkout', 'MerchandiseShopController@memberCheckout');
$router->post('/api/member/store/orders/{id}/submit-payment', 'MerchandiseShopController@submitPayment');

// 物販ショップ（暫定購入: DB未登録の入会予定者向け） - 固定パスを先に登録
$router->get('/store/pending', 'MerchandiseShopController@pendingShop');
$router->post('/api/store/pending/checkout', 'MerchandiseShopController@pendingCheckout');

// 物販ショップ（公開URL）
$router->get('/store/{token}', 'MerchandiseShopController@publicShop');
$router->post('/api/store/{token}/checkout', 'MerchandiseShopController@publicCheckout');

// ===== 目安箱 =====
// 幹部側 管理ページ（固定パスを {id} より前に登録）
$router->get('/suggestions', 'SuggestionController@adminIndex');
$router->get('/api/suggestion-categories', 'SuggestionController@listCategories');
$router->post('/api/suggestion-categories', 'SuggestionController@storeCategory');
$router->put('/api/suggestion-categories/{id}', 'SuggestionController@updateCategory');
$router->delete('/api/suggestion-categories/{id}', 'SuggestionController@destroyCategory');
$router->post('/api/suggestions/{id}/replies', 'SuggestionController@adminReply');
$router->post('/api/suggestions/{id}/status', 'SuggestionController@setStatus');
$router->delete('/api/suggestions/{id}', 'SuggestionController@destroy');
$router->get('/suggestions/{id}', 'SuggestionController@adminShow');

// 会員側 目安箱
$router->get('/member/suggestions', 'SuggestionController@memberIndex');
$router->post('/api/member/suggestions', 'SuggestionController@memberStore');
$router->post('/api/member/suggestions/{id}/replies', 'SuggestionController@memberReply');
$router->get('/member/suggestions/{id}', 'SuggestionController@memberShow');

// デバッグ用（確認後削除）
$router->get('/debug-member', 'MemberPortalController@debugMember');

// ルーティング実行
$router->dispatch();
