-- 班決め（グループ分け）機能

-- 申込レコードに班番号を追加（NULL=未割り当て）
ALTER TABLE event_applications
    ADD COLUMN team_no INT DEFAULT NULL COMMENT '班番号（NULL=未割り当て）' AFTER promoted;

-- 班の制約（絶対一緒/絶対別）テーブル
-- type = 'together'（必ず同じ班）/ 'apart'（必ず別の班）
CREATE TABLE IF NOT EXISTS event_team_constraints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    member_a_id INT NOT NULL,
    member_b_id INT NOT NULL,
    type ENUM('together', 'apart') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_constraint (event_id, member_a_id, member_b_id, type),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (member_a_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (member_b_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
