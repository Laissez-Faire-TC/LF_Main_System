<?php
/**
 * 目安箱カテゴリモデル
 */
class SuggestionCategory
{
    public static function findAll(bool $activeOnly = false): array
    {
        $where = $activeOnly ? "WHERE is_active = 1" : "";
        return Database::getInstance()->fetchAll(
            "SELECT * FROM suggestion_categories {$where} ORDER BY sort_order ASC, id ASC"
        );
    }

    public static function find(int $id): ?array
    {
        return Database::getInstance()->fetch(
            "SELECT * FROM suggestion_categories WHERE id = ?",
            [$id]
        );
    }

    public static function create(array $data): ?array
    {
        $id = Database::getInstance()->insert(
            "INSERT INTO suggestion_categories (name, sort_order, is_active) VALUES (?, ?, ?)",
            [
                trim($data['name'] ?? ''),
                (int)($data['sort_order'] ?? 0),
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
            ]
        );
        return self::find($id);
    }

    public static function update(int $id, array $data): ?array
    {
        $allowed = ['name', 'sort_order', 'is_active'];
        $fields  = [];
        $values  = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "{$f} = ?";
                $values[] = in_array($f, ['sort_order', 'is_active']) ? (int)$data[$f] : trim((string)$data[$f]);
            }
        }
        if (empty($fields)) {
            return self::find($id);
        }
        $values[] = $id;
        Database::getInstance()->execute(
            "UPDATE suggestion_categories SET " . implode(', ', $fields) . " WHERE id = ?",
            $values
        );
        return self::find($id);
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->execute(
            "DELETE FROM suggestion_categories WHERE id = ?",
            [$id]
        );
    }
}
