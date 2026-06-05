<?php
/**
 * 学籍番号解析サービス
 * 早稲田大学理工学部の学籍番号を解析して学部・学科・入学年度を特定
 *
 * 通常フォーマット: 1Y23F158-5
 * - 1-2文字目: 学部コード（1W=基幹理工、1X=創造理工、1Y=先進理工）
 * - 3-4文字目: 入学年度（下2桁、例: 23=2023年）
 * - 5文字目: 学科コード（創造・先進のみ、基幹は学系）
 *
 * 英語学位フォーマット: 1W25AS02-5
 * - 6文字目が数字でない場合、英語学位プログラムと判定
 * - 5文字目の学科コード: A=Mathematical Sciences, B=Mechanical Engineering,
 *   C=Computer Sciences and Communications Engineering, D=Civil and Environmental Engineering
 */

if (!class_exists('StudentIdParserService')) {

class StudentIdParserService
{
    /**
     * 学部コードのマッピング
     */
    private const FACULTY_CODES = [
        '1W' => '基幹理工学部',
        '1X' => '創造理工学部',
        '1Y' => '先進理工学部',
    ];

    /**
     * 英語学位プログラムの学科コード（6文字目が数字でない場合に適用）
     */
    private const ENGLISH_DEGREE_DEPARTMENT_CODES = [
        'A' => 'Mathematical Sciences',
        'B' => 'Mechanical Engineering',
        'C' => 'Computer Sciences and Communications Engineering',
        'D' => 'Civil and Environmental Engineering',
    ];

    /**
     * 創造理工学部の学科コード
     */
    private const SOZO_DEPARTMENT_CODES = [
        'A' => '建築学科',
        'B' => '総合機械工学科',
        'C' => '経営システム工学科',
        'D' => '社会環境工学科',
        'E' => '環境資源工学科',
        'F' => '社会文化領域',
    ];

    /**
     * 先進理工学部の学科コード
     */
    private const SENSHIN_DEPARTMENT_CODES = [
        'A' => '物理学科',
        'B' => '応用物理学科',
        'C' => '化学・生命化学科',
        'D' => '応用化学科',
        'E' => '生命医科学科',
        'F' => '電気・情報生命工学科',
    ];

    /**
     * 基幹理工学部の学系（2024年度以前入学）
     */
    private const KIKAN_GAKUKEI_OLD = [
        '学系I',
        '学系II',
        '学系III',
    ];

    /**
     * 基幹理工学部の学系（2025年度以降入学）
     * 2025年度から学系IVが追加されたが、表記は旧形式（系名なし）で統一する。
     */
    private const KIKAN_GAKUKEI_NEW = [
        '学系I',
        '学系II',
        '学系III',
        '学系IV',
    ];

    /**
     * 基幹理工学部の学系コード（5文字目）→ 学系名マップ
     * 例: 1W263174-6 → 5文字目「3」→ 学系III
     */
    private const KIKAN_GAKUKEI_BY_CODE = [
        '1' => '学系I',
        '2' => '学系II',
        '3' => '学系III',
        '4' => '学系IV',
    ];

    /**
     * 学系名の正規化マップ
     * 旧表記・新表記・全角ローマ数字などの揺れを統一表記（学系I/II/III/IV）にマッピング
     */
    private const GAKUKEI_NORMALIZATION_MAP = [
        '学系I（数学系）'       => '学系I',
        '学系II（工学系）'      => '学系II',
        '学系III（情報系）'     => '学系III',
        '学系IV（メディア系）'  => '学系IV',
        '学系Ⅰ（数学系）'       => '学系I',
        '学系Ⅱ（工学系）'       => '学系II',
        '学系Ⅲ（情報系）'       => '学系III',
        '学系Ⅳ（メディア系）'   => '学系IV',
        '学系Ⅰ'                  => '学系I',
        '学系Ⅱ'                  => '学系II',
        '学系Ⅲ'                  => '学系III',
        '学系Ⅳ'                  => '学系IV',
        '学系1'                  => '学系I',
        '学系2'                  => '学系II',
        '学系3'                  => '学系III',
        '学系4'                  => '学系IV',
    ];

    /**
     * 基幹理工学部の全学科リスト
     */
    private const KIKAN_DEPARTMENTS = [
        '数学科',
        '応用数理学科',
        '機械科学・航空宇宙学科',
        '電子物理システム学科',
        '情報理工学科',
        '情報通信学科',
        '表現工学科',
    ];

    /**
     * 学籍番号を解析
     *
     * @param string $studentId 学籍番号（例: 1Y23F158-5）
     * @return array 解析結果
     */
    public function parse(string $studentId): array
    {
        // デフォルトの結果（無効な学籍番号）
        $result = [
            'student_id' => $studentId,
            'faculty' => null,
            'faculty_code' => null,
            'enrollment_year' => null,
            'department' => null,
            'department_code' => null,
            'needs_department_selection' => false,
            'is_english_degree' => false,
            'is_valid' => false,
            'error' => null,
        ];

        // 入力の正規化（半角・大文字に統一）
        $normalizedId = mb_convert_kana($studentId, 'as');
        $normalizedId = strtoupper(trim($normalizedId));

        // 基本的なフォーマットチェック（最低限5文字）
        if (strlen($normalizedId) < 5) {
            $result['error'] = '学籍番号が短すぎます';
            return $result;
        }

        // 学部コード（1-2文字目）を抽出
        $facultyCode = substr($normalizedId, 0, 2);
        if (!isset(self::FACULTY_CODES[$facultyCode])) {
            $result['error'] = '不明な学部コードです: ' . $facultyCode;
            return $result;
        }

        $result['faculty_code'] = $facultyCode;
        $result['faculty'] = self::FACULTY_CODES[$facultyCode];

        // 入学年度（3-4文字目）を抽出
        $yearCode = substr($normalizedId, 2, 2);
        if (!preg_match('/^\d{2}$/', $yearCode)) {
            $result['error'] = '入学年度が不正です: ' . $yearCode;
            return $result;
        }

        $yearNum = (int)$yearCode;
        // 00-50は2000年代、51-99は1900年代として解釈
        $enrollmentYear = $yearNum <= 50 ? 2000 + $yearNum : 1900 + $yearNum;
        $result['enrollment_year'] = $enrollmentYear;

        // 学科コード（5文字目）を抽出
        $deptCode = substr($normalizedId, 4, 1);
        $result['department_code'] = $deptCode;

        // 6文字目が数字でない場合は英語学位フォーマット
        $sixthChar = strlen($normalizedId) > 5 ? substr($normalizedId, 5, 1) : '';
        $isEnglishDegree = $sixthChar !== '' && !ctype_digit($sixthChar);

        // 学部に応じた学科の判定
        switch ($facultyCode) {
            case '1W': // 基幹理工学部
                if ($isEnglishDegree) {
                    // 英語学位プログラム: 5文字目で学科を判定
                    if (isset(self::ENGLISH_DEGREE_DEPARTMENT_CODES[$deptCode])) {
                        $result['department'] = self::ENGLISH_DEGREE_DEPARTMENT_CODES[$deptCode];
                        $result['is_english_degree'] = true;
                        $result['is_valid'] = true;
                    } else {
                        $result['error'] = '不明な英語学位学科コードです: ' . $deptCode;
                    }
                } else {
                    // 通常フォーマット: 5文字目（1〜4）が学系番号に対応
                    // 1年生は学系を自動判定、2年生以降は進振り後の学科を選択させる
                    $gakukei = self::KIKAN_GAKUKEI_BY_CODE[$deptCode] ?? null;
                    $isFirstYear = $this->calculateGrade($enrollmentYear) === 1;

                    if ($gakukei !== null && $isFirstYear) {
                        $result['department'] = $gakukei;
                        $result['is_valid'] = true;
                    } else {
                        // 2年生以降、または学系コードが不明な場合は選択させる
                        $result['needs_department_selection'] = true;
                        $result['is_valid'] = true;
                    }
                }
                break;

            case '1X': // 創造理工学部
                if (isset(self::SOZO_DEPARTMENT_CODES[$deptCode])) {
                    $result['department'] = self::SOZO_DEPARTMENT_CODES[$deptCode];
                    $result['is_valid'] = true;
                } else {
                    $result['error'] = '不明な学科コードです（創造理工）: ' . $deptCode;
                }
                break;

            case '1Y': // 先進理工学部
                if (isset(self::SENSHIN_DEPARTMENT_CODES[$deptCode])) {
                    $result['department'] = self::SENSHIN_DEPARTMENT_CODES[$deptCode];
                    $result['is_valid'] = true;
                } else {
                    $result['error'] = '不明な学科コードです（先進理工）: ' . $deptCode;
                }
                break;
        }

        return $result;
    }

    /**
     * 学部コードから学部名を取得
     *
     * @param string $code 学部コード（例: 1W, 1X, 1Y）
     * @return string|null 学部名（不明な場合はnull）
     */
    public function getFacultyByCode(string $code): ?string
    {
        $code = strtoupper(trim($code));
        return self::FACULTY_CODES[$code] ?? null;
    }

    /**
     * 学科コードから学科名を取得
     *
     * @param string $facultyCode 学部コード
     * @param string $deptCode 学科コード
     * @return string|null 学科名（不明な場合はnull）
     */
    public function getDepartmentByCode(string $facultyCode, string $deptCode): ?string
    {
        $facultyCode = strtoupper(trim($facultyCode));
        $deptCode = strtoupper(trim($deptCode));

        switch ($facultyCode) {
            case '1X': // 創造理工学部
                return self::SOZO_DEPARTMENT_CODES[$deptCode] ?? null;

            case '1Y': // 先進理工学部
                return self::SENSHIN_DEPARTMENT_CODES[$deptCode] ?? null;

            case '1W': // 基幹理工学部
                // 基幹理工は入学時に学科が決まっていないため、nullを返す
                return null;

            default:
                return null;
        }
    }

    /**
     * 基幹理工学部の学系選択肢を取得
     *
     * @param int $enrollmentYear 入学年度
     * @return array 学系選択肢の配列
     */
    public function getGakukeiOptions(int $enrollmentYear): array
    {
        if ($enrollmentYear >= 2025) {
            return self::KIKAN_GAKUKEI_NEW;
        }
        return self::KIKAN_GAKUKEI_OLD;
    }

    /**
     * 学系名の表記を統一形式（学系I/II/III/IV）に正規化する。
     * 「学系III（情報系）」「学系Ⅲ」「学系3」などの揺れを吸収する。
     * 学系以外の文字列はそのまま返す。
     */
    public function normalizeGakukei(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim($value);
        if ($trimmed === '') {
            return $trimmed;
        }
        return self::GAKUKEI_NORMALIZATION_MAP[$trimmed] ?? $trimmed;
    }

    /**
     * 基幹理工学部の全学科リストを取得
     *
     * @return array 学科名の配列
     */
    public function getKikanDepartments(): array
    {
        return self::KIKAN_DEPARTMENTS;
    }

    /**
     * 創造理工学部の全学科リストを取得
     *
     * @return array [コード => 学科名] の配列
     */
    public function getSozoDepartments(): array
    {
        return self::SOZO_DEPARTMENT_CODES;
    }

    /**
     * 先進理工学部の全学科リストを取得
     *
     * @return array [コード => 学科名] の配列
     */
    public function getSenshinDepartments(): array
    {
        return self::SENSHIN_DEPARTMENT_CODES;
    }

    /**
     * 全学部のリストを取得
     *
     * @return array [コード => 学部名] の配列
     */
    public function getAllFaculties(): array
    {
        return self::FACULTY_CODES;
    }

    /**
     * 学籍番号のフォーマットが正しいかチェック
     *
     * @param string $studentId 学籍番号
     * @return bool 正しいフォーマットならtrue
     */
    public function isValidFormat(string $studentId): bool
    {
        // 正規化
        $normalizedId = mb_convert_kana($studentId, 'as');
        $normalizedId = strtoupper(trim($normalizedId));

        // 通常フォーマット: 学部コード(2文字) + 年度(2桁) + 学科コード(1文字/数字) + 番号(3-4桁) + ハイフン + チェックデジット(1桁)
        // 例: 1Y23F158-5, 1W211073-8
        if (preg_match('/^1[WXY]\d{2}[A-Z0-9]\d{3,4}-\d$/', $normalizedId) === 1) {
            return true;
        }
        // 英語学位フォーマット: 学部コード(2文字) + 年度(2桁) + 学科コード(1文字) + 英字(1文字) + 番号(2桁) + ハイフン + チェックデジット(1桁)
        // 例: 1W25AS02-5
        return preg_match('/^1[WXY]\d{2}[A-D][A-Z]\d{2}-\d$/', $normalizedId) === 1;
    }

    /**
     * 入学年度から現在の学年を計算
     *
     * @param int $enrollmentYear 入学年度
     * @param int|null $currentYear 現在の年度（nullの場合は現在年を使用）
     * @param int|null $currentMonth 現在の月（nullの場合は現在月を使用）
     * @return int 学年（1-4、5以上は留年含む）
     */
    public function calculateGrade(int $enrollmentYear, ?int $currentYear = null, ?int $currentMonth = null): int
    {
        if ($currentYear === null) {
            $currentYear = (int)date('Y');
        }
        if ($currentMonth === null) {
            $currentMonth = (int)date('n');
        }

        // 4月以降は新年度として計算
        $academicYear = $currentMonth >= 4 ? $currentYear : $currentYear - 1;

        $grade = $academicYear - $enrollmentYear + 1;

        return max(1, $grade);
    }
}

}
