-- ============================================================
-- 幹部アカウント＋IAM風 権限管理
--   従来の共有パスワードログインは残しつつ、Googleアカウント単位の
--   幹部ログインと、機能（将来はタブ）単位のアクセス権限を導入する。
--
--   admin_users        … 幹部ユーザー（IAMの「ユーザー」）
--   admin_permissions  … 各幹部が持つ権限キー（IAMの「ポリシー」）
--
--   permission_key は機能キー（例: members, expeditions, camps...）。
--   将来のタブ単位拡張のため階層キー（例: expeditions.cars）も格納可能。
--   権限判定は前方一致で行う（expeditions を持てば expeditions.* も許可）。
--
--   固定Admin（config/admin.php の admin_emails）は DB に関わらず常に全権。
--   共有パスワードでのログインも常に全権（移行期間の保険）。
-- ============================================================

CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL COMMENT 'Googleアカウントのメール（ログイン識別）',
    name VARCHAR(255) DEFAULT NULL COMMENT '表示名',
    google_sub VARCHAR(255) DEFAULT NULL COMMENT 'Googleの不変ID（初回ログインで確定）',
    is_admin TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=全権Admin（権限管理が可能）',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0=無効化（ログイン不可）',
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_admin_email (email),
    UNIQUE KEY uniq_admin_google_sub (google_sub)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_user_id INT NOT NULL,
    permission_key VARCHAR(100) NOT NULL COMMENT '機能/タブキー（例: members, expeditions, expeditions.cars）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_admin_perm (admin_user_id, permission_key),
    INDEX idx_perm_admin (admin_user_id),
    FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
