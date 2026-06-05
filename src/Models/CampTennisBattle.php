<?php
/**
 * テニス班分けモデル（団体戦チーム / 紅白戦チーム / 紅白戦対戦表）
 * 合宿ごとに1行。JSON カラムで保持。
 */
class CampTennisBattle
{
    private Database $db;

    private const JSON_FIELDS = [
        'team_battle_teams',
        'kohaku_teams',
        'kohaku_matches',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByCampId(int $campId): ?array
    {
        $row = $this->db->fetch(
            "SELECT * FROM camp_tennis_battles WHERE camp_id = ?",
            [$campId]
        );

        if (!$row) {
            return null;
        }

        return $this->decode($row);
    }

    public function upsert(int $campId, array $data): bool
    {
        $existing = $this->db->fetch(
            "SELECT id FROM camp_tennis_battles WHERE camp_id = ?",
            [$campId]
        );

        $allowedFields = [
            'team_battle_teams', 'team_battle_rules',
            'kohaku_teams', 'kohaku_rules', 'kohaku_matches',
        ];

        $filtered = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $val = $data[$field];
                if (in_array($field, self::JSON_FIELDS) && (is_array($val) || is_object($val))) {
                    $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                }
                $filtered[$field] = $val;
            }
        }

        if (empty($filtered)) {
            return false;
        }

        if ($existing) {
            $sets = implode(', ', array_map(fn($f) => "{$f} = ?", array_keys($filtered)));
            $values = array_values($filtered);
            $values[] = $campId;
            return $this->db->execute(
                "UPDATE camp_tennis_battles SET {$sets} WHERE camp_id = ?",
                $values
            ) >= 0;
        }

        $filtered['camp_id'] = $campId;
        $cols = implode(', ', array_keys($filtered));
        $placeholders = implode(', ', array_fill(0, count($filtered), '?'));
        $this->db->insert(
            "INSERT INTO camp_tennis_battles ({$cols}) VALUES ({$placeholders})",
            array_values($filtered)
        );
        return true;
    }

    private function decode(array $row): array
    {
        foreach (self::JSON_FIELDS as $field) {
            if (isset($row[$field]) && is_string($row[$field])) {
                $row[$field] = json_decode($row[$field], true);
            }
        }
        // kohaku_teams は連想配列、その他は配列をデフォルトに
        if (empty($row['team_battle_teams'])) $row['team_battle_teams'] = [];
        if (empty($row['kohaku_matches']))    $row['kohaku_matches']    = [];
        if (empty($row['kohaku_teams']))      $row['kohaku_teams']      = ['red' => [], 'white' => []];
        return $row;
    }
}
