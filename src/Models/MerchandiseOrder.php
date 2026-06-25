<?php
/**
 * 物販注文モデル
 */
class MerchandiseOrder
{
    /**
     * 全注文を取得（明細・購入者情報付き）
     */
    public static function findAll(?string $paymentStatus = null): array
    {
        $where  = '';
        $params = [];
        if ($paymentStatus !== null) {
            $where    = ' WHERE o.payment_status = ?';
            $params[] = $paymentStatus;
        }

        $orders = Database::getInstance()->fetchAll(
            "SELECT o.*, m.name_kanji as member_name_kanji, m.student_id as member_student_id
             FROM merchandise_orders o
             LEFT JOIN members m ON m.id = o.member_id
             {$where}
             ORDER BY o.created_at DESC",
            $params
        );

        foreach ($orders as &$o) {
            $o['items'] = self::getItems((int)$o['id']);
        }
        return $orders;
    }

    /**
     * 支払い確認タブ用：全商品横断の注文検索
     * @param string|null $status     payment_status 絞り込み（unpaid/paid/cancelled）
     * @param string|null $q          購入者名・カナ・学籍番号・会員名の部分一致
     * @param bool        $submittedOnly true=会員が振込報告済みの注文のみ
     */
    public static function search(?string $status = null, ?string $q = null, bool $submittedOnly = false): array
    {
        $where  = [];
        $params = [];

        if ($status !== null && $status !== '') {
            $where[]  = 'o.payment_status = ?';
            $params[] = $status;
        }
        if ($submittedOnly) {
            $where[] = 'o.payment_submitted = 1';
        }
        if ($q !== null && trim($q) !== '') {
            $term = trim($q);
            // 名前（漢字）・フリガナ（カナ）はひらがな⇔カタカナを揃えて検索
            [$nameCond, $nameParams] = NameSearchService::buildCondition(
                $term,
                ['o.buyer_name', 'm.name_kanji'],
                ['o.buyer_kana']
            );
            // 学籍番号は入力そのままで部分一致
            $idLike  = '%' . $term . '%';
            $clauses = [];
            $params2 = [];
            if ($nameCond !== '') {
                $clauses[] = $nameCond;
                foreach ($nameParams as $p) $params2[] = $p;
            }
            $clauses[] = 'o.pending_student_id LIKE ?';
            $params2[] = $idLike;
            $clauses[] = 'm.student_id LIKE ?';
            $params2[] = $idLike;

            $where[]  = '(' . implode(' OR ', $clauses) . ')';
            $params   = array_merge($params, $params2);
        }

        $sql = "SELECT o.*, m.name_kanji as member_name_kanji, m.student_id as member_student_id
                FROM merchandise_orders o
                LEFT JOIN members m ON m.id = o.member_id";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        // 振込報告済みかつ未入金（＝確認待ち）を上に、その後は新しい順
        $sql .= " ORDER BY (o.payment_status = 'unpaid' AND o.payment_submitted = 1) DESC, o.created_at DESC";

        $orders = Database::getInstance()->fetchAll($sql, $params);
        foreach ($orders as &$o) {
            $o['items'] = self::getItems((int)$o['id']);
        }
        unset($o);

        // 商品が物販管理から削除された注文は除外
        return array_values(array_filter($orders, fn($o) => self::hasExistingMerchandise($o)));
    }

    public static function findById(int $id): ?array
    {
        $order = Database::getInstance()->fetch(
            "SELECT o.*, m.name_kanji as member_name_kanji, m.student_id as member_student_id
             FROM merchandise_orders o
             LEFT JOIN members m ON m.id = o.member_id
             WHERE o.id = ?",
            [$id]
        );
        if (!$order) return null;
        $order['items'] = self::getItems($id);
        return $order;
    }

    public static function getItems(int $order_id): array
    {
        return Database::getInstance()->fetchAll(
            "SELECT * FROM merchandise_order_items WHERE order_id = ? ORDER BY id ASC",
            [$order_id]
        );
    }

