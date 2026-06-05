<?php
/**
 * 物販公開ショップURL用トークン
 */
class MerchandiseToken
{
    public static function findAll(): array
    {
        return Database::getInstance()->fetchAll(
            "SELECT * FROM merchandise_tokens ORDER BY created_at DESC"
        );
    }

    public static function findActive(): ?array
    {
        return Database::getInstance()->fetch(
            "SELECT * FROM merchandise_tokens
             WHERE expires_at IS NULL OR expires_at > NOW()
             ORDER BY created_at DESC
             LIMIT 1"
        );
    }

    public static function findByToken(string $token): ?array
    {
        return Database::getInstance()->fetch(
            "SELECT * FROM merchandise_tokens WHERE token = ?",
            [$token]
        );
    }

    /**
     * 指定商品に紐付いたトークン一覧を取得
     */
    public static function findByMerchandise(int $merchandiseId): array
    {
        return Database::getInstance()->fetchAll(
            "SELECT * FROM merchandise_tokens WHERE merchandise_id = ? ORDER BY created_at DESC",
            [$merchandiseId]
        );
    }

    /**
     * 新しいトークンを生成（既存はそのまま残す）
     */
    public static function generate(?string $label = null, int $expireDays = 90, int $merchandiseId = 0): ?array
    {
        $db        = Database::getInstance();
        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expireDays} days"));
        $mid       = $merchandiseId > 0 ? $merchandiseId : null;

        $id = $db->insert(
            "INSERT INTO merchandise_tokens (merchandise_id, token, label, expires_at) VALUES (?, ?, ?, ?)",
            [$mid, $token, $label, $expiresAt]
        );
        return $db->fetch("SELECT * FROM merchandise_tokens WHERE id = ?", [$id]);
    }

    public static function delete(int $id): bool
    {
        return Database::getInstance()->execute(
            "DELETE FROM merchandise_tokens WHERE id = ?",
            [$id]
        ) > 0;
    }
}
