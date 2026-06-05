-- 基幹理工学部の学系表記を統一形式（学系I/II/III/IV）に正規化するマイグレーション
-- 「学系III（情報系）」「学系Ⅲ」「学系3」などの揺れを「学系III」に統一する。

-- 新表記（系名付き）→ 旧表記
UPDATE members SET department = '学系I'   WHERE department = '学系I（数学系）';
UPDATE members SET department = '学系II'  WHERE department = '学系II（工学系）';
UPDATE members SET department = '学系III' WHERE department = '学系III（情報系）';
UPDATE members SET department = '学系IV'  WHERE department = '学系IV（メディア系）';

-- 全角ローマ数字（系名付き）→ 旧表記
UPDATE members SET department = '学系I'   WHERE department = '学系Ⅰ（数学系）';
UPDATE members SET department = '学系II'  WHERE department = '学系Ⅱ（工学系）';
UPDATE members SET department = '学系III' WHERE department = '学系Ⅲ（情報系）';
UPDATE members SET department = '学系IV'  WHERE department = '学系Ⅳ（メディア系）';

-- 全角ローマ数字（系名なし）→ 半角ローマ数字
UPDATE members SET department = '学系I'   WHERE department = '学系Ⅰ';
UPDATE members SET department = '学系II'  WHERE department = '学系Ⅱ';
UPDATE members SET department = '学系III' WHERE department = '学系Ⅲ';
UPDATE members SET department = '学系IV'  WHERE department = '学系Ⅳ';

-- アラビア数字 → ローマ数字
UPDATE members SET department = '学系I'   WHERE department = '学系1';
UPDATE members SET department = '学系II'  WHERE department = '学系2';
UPDATE members SET department = '学系III' WHERE department = '学系3';
UPDATE members SET department = '学系IV'  WHERE department = '学系4';

-- 確認用：基幹理工学部の現在の学系分布
SELECT department, COUNT(*) AS count
FROM members
WHERE faculty = '基幹理工学部'
GROUP BY department
ORDER BY department;

SELECT '学系表記の正規化が完了しました（members テーブル）' AS message;