    /**
     * 支払い完了（入金確認済み）注文の合計金額を集計する。
     * payment_status = 'paid' の注文の total_amount を合計する。
     * 期間指定がある場合は paid_at（入金確認日時）で絞り込む。
     *
     * @param string|null $from 集計開始日（YYYY-MM-DD）。その日の 00:00:00 以降。
     * @param string|null $to   集計終了日（YYYY-MM-DD）。その日の 23:59:59 まで。
     * @return array{total: int, count: int}
     */
    public static function paidTotal(?string $from = null, ?string $to = null): array
    {
        $where  = ["o.payment_status = 'paid'"];
        $params = [];

        if ($from !== null && trim($from) !== '') {
            $where[]  = 'o.paid_at >= ?';
            $params[] = trim($from) . ' 00:00:00';
        }
        if ($to !== null && trim($to) !== '') {
            $where[]  = 'o.paid_at <= ?';
            $params[] = trim($to) . ' 23:59:59';
        }

        $row = Database::getInstance()->fetch(
            "SELECT COALESCE(SUM(o.total_amount), 0) AS total, COUNT(*) AS cnt
             FROM merchandise_orders o
             WHERE " . implode(' AND ', $where),
            $params
        );

        return [
            'total' => (int)($row['total'] ?? 0),
            'count' => (int)($row['cnt'] ?? 0),
        ];
    }

    /**
     * 商品別の注文集計（管理画面の「商品別売上」用）
     */
    public static function summaryByMerchandise(int $merchandise_id): array
    {
        return Database::getInstance()->fetchAll(
            "SELECT moi.color_name, moi.size_name,
                    SUM(moi.quantity) as total_quantity,
                    SUM(moi.subtotal) as total_amount,
                    COUNT(DISTINCT moi.order_id) as order_count
             FROM merchandise_order_items moi
             JOIN merchandise_orders o ON o.id = moi.order_id
             WHERE moi.merchandise_id = ?
               AND o.payment_status != 'cancelled'
             GROUP BY moi.color_name, moi.size_name
             ORDER BY moi.color_name, moi.size_name",
            [$merchandise_id]
        );
    }

    /**
     * カート内容から明細配列と合計金額を構築
     * @return array{items: array, total: int}
     */
    private static function buildItemsFromCart(array $cart): array
    {
        $db    = Database::getInstance();
        $items = [];
        $total = 0;
        foreach ($cart as $line) {
            $mid = (int)($line['merchandise_id'] ?? 0);
            $qty = max(1, (int)($line['quantity'] ?? 1));
            if ($mid <= 0 || $qty <= 0) continue;

            $merch = $db->fetch(
                "SELECT id, name, price FROM merchandise WHERE id = ? AND is_active = 1",
                [$mid]
            );
            if (!$merch) {
                throw new Exception('商品が見つかりません');
            }

            $colorName = null;
            $colorId   = null;
            if (!empty($line['color_id'])) {
                $color = $db->fetch(
                    "SELECT id, color_name FROM merchandise_colors WHERE id = ? AND merchandise_id = ?",
                    [(int)$line['color_id'], $mid]
                );
                if ($color) {
                    $colorId   = (int)$color['id'];
                    $colorName = $color['color_name'];
                }
            }

            $sizeName = null;
            $sizeId   = null;
            if (!empty($line['size_id'])) {
                $size = $db->fetch(
                    "SELECT id, size_name FROM merchandise_sizes WHERE id = ? AND merchandise_id = ?",
                    [(int)$line['size_id'], $mid]
                );
                if ($size) {
                    $sizeId   = (int)$size['id'];
                    $sizeName = $size['size_name'];
                }
            }

            $unit     = (int)$merch['price'];
            $subtotal = $unit * $qty;
            $total   += $subtotal;

            $items[] = [
                'merchandise_id'   => $mid,
                'merchandise_name' => $merch['name'],
                'color_id'         => $colorId,
                'color_name'       => $colorName,
                'size_id'          => $sizeId,
                'size_name'        => $sizeName,
                'quantity'         => $qty,
                'unit_price'       => $unit,
                'subtotal'         => $subtotal,
            ];
        }

        if (empty($items)) {
            throw new Exception('カートが空です');
        }

        return ['items' => $items, 'total' => $total];
    }

