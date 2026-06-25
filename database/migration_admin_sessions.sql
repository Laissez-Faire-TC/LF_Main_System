-- 幹部のログインセッション監視（ログイン/ログアウト/活動時間）
-- 1ログイン = 1行。Adminが /admin-sessions で閲覧する。
--   - started_at  : ログイン時刻
--   - last_seen_at: 最終アクセス時刻（認証チェックのたびに更新・スロットリングあり）
--   - ended_at    : ログアウト/セッション切れ時刻（NULL=セッション継続中=オンライン候補）
--   - end_reason  : logout（明示ログアウト）/ timeout（有効期限切れ）/ NULL（継続中）
-- 滞在時間 = COALESCE(ended_at, last_seen_at) - started_at で集計する。

CREATE TABLE IF NOT EXISTS admin_sessions (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_user_id INT NULL COMMENT 'admin_users.id（共有パスワードログイン時はNULL）',
    admin_email   VARCHAR(255) NULL COMMENT '操作者メール（記録時点のスナップショット）',
    admin_name    VARCHAR(255) NULL COMMENT '操作者表示名（記録時点のスナップショット）',
    login_type    VARCHAR(20)  NULL COMMENT 'google / password',
    ip_address    VARCHAR(45)  NULL COMMENT 'ログイン元IP',
    user_agent    VARCHAR(255) NULL COMMENT 'User-Agent（先頭255文字）',
    started_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'ログイン時刻',
    last_seen_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '最終アクセス時刻',
    ended_at      DATETIME NULL COMMENT 'ログアウト/切れ時刻（NULL=継続中）',
    end_reason    VARCHAR(20) NULL COMMENT 'logout / timeout',
    PRIMARY KEY (id),
    KEY idx_admin_user (admin_user_id),
    KEY idx_started_at (started_at),
    KEY idx_active (ended_at, last_seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
