-- 申込フォームのカスタム項目を「会員（入会者）用」と「ゲスト用」で分けて管理する。
--
-- 既存の event_guest_fields に audience 列を追加（既存行はすべて 'guest' とみなす）。
-- 会員の回答は event_member_field_values（会員申込 event_applications に紐づく）で保持する。

-- ── 項目の対象（会員 / ゲスト） ──
ALTER TABLE event_guest_fields
    ADD COLUMN audience ENUM('member', 'guest') NOT NULL DEFAULT 'guest'
    COMMENT '項目の対象（member=会員/入会者, guest=会員以外）' AFTER event_id;

-- ── 会員申込のカスタム項目回答 ──
CREATE TABLE IF NOT EXISTS event_member_field_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL COMMENT 'event_applications.id',
    field_id INT NOT NULL COMMENT 'event_guest_fields.id（audience=member）',
    value TEXT DEFAULT NULL,
    UNIQUE KEY unique_app_field (application_id, field_id),
    FOREIGN KEY (application_id) REFERENCES event_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES event_guest_fields(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
