<?php
/**
 * 会員名簿モデル
 */

if (!class_exists('Member')) {

class Member
{
    private Database $db;

    /**
     * ステータス定数
     */
    const STATUS_PENDING = 'pending';      // 承認待ち
    const STATUS_ACTIVE = 'active';        // 現役
    const STATUS_OB_OG = 'ob_og';          // OB/OG
    const STATUS_WITHDRAWN = 'withdrawn';  // 退会

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 氏名のスペース正規化
     * 半角スペース（1つ・2つ以上）や先頭末尾スペースを除去し、
     * 姓と名の間のスペースを全角スペース1つに統一する。
     * スペースがない場合（山田太郎）は変更しない。
     */
    public static function normalizeJapaneseName(string $name): string
    {
        // 先頭・末尾の半角・全角スペースを除去
        $name = preg_replace('/^[\s　]+|[\s　]+$/u', '', $name);
        // 連続するスペース（半角・全角の混在含む）を全角スペース1つに統一
        $name = preg_replace('/[\s　]+/u', '　', $name);
        return $name;
    }

    /**
     * 全件取得
     */
    public function all(): array
    {
        return Auth::maskMemberData($this->db->fetchAll(
            "SELECT * FROM members ORDER BY name_kana ASC"
        ));
    }

    /**
     * ID指定で取得
     */
    public function find(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM members WHERE id = ?",
            [$id]
        );
    }

    /**
     * 検索・フィルタ・ページネーション対応の一覧取得
     *
     * @param array $filters フィルタ条件
     * @param int $perPage 1ページあたりの件数
     * @param int $offset オフセット
     * @return array 会員リスト
     */
    public function search(array $filters = [], int $perPage = 20, int $offset = 0): array
    {
        $where = [];
        $params = [];

        // 検索条件（名前・カナ）※ひらがな・カタカナどちらの入力でもヒットさせる
        if (!empty($filters['search'])) {
            [$cond, $condParams] = NameSearchService::buildCondition(
                (string)$filters['search'], ['name_kanji'], ['name_kana']
            );
            if ($cond !== '') {
                $where[] = $cond;
                foreach ($condParams as $p) $params[] = $p;
            }
        }

        // 配列フィルタをIN句に変換するヘルパー
        $addInFilter = function(string $col, $val) use (&$where, &$params): void {
            $arr = is_array($val) ? array_values(array_filter($val)) : ($val !== '' && $val !== null ? [$val] : []);
            if (empty($arr)) return;
            $placeholders = implode(',', array_fill(0, count($arr), '?'));
            $where[] = "{$col} IN ({$placeholders})";
            foreach ($arr as $v) $params[] = $v;
        };

        // フィルタ: 学年
        $addInFilter('grade', $filters['grade'] ?? null);

        // フィルタ: 学部
        $addInFilter('faculty', $filters['faculty'] ?? null);

        // フィルタ: ステータス
        $addInFilter('status', $filters['status'] ?? null);

        // フィルタ: 学科未設定
        if (isset($filters['department_not_set']) && $filters['department_not_set'] !== '') {
            $where[] = "department_not_set = ?";
            $params[] = (int)$filters['department_not_set'];
        }

        // フィルタ: 入学年度
        if (isset($filters['enrollment_year']) && $filters['enrollment_year'] !== '') {
            $where[] = "enrollment_year = ?";
            $params[] = $filters['enrollment_year'];
        }

        // フィルタ: 性別
        $addInFilter('gender', $filters['gender'] ?? null);

        // フィルタ: 学科
        $addInFilter('department', $filters['department'] ?? null);

        // フィルタ: 年度
        if (isset($filters['academic_year']) && $filters['academic_year'] !== '') {
            $where[] = "academic_year = ?";
            $params[] = (int)$filters['academic_year'];
        }

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        // データ取得
        $sql = "SELECT * FROM members {$whereClause} ORDER BY name_kana ASC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        return Auth::maskMemberData($this->db->fetchAll($sql, $params));
    }

    /**
     * 検索条件に一致する件数を取得
     *
     * @param array $filters フィルタ条件
     * @return int 件数
     */
    public function countSearch(array $filters = []): int
    {
        $where = [];
        $params = [];

        // 検索条件（名前・カナ）※ひらがな・カタカナどちらの入力でもヒットさせる
        if (!empty($filters['search'])) {
            [$cond, $condParams] = NameSearchService::buildCondition(
                (string)$filters['search'], ['name_kanji'], ['name_kana']
            );
            if ($cond !== '') {
                $where[] = $cond;
                foreach ($condParams as $p) $params[] = $p;
            }
        }

        // 配列フィルタをIN句に変換するヘルパー
        $addInFilter = function(string $col, $val) use (&$where, &$params): void {
            $arr = is_array($val) ? array_values(array_filter($val)) : ($val !== '' && $val !== null ? [$val] : []);
            if (empty($arr)) return;
            $placeholders = implode(',', array_fill(0, count($arr), '?'));
            $where[] = "{$col} IN ({$placeholders})";
            foreach ($arr as $v) $params[] = $v;
        };

        // フィルタ: 学年
        $addInFilter('grade', $filters['grade'] ?? null);

        // フィルタ: 学部
        $addInFilter('faculty', $filters['faculty'] ?? null);

        // フィルタ: ステータス
        $addInFilter('status', $filters['status'] ?? null);

        // フィルタ: 学科未設定
        if (isset($filters['department_not_set']) && $filters['department_not_set'] !== '') {
            $where[] = "department_not_set = ?";
            $params[] = (int)$filters['department_not_set'];
        }

        // フィルタ: 入学年度
        if (isset($filters['enrollment_year']) && $filters['enrollment_year'] !== '') {
            $where[] = "enrollment_year = ?";
            $params[] = $filters['enrollment_year'];
        }

        // フィルタ: 性別
        $addInFilter('gender', $filters['gender'] ?? null);

        // フィルタ: 学科
        $addInFilter('department', $filters['department'] ?? null);

        // フィルタ: 年度
        if (isset($filters['academic_year']) && $filters['academic_year'] !== '') {
            $where[] = "academic_year = ?";
            $params[] = (int)$filters['academic_year'];
        }

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT COUNT(*) as total FROM members {$whereClause}";
        $result = $this->db->fetch($sql, $params);

        return $result['total'] ?? 0;
    }

    /**
     * 新規作成
     */
    public function create(array $data): int
    {
        if (!empty($data['name_kanji'])) {
            $data['name_kanji'] = self::normalizeJapaneseName($data['name_kanji']);
        }
        if (!empty($data['name_kana'])) {
            $data['name_kana'] = self::normalizeJapaneseName($data['name_kana']);
        }

        $sql = "INSERT INTO members (
            name_kanji, name_kana, gender, grade, faculty, department,
            student_id, phone, address, emergency_contact, birthdate,
            allergy, line_name, sns_allowed, sports_registration_no, sports_registration_shared, email,
            status, department_not_set, enrollment_year, academic_year
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        return $this->db->insert($sql, [
            $data['name_kanji'],
            $data['name_kana'],
            $data['gender'],
            $data['grade'],
            $data['faculty'],
            $data['department'],
            $data['student_id'],
            $data['phone'],
            $data['address'],
            $data['emergency_contact'],
            $data['birthdate'],
            $data['allergy'] ?? null,
            $data['line_name'],
            $data['sns_allowed'] ?? 1,
            $data['sports_registration_no'] ?? null,
            $data['sports_registration_shared'] ?? 0,
            $data['email'] ?? null,
            $data['status'] ?? self::STATUS_PENDING,
            $data['department_not_set'] ?? 0,
            $data['enrollment_year'] ?? null,
            $data['academic_year'] ?? null,
        ]);
    }

    /**
     * 更新
     */
    public function update(int $id, array $data): bool
    {
        if (isset($data['name_kanji'])) {
            $data['name_kanji'] = self::normalizeJapaneseName($data['name_kanji']);
        }
        if (isset($data['name_kana'])) {
            $data['name_kana'] = self::normalizeJapaneseName($data['name_kana']);
        }

        $fields = [];
        $values = [];

        $allowedFields = [
            'name_kanji', 'name_kana', 'gender', 'grade', 'faculty', 'department',
            'student_id', 'phone', 'address', 'emergency_contact', 'birthdate',
            'allergy', 'line_name', 'sns_allowed', 'sports_registration_no', 'sports_registration_shared', 'email',
            'status', 'department_not_set', 'enrollment_year', 'academic_year',
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = "UPDATE members SET " . implode(', ', $fields) . " WHERE id = ?";

        return $this->db->execute($sql, $values) > 0;
    }

    /**
     * 削除
     */
    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM members WHERE id = ?", [$id]) > 0;
    }

    /**
     * 学籍番号で検索
     *
     * @param string $studentId 学籍番号
     * @return array|null 会員情報
     */
    public function findByStudentId(string $studentId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM members WHERE student_id = ?",
            [$studentId]
        );
    }

    /**
     * 学籍番号の重複チェック
     *
     * @param string $studentId 学籍番号
     * @return bool 存在する場合true
     */
    public function existsByStudentId(string $studentId): bool
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM members WHERE student_id = ?",
            [$studentId]
        );
        return ($result['count'] ?? 0) > 0;
    }

    /**
     * メールアドレスで検索
     *
     * @param string $email メールアドレス
     * @return array|null 会員情報
     */
    public function findByEmail(string $email): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM members WHERE email = ?",
            [$email]
        );
    }

    /**
     * 最近入会した会員を取得（active ステータス・直近60日以内に登録）
     *
     * @return array 会員リスト
     */
    public function getRecentlyJoined(): array
    {
        return Auth::maskMemberData($this->db->fetchAll(
            "SELECT * FROM members
             WHERE status = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
             ORDER BY created_at DESC",
            [self::STATUS_ACTIVE]
        ));
    }

    /**
     * 承認待ち会員を取得（後方互換のため残す）
     *
     * @return array 会員リスト
     */
    public function getPending(): array
    {
        return Auth::maskMemberData($this->db->fetchAll(
            "SELECT * FROM members WHERE status = ? ORDER BY created_at DESC",
            [self::STATUS_PENDING]
        ));
    }

    /**
     * ステータス別に会員を取得
     *
     * @param string $status ステータス
     * @return array 会員リスト
     */
    public function getByStatus(string $status): array
    {
        return Auth::maskMemberData($this->db->fetchAll(
            "SELECT * FROM members WHERE status = ? ORDER BY name_kana ASC",
            [$status]
        ));
    }

    /**
     * ステータスを更新
     *
     * @param int $id 会員ID
     * @param string $status 新しいステータス
     * @param string $reason 理由（却下時など）
     * @return bool 成功/失敗
     */
    public function updateStatus(int $id, string $status, string $reason = ''): bool
    {
        return $this->update($id, ['status' => $status]);
    }

    /**
     * ステータス別の集計を取得
     *
     * @return array ['pending' => int, 'active' => int, 'ob_og' => int, 'withdrawn' => int, 'total' => int]
     */
    public function getStatusCounts(): array
    {
        $sql = "SELECT
                    status,
                    COUNT(*) as count
                FROM members
                GROUP BY status";

        $results = $this->db->fetchAll($sql);

        $counts = [
            self::STATUS_PENDING => 0,
            self::STATUS_ACTIVE => 0,
            self::STATUS_OB_OG => 0,
            self::STATUS_WITHDRAWN => 0,
            'total' => 0,
        ];

        foreach ($results as $row) {
            if (isset($counts[$row['status']])) {
                $counts[$row['status']] = (int)$row['count'];
            }
            $counts['total'] += (int)$row['count'];
        }

        return $counts;
    }

    /**
     * 学年別の集計を取得
     *
     * @return array ['1' => int, '2' => int, '3' => int, '4' => int, ...]
     */
    public function getGradeCounts(): array
    {
        $sql = "SELECT
                    grade,
                    COUNT(*) as count
                FROM members
                WHERE status = ?
                GROUP BY grade";

        $results = $this->db->fetchAll($sql, [self::STATUS_ACTIVE]);

        $counts = [
            '1' => 0,
            '2' => 0,
            '3' => 0,
            '4' => 0,
            'M1' => 0,
            'M2' => 0,
            'OB' => 0,
            'OG' => 0,
        ];

        foreach ($results as $row) {
            if (isset($counts[$row['grade']])) {
                $counts[$row['grade']] = (int)$row['count'];
            }
        }

        return $counts;
    }

    /**
     * 学科未設定の会員を取得
     *
     * @return array 会員リスト
     */
    public function getDepartmentNotSet(): array
    {
        return Auth::maskMemberData($this->db->fetchAll(
            "SELECT * FROM members WHERE department_not_set = 1 ORDER BY name_kana ASC"
        ));
    }

    /**
     * 入学年度一覧を取得
     *
     * @return array 入学年度リスト
     */
    public function getEnrollmentYears(): array
    {
        $results = $this->db->fetchAll(
            "SELECT DISTINCT enrollment_year FROM members
             WHERE enrollment_year IS NOT NULL
             ORDER BY enrollment_year DESC"
        );

        return array_column($results, 'enrollment_year');
    }

    /**
     * 一括インポート
     *
     * @param array $members 会員データの配列
     * @return array ['imported' => int, 'updated' => int, 'errors' => array]
     */
    public function bulkImport(array $members): array
    {
        $imported = 0;
        $updated = 0;
        $errors = [];

        // トランザクションを使わず、個別にコミット（エラーがあっても続行）
        foreach ($members as $index => $member) {
            try {
                // 学籍番号で既存チェック
                $existing = $this->findByStudentId($member['student_id']);

                if ($existing) {
                    // 既存の場合は更新
                    $this->update($existing['id'], $member);
                    $updated++;
                } else {
                    // 新規の場合は作成
                    $this->create($member);
                    $imported++;
                }
            } catch (Exception $e) {
                // 個別のエラーは記録するが、処理は続行
                $name = $member['name_kanji'] ?? '不明';
                $errors[] = "{$name} (行" . ($index + 2) . "): " . $e->getMessage();
            }
        }

        return [
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    /**
     * 学籍番号と年度で検索
     *
     * @param string $studentId 学籍番号
     * @param int $year 年度
     * @return array|null 会員情報
     */
    public function findByStudentIdAndYear(string $studentId, int $year): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM members WHERE student_id = ? AND academic_year = ?",
            [$studentId, $year]
        );
    }

    /**
     * 前年度から名前で検索
     *
     * @param string $name 名前（カナまたは漢字）
     * @param int $year 検索対象年度
     * @return array 会員リスト
     */
    public function searchPreviousYear(string $name, int $year): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM members
             WHERE academic_year = ?
             AND (name_kanji LIKE ? OR name_kana LIKE ?)
             ORDER BY name_kana ASC",
            [$year, "%{$name}%", "%{$name}%"]
        );
    }

    /**
     * 次年度にコピー（継続入会）
     *
     * @param int $memberId 元となる会員ID
     * @param int $newYear 新しい年度
     * @return int 新しく作成された会員ID
     */
    public function copyToNextYear(int $memberId, int $newYear): int
    {
        // 元のデータを取得
        $member = $this->find($memberId);
        if (!$member) {
            throw new Exception('会員が見つかりません');
        }

        // 学年を自動的に+1
        $newGrade = $this->calculateNextGrade($member['grade'], $member['gender']);

        // 新年度のデータを作成
        $newData = [
            'name_kanji' => $member['name_kanji'],
            'name_kana' => $member['name_kana'],
            'gender' => $member['gender'],
            'grade' => $newGrade,
            'faculty' => $member['faculty'],
            'department' => $member['department'],
            'student_id' => $member['student_id'],
            'phone' => $member['phone'],
            'address' => $member['address'],
            'emergency_contact' => $member['emergency_contact'],
            'birthdate' => $member['birthdate'],
            'allergy' => $member['allergy'],
            'line_name' => $member['line_name'],
            'sns_allowed' => $member['sns_allowed'],
            'sports_registration_no' => $member['sports_registration_no'],
            'email' => $member['email'],
            'status' => self::STATUS_ACTIVE,  // 継続入会なので即座にactive
            'department_not_set' => $member['department_not_set'],
            'enrollment_year' => $member['enrollment_year'],
            'academic_year' => $newYear,
        ];

        // 挿入
        $sql = "INSERT INTO members (
            name_kanji, name_kana, gender, grade, faculty, department,
            student_id, phone, address, emergency_contact, birthdate,
            allergy, line_name, sns_allowed, sports_registration_no, email,
            status, department_not_set, enrollment_year, academic_year
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        return $this->db->insert($sql, [
            $newData['name_kanji'],
            $newData['name_kana'],
            $newData['gender'],
            $newData['grade'],
            $newData['faculty'],
            $newData['department'],
            $newData['student_id'],
            $newData['phone'],
            $newData['address'],
            $newData['emergency_contact'],
            $newData['birthdate'],
            $newData['allergy'],
            $newData['line_name'],
            $newData['sns_allowed'],
            $newData['sports_registration_no'],
            $newData['email'],
            $newData['status'],
            $newData['department_not_set'],
            $newData['enrollment_year'],
            $newData['academic_year'],
        ]);
    }

    /**
     * 次の学年を計算
     *
     * @param string $currentGrade 現在の学年
     * @param string $gender 性別
     * @return string 次の学年
     */
    private function calculateNextGrade(string $currentGrade, string $gender): string
    {
        // B3は10月に引退（executeOctoberRetirement）、4月には残っていればOBへ
        // M1/M2は最初からOB扱いだが、4月更新でも念のためOBへ
        $gradeMap = [
            '1'  => '2',
            '2'  => '3',
            '3'  => $gender === 'male' ? 'OB' : 'OG',
            '4'  => $gender === 'male' ? 'OB' : 'OG',
            'M1' => $gender === 'male' ? 'OB' : 'OG',
            'M2' => $gender === 'male' ? 'OB' : 'OG',
        ];

        return $gradeMap[$currentGrade] ?? $currentGrade;
    }

    /**
     * 指定年度の会員数を取得
     *
     * @param int $year 年度
     * @return int 会員数
     */
    public function countByYear(int $year): int
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM members WHERE academic_year = ?",
            [$year]
        );
        return (int)($result['count'] ?? 0);
    }

    /**
     * 指定年度の会員を取得
     *
     * @param int $year 年度
     * @param string|null $status ステータス（オプション）
     * @return array 会員リスト
     */
    public function findByYear(int $year, ?string $status = null): array
    {
        if ($status) {
            return Auth::maskMemberData($this->db->fetchAll(
                "SELECT * FROM members WHERE academic_year = ? AND status = ? ORDER BY name_kana ASC",
                [$year, $status]
            ));
        } else {
            return Auth::maskMemberData($this->db->fetchAll(
                "SELECT * FROM members WHERE academic_year = ? ORDER BY name_kana ASC",
                [$year]
            ));
        }
    }
}

}
