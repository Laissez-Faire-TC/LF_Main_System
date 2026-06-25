-- 物販（商品マスタ）に「入金期限」を追加
-- ペナルティ点システムで「期限超過かつ未入金」を自動検知するために使用する。
-- 期限は商品ごとに設定し、その商品の全注文に適用される（注文へのコピーはせず、集計時に商品マスタを参照）。
-- NULL の商品は期限なし扱い（自動減点の対象外）。

ALTER TABLE merchandise
  ADD COLUMN payment_deadline DATE DEFAULT NULL COMMENT '入金期限（超過かつ未入金の注文にペナルティ点加算。NULLは対象外）' AFTER price;
