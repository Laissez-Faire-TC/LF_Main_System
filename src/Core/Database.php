<?php
/**
 * データベース接続クラス（シングルトン）
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $config = require CONFIG_PATH . '/database.php';

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['dbname'],
            $config['charset']
        );

        $this->pdo = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            $config['options']
        );
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * SELECT クエリ実行（複数行）
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * SELECT クエリ実行（1行）
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * INSERT/UPDATE/DELETE 実行
     */
    public function execute(string $sql, array $params = []): int
    {
        // 監査ログ用: UPDATE/DELETE は実行前に対象行（before）を取得
        $beforeRows = AuditLogger::fetchBefore($this->pdo, $sql, $params);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rowCount = $stmt->rowCount();

        // 監査ログ記録（幹部セッション中の書き込みのみ・失敗は無視）
        AuditLogger::record($this->pdo, $sql, $params, $beforeRows, null);

        return $rowCount;
    }

    /**
     * INSERT 実行（lastInsertId取得）
     */
    public function insert(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $insertId = (int)$this->pdo->lastInsertId();

        // 監査ログ記録（INSERT）
        AuditLogger::record($this->pdo, $sql, $params, [], $insertId);

        return $insertId;
    }

    /**
     * トランザクション開始
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * コミット
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * ロールバック
     */
    public function rollback(): void
    {
        $this->pdo->rollBack();
    }
}
