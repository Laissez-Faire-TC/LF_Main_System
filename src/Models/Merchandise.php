<?php
/**
 * 物販商品モデル
 */
class Merchandise
{
    public static function findAll(): array
    {
        $rows = Database::getInstance()->fetchAll(
            "SELECT m.*, COUNT(DISTINCT moi.order_id) as order_count
             FROM merchandise m
             LEFT JOIN merchandise_order_items moi ON moi.merchandise_id = m.id
             GROUP BY m.id
             ORDER BY m.sort_order ASC, m.id DESC"
        );
        return $rows;
    }

    public static function findById(int $id): ?array
    {
        $row = Database::getInstance()->fetch(
            "SELECT * FROM merchandise WHERE id = ?",
            [$id]
        );
        if (!$row) return null;

        $row['colors'] = self::getColors($id);
        $row['sizes']  = self::getSizes($id);
        return $row;
    }

    public static function getColors(int $merchandise_id): array
    {
        return Database::getInstance()->fetchAll(
            "SELECT * FROM merchandise_colors WHERE merchandise_id = ? ORDER BY sort_order ASC, id ASC",
            [$merchandise_id]
        );
    }

    public static function getSizes(int $merchandise_id): array
    {
        return Database::getInstance()->fetchAll(
            "SELECT * FROM merchandise_sizes WHERE merchandise_id = ? ORDER BY sort_order ASC, id ASC",
            [$merchandise_id]
        );
    }

