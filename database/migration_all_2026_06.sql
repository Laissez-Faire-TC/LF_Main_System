-- ============================================================================
-- まとめマイグレーション（2026-06 一括）
--   このセッションで追加した未適用の変更をまとめたもの。
--   何回実行しても安全（列追加は存在チェック付き／テーブルは IF NOT EXISTS）。
--
-- 実行方法: phpMyAdmin の「SQL」タブにこのファイルの内容を貼り付けて実行、
--           または mysql クライアントで `SOURCE migration_all_2026_06.sql;`
--
-- 含まれる変更:
--   1. 物販: 入金期限・申告金額
--   2. 幹部: 活動ログ・セッション監視
--   3. ペナルティ: 手動調整・遅延スナップショット
--   4. 企画ゲスト申込: 基本・説明文・新歓/OBOG・再提出・会員/ゲスト項目分離
-- ============================================================================

-- 列を「存在しなければ追加」するための共通プロシージャ（実行後に削除）
DROP PROCEDURE IF EXISTS add_column_if_missing;
DELIMITER //
CREATE PROCEDURE add_column_if_missing(
    IN tbl  VARCHAR(64),
    IN col  VARCHAR(64),
    IN ddl  TEXT          -- 例: "ADD COLUMN xxx INT NULL AFTER yyy"
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND COLUMN_NAME = col
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tbl, '` ', ddl);
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;


-- ============================================================
-- 1. 物販
-- ============================================================

-- 入金期限（商品マスタ）
CALL add_column_if_missing('merchandise', 'payment_deadline',
    "ADD COLUMN payment_deadline DATE DEFAULT NULL COMMENT '入金期限（超過かつ未入金の注文にペナルティ点加算。NULLは対象外）' AFTER price");

-- 会員が申告した支払い金額
CALL add_column_if_missing('merchandise_orders', 'paid_amount',
    "ADD COLUMN paid_amount INT DEFAULT NULL COMMENT '会員が申告した支払い金額（振込完了報告時に保存）' AFTER payment_submitted_at");


-- ============================================================
-- 2. 幹部（活動ログ・セッション）
-- ============================================================

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


-- ============================================================
-- 3. ペナルティ
-- ============================================================

