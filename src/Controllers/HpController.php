<?php
/**
 * HP コンテンツ管理コントローラー
 */
class HpController
{
    private HpNews     $newsModel;
    private HpSchedule $scheduleModel;
    private HpSettings $settingsModel;

    public function __construct()
    {
        $this->newsModel     = new HpNews();
        $this->scheduleModel = new HpSchedule();
        $this->settingsModel = new HpSettings();
    }

    // ─────────────────────────────────────────
    // 管理者ページ（HTML表示）
    // ─────────────────────────────────────────

    /**
     * GET /hp
     * HP管理メインページ
     */
    public function indexPage(array $params): void
    {
        Auth::requirePermission('hp');

        $schedule = $this->scheduleModel->all();
        foreach ($schedule as &$s) {
            $s['images'] = json_decode($s['images'] ?? '[]', true) ?: [];
        }
        unset($s);

        $news     = $this->newsModel->all();
        $settings = $this->settingsModel->all();

        $this->render('hp/index', [
            'schedule' => $schedule,
            'news'     => $news,
            'settings' => $settings,
        ]);
    }

    // ─────────────────────────────────────────
    // 公開 API（認証不要）
    // ─────────────────────────────────────────

    /**
     * GET /api/hp/public
     * 公開HP用コンテンツを一括返却
     */
    public function publicContent(array $params): void
    {
        $settings = $this->settingsModel->all();

        // JSON値をデコード
        foreach (['about_info', 'about_achievements', 'quick_news'] as $key) {
            if (isset($settings[$key])) {
                $settings[$key] = json_decode($settings[$key], true);
            }
        }

        Response::success([
            'settings' => $settings,
            'news'     => $this->newsModel->all(),
            'schedule' => $this->scheduleModel->allAsMap(),
        ]);
    }

    // ─────────────────────────────────────────
    // 管理者 API
    // ─────────────────────────────────────────

    /**
     * PUT /api/hp/settings
     * About・Contact設定を更新
     */
    public function updateSettings(array $params): void
    {
        Auth::requirePermission('hp');

        $keys = ['about_description', 'about_info', 'about_achievements', 'contact_instagram', 'contact_twitter', 'quick_news'];
        foreach ($keys as $key) {
            $val = Request::get($key);
            if ($val !== null) {
                $this->settingsModel->set($key, $val);
            }
        }

        Response::success([], '保存しました');
    }

    /**
     * POST /api/hp/news
     * ニュースを新規作成
     */
    public function storeNews(array $params): void
    {
        Auth::requirePermission('hp');

        $errors = Request::validate([
            'title'     => 'required',
            'news_date' => 'required',
        ]);
        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        $id = $this->newsModel->create([
            'news_date'     => Request::get('news_date'),
            'title'         => Request::get('title'),
            'description'   => Request::get('description') ?? '',
            'image_path'    => Request::get('image_path') ?? null,
            'anchor_id'     => Request::get('anchor_id') ?? null,
            'is_quick_news' => Request::get('is_quick_news') ?? 0,
            'sort_order'    => Request::get('sort_order') ?? 0,
        ]);

        Response::success(['id' => $id], '作成しました');
    }

    /**
     * PUT /api/hp/news/{id}
     * ニュースを更新
     */
    public function updateNews(array $params): void
    {
        Auth::requirePermission('hp');

        $id   = (int)$params['id'];
        $item = $this->newsModel->find($id);
        if (!$item) {
            Response::error('見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $this->newsModel->update($id, [
            'news_date'     => Request::get('news_date')     ?? $item['news_date'],
            'title'         => Request::get('title')         ?? $item['title'],
            'description'   => Request::get('description')   ?? $item['description'],
            'image_path'    => Request::get('image_path')    ?? $item['image_path'],
            'anchor_id'     => Request::get('anchor_id')     ?? $item['anchor_id'],
            'is_quick_news' => Request::get('is_quick_news') ?? $item['is_quick_news'],
            'sort_order'    => Request::get('sort_order')    ?? $item['sort_order'],
        ]);

        Response::success([], '更新しました');
    }

    /**
     * DELETE /api/hp/news/{id}
     * ニュースを削除
     */
    public function destroyNews(array $params): void
    {
        Auth::requirePermission('hp');

        $id = (int)$params['id'];
        if (!$this->newsModel->find($id)) {
            Response::error('見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $this->newsModel->delete($id);
        Response::success([], '削除しました');
    }

    /**
     * PUT /api/hp/schedule/{id}
     * スケジュール月データを更新
     */
    public function updateSchedule(array $params): void
    {
        Auth::requirePermission('hp');

        $id  = (int)$params['id'];
        $row = $this->scheduleModel->find($id);
        if (!$row) {
            Response::error('見つかりません', 404, 'NOT_FOUND');
            return;
        }

        $imagesRaw = Request::get('images');
        $images    = null;
        if ($imagesRaw !== null) {
            // JSON文字列またはすでに配列
            $images = is_array($imagesRaw) ? $imagesRaw : (json_decode($imagesRaw, true) ?: []);
        }

        $data = [];
        if (Request::get('title')     !== null) $data['title']     = Request::get('title');
        if (Request::get('text_html') !== null) $data['text_html'] = Request::get('text_html');
        if (Request::get('extra_html')!== null) $data['extra_html']= Request::get('extra_html');
        if (Request::get('type')      !== null) $data['type']      = Request::get('type');
        if ($images !== null)                   $data['images']    = $images;

        $this->scheduleModel->update($id, $data);
        Response::success([], '更新しました');
    }

    /**
     * POST /api/hp/upload
     * 画像をアップロードしてパスを返す
     */
    public function uploadImage(array $params): void
    {
        Auth::requirePermission('hp');

        if (empty($_FILES['image'])) {
            Response::error('ファイルが選択されていません', 400, 'NO_FILE');
            return;
        }

        $file = $_FILES['image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            Response::error('アップロードに失敗しました', 400, 'UPLOAD_ERROR');
            return;
        }

        // ファイルサイズ制限 5MB
        if ($file['size'] > 5 * 1024 * 1024) {
            Response::error('ファイルサイズは5MB以下にしてください', 400, 'FILE_TOO_LARGE');
            return;
        }

        // 拡張子チェック
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            Response::error('jpg/png/gif/webpのみアップロードできます', 400, 'INVALID_TYPE');
            return;
        }

        // MIMEタイプ検証（拡張子偽装対策）
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false || !in_array($imageInfo['mime'], $allowedMimes)) {
            Response::error('画像ファイルではありません', 400, 'INVALID_MIME');
            return;
        }

        // 保存先
        $uploadDir = BASE_PATH . '/uploads/hp/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // JPEG/WebP は .jpg で統一して保存
        $saveExt  = in_array($ext, ['jpg', 'jpeg']) ? 'jpg' : $ext;
        $safeFile = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $saveExt;
        $dest     = $uploadDir . $safeFile;

        // GD で圧縮・リサイズして保存。失敗時はそのままコピー
        if (!$this->compressImage($file['tmp_name'], $dest, $imageInfo['mime'])) {
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                Response::error('ファイルの保存に失敗しました', 500, 'SAVE_ERROR');
                return;
            }
        }

        Response::success(['path' => '/uploads/hp/' . $safeFile], 'アップロードしました');
    }

    // ─────────────────────────────────────────
    // ヘルパー
    // ─────────────────────────────────────────

    /**
     * GD で画像をリサイズ＋圧縮して保存する
     * 長辺が $maxDim px を超える場合のみリサイズ
     * JPEG/WebP は品質 $quality、PNG は圧縮レベル 6
     */
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

            // PNG/GIF の透過を維持
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