    /**
     * 同一購入者・同一商品の未払い注文を返す（あれば）
     * 会員: member_id 一致／暫定: pending_student_id 一致 かつ member_id NULL
     * カート内の merchandise_id と一致する注文のみを対象にし、
     * 別商品の注文を誤って上書きしないようにする。
     */
    private static function findExistingUnpaidOrder(array $buyer, array $cart): ?array
    {
        $db = Database::getInstance();

        // カート内の商品IDを収集
        $merchandiseIds = array_values(array_unique(array_filter(
            array_map(fn($line) => (int)($line['merchandise_id'] ?? 0), $cart)
        )));
        if (empty($merchandiseIds)) return null;

        $placeholders = implode(',', array_fill(0, count($merchandiseIds), '?'));

        if (!empty($buyer['member_id'])) {
            return $db->fetch(
                "SELECT DISTINCT o.id FROM merchandise_orders o
                 JOIN merchandise_order_items oi ON oi.order_id = o.id
                 WHERE o.member_id = ? AND o.payment_status = 'unpaid'
                   AND oi.merchandise_id IN ({$placeholders})
                 ORDER BY o.created_at DESC LIMIT 1",
                array_merge([(int)$buyer['member_id']], $merchandiseIds)
            );
        }
        if (!empty($buyer['pending_student_id'])) {
            return $db->fetch(
                "SELECT DISTINCT o.id FROM merchandise_orders o
                 JOIN merchandise_order_items oi ON oi.order_id = o.id
                 WHERE o.pending_student_id = ?
                   AND o.member_id IS NULL
                   AND o.payment_status = 'unpaid'
                   AND oi.merchandise_id IN ({$placeholders})
                 ORDER BY o.created_at DESC LIMIT 1",
                array_merge([trim($buyer['pending_student_id'])], $merchandiseIds)
            );
        }
        return null;
    }

    /**
     * 注文を作成または既存の未払い注文を上書き
     * 同一購入者・同一商品の未払い注文があれば内容を完全上書きし、なければ新規作成する。
     * 戻り値の 'was_updated' は true=更新, false=新規。
     */
    public static function createOrUpdate(array $cart, array $buyer): ?array
    {
        $run = function () use ($cart, $buyer) {
            $existing = self::findExistingUnpaidOrder($buyer, $cart);
            if ($existing) {
                $order = self::update((int)$existing['id'], $cart, $buyer);
                if ($order) $order['was_updated'] = true;
                return $order;
            }
            $order = self::create($cart, $buyer);
            if ($order) $order['was_updated'] = false;
            return $order;
        };

        // 幹部が代理操作したときのみ、注文ヘッダ＋明細を「1操作=1行」でまとめて記録。
        // 会員自身の購入（公開フォーム）は Auth が非幹部のため group() でも記録されない。
        if (class_exists('AuditLogger')) {
            return AuditLogger::group([
                'feature'      => 'merchandise',
                'target_table' => 'merchandise_orders',
                'resolve'      => function ($order) {
                    if (!$order) return ['action_label' => '物販注文を登録（失敗）'];
                    $qty = 0;
                    foreach (($order['items'] ?? []) as $it) { $qty += (int)($it['quantity'] ?? 0); }
                    return [
                        'method'       => !empty($order['was_updated']) ? 'PUT' : 'POST',
                        'action_label' => '物販注文を' . (!empty($order['was_updated']) ? '変更' : '作成'),
                        'target_id'    => (int)($order['id'] ?? 0),
                        'changes'      => [
                            '購入者'    => $order['buyer_name'] ?? '',
                            '点数'      => $qty,
                            '合計金額'  => (int)($order['total_amount'] ?? 0) . '円',
                        ],
                    ];
                },
            ], $run);
        }
        return $run();
    }

    /**
     * 注文を作成（明細含む）
     * cart: [{merchandise_id, color_id, size_id, quantity}, ...]
     * buyer: {name, kana, contact, member_id, notes}
     */
    public static function create(array $cart, array $buyer): ?array
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $built = self::buildItemsFromCart($cart);
            $items = $built['items'];
            $total = $built['total'];

            $orderId = $db->insert(
                "INSERT INTO merchandise_orders
                 (member_id, pending_student_id, buyer_name, buyer_kana, pending_line_name, pending_phone, buyer_contact, total_amount, payment_status, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'unpaid', ?)",
                [
                    !empty($buyer['member_id']) ? (int)$buyer['member_id'] : null,
                    !empty($buyer['pending_student_id']) ? trim($buyer['pending_student_id']) : null,
                    trim($buyer['name'] ?? ''),
                    trim($buyer['kana']               ?? '') ?: null,
                    trim($buyer['pending_line_name']  ?? '') ?: null,
                    trim($buyer['pending_phone']      ?? '') ?: null,
                    trim($buyer['contact']            ?? '') ?: null,
                    $total,
                    trim($buyer['notes']              ?? '') ?: null,
                ]
            );

