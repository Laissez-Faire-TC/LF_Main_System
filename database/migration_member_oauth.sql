-- ============================================================
-- 会員のOAuth2.0ログイン連携（希望者のみ）
--   会員は学籍番号ログイン後、自分のアカウントに Google / LINE を連携できる。
--   1会員につき各プロバイダ1つまで（Google・LINE 両方連携可）。
--   1つの外部アカウント（provider + provider_user_id）は1会員にのみ紐づく。
--
--   重要な認証ルール:
--     OAuth連携が1つでもある会員は、学籍番号ログインを禁止する
--     （= 必ずOAuth経由でのみログイン。本人確認を強化）。
--     連携手段が使えなくなった場合は、本人が別プロバイダで解除、
--     または幹部が管理画面から強制解除して学籍番号ログインを復活させる。
-- ============================================================

CREATE TABLE IF NOT EXISTS member_oauth_identities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    provider ENUM('google', 'line') NOT NULL,
    provider_user_id VARCHAR(255) NOT NULL COMMENT 'プロバイダ側の不変ID（Google=sub / LINE=userId）',
    email VARCHAR(255) DEFAULT NULL COMMENT '連携時に取得したメール（参考用・照合には使わない）',
    display_name VARCHAR(255) DEFAULT NULL COMMENT '連携時に取得した表示名',
    linked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_provider_user (provider, provider_user_id),
    UNIQUE KEY uniq_member_provider (member_id, provider),
    INDEX idx_oauth_member (member_id),
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
