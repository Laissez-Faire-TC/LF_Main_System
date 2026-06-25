-- 非会員（ゲスト）申込機能
-- 名簿に載っていない参加者（新歓・OB交流会など）が URL フォームから申し込めるようにする。
-- 会員用の event_applications はそのまま、ゲストは別テーブルで管理する。

-- ── events に設定列を追加 ──
ALTER TABLE events
    ADD COLUMN allow_guest TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=非会員（ゲスト）の申込を許可' AFTER allow_waitlist;

ALTER TABLE events
    ADD COLUMN include_guests_in_calc TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=班分け・費用計算にゲストを含める' AFTER allow_guest;

-- ── 企画ごとのゲストフォーム項目（カスタム質問） ──
-- type = text / textarea / select / radio
-- options は select/radio のときの選択肢を JSON 配列で保持（例: ["現役","OB"]）
CREATE TABLE IF NOT EXISTS event_guest_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    label VARCHAR(255) NOT NULL COMMENT '項目名（例: 学年、代、現役/OB）',
    type ENUM('text', 'textarea', 'select', 'radio') NOT NULL DEFAULT 'text',
    options JSON DEFAULT NULL COMMENT 'select/radio の選択肢',
    is_required TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── ゲスト申込本体 ──
-- 氏名のみ固定。その他の属性はカスタム項目（event_guest_field_values）で持つ。
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

-- ── ゲスト申込のカスタム項目回答 ──
CREATE TABLE IF NOT EXISTS event_guest_field_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guest_application_id INT NOT NULL,
    field_id INT NOT NULL,
    value TEXT DEFAULT NULL,
    FOREIGN KEY (guest_application_id) REFERENCES event_guest_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES event_guest_fields(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
