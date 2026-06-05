<?php
/**
 * 会員のOAuth連携モデル（member_oauth_identities）
 */

if (!class_exists('MemberOauthIdentity')) {

class MemberOauthIdentity
{
    private Database $db;

    /** 対応プロバイダ */
    const PROVIDER_GOOGLE = 'google';
    const PROVIDER_LINE   = 'line';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * provider + provider_user_id で連携を検索（ログイン照合用）
     */
    public function findByProviderUser(string $provider, string $providerUserId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM member_oauth_identities WHERE provider = ? AND provider_user_id = ?",
            [$provider, $providerUserId]
        );
    }

    /**
     * 会員IDの全連携を取得
     */
    public function findByMember(int $memberId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM member_oauth_identities WHERE member_id = ? ORDER BY provider ASC",
            [$memberId]
        );
    }

    /**
     * 会員IDの連携件数（>0 なら学籍番号ログイン禁止対象）
     */
    public function countByMember(int $memberId): int
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS cnt FROM member_oauth_identities WHERE member_id = ?",
            [$memberId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * 会員が指定プロバイダを連携済みか
     */
    public function findByMemberAndProvider(int $memberId, string $provider): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM member_oauth_identities WHERE member_id = ? AND provider = ?",
            [$memberId, $provider]
        );
    }

    /**
     * 連携を作成
     */
    public function create(int $memberId, string $provider, string $providerUserId, ?string $email, ?string $displayName): int
    {
        return $this->db->insert(
            "INSERT INTO member_oauth_identities (member_id, provider, provider_user_id, email, display_name)
             VALUES (?, ?, ?, ?, ?)",
            [$memberId, $provider, $providerUserId, $email, $displayName]
        );
    }

    /**
     * 連携を解除（会員本人 or 幹部）
     */
    public function deleteByMemberAndProvider(int $memberId, string $provider): bool
    {
        return $this->db->execute(
            "DELETE FROM member_oauth_identities WHERE member_id = ? AND provider = ?",
            [$memberId, $provider]
        ) > 0;
    }

    /**
     * 会員の全連携を解除（幹部による救済用）
     */
    public function deleteAllByMember(int $memberId): int
    {
        return $this->db->execute(
            "DELETE FROM member_oauth_identities WHERE member_id = ?",
            [$memberId]
        );
    }
}

}
