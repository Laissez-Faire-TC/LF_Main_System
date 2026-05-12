<?php
/**
 * 遠征申し込みURLトークンモデル
 */
class ExpeditionToken
{
    /**
     * 遠征IDでトークンを取得
     */
    public static function findByExpedition(int $expedition_id): ?array
    {
        return Database::getInstance()->fetch(
            "SELECT * FROM expedition_tokens WHERE expedition_id = ?",
            [$expedition_id]
        );
    }

    /**
     * トークン文字列で取得
     * ※expires_at の有効期限チェックは呼び出し元で行う
     */
    public static function findByToken(string $token): ?array
    {
        return Database::getInstance()->fetch(
            "SELECT * FROM expedition_tokens WHERE token = ?",
            [$token]
        );
    }

    /**
     * トークンを生成する
     * 既存トークンを削除してから新しいトークンをINSERTし、作成した行を返す
     */
    public static function generate(int $expedition_id): ?array
    {
        $db = Database::getInstance();

        // 既存トークンを削除
        $db->execute("DELETE FROM expedition_tokens WHERE expedition_id = ?", [$expedition_id]);

        // 64文字の16進数トークンを生成
        $token = bin2hex(random_bytes(32));

        // 有効期限は現在時刻から30日後
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

        $db->insert(
            "INSERT INTO expedition_tokens (expedition_id, token, expires_at) VALUES (?, ?, ?)",
            [$expedition_id, $token, $expiresAt]
        );

        return self::findByExpedition($expedition_id);
    }

    /**
     * 有効期限内のトークンを持つ遠征一覧を取得（会員ホーム表示用）
     * 申込開始日時(application_start)が未到来のものは除外する
     */
    public static function getActiveExpeditionsWithTokens(): array
    {
        return Database::getInstance()->fetchAll(
            "SELECT et.token, et.expires_at, e.*
             FROM expedition_tokens et
             JOIN expeditions e ON e.id = et.expedition_id
             WHERE (et.expires_at IS NULL OR et.expires_at > NOW())
               AND (e.deadline IS NULL OR e.deadline >= CURDATE())
               AND (e.application_start IS NULL OR e.application_start <= NOW())
             ORDER BY e.start_date ASC",
            []
        );
    }

    /**
     * 遠征IDに紐づくトークンを削除
     */
    public static function delete(int $expedition_id): bool
    {
        return Database::getInstance()->execute(
            "DELETE FROM expedition_tokens WHERE expedition_id = ?",
            [$expedition_id]
        ) > 0;
    }
}
