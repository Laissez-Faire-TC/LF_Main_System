-- ============================================================
-- 目安箱（会員からの意見・質問・要望）
--   会員ログインページに常設し、題名・カテゴリ・本文・公開設定で送信。
--   幹部はダッシュボードから確認・返信。返信に対する返信（スレッド）対応。
--
--   公開設定:
--     public  … 全体公開（投稿・返信を全会員が閲覧可。ただし会員間では投稿者名は匿名表示）
--     private … 非公開（投稿者本人と幹部のみ閲覧可）
--   投稿者は member_id で常に内部記録（幹部には記名表示・会員間では匿名表示）。
-- ============================================================

-- ------------------------------------------------------------
-- カテゴリ（幹部が管理画面で追加・編集）
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS suggestion_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'カテゴリ名（例: 要望, 質問, 相談）',
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 初期カテゴリ
INSERT INTO suggestion_categories (name, sort_order) VALUES
    ('要望', 1),
    ('質問', 2),
    ('相談・不満', 3),
    ('イベント案', 4),
    ('その他', 5);

-- ------------------------------------------------------------
-- 投稿
--   category_id は ON DELETE SET NULL（カテゴリ削除後も投稿は残す）
--   member_id は投稿者（会員ログインのID）。会員削除時は SET NULL で投稿保持。
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS suggestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT DEFAULT NULL COMMENT '投稿者の会員ID（退会で NULL）',
    author_name VARCHAR(255) DEFAULT NULL COMMENT '投稿時点の投稿者氏名スナップショット',
    category_id INT DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    visibility ENUM('public', 'private') NOT NULL DEFAULT 'private',
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open' COMMENT '幹部側の対応状況',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_suggestions_category (category_id),
    INDEX idx_suggestions_member (member_id),
    INDEX idx_suggestions_visibility (visibility),
    FOREIGN KEY (category_id) REFERENCES suggestion_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 返信（スレッド）
--   author_type: 'admin'（幹部）/ 'member'（投稿者本人）
--   parent_reply_id: 返信に対する返信（NULL=投稿への直接返信）
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS suggestion_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    suggestion_id INT NOT NULL,
    parent_reply_id INT DEFAULT NULL COMMENT '親返信ID（NULL=投稿への直接返信）',
    author_type ENUM('admin', 'member') NOT NULL,
    author_member_id INT DEFAULT NULL COMMENT '会員返信時の会員ID',
    author_name VARCHAR(255) DEFAULT NULL COMMENT '返信者の表示名スナップショット',
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_replies_suggestion (suggestion_id),
    INDEX idx_replies_parent (parent_reply_id),
    FOREIGN KEY (suggestion_id) REFERENCES suggestions(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_reply_id) REFERENCES suggestion_replies(id) ON DELETE CASCADE,
    FOREIGN KEY (author_member_id) REFERENCES members(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
