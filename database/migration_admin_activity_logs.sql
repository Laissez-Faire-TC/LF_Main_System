-- 幹部の活動ログ（誰が・いつ・どこで・何をしたか＋変更差分）
-- Adminのみが /admin-activity-logs で閲覧する。
-- 記録は Database 層（AuditLogger）で自動的に行う。
--   - 幹部セッション中の書き込み（INSERT/UPDATE/DELETE）のみ記録
--   - UPDATE/DELETE は変更前後の差分を changes_json に保存

CREATE TABLE IF NOT EXISTS admin_activity_logs (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_user_id INT NULL COMMENT 'admin_users.id（共有パスワードログイン時はNULL）',
    admin_email   VARCHAR(255) NULL COMMENT '操作者メール（パスワードログイン時はNULL）',
    admin_name    VARCHAR(255) NULL COMMENT '操作者表示名（記録時点のスナップショット）',
    login_type    VARCHAR(20)  NULL COMMENT 'google / password',
    method        VARCHAR(10)  NOT NULL COMMENT '操作種別（POST=作成/PUT=更新/DELETE=削除）',
    path          VARCHAR(255) NOT NULL COMMENT '操作したリクエストパス',
    feature       VARCHAR(50)  NULL COMMENT '機能カテゴリ（members/camps/expeditions等）',
    action_label  VARCHAR(255) NULL COMMENT '人間向けの操作内容ラベル',
    target_table  VARCHAR(64)  NULL COMMENT '操作対象テーブル',
    target_id     BIGINT       NULL COMMENT '操作対象レコードID',
    changes_json  MEDIUMTEXT   NULL COMMENT '変更差分（JSON）。INSERT=挿入値、UPDATE=before/after、DELETE=削除内容',
    ip_address    VARCHAR(45)  NULL COMMENT '操作元IP',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_admin_user (admin_user_id),
    KEY idx_created_at (created_at),
    KEY idx_feature (feature),
    KEY idx_target (target_table, target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 既に旧版（差分カラム無し）を作成済みの場合は以下を実行:
-- ALTER TABLE admin_activity_logs
--   ADD COLUMN target_table VARCHAR(64) NULL AFTER action_label,
--   ADD COLUMN target_id    BIGINT      NULL AFTER target_table,
--   ADD COLUMN changes_json MEDIUMTEXT  NULL AFTER target_id,
--   ADD KEY idx_target (target_table, target_id);
