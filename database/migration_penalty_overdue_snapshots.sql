-- ペナルティ点の永続化（入金確認時点の遅延を確定記録）
--
-- 未入金の遅延は元データから動的計算するが、入金が確認された時点で
-- その時の遅延日数・点数を確定し、ここに記録する。
-- これにより入金後や元データ削除後も過去の遅延ペナルティが維持される。
--
-- source_key: 集金の一意キー。種別ごとに以下の値を入れる（重複登録防止用）。
--   camp        : camp_collection_items.id
--   expedition  : expedition_collection_items.id
--   merchandise : merchandise_orders.id
CREATE TABLE IF NOT EXISTS member_penalty_overdue_snapshots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    kind ENUM('camp', 'expedition', 'merchandise') NOT NULL,
    source_key INT NOT NULL COMMENT '集金レコードのID（種別ごと）',
    title VARCHAR(255) DEFAULT NULL COMMENT '集金名（表示用に確定時点の値を保持）',
    deadline DATE DEFAULT NULL COMMENT '期限（確定時点の値）',
    days_late INT NOT NULL DEFAULT 0 COMMENT '確定した遅延日数',
    points INT NOT NULL DEFAULT 0 COMMENT '確定した加算点（days_late × 種別の1日あたり点）',
    confirmed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '入金確認・確定した日時',
    UNIQUE KEY unique_snapshot (kind, source_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
