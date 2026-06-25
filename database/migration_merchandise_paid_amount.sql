-- 物販注文に「会員が申告した支払い金額」を追加
-- 支払いフォーム（会員ログイン必須）で、購入者が実際に振り込んだ金額を申告する。
-- 初期値は注文合計（total_amount）を表示し、購入者が編集できる。
-- 支払い完了の申告自体は既存の payment_submitted / payment_submitted_at を流用する。

ALTER TABLE merchandise_orders
  ADD COLUMN paid_amount INT DEFAULT NULL COMMENT '会員が申告した支払い金額（振込完了報告時に保存）' AFTER payment_submitted_at;
