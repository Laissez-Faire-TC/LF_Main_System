<?php
/**
 * 企画申し込みURLトークンモデル
 */
class EventToken
{
    public static function findByEvent(int $event_id): ?array
    {
        return Database::getInstance()->fetch(
            "SELECT * FROM event_tokens WHERE event_id = ?",
            [$event_id]
        );
    }

    public static function findByToken(string $token): ?array
    {
        return Database::getInstance()->fetch(
            "SELECT * FROM event_tokens WHERE token = ?",
            [$token]
        );
    }

    public static function generate(int $event_id): ?array
    {
        $db = Database::getInstance();
        $db->execute("DELETE FROM event_tokens WHERE event_id = ?", [$event_id]);
        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+90 days'));
        $db->insert(
            "INSERT INTO event_tokens (event_id, token, expires_at) VALUES (?, ?, ?)",
            [$event_id, $token, $expiresAt]
        );
        return self::findByEvent($event_id);
    }

    public static function delete(int $event_id): bool
    {
        return Database::getInstance()->execute(
            "DELETE FROM event_tokens WHERE event_id = ?",
            [$event_id]
        ) > 0;
    }
}