-- 手動調整の記録
CREATE TABLE IF NOT EXISTS member_penalty_adjustments (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    member_id     INT NOT NULL,
    points        INT NOT NULL COMMENT '加点は正・減点（取り消し）は負',
    reason        VARCHAR(255) NOT NULL COMMENT '調整理由（Admin入力）',
    admin_user_id INT          DEFAULT NULL COMMENT '操作した幹部 admin_users.id',
    admin_name    VARCHAR(255) DEFAULT NULL COMMENT '操作時点の幹部名',
    created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
    KEY idx_member (member_id),
    KEY idx_created_at (created_at),
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 遅延スナップショット（入金確認時点の遅延を確定記録して永続化）
CREATE TABLE IF NOT EXISTS member_penalty_overdue_snapshots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    kind ENUM('camp', 'expedition', 'merchandise') NOT NULL,
    source_key INT NOT NULL COMMENT '集金レコードのID（種別ごと）',
    title VARCHAR(255) DEFAULT NULL COMMENT '集金名（確定時点の値）',
    deadline DATE DEFAULT NULL COMMENT '期限（確定時点の値）',
    days_late INT NOT NULL DEFAULT 0 COMMENT '確定した遅延日数',
    points INT NOT NULL DEFAULT 0 COMMENT '確定した加算点（days_late × 種別の1日あたり点）',
    confirmed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '入金確認・確定した日時',
    UNIQUE KEY unique_snapshot (kind, source_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 4. 企画ゲスト申込
-- ============================================================

-- 4-1. events に設定列を追加（順序: allow_waitlist → allow_guest → guest_type → include_guests_in_calc）
CALL add_column_if_missing('events', 'allow_guest',
    "ADD COLUMN allow_guest TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=非会員（ゲスト）の申込を許可' AFTER allow_waitlist");
CALL add_column_if_missing('events', 'guest_type',
    "ADD COLUMN guest_type ENUM('shinkan','obog') DEFAULT NULL COMMENT 'ゲストフォーム種別（新歓/OBOG）' AFTER allow_guest");
CALL add_column_if_missing('events', 'include_guests_in_calc',
    "ADD COLUMN include_guests_in_calc TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=班分け・費用計算にゲストを含める' AFTER guest_type");

-- 4-2. ゲスト人物マスタ（企画横断で共有）
CREATE TABLE IF NOT EXISTS guest_persons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_type ENUM('shinkan', 'obog') NOT NULL,
    name VARCHAR(255) NOT NULL COMMENT '氏名',
    name_kana VARCHAR(255) NOT NULL COMMENT 'カナ氏名',
    match_key VARCHAR(255) NOT NULL COMMENT '同定キー（新歓=学科 / OBOG=代）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_person (person_type, name, name_kana, match_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4-3. 企画ごとのフォーム項目（カスタム質問）
CREATE TABLE IF NOT EXISTS event_guest_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    label VARCHAR(255) NOT NULL COMMENT '項目名',
    type ENUM('text', 'textarea', 'select', 'radio') NOT NULL DEFAULT 'text',
    options JSON DEFAULT NULL COMMENT 'select/radio の選択肢',
    is_required TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 既存 event_guest_fields への追加列（audience / description）
CALL add_column_if_missing('event_guest_fields', 'audience',
    "ADD COLUMN audience ENUM('member','guest') NOT NULL DEFAULT 'guest' COMMENT '項目の対象（member=会員, guest=会員以外）' AFTER event_id");
CALL add_column_if_missing('event_guest_fields', 'description',
    "ADD COLUMN description TEXT DEFAULT NULL COMMENT '項目の説明文（質問文・補足）' AFTER label");

-- 4-4. ゲスト申込本体
CREATE TABLE IF NOT EXISTS event_guest_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    name VARCHAR(255) NOT NULL COMMENT '氏名',
    status ENUM('submitted', 'waitlisted', 'cancelled') NOT NULL DEFAULT 'submitted',
    promoted TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=キャンセル待ちから繰り上げ',
    team_no INT DEFAULT NULL COMMENT '班番号（NULL=未割り当て）',
    note TEXT DEFAULT NULL COMMENT '備考',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ゲスト申込への追加列（人物紐付け・カナ・再提出フラグ）
CALL add_column_if_missing('event_guest_applications', 'guest_person_id',
    "ADD COLUMN guest_person_id INT DEFAULT NULL COMMENT '同定された人物（guest_persons）' AFTER event_id");
CALL add_column_if_missing('event_guest_applications', 'name_kana',
    "ADD COLUMN name_kana VARCHAR(255) DEFAULT NULL COMMENT 'カナ氏名' AFTER name");
CALL add_column_if_missing('event_guest_applications', 'resubmitted_at',
    "ADD COLUMN resubmitted_at TIMESTAMP NULL DEFAULT NULL COMMENT '未確認の内容変更があった日時（確認したらNULL）' AFTER note");

-- ゲスト申込の制約（FK・ユニーク）を「無ければ追加」
DROP PROCEDURE IF EXISTS add_guest_app_constraints;
DELIMITER //
CREATE PROCEDURE add_guest_app_constraints()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'event_guest_applications'
          AND CONSTRAINT_NAME = 'fk_guest_app_person'
    ) THEN
        ALTER TABLE event_guest_applications
            ADD CONSTRAINT fk_guest_app_person
            FOREIGN KEY (guest_person_id) REFERENCES guest_persons(id) ON DELETE SET NULL;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'event_guest_applications'
          AND CONSTRAINT_NAME = 'unique_event_person'
    ) THEN
        ALTER TABLE event_guest_applications
            ADD UNIQUE KEY unique_event_person (event_id, guest_person_id);
    END IF;
END //
DELIMITER ;
CALL add_guest_app_constraints();
DROP PROCEDURE IF EXISTS add_guest_app_constraints;

-- 4-5. ゲスト申込のカスタム項目回答
CREATE TABLE IF NOT EXISTS event_guest_field_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guest_application_id INT NOT NULL,
    field_id INT NOT NULL,
    value TEXT DEFAULT NULL,
    FOREIGN KEY (guest_application_id) REFERENCES event_guest_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES event_guest_fields(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4-6. 会員申込のカスタム項目回答
CREATE TABLE IF NOT EXISTS event_member_field_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL COMMENT 'event_applications.id',
    field_id INT NOT NULL COMMENT 'event_guest_fields.id（audience=member）',
    value TEXT DEFAULT NULL,
    UNIQUE KEY unique_app_field (application_id, field_id),
    FOREIGN KEY (application_id) REFERENCES event_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES event_guest_fields(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 後始末
DROP PROCEDURE IF EXISTS add_column_if_missing;
