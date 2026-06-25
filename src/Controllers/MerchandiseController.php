<?php
/**
 * 物販管理コントローラー（管理者）
 */
class MerchandiseController
{
    /**
     * 一覧ページ
     */
    public function indexPage(array $params): void
    {
        Auth::requirePermission('merchandise');
        $this->render('merchandise/index');
    }

    /**
     * 詳細ページ
     */
    public function detailPage(array $params): void
    {
        Auth::requirePermission('merchandise');
        $merch = Merchandise::findById((int)$params['id']);
        if (!$merch) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }
        $this->render('merchandise/detail', ['id' => (int)$params['id'], 'merchandise' => $merch]);
    }

    // ===== API: 商品 =====

    public function index(array $params): void
    {
        Auth::requirePermission('merchandise');
        Response::success(['merchandise' => Merchandise::findAll()]);
    }

    public function show(array $params): void
    {
        Auth::requirePermission('merchandise');
        $merch = Merchandise::findById((int)$params['id']);
        if (!$merch) {
            Response::error('商品が見つかりません', 404, 'NOT_FOUND');
            return;
        }
        Response::success($merch);
    }

    public function store(array $params): void
    {
        Auth::requirePermission('merchandise');
        $data = Request::only(['name', 'description', 'price', 'payment_deadline', 'sale_start', 'sale_end', 'is_active', 'sort_order']);

        if (empty(trim($data['name'] ?? ''))) {
            Response::error('商品名を入力してください', 400, 'VALIDATION_ERROR');
            return;
        }

        $merch = Merchandise::create($data);
        Response::success($merch);
    }

    public function update(array $params): void
    {
        Auth::requirePermission('merchandise');
        $data  = Request::only(['name', 'description', 'price', 'payment_deadline', 'sale_start', 'sale_end', 'is_active', 'sort_order']);
        $merch = Merchandise::update((int)$params['id'], $data);
        if (!$merch) {
            Response::error('商品が見つかりません', 404, 'NOT_FOUND');
            return;
        }
        Response::success($merch);
    }

    public function destroy(array $params): void
    {
        Auth::requirePermission('merchandise');
        Merchandise::delete((int)$params['id']);
        Response::success([]);
    }

    // ===== API: 色・サイズ =====

    public function saveColors(array $params): void
    {
        Auth::requirePermission('merchandise');
        $body   = Request::json();
        $colors = is_array($body['colors'] ?? null) ? $body['colors'] : [];
        Merchandise::saveColors((int)$params['id'], $colors);
        Response::success(['colors' => Merchandise::getColors((int)$params['id'])]);
    }

    public function saveSizes(array $params): void
    {
        Auth::requirePermission('merchandise');
        $body  = Request::json();
        $sizes = is_array($body['sizes'] ?? null) ? $body['sizes'] : [];
        Merchandise::saveSizes((int)$params['id'], $sizes);
        Response::success(['sizes' => Merchandise::getSizes((int)$params['id'])]);
    }

    // ===== API: 注文 =====

    public function orders(array $params): void
    {
        Auth::requirePermission('merchandise');
        $status = $_GET['status'] ?? null;
        $orders = MerchandiseOrder::findAll($status);

        // 商品IDで絞り込み（ルートパラメータ優先、なければクエリパラメータ）
        $mid = !empty($params['id']) ? (int)$params['id'] : (!empty($_GET['merchandise_id']) ? (int)$_GET['merchandise_id'] : 0);
        if ($mid > 0) {
            $orders = array_values(array_filter($orders, function ($o) use ($mid) {
                foreach ($o['items'] as $it) {
                    if ((int)$it['merchandise_id'] === $mid) return true;
                }
                return false;
            }));
        }

        Response::success(['orders' => $orders]);
    }

    /**
     * 支払い確認タブ用：全商品横断の注文検索 API
     * GET /api/merchandise/payments?status=&q=&submitted=
     */
    public function searchOrders(array $params): void
    {
        Auth::requirePermission('merchandise');
        $status        = $_GET['status'] ?? null;
        $q             = $_GET['q'] ?? null;
        $submittedOnly = !empty($_GET['submitted']);
        $orders        = MerchandiseOrder::search($status, $q, $submittedOnly);
        Response::success(['orders' => $orders]);
    }

    /**
     * 支払い完了（入金確認済み）注文の合計金額
     * GET /api/merchandise/paid-total?from=YYYY-MM-DD&to=YYYY-MM-DD
     * from/to を省略すると全期間の合計を返す。
     */
    public function paidTotal(array $params): void
    {
        Auth::requirePermission('merchandise');
        $from = $_GET['from'] ?? null;
        $to   = $_GET['to'] ?? null;
        Response::success(MerchandiseOrder::paidTotal($from, $to));
    }

    public function showOrder(array $params): void
    {
        Auth::requirePermission('merchandise');
        $order = MerchandiseOrder::findById((int)$params['id']);
        if (!$order) {
            Response::error('注文が見つかりません', 404, 'NOT_FOUND');
            return;
        }
        Response::success($order);
    }

    public function togglePaid(array $params): void
    {
        Auth::requirePermission('merchandise');
        $order = MerchandiseOrder::togglePaid((int)$params['id']);
        if (!$order) {
            Response::error('注文が見つかりません', 404, 'NOT_FOUND');
            return;
        }
        Response::success($order);
    }

    public function updateOrderStatus(array $params): void
    {
        Auth::requirePermission('merchandise');
        $status = Request::get('status') ?? '';
        $order  = MerchandiseOrder::updateStatus((int)$params['id'], $status);
        if (!$order) {
            Response::error('ステータスが不正です', 400, 'VALIDATION_ERROR');
            return;
        }
        Response::success($order);
    }

    public function destroyOrder(array $params): void
    {
        Auth::requirePermission('merchandise');
        MerchandiseOrder::delete((int)$params['id']);
        Response::success([]);
    }

    public function summary(array $params): void
    {
        Auth::requirePermission('merchandise');
        Response::success(['summary' => MerchandiseOrder::summaryByMerchandise((int)$params['id'])]);
    }

    // ===== API: 未マッチ注文（DB未登録の暫定購入者） =====

    public function pendingOrders(array $params): void
    {
        Auth::requirePermission('merchandise');
        Response::success(['orders' => MerchandiseOrder::findPending()]);
    }

    public function matchAllPending(array $params): void
    {
        Auth::requirePermission('merchandise');
        $result = MerchandiseOrder::matchAllPending();
        Response::success($result);
    }

    public function linkOrderToMember(array $params): void
    {
        Auth::requirePermission('merchandise');
        $memberId = (int)(Request::get('member_id') ?? 0);
        if ($memberId <= 0) {
            Response::error('会員IDが不正です', 400, 'VALIDATION_ERROR');
            return;
        }
        $ok = MerchandiseOrder::linkToMember((int)$params['id'], $memberId);
        if (!$ok) {
            Response::error('紐付けに失敗しました', 400, 'LINK_FAILED');
            return;
        }
        Response::success([]);
    }

    // ===== API: 公開URL =====

    public function getTokens(array $params): void
    {
        Auth::requirePermission('merchandise');
        $mid    = (int)($params['id'] ?? 0);
        $tokens = MerchandiseToken::findByMerchandise($mid);
        Response::success(['tokens' => $tokens]);
    }

    public function generateToken(array $params): void
    {
        Auth::requirePermission('merchandise');
        $mid   = (int)($params['id'] ?? 0);
        $label = Request::get('label');
        $token = MerchandiseToken::generate($label, 90, $mid);
        Response::success($token);
    }

    public function destroyToken(array $params): void
    {
        Auth::requirePermission('merchandise');
        MerchandiseToken::delete((int)$params['id']);
        Response::success([]);
    }

    // ===== API: 画像アップロード =====

    public function uploadImage(array $params): void
    {
        Auth::requirePermission('merchandise');

        if (empty($_FILES['image'])) {
            Response::error('ファイルが選択されていません', 400, 'NO_FILE');
            return;
        }

        $file = $_FILES['image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            Response::error('アップロードに失敗しました', 400, 'UPLOAD_ERROR');
            return;
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            Response::error('ファイルサイズは5MB以下にしてください', 400, 'FILE_TOO_LARGE');
            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            Response::error('jpg/png/gif/webpのみアップロードできます', 400, 'INVALID_TYPE');
            return;
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $imageInfo    = @getimagesize($file['tmp_name']);
        if ($imageInfo === false || !in_array($imageInfo['mime'], $allowedMimes)) {
            Response::error('画像ファイルではありません', 400, 'INVALID_MIME');
            return;
        }

        $uploadDir = BASE_PATH . '/uploads/merchandise/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $saveExt  = in_array($ext, ['jpg', 'jpeg']) ? 'jpg' : $ext;
        $safeFile = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $saveExt;
        $dest     = $uploadDir . $safeFile;

        if (!$this->compressImage($file['tmp_name'], $dest, $imageInfo['mime'])) {
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                Response::error('ファイルの保存に失敗しました', 500, 'SAVE_ERROR');
                return;
            }
        }

        Response::success(['path' => '/uploads/merchandise/' . $safeFile]);
    }

    private function compressImage(string $src, string $dest, string $mime, int $maxDim = 1920, int $quality = 85): bool
    {
        if (!function_exists('imagecreatefromjpeg')) return false;

        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($src),
            'image/png'  => @imagecreatefrompng($src),
            'image/gif'  => @imagecreatefromgif($src),
            'image/webp' => @imagecreatefromwebp($src),
            default      => false,
        };
        if (!$img) return false;

        $w = imagesx($img);
        $h = imagesy($img);
        if ($w > $maxDim || $h > $maxDim) {
            if ($w >= $h) {
                $newW = $maxDim;
                $newH = (int)round($h * $maxDim / $w);
            } else {
                $newH = $maxDim;
                $newW = (int)round($w * $maxDim / $h);
            }
            $canvas = imagecreatetruecolor($newW, $newH);
            if ($mime === 'image/png' || $mime === 'image/gif') {
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                $trans = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefilledrectangle($canvas, 0, 0, $newW, $newH, $trans);
            }
            imagecopyresampled($canvas, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
            imagedestroy($img);
            $img = $canvas;
        }

        $result = match ($mime) {
            'image/jpeg' => imagejpeg($img, $dest, $quality),
            'image/png'  => imagepng($img, $dest, 6),
            'image/gif'  => imagegif($img, $dest),
            'image/webp' => imagewebp($img, $dest, $quality),
            default      => false,
        };
        imagedestroy($img);
        return (bool)$result;
    }

    private function render(string $view, array $data = []): void
    {
        extract($data);
        $config  = require CONFIG_PATH . '/app.php';
        $appName = $config['name'];

        ob_start();
        include VIEWS_PATH . '/' . $view . '.php';
        $content = ob_get_clean();

        include VIEWS_PATH . '/layouts/main.php';
    }
}
