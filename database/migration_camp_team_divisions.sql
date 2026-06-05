-- ============================================================
-- 合宿: テニス班分け & 企画班分け
-- しおりタブから team_battle / kohaku / 夜レク を分離し、
-- 専用タブ・専用テーブルへ移行する。
-- ============================================================

-- ------------------------------------------------------------
-- テニス班分け（団体戦チーム / 紅白戦チーム / 紅白戦対戦表）
-- 合宿ごとに1行。内容は JSON で保持（参加者名キー・手動編集中心のため）。
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS camp_tennis_battles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    camp_id INT NOT NULL,
    team_battle_teams JSON DEFAULT NULL COMMENT '団体戦チーム [{team_name, members:[{name,is_leader}]}]',
    team_battle_rules TEXT DEFAULT NULL,
    kohaku_teams JSON DEFAULT NULL COMMENT '紅白戦チーム {red:[{name}], white:[{name}]}',
    kohaku_rules TEXT DEFAULT NULL,
    kohaku_matches JSON DEFAULT NULL COMMENT '紅白戦対戦表 [{round, courts:[{type,red1,red2,white1,white2}]}]',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_camp_tennis (camp_id),
    FOREIGN KEY (camp_id) REFERENCES camps(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 企画班分け：企画（班分けの単位）。1合宿に複数作成可・各々に名前。
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS camp_plan_divisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    camp_id INT NOT NULL,
    name VARCHAR(255) NOT NULL COMMENT '企画名（例: 夜レク、BBQ班）',
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (camp_id) REFERENCES camps(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 企画班分け：各企画に属する参加者と班番号
-- participant_id で合宿参加者を参照。team_no=NULL は未割り当て。
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS camp_plan_division_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    division_id INT NOT NULL,
    participant_id INT NOT NULL,
    team_no INT DEFAULT NULL COMMENT '班番号（NULL=未割り当て）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_division_participant (division_id, participant_id),
    FOREIGN KEY (division_id) REFERENCES camp_plan_divisions(id) ON DELETE CASCADE,
    FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 企画班分け：組み合わせ制約（絶対一緒 / 絶対別）
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS camp_plan_division_constraints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    division_id INT NOT NULL,
    participant_a_id INT NOT NULL,
    participant_b_id INT NOT NULL,
    type ENUM('together', 'apart') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_constraint (division_id, participant_a_id, participant_b_id, type),
    FOREIGN KEY (division_id) REFERENCES camp_plan_divisions(id) ON DELETE CASCADE,
    FOREIGN KEY (participant_a_id) REFERENCES participants(id) ON DELETE CASCADE,
    FOREIGN KEY (participant_b_id) REFERENCES participants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
