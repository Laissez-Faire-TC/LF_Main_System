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
        ['key' => 'hp',           'label' => 'HP管理'],
        ['key' => 'enrollment',   'label' => '入会管理'],
        ['key' => 'academic',     'label' => '年度管理'],
        ['key' => 'settings',     'label' => 'システム設定'],
    ],
];
