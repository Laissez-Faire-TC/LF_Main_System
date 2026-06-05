<?php
/**
 * 目安箱 返信モデル（スレッド）
 */
class SuggestionReply
{
    /**
     * 返信作成
     *   $authorType: 'admin' | 'member'
     */
    public static function create(array $data): ?array
    {
        $id = Database::getInstance()->insert(
            "INSERT INTO suggestion_replies
                (suggestion_id, parent_reply_id, author_type, author_member_id, author_name, body)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                (int)$data['suggestion_id'],
                !empty($data['parent_reply_id']) ? (int)$data['parent_reply_id'] : null,
                ($data['author_type'] ?? 'member') === 'admin' ? 'admin' : 'member',
                isset($data['author_member_id']) ? (int)$data['author_member_id'] : null,
                $data['author_name'] ?? null,
                trim($data['body'] ?? ''),
            ]
        );
        return self::find($id);
    }

    public static function find(int $id): ?array
    {
        return Database::getInstance()->fetch(
            "SELECT * FROM suggestion_replies WHERE id = ?",
            [$id]
        );
    }

    /**
     * 投稿に紐づく返信を時系列で取得（ツリーは呼び出し側で組み立て）
     */
    public static function findBySuggestion(int $suggestionId): array
    {
        return Database::getInstance()->fetchAll(
            "SELECT * FROM suggestion_replies WHERE suggestion_id = ? ORDER BY created_at ASC, id ASC",
            [$suggestionId]
        );
    }

    /**
     * フラットな返信配列を parent_reply_id でネストしたツリーに変換
     * 各ノードに children 配列を付与
     */
    public static function buildTree(array $replies): array
    {
        $byId = [];
        foreach ($replies as $r) {
            $r['children'] = [];
            $byId[(int)$r['id']] = $r;
        }
        $roots = [];
        foreach ($byId as $id => &$node) {
            $pid = $node['parent_reply_id'] !== null ? (int)$node['parent_reply_id'] : null;
            if ($pid !== null && isset($byId[$pid])) {
                $byId[$pid]['children'][] = &$node;
            } else {
                $roots[] = &$node;
            }
        }
        unset($node);
        return $roots;
    }
}
