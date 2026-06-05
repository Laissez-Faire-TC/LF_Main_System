<?php
/**
 * 目安箱 投稿モデル
 */
class Suggestion
{
    /**
     * 投稿作成
     */
    public static function create(array $data): ?array
    {
        $id = Database::getInstance()->insert(
            "INSERT INTO suggestions (member_id, author_name, category_id, title, body, visibility)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                isset($data['member_id']) ? (int)$data['member_id'] : null,
                $data['author_name'] ?? null,
                !empty($data['category_id']) ? (int)$data['category_id'] : null,
                trim($data['title'] ?? ''),
                trim($data['body'] ?? ''),
                ($data['visibility'] ?? 'private') === 'public' ? 'public' : 'private',
            ]
        );
        return self::find($id);
    }

    public static function find(int $id): ?array
    {
        return Database::getInstance()->fetch(
            "SELECT s.*, c.name AS category_name
             FROM suggestions s
             LEFT JOIN suggestion_categories c ON c.id = s.category_id
             WHERE s.id = ?",
            [$id]
        );
    }

    /**
     * 幹部向け一覧（カテゴリで絞り込み可）
     */
    public static function findAllForAdmin(?int $categoryId = null): array
    {
        $where  = [];
        $params = [];
        if ($categoryId !== null) {
            $where[]  = "s.category_id = ?";
            $params[] = $categoryId;
        }
        $whereSql = $where ? "WHERE " . implode(' AND ', $where) : "";

        return Database::getInstance()->fetchAll(
            "SELECT s.*, c.name AS category_name,
                    (SELECT COUNT(*) FROM suggestion_replies r WHERE r.suggestion_id = s.id) AS reply_count
             FROM suggestions s
             LEFT JOIN suggestion_categories c ON c.id = s.category_id
             {$whereSql}
             ORDER BY s.created_at DESC",
            $params
        );
    }

    /**
     * 会員向け一覧
     *   - 全体公開（public）はすべて表示（投稿者名は呼び出し側で匿名化）
     *   - 非公開（private）は本人の投稿のみ表示
     */
    public static function findAllForMember(int $memberId, ?int $categoryId = null): array
    {
        $params = [$memberId];
        $catSql = "";
        if ($categoryId !== null) {
            $catSql   = "AND s.category_id = ?";
            $params[] = $categoryId;
        }

        return Database::getInstance()->fetchAll(
            "SELECT s.*, c.name AS category_name,
                    (SELECT COUNT(*) FROM suggestion_replies r WHERE r.suggestion_id = s.id) AS reply_count
             FROM suggestions s
             LEFT JOIN suggestion_categories c ON c.id = s.category_id
             WHERE (s.visibility = 'public' OR s.member_id = ?)
             {$catSql}
             ORDER BY s.created_at DESC",
            $params
        );
    }

    /**
     * 会員がこの投稿を閲覧できるか
     */
    public static function canMemberView(array $suggestion, int $memberId): bool
    {
        if ($suggestion['visibility'] === 'public') {
            return true;
        }
        return (int)$suggestion['member_id'] === $memberId;
    }

    public static function setStatus(int $id, string $status): void
    {
        $status = $status === 'closed' ? 'closed' : 'open';
        Database::getInstance()->execute(
            "UPDATE suggestions SET status = ? WHERE id = ?",
            [$status, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->execute(
            "DELETE FROM suggestions WHERE id = ?",
            [$id]
        );
    }
}
