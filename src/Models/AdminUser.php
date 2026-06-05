<?php
/**
 * 幹部ユーザーモデル（admin_users / admin_permissions）
 */

if (!class_exists('AdminUser')) {

class AdminUser
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 全幹部を権限付きで取得
     */
    public function allWithPermissions(): array
    {
        $users = $this->db->fetchAll("SELECT * FROM admin_users ORDER BY is_admin DESC, email ASC");
        foreach ($users as &$u) {
            $u['permissions'] = $this->getPermissions((int)$u['id']);
        }
        unset($u);
        return $users;
    }

    public function find(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM admin_users WHERE id = ?", [$id]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetch("SELECT * FROM admin_users WHERE email = ?", [$email]);
    }

    public function findByGoogleSub(string $sub): ?array
    {
        return $this->db->fetch("SELECT * FROM admin_users WHERE google_sub = ?", [$sub]);
    }

    /**
     * 幹部を追加（メール招待）。重複メールは例外。
     */
    public function create(string $email, ?string $name, bool $isAdmin): int
    {
        return $this->db->insert(
            "INSERT INTO admin_users (email, name, is_admin, is_active) VALUES (?, ?, ?, 1)",
            [$email, $name, $isAdmin ? 1 : 0]
        );
    }

    public function updateProfile(int $id, ?string $name, bool $isAdmin, bool $isActive): bool
    {
        return $this->db->execute(
            "UPDATE admin_users SET name = ?, is_admin = ?, is_active = ? WHERE id = ?",
            [$name, $isAdmin ? 1 : 0, $isActive ? 1 : 0, $id]
        ) > 0;
    }

    /**
     * 初回Googleログイン時に google_sub と表示名を確定
     */
    public function linkGoogle(int $id, string $sub, ?string $name): void
    {
        // 表示名は未設定のときだけ補完
        $this->db->execute(
            "UPDATE admin_users SET google_sub = ?, name = COALESCE(NULLIF(name, ''), ?) WHERE id = ?",
            [$sub, $name, $id]
        );
    }

    public function touchLogin(int $id): void
    {
        $this->db->execute("UPDATE admin_users SET last_login_at = NOW() WHERE id = ?", [$id]);
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM admin_users WHERE id = ?", [$id]) > 0;
    }

    // ---------------- 権限 ----------------

    public function getPermissions(int $adminUserId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT permission_key FROM admin_permissions WHERE admin_user_id = ?",
            [$adminUserId]
        );
        return array_column($rows, 'permission_key');
    }

    /**
     * 権限キーの集合を丸ごと差し替え
     */
    public function setPermissions(int $adminUserId, array $keys): void
    {
        $this->db->execute("DELETE FROM admin_permissions WHERE admin_user_id = ?", [$adminUserId]);
        foreach (array_unique($keys) as $key) {
            $key = trim((string)$key);
            if ($key === '') continue;
            $this->db->execute(
                "INSERT INTO admin_permissions (admin_user_id, permission_key) VALUES (?, ?)",
                [$adminUserId, $key]
            );
        }
    }
}

}
