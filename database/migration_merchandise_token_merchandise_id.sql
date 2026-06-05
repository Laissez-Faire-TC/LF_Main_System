-- 物販公開URLトークンに商品IDを紐付け（商品ごとにURLを発行できるようにする）
ALTER TABLE merchandise_tokens
  ADD COLUMN merchandise_id INT NULL COMMENT '対象商品ID（NULLは旧データ互換）' AFTER id,
  ADD CONSTRAINT fk_merchandise_tokens_merchandise
    FOREIGN KEY (merchandise_id) REFERENCES merchandise(id) ON DELETE CASCADE;
