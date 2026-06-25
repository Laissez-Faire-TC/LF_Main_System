-- ゲスト申込の「再提出（内容変更）」を幹部に通知するためのフラグ
-- 本人照合後に申込内容を変更（updateContent）したとき resubmitted_at を立てる。
-- 幹部が確認したら NULL に戻す（既読）。
ALTER TABLE event_guest_applications
    ADD COLUMN resubmitted_at TIMESTAMP NULL DEFAULT NULL COMMENT '未確認の内容変更があった日時（確認したらNULL）' AFTER note;