            foreach ($items as $it) {
                $db->insert(
                    "INSERT INTO merchandise_order_items
                     (order_id, merchandise_id, color_id, size_id, color_name, size_name, merchandise_name, quantity, unit_price, subtotal)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $orderId,
                        $it['merchandise_id'],
                        $it['color_id'],
                        $it['size_id'],
                        $it['color_name'],
                        $it['size_name'],
                        $it['merchandise_name'],
                        $it['quantity'],
                        $it['unit_price'],
                        $it['subtotal'],
                    ]
                );
            }

            $db->commit();
            return self::findById($orderId);

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * 既存の未払い注文を完全上書き
     * 明細は全削除して再登録、ヘッダ（合計・購入者情報・備考）も最新値に更新する。
     */
    public static function update(int $orderId, array $cart, array $buyer): ?array
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $current = $db->fetch(
                "SELECT id, payment_status FROM merchandise_orders WHERE id = ?",
                [$orderId]
            );
            if (!$current) {
                throw new Exception('注文が見つかりません');
            }
            if ($current['payment_status'] !== 'unpaid') {
                throw new Exception('支払い済み・キャンセル済みの注文は変更できません');
            }

            $built = self::buildItemsFromCart($cart);
            $items = $built['items'];
            $total = $built['total'];

            $db->execute(
                "UPDATE merchandise_orders SET
                    member_id            = ?,
                    pending_student_id   = ?,
                    buyer_name           = ?,
                    buyer_kana           = ?,
                    pending_line_name    = ?,
                    pending_phone        = ?,
                    buyer_contact        = ?,
                    total_amount         = ?,
                    notes                = ?,
                    payment_submitted    = 0,
                    payment_submitted_at = NULL
                 WHERE id = ?",
                [
                    !empty($buyer['member_id']) ? (int)$buyer['member_id'] : null,
                    !empty($buyer['pending_student_id']) ? trim($buyer['pending_student_id']) : null,
                    trim($buyer['name'] ?? ''),
                    trim($buyer['kana']               ?? '') ?: null,
                    trim($buyer['pending_line_name']  ?? '') ?: null,
                    trim($buyer['pending_phone']      ?? '') ?: null,
                    trim($buyer['contact']            ?? '') ?: null,
                    $total,
                    trim($buyer['notes']              ?? '') ?: null,
                    $orderId,
                ]
            );

            $db->execute("DELETE FROM merchandise_order_items WHERE order_id = ?", [$orderId]);

            foreach ($items as $it) {
                $db->insert(
                    "INSERT INTO merchandise_order_items
                     (order_id, merchandise_id, color_id, size_id, color_name, size_name, merchandise_name, quantity, unit_price, subtotal)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $orderId,
                        $it['merchandise_id'],
                        $it['color_id'],
                        $it['size_id'],
                        $it['color_name'],
                        $it['size_name'],
                        $it['merchandise_name'],
                        $it['quantity'],
                        $it['unit_price'],
                        $it['subtotal'],
                    ]
                );
            }

            $db->commit();
            return self::findById($orderId);

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * 振込確認状態を切り替え（paid <-> unpaid）
     */
    public static function togglePaid(int $id): ?array
    {
        $db = Database::getInstance();
        $row = $db->fetch("SELECT payment_status FROM merchandise_orders WHERE id = ?", [$id]);
        if (!$row) return null;

        $newStatus = $row['payment_status'] === 'paid' ? 'unpaid' : 'paid';
        $paidAt    = $newStatus === 'paid' ? date('Y-m-d H:i:s') : null;

        // 入金確認（paid化）の場合、期限超過していたら遅延を確定記録（ペナルティ永続化）
        if ($newStatus === 'paid' && class_exists('MemberPenalty')) {
            (new MemberPenalty())->recordSnapshot('merchandise', $id);
        }

        $db->execute(
            "UPDATE merchandise_orders SET payment_status = ?, paid_at = ? WHERE id = ?",
            [$newStatus, $paidAt, $id]
        );
        return self::findById($id);
    }

    /**
     * 会員による振込完了報告
     * 注文が指定会員のもので未払いの場合のみ payment_submitted=1 にする。
     * すでに報告済み・支払い済み・他人の注文の場合は false。
     * $paidAmount に申告金額（null なら注文合計を使用）を保存する。
     */
    public static function submitPayment(int $orderId, int $memberId, ?int $paidAmount = null): bool
    {
        $db  = Database::getInstance();
        $row = $db->fetch(
            "SELECT id, member_id, payment_status, payment_submitted, total_amount
             FROM merchandise_orders WHERE id = ?",
            [$orderId]
        );
        if (!$row) return false;
        if ((int)$row['member_id'] !== $memberId) return false;
        if ($row['payment_status'] !== 'unpaid') return false;
        if ((int)$row['payment_submitted'] === 1) return false;

        // 金額未指定なら注文合計を使用、負数は0に丸める
        $amount = $paidAmount !== null ? max(0, $paidAmount) : (int)$row['total_amount'];

        // 振込報告（提出）時点で期限超過していたら遅延を確定記録（ペナルティ永続化）
        if (class_exists('MemberPenalty')) {
            (new MemberPenalty())->recordSnapshot('merchandise', $orderId);
        }

        $db->execute(
            "UPDATE merchandise_orders
             SET payment_submitted = 1, payment_submitted_at = ?, paid_amount = ?
             WHERE id = ?",
            [date('Y-m-d H:i:s'), $amount, $orderId]
        );
        return true;
    }

    /**
     * 会員ホーム表示用：支払い報告がまだの注文一覧
     * 条件＝指定会員・未入金・未報告。
     * 集金は販売締切後に行うため、販売期間が過ぎても未報告なら表示し続ける。
     * ただし、商品が物販管理から削除された注文は表示しない。
     */
    public static function getPendingPaymentsByMemberId(int $memberId): array
    {
        if ($memberId <= 0) return [];

        $orders = Database::getInstance()->fetchAll(
            "SELECT * FROM merchandise_orders
             WHERE member_id = ?
               AND payment_status = 'unpaid'
               AND payment_submitted = 0
             ORDER BY created_at DESC",
            [$memberId]
        );
        $result = [];
        foreach ($orders as $o) {
            $o['items'] = self::getItems((int)$o['id']);
            if (self::hasExistingMerchandise($o)) {
                $result[] = $o;
            }
        }
        return $result;
    }

    /**
     * 注文に、現在も存在する（管理から削除されていない）商品が
     * 1つでも含まれているか。削除済み商品のみの注文は false。
     */
    public static function hasExistingMerchandise(array $order): bool
    {
        $items = $order['items'] ?? self::getItems((int)$order['id']);
        $ids   = array_values(array_unique(array_filter(
            array_map(fn($it) => (int)($it['merchandise_id'] ?? 0), $items)
        )));
        if (empty($ids)) return false;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $count = (int)(Database::getInstance()->fetch(
            "SELECT COUNT(*) AS c FROM merchandise WHERE id IN ({$placeholders})",
            $ids
        )['c'] ?? 0);
        return $count > 0;
    }

    /**
     * 支払いフォーム表示用に、指定会員の注文1件を取得する。
     * 他人の注文・存在しない注文は null。
     */
    public static function findForMember(int $orderId, int $memberId): ?array
    {
        if ($orderId <= 0 || $memberId <= 0) return null;
        $order = self::findById($orderId);
        if (!$order) return null;
        if ((int)$order['member_id'] !== $memberId) return null;
        return $order;
    }

    public static function updateStatus(int $id, string $status): ?array
    {
        $allowed = ['unpaid', 'paid', 'cancelled'];
        if (!in_array($status, $allowed)) return null;

        $paidAt = $status === 'paid' ? date('Y-m-d H:i:s') : null;
        Database::getInstance()->execute(
            "UPDATE merchandise_orders SET payment_status = ?, paid_at = ? WHERE id = ?",
            [$status, $paidAt, $id]
        );
        return self::findById($id);
    }

    public static function delete(int $id): bool
    {
        return Database::getInstance()->execute(
            "DELETE FROM merchandise_orders WHERE id = ?",
            [$id]
        ) > 0;
    }

    /**
     * 商品価格の変更を、その商品を含む全注文に反映する。
     * 該当明細の unit_price / subtotal を新価格で再計算し、
     * 影響を受けた注文の total_amount を再計算する。
     * @return int 金額が変わった注文の件数
     */
    public static function repriceByMerchandise(int $merchandiseId, int $newPrice): int
    {
        if ($merchandiseId <= 0) return 0;
        $db = Database::getInstance();

        // 対象商品を含む注文IDを先に把握
        $orderIds = array_map(
            fn($r) => (int)$r['order_id'],
            $db->fetchAll(
                "SELECT DISTINCT order_id FROM merchandise_order_items WHERE merchandise_id = ?",
                [$merchandiseId]
            )
        );
        if (empty($orderIds)) return 0;

        $db->beginTransaction();
        try {
            // 明細の単価・小計を新価格で更新
            $db->execute(
                "UPDATE merchandise_order_items
                 SET unit_price = ?, subtotal = ? * quantity
                 WHERE merchandise_id = ?",
                [$newPrice, $newPrice, $merchandiseId]
            );

            // 影響を受けた注文の合計を再計算
            $changed = 0;
            foreach ($orderIds as $oid) {
                $sum = (int)($db->fetch(
                    "SELECT COALESCE(SUM(subtotal), 0) AS total FROM merchandise_order_items WHERE order_id = ?",
                    [$oid]
                )['total'] ?? 0);
                $affected = $db->execute(
                    "UPDATE merchandise_orders SET total_amount = ? WHERE id = ? AND total_amount <> ?",
                    [$sum, $oid, $sum]
                );
                if ($affected > 0) $changed++;
            }

            $db->commit();
            return $changed;
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * 未マッチ注文一覧（pending_student_id があり member_id が NULL）
     */
    public static function findPending(): array
    {
        $orders = Database::getInstance()->fetchAll(
            "SELECT o.*,
                    m.id           AS matched_member_id,
                    m.name_kanji   AS matched_member_name
             FROM merchandise_orders o
             LEFT JOIN members m ON m.student_id = o.pending_student_id
             WHERE o.member_id IS NULL
               AND o.pending_student_id IS NOT NULL
             ORDER BY o.created_at DESC"
        );
        foreach ($orders as &$o) {
            $o['items'] = self::getItems((int)$o['id']);
        }
        return $orders;
    }

    /**
     * 未マッチ注文を会員DBと突合し、見つかれば紐付け
     * @return array ['matched' => int, 'unmatched' => int]
     */
    public static function matchAllPending(): array
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT o.id, m.id AS member_id
             FROM merchandise_orders o
             JOIN members m ON m.student_id = o.pending_student_id
             WHERE o.member_id IS NULL
               AND o.pending_student_id IS NOT NULL"
        );
        $matched = 0;
        foreach ($rows as $r) {
            $db->execute(
                "UPDATE merchandise_orders SET member_id = ? WHERE id = ?",
                [(int)$r['member_id'], (int)$r['id']]
            );
            $matched++;
        }
        $unmatched = (int)($db->fetch(
            "SELECT COUNT(*) AS c FROM merchandise_orders
             WHERE member_id IS NULL AND pending_student_id IS NOT NULL"
        )['c'] ?? 0);

        return ['matched' => $matched, 'unmatched' => $unmatched];
    }

    /**
     * 単一の会員IDに対し、同じ学籍番号の未マッチ注文を紐付け
     * 会員追加時のフックから呼ぶ
     */
    public static function matchByStudentId(string $studentId, int $memberId): int
    {
        if ($studentId === '') return 0;
        return Database::getInstance()->execute(
            "UPDATE merchandise_orders
             SET member_id = ?
             WHERE member_id IS NULL
               AND pending_student_id = ?",
            [$memberId, $studentId]
        );
    }

    /**
     * 注文を手動で会員に紐付ける
     */
    public static function linkToMember(int $orderId, int $memberId): bool
    {
        return Database::getInstance()->execute(
            "UPDATE merchandise_orders SET member_id = ? WHERE id = ?",
            [$memberId, $orderId]
        ) > 0;
    }
}
