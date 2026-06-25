<?php
/**
 * ゲスト人物マスタ（企画横断で共有）
 *
 * 新歓は「氏名・カナ・学科」、OBOG は「氏名・カナ・代」で個人を同定する。
 * person_type + name + name_kana + match_key の完全一致で同一人物とみなす。
 */
class GuestPerson
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 3項目の完全一致で人物を検索（無ければ null）
     */
    public function findByKeys(string $personType, string $name, string $nameKana, string $matchKey): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM guest_persons
              WHERE person_type = ? AND name = ? AND name_kana = ? AND match_key = ?",
            [$personType, $name, $nameKana, $matchKey]
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM guest_persons WHERE id = ?", [$id]);
    }

    /**
     * 既存の人物を取得、無ければ作成して ID を返す
     */
    public function findOrCreate(string $personType, string $name, string $nameKana, string $matchKey): int
    {
        $existing = $this->findByKeys($personType, $name, $nameKana, $matchKey);
        if ($existing) {
            return (int)$existing['id'];
        }
        return $this->db->insert(
            "INSERT INTO guest_persons (person_type, name, name_kana, match_key)
             VALUES (?, ?, ?, ?)",
            [$personType, $name, $nameKana, $matchKey]
        );
    }
}