    public static function create(array $data): ?array
    {
        $id = Database::getInstance()->insert(
            "INSERT INTO merchandise (name, description, price, payment_deadline, sale_start, sale_end, is_active, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['name'],
                $data['description'] ?? null,
                (int)($data['price'] ?? 0),
                ($data['payment_deadline'] ?? '') !== '' ? $data['payment_deadline'] : null,
                $data['sale_start'] ?: null,
                $data['sale_end']   ?: null,
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                (int)($data['sort_order'] ?? 0),
            ]
        );
        return self::findById($id);
    }

    public static function update(int $id, array $data): ?array
    {
        // 価格変更を検知するため、更新前の価格を控えておく
        $before    = self::findById($id);
        $oldPrice  = $before ? (int)$before['price'] : null;

        $allowed = ['name', 'description', 'price', 'payment_deadline', 'sale_start', 'sale_end', 'is_active', 'sort_order'];
        $fields  = [];
        $values  = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "{$f} = ?";
                if (in_array($f, ['payment_deadline', 'sale_start', 'sale_end'])) {
                    $values[] = $data[$f] !== '' ? $data[$f] : null;
                } elseif (in_array($f, ['price', 'is_active', 'sort_order'])) {
                    $values[] = (int)$data[$f];
                } else {
                    $values[] = $data[$f];
                }
            }
        }
        if (empty($fields)) return self::findById($id);

        $values[] = $id;
        Database::getInstance()->execute(
            "UPDATE merchandise SET " . implode(', ', $fields) . " WHERE id = ?",
            $values
        );

        // 価格が変わったら、その商品を含む既存注文の金額も新価格に連動させる
        if (array_key_exists('price', $data)) {
            $newPrice = (int)$data['price'];
            if ($oldPrice !== null && $oldPrice !== $newPrice) {
                MerchandiseOrder::repriceByMerchandise($id, $newPrice);
            }
        }

        return self::findById($id);
    }

    public static function delete(int $id): bool
    {
        return Database::getInstance()->execute(
            "DELETE FROM merchandise WHERE id = ?",
            [$id]
        ) > 0;
    }

    /**
     * 色を保存（送られた配列で完全置換）
     * 各要素: ['color_name' => string, 'image_path' => string|null, 'sort_order' => int]
     */
    public static function saveColors(int $merchandise_id, array $colors): void
    {
        $db = Database::getInstance();

        // 差し替え（全削除→全件INSERT）。1色足しただけでも全色が入れ替わるので、
        // before/after の色名を比較して「追加/削除した色」を1行にまとめて記録する。
        $beforeNames = array_values(array_filter(array_map(
            fn($c) => trim((string)($c['color_name'] ?? '')),
            self::getColors($merchandise_id)
        ), fn($n) => $n !== ''));
        $afterNames = array_values(array_filter(array_map(
            fn($c) => trim((string)($c['color_name'] ?? '')),
            $colors
        ), fn($n) => $n !== ''));

        $run = function () use ($db, $merchandise_id, $colors) {
            $db->execute("DELETE FROM merchandise_colors WHERE merchandise_id = ?", [$merchandise_id]);
            foreach ($colors as $i => $c) {
                $name = trim($c['color_name'] ?? '');
                if ($name === '') continue;
                $db->insert(
                    "INSERT INTO merchandise_colors (merchandise_id, color_name, image_path, sort_order)
                     VALUES (?, ?, ?, ?)",
                    [
                        $merchandise_id,
                        $name,
                        $c['image_path'] ?? null,
                        (int)($c['sort_order'] ?? $i),
                    ]
                );
            }
        };

        if (class_exists('AuditLogger')) {
            AuditLogger::group([
                'feature'      => 'merchandise',
                'method'       => 'PUT',
                'target_table' => 'merchandise',
                'target_id'    => $merchandise_id,
                'action_label' => '商品の色を更新',
                'changes'      => self::setDiff('色', $beforeNames, $afterNames),
            ], $run);
            return;
        }
        $run();
    }

    /**
     * 集合の before/after から「追加/削除した要素」をまとめた changes を作る。
     * 差し替え系（色・サイズ等）のログを「やっていない作成/削除」に見せないため。
     */
    private static function setDiff(string $unit, array $before, array $after): array
    {
        $added   = array_values(array_diff($after, $before));
        $removed = array_values(array_diff($before, $after));
        $changes = [$unit . '数' => count($before) . '件 → ' . count($after) . '件'];
        if ($added)   $changes['追加'] = $added;
        if ($removed) $changes['削除'] = $removed;
        if (!$added && !$removed) $changes['内容'] = '変更なし（並び順等のみ）';
        return $changes;
    }

    /**
     * サイズを保存（完全置換）
     */
    public static function saveSizes(int $merchandise_id, array $sizes): void
    {
        $db = Database::getInstance();

        // 差し替え。before/after のサイズ名を比較して「追加/削除したサイズ」を1行記録。
        $beforeNames = array_values(array_filter(array_map(
            fn($s) => trim((string)($s['size_name'] ?? '')),
            self::getSizes($merchandise_id)
        ), fn($n) => $n !== ''));
        $afterNames = array_values(array_filter(array_map(
            fn($s) => trim((string)($s['size_name'] ?? '')),
            $sizes
        ), fn($n) => $n !== ''));

        $run = function () use ($db, $merchandise_id, $sizes) {
            $db->execute("DELETE FROM merchandise_sizes WHERE merchandise_id = ?", [$merchandise_id]);
            foreach ($sizes as $i => $s) {
                $name = trim($s['size_name'] ?? '');
                if ($name === '') continue;
                $db->insert(
                    "INSERT INTO merchandise_sizes (merchandise_id, size_name, sort_order)
                     VALUES (?, ?, ?)",
                    [
                        $merchandise_id,
                        $name,
                        (int)($s['sort_order'] ?? $i),
                    ]
                );
            }
        };

        if (class_exists('AuditLogger')) {
            AuditLogger::group([
                'feature'      => 'merchandise',
                'method'       => 'PUT',
                'target_table' => 'merchandise',
                'target_id'    => $merchandise_id,
                'action_label' => '商品のサイズを更新',
                'changes'      => self::setDiff('サイズ', $beforeNames, $afterNames),
            ], $run);
            return;
        }
        $run();
    }

    /**
     * 現在販売可能な商品を取得（is_active=1 かつ 販売期間内）
     */
    public static function findAvailable(): array
    {
        $now  = date('Y-m-d H:i:s');
        $rows = Database::getInstance()->fetchAll(
            "SELECT * FROM merchandise
             WHERE is_active = 1
               AND (sale_start IS NULL OR sale_start <= ?)
               AND (sale_end   IS NULL OR sale_end   >= ?)
             ORDER BY sort_order ASC, id DESC",
            [$now, $now]
        );
        foreach ($rows as &$r) {
            $r['colors'] = self::getColors((int)$r['id']);
            $r['sizes']  = self::getSizes((int)$r['id']);
        }
        return $rows;
    }
}
