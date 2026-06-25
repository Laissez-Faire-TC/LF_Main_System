-- 新歓用・OBOG用フォームの分離 ＋ ゲスト人物マスタ
-- 新歓は「氏名・カナ・学科」、OBOGは「氏名・カナ・代」で個人を同定し、
-- 同定した人物（guest_persons）を企画横断で共有する。
-- 「申込済み」判定は企画ごと（event_id + guest_person_id）に行う。

-- ── 企画にゲスト種別を追加 ──
-- guest_type = NULL（種別なし）/ 'shinkan'（新歓）/ 'obog'（OBOG）
ALTER TABLE events
    ADD COLUMN guest_type ENUM('shinkan', 'obog') DEFAULT NULL COMMENT 'ゲストフォーム種別（新歓/OBOG）' AFTER allow_guest;

-- ── ゲスト人物マスタ（企画横断で共有） ──
-- person_type ごとに 氏名・カナ・match_key（新歓=学科 / OBOG=代）の組で一意。
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

-- ── ゲスト申込に人物・カナを追加 ──
-- guest_person_id: どの人物の申込か（企画横断の同定用）
-- name_kana: 表示・並び替え用にカナも保持（既存 name はそのまま氏名）
ALTER TABLE event_guest_applications
    ADD COLUMN guest_person_id INT DEFAULT NULL COMMENT '同定された人物（guest_persons）' AFTER event_id,
    ADD COLUMN name_kana VARCHAR(255) DEFAULT NULL COMMENT 'カナ氏名' AFTER name;

ALTER TABLE event_guest_applications
    ADD CONSTRAINT fk_guest_app_person
        FOREIGN KEY (guest_person_id) REFERENCES guest_persons(id) ON DELETE SET NULL;

-- 同じ企画に同じ人物が二重申込しないよう、企画×人物で一意（NULLは複数可）
ALTER TABLE event_guest_applications
    ADD UNIQUE KEY unique_event_person (event_id, guest_person_id);
