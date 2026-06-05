-- 企画班分け：開催タイミング（タイムスロット）を追加
-- どの time_slot（何日目・どのコマ）で行うかを保持し、
-- その時点の参加者全員を自動的に班分け対象にする。
ALTER TABLE camp_plan_divisions
    ADD COLUMN time_slot_id INT DEFAULT NULL COMMENT '開催タイミング（time_slots.id）NULL=未設定' AFTER name,
    ADD CONSTRAINT fk_plan_division_timeslot
        FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE SET NULL;
