-- 物販注文に「会員からの振込完了報告」フラグを追加
-- 会員側で「振込完了を報告」ボタンから自己申告できるようにし、
-- 管理者は最終的に payment_status を paid に切り替えて確定する。

ALTER TABLE merchandise_orders
  ADD COLUMN payment_submitted    TINYINT(1) NOT NULL DEFAULT 0 COMMENT '会員からの振込完了報告フラグ' AFTER paid_at,
  ADD COLUMN payment_submitted_at DATETIME   DEFAULT NULL       COMMENT '振込完了報告日時'             AFTER payment_submitted;
