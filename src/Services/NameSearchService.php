<?php
/**
 * 名前検索サービス
 *
 * 名前の部分一致検索を、ひらがな・カタカナどちらの入力でも当たるようにする。
 * フリガナ列（name_kana / buyer_kana 等）は通常カタカナで保存されているため、
 * 検索語をカタカナ・ひらがな両方に正規化して LIKE 条件を組み立てる。
 *
 * 例: 「やまだ」でも「ヤマダ」でも、カタカナ保存の「ヤマダ」にヒットする。
 */
class NameSearchService
{
    /**
     * 名前検索の WHERE 条件（断片）とバインド値を生成する。
     *
     * @param string $term       検索語（ユーザー入力）
     * @param array  $kanjiCols  漢字名など、入力そのままで部分一致させる列（例: ['name_kanji']）
     * @param array  $kanaCols   フリガナなど、ひらがな⇔カタカナを揃えて部分一致させる列（例: ['name_kana']）
     * @return array{0: string, 1: array}  [SQL断片（括弧付き・空なら ''）, パラメータ配列]
     */
    public static function buildCondition(string $term, array $kanjiCols, array $kanaCols): array
    {
        $term = trim($term);
        if ($term === '') {
            return ['', []];
        }

        // カナ列用：カタカナ版とひらがな版の両方を用意（保存がどちらでも当たるように）
        $katakana = mb_convert_kana($term, 'KVC'); // 半角カナ→全角・ひらがな→カタカナ
        $hiragana = mb_convert_kana($term, 'KVc'); // 半角カナ→全角・カタカナ→ひらがな

        $clauses = [];
        $params  = [];

        // 漢字名などは入力そのままで部分一致
        foreach ($kanjiCols as $col) {
            $clauses[] = "{$col} LIKE ?";
            $params[]  = '%' . $term . '%';
        }

        // フリガナ列はカタカナ・ひらがな両方で部分一致
        foreach ($kanaCols as $col) {
            $clauses[] = "{$col} LIKE ?";
            $params[]  = '%' . $katakana . '%';
            if ($hiragana !== $katakana) {
                $clauses[] = "{$col} LIKE ?";
                $params[]  = '%' . $hiragana . '%';
            }
        }

        if (empty($clauses)) {
            return ['', []];
        }

        return ['(' . implode(' OR ', $clauses) . ')', $params];
    }
}
