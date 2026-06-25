-- ゲストフォーム項目に「説明文（質問文・補足）」を追加
-- 申込フォームで項目名の下に表示し、回答者への案内文として使う。
ALTER TABLE event_guest_fields
    ADD COLUMN description TEXT DEFAULT NULL COMMENT '項目の説明文（質問文・補足）' AFTER label;
