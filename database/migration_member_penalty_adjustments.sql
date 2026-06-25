-- 会員のペナルティ点：手動調整の記録（Admin限定機能）
--
-- ペナルティ点の考え方:
--   合計点 = 自動分（合宿費・遠征費・物販の「入金期限超過かつ未入金」の件数 × ルール点）
--          + 手動分（このテーブルの points の合計）
--
--   - 自動分は画面を開くたびにリアルタイム集計するため永続テーブルを持たない
--     （cron 不要・レンタルサーバーでも数字がズレない）。
--   - 手動分（Admin による加点・減点）はここに1行ずつ追記する。
--     減点したい場合は負の points を入れる（例: -5 で5点取り消し）。
--   - 取り消し（誤操作の取り消し等）も負の調整行を足すことで実現し、行は削除しない（監査性のため）。

CREATE TABLE member_penalty_adjustments (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  member_id     INT NOT NULL,
  points        INT NOT NULL COMMENT '加点は正・減点（取り消し）は負',
  reason        VARCHAR(255) NOT NULL COMMENT '調整理由（Admin入力）',
  admin_user_id INT          DEFAULT NULL COMMENT '操作した幹部 admin_users.id',
  admin_name    VARCHAR(255) DEFAULT NULL COMMENT '操作時点の幹部名（後からの改名に影響されない記録用）',
  created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
  KEY idx_member (member_id),
  KEY idx_created_at (created_at),
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);
