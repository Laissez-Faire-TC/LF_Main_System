<?php
/**
 * 幹部ログインセッション モデル（admin_sessions）
 *
 * ログイン/ログアウト/活動時間を「1ログイン=1行」で記録する。
 *   - open()       : ログイン時に行を作成し、そのIDを返す（Auth から呼ばれる）
 *   - touch()      : 最終アクセス時刻を更新（認証チェックごと・スロットリングあり）
 *   - close()      : ログアウト/切れ時に終了時刻と理由を記録
 *   - closeStale() : 一定時間アクセスのない継続中セッションを timeout として閉じる
 * 閲覧は AdminSessionController（Admin限定）。
 */

if (!class_exists('AdminSession')) {

class AdminSession
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * ログインセッションを開始（1行作成）。
     * 記録失敗で本処理（ログイン）を止めないよう例外は握りつぶす。
     *
     * @return int 作成した行のID（失敗時は0）。Auth がセッションに保持する。
     */
    public function open(array $data): int
    {
        try {
            return (int)$this->db->insert(
                "INSERT INTO admin_sessions
                    (admin_user_id, admin_email, admin_name, login_type, ip_address, user_agent,
                     started_at, last_seen_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $data['admin_user_id'] ?? null,
                    $data['admin_email']   ?? null,
                    $data['admin_name']    ?? null,
                    $data['login_type']    ?? null,
                    $data['ip_address']    ?? null,
                    $data['user_agent']    ?? null,
                ]
            );
        } catch (Throwable $e) {
            error_log('AdminSession::open failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 最終アクセス時刻を更新（継続中＝ended_at IS NULL のときのみ）。
     */
    public function touch(int $id): void
    {
        if ($id <= 0) return;
        try {
            $this->db->execute(
                "UPDATE admin_sessions SET last_seen_at = NOW() WHERE id = ? AND ended_at IS NULL",
                [$id]
            );
        } catch (Throwable $e) {
            error_log('AdminSession::touch failed: ' . $e->getMessage());
        }
    }

    /**
     * セッションを終了（ログアウト/切れ）。既に終了済みなら何もしない。
     *
     * @param int    $id     セッション行ID
     * @param string $reason 'logout' / 'timeout'
     */
    public function close(int $id, string $reason): void
    {
        if ($id <= 0) return;
        try {
            $this->db->execute(
                "UPDATE admin_sessions
                    SET ended_at = NOW(), end_reason = ?
                  WHERE id = ? AND ended_at IS NULL",
                [$reason, $id]
            );
        } catch (Throwable $e) {
            error_log('AdminSession::close failed: ' . $e->getMessage());
        }
    }

    /**
     * 一定時間アクセスのない「継続中」セッションを timeout として閉じる。
     * ブラウザを閉じただけでログアウトを通らなかったセッションの後始末。
     *
     * @param int $idleSeconds 無アクセスとみなす秒数（セッション有効期限に合わせる）
     */
    public function closeStale(int $idleSeconds): void
    {
        try {
            $this->db->execute(
                "UPDATE admin_sessions
                    SET ended_at = last_seen_at, end_reason = 'timeout'
                  WHERE ended_at IS NULL
                    AND last_seen_at < (NOW() - INTERVAL ? SECOND)",
                [(int)$idleSeconds]
            );
        } catch (Throwable $e) {
            error_log('AdminSession::closeStale failed: ' . $e->getMessage());
        }
    }

    /**
     * 一覧（新しいログイン順）。フィルタ: admin_user_id / login_type / from / to / online
     */
    public function search(array $filters = [], int $limit = 200, int $offset = 0): array
    {
        [$where, $params] = $this->buildWhere($filters);

        $sql = "SELECT * FROM admin_sessions";
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY started_at DESC, id DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;

        return $this->db->fetchAll($sql, $params);
    }

    public function count(array $filters = []): int
    {
        [$where, $params] = $this->buildWhere($filters);

        $sql = "SELECT COUNT(*) AS c FROM admin_sessions";
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $row = $this->db->fetch($sql, $params);
        return (int)($row['c'] ?? 0);
    }

    /**
     * 現在オンライン（継続中かつ直近 $withinSeconds 以内にアクセス）のセッション。
     */
    public function online(int $withinSeconds = 300): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM admin_sessions
              WHERE ended_at IS NULL
                AND last_seen_at >= (NOW() - INTERVAL ? SECOND)
              ORDER BY last_seen_at DESC",
            [(int)$withinSeconds]
        );
    }

    /**
     * 操作者一覧（フィルタ用ドロップダウン）。
     */
    public function distinctActors(): array
    {
        return $this->db->fetchAll(
            "SELECT admin_user_id, MAX(admin_email) AS admin_email, MAX(admin_name) AS admin_name
               FROM admin_sessions
              GROUP BY admin_user_id
              ORDER BY admin_email ASC"
        );
    }

    private function buildWhere(array $filters): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['admin_user_id'])) {
            $where[] = 'admin_user_id = ?';
            $params[] = (int)$filters['admin_user_id'];
        }
        if (!empty($filters['login_type'])) {
            $where[] = 'login_type = ?';
            $params[] = $filters['login_type'];
        }
        if (!empty($filters['from'])) {
            $where[] = 'started_at >= ?';
            $params[] = $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $where[] = 'started_at <= ?';
            $params[] = $filters['to'] . ' 23:59:59';
        }
        if (!empty($filters['online'])) {
            // 継続中（ended_at IS NULL）のみ
            $where[] = 'ended_at IS NULL';
        }

        return [$where, $params];
    }
}

}
