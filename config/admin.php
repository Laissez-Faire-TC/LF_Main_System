<?php
/**
 * 幹部権限の定義（IAM風）
 *
 *  - admin_emails : DBに関わらず常に全権Adminとして扱うGoogleアカウント。
 *                   設定ミスでロックアウトされないための「最後の砦」。
 *                   ここに記載した人は権限管理画面で降格・削除できない。
 *
 *  - permissions  : 権限キーの正準リスト（管理画面のチェックボックス生成元）。
 *                   key は機能キー。将来タブ単位に分けたい場合は children に
 *                   子キー（例: expeditions.cars）を追加すれば、権限判定は
 *                   前方一致なので自動的に機能します。
 */

return [
    // 常に全権のGoogleアカウント（複数可）
    'admin_emails' => [
        'kohetsu.watanabe@gmail.com',
    ],

    // 権限キーの正準リスト（label は管理画面表示用）
    // controller には、この権限で保護されるルートのプレフィックス判定に使うキーを置く。
    'permissions' => [
        ['key' => 'members',      'label' => '会員名簿'],
        ['key' => 'camps',        'label' => '合宿管理'],
        ['key' => 'expeditions',  'label' => '遠征管理'],
        ['key' => 'events',       'label' => '企画管理'],
        ['key' => 'merchandise',  'label' => '物販管理'],
        ['key' => 'suggestions',  'label' => '目安箱'],
        ['key' => 'penalty',      'label' => 'ペナルティ点'],
        ['key' => 'hp',           'label' => 'HP管理'],
        ['key' => 'enrollment',   'label' => '入会管理'],
        ['key' => 'academic',     'label' => '年度管理'],
        ['key' => 'settings',     'label' => 'システム設定'],
    ],

    // 会員の個人情報項目の閲覧権限（明示許可制／ホワイトリスト）。
    //   key は members.field.<column> 形式。
    //   - `members`（名簿アクセス権）を持っていても、個人情報項目は自動では見えない。
    //     ここで明示的に付与した項目だけが表示される（Auth::canViewMemberField は明示一致のみ）。
    //   - Admin（全権）は常に全項目を閲覧可。
    //   column は実際の members テーブルのカラム名（マスキング対象）。
    //   ※ 会員名簿に表示される項目のうち、氏名・学部・学科を除く全項目を対象とする。
    'member_fields' => [
        ['key' => 'members.field.grade',             'column' => 'grade',             'label' => '学年'],
        ['key' => 'members.field.student_id',        'column' => 'student_id',        'label' => '学籍番号'],
        ['key' => 'members.field.status',            'column' => 'status',            'label' => 'ステータス'],
        ['key' => 'members.field.phone',             'column' => 'phone',             'label' => '電話番号'],
        ['key' => 'members.field.email',             'column' => 'email',             'label' => 'メール'],
        ['key' => 'members.field.line_name',         'column' => 'line_name',         'label' => 'LINE名'],
        ['key' => 'members.field.address',           'column' => 'address',           'label' => '住所'],
        ['key' => 'members.field.emergency_contact', 'column' => 'emergency_contact', 'label' => '緊急連絡先'],
        ['key' => 'members.field.birthdate',         'column' => 'birthdate',         'label' => '生年月日'],
        ['key' => 'members.field.allergy',           'column' => 'allergy',           'label' => 'アレルギー'],
        ['key' => 'members.field.sns_allowed',       'column' => 'sns_allowed',       'label' => 'SNS投稿可否'],
        ['key' => 'members.field.sports_registration_no', 'column' => 'sports_registration_no', 'label' => 'コート予約番号'],
        ['key' => 'members.field.enrollment_year',   'column' => 'enrollment_year',   'label' => '入学年度'],
        ['key' => 'members.field.academic_year',     'column' => 'academic_year',     'label' => '年度'],
    ],
];
