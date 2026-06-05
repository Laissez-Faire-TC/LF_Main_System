<?php
/**
 * 遠征エクスポートコントローラー
 * Excel（4シート）およびPDF（印刷用HTML）出力
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExpeditionExportController
{
    public function __construct()
    {
        Auth::requirePermission('expeditions');
    }

    /**
     * Excel出力（参加者一覧・チーム分け・車割・アレルギー一覧の4シート）
     * GET /api/expeditions/{id}/export/xlsx
     */
    public function xlsx(array $params): void
    {
        $expedition = Expedition::findById((int)$params['id']);
        if (!$expedition) Response::error('遠征が見つかりません', 404);

        $participants = ExpeditionParticipant::findByExpedition((int)$params['id']);
        $teams        = ExpeditionTeam::findByExpedition((int)$params['id']);
        $cars         = ExpeditionCar::findByExpedition((int)$params['id']);

        $spreadsheet = new Spreadsheet();

        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('参加者一覧');
        $this->buildParticipantsSheet($sheet1, $expedition, $participants);

        $sheet2 = $spreadsheet->createSheet(1);
        $sheet2->setTitle('チーム分け');
        $this->buildTeamsSheet($sheet2, $expedition, $teams);

        $sheet3 = $spreadsheet->createSheet(2);
        $sheet3->setTitle('車割');
        $this->buildCarsSheet($sheet3, $expedition, $cars);

        $sheet4 = $spreadsheet->createSheet(3);
        $sheet4->setTitle('アレルギー一覧');
        $this->buildAllergySheet($sheet4, $expedition, $participants);

        $spreadsheet->setActiveSheetIndex(0);

        $name     = preg_replace('/[\\\\\/:\*\?"<>\|]/', '_', $expedition['name']);
        $filename = '遠征資料_' . $name . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Cache-Control: max-age=0');

        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    /**
     * PDF出力（印刷用HTML）
     * GET /api/expeditions/{id}/export/pdf
     */
    public function pdf(array $params): void
    {
        $expedition = Expedition::findById((int)$params['id']);
        if (!$expedition) Response::error('遠征が見つかりません', 404);

        $participants = ExpeditionParticipant::findByExpedition((int)$params['id']);
        $teams        = ExpeditionTeam::findByExpedition((int)$params['id']);
        $cars         = ExpeditionCar::findByExpedition((int)$params['id']);

        header('Content-Type: text/html; charset=utf-8');
        echo $this->generatePdfHtml($expedition, $participants, $teams, $cars);
        exit;
    }

    // ==================== 内部ヘルパー ====================

    /**
     * 学年性別ラベル取得（10月引退ルール対応）
     */
    private function gradeGenderLabel($grade, ?string $gender): string
    {
        if ($grade === null && $gender === null) return '';
        if ((int)$grade === 0) {
            if ($gender === 'male')   return 'OB';
            if ($gender === 'female') return 'OG';
            return 'OB/OG';
        }
        $month = (int)date('n');
        if (((int)$grade === 3) && ($month >= 10 || $month <= 3)) {
            if ($gender === 'male')   return 'OB';
            if ($gender === 'female') return 'OG';
        }
        $g = $gender === 'male' ? '男' : ($gender === 'female' ? '女' : '');
        return ($grade ? (string)$grade : '') . $g;
    }

    /**
     * ヘッダー行スタイル適用
     */
    private function headerStyle(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font'      => ['bold' => true],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E1F2']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
    }

    /**
     * 罫線スタイル適用
     */
    private function borderStyle(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);
    }

    // ==================== Excel シート構築 ====================

    /**
     * シート1: 参加者一覧
     */
    private function buildParticipantsSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $expedition,
        array $participants
    ): void {
        // タイトル
        $sheet->mergeCells('A1:J1');
        $sheet->setCellValue('A1', '参加者一覧　' . $expedition['name']);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        // ヘッダー
        $cols = ['A' => 'No.', 'B' => '氏名', 'C' => 'フリガナ', 'D' => '学年性別', 'E' => '前泊', 'F' => '昼食', 'G' => '金曜終了時限', 'H' => 'ドライバー', 'I' => 'アレルギー', 'J' => '住所'];
        foreach ($cols as $col => $label) {
            $sheet->setCellValue($col . '2', $label);
        }
        $this->headerStyle($sheet, 'A2:J2');

        // 列幅
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(7);
        $sheet->getColumnDimension('F')->setWidth(7);
        $sheet->getColumnDimension('G')->setWidth(13);
        $sheet->getColumnDimension('H')->setWidth(14);
        $sheet->getColumnDimension('I')->setWidth(35);
        $sheet->getColumnDimension('J')->setWidth(40);

        // データ行
        $row = 3;
        foreach ($participants as $i => $p) {
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValue('B' . $row, $p['name_kanji'] ?? '');
            $sheet->setCellValue('C' . $row, $p['name_kana']  ?? '');
            $sheet->setCellValue('D' . $row, $this->gradeGenderLabel($p['grade'] ?? null, $p['gender'] ?? null));
            $sheet->setCellValue('E' . $row, $p['pre_night'] ? '○' : '');
            $sheet->setCellValue('F' . $row, $p['lunch']     ? '○' : '');
            $sheet->setCellValue('G' . $row, $this->fridayClassLabel($p['friday_last_class'] ?? null));
            $sheet->setCellValue('H' . $row, $this->driverLabel($p['driver_type'] ?? null, (int)($p['can_book_car'] ?? 0) === 1));
            $sheet->setCellValue('I' . $row, $p['allergy']   ?? '');
            if (!empty($p['allergy'])) {
                $sheet->getStyle('I' . $row)->getFont()->setColor(new Color('FFCC0000'));
            }
            $sheet->getStyle('I' . $row)->getAlignment()->setWrapText(true);
            $sheet->setCellValue('J' . $row, $p['address'] ?? '');
            $sheet->getStyle('J' . $row)->getAlignment()->setWrapText(true);
            $row++;
        }

        if ($row > 3) {
            $this->borderStyle($sheet, 'A2:J' . ($row - 1));
            $sheet->getStyle('A3:H' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // 合計行
        $sheet->setCellValue('A' . $row, '合計');
        $sheet->setCellValue('B' . $row, count($participants) . '名');
        $preCount   = count(array_filter($participants, fn($p) => $p['pre_night']));
        $lunchCount = count(array_filter($participants, fn($p) => $p['lunch']));
        $sheet->setCellValue('E' . $row, $preCount   . '名');
        $sheet->setCellValue('F' . $row, $lunchCount . '名');
        $sheet->getStyle('A' . $row . ':J' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':J' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');
    }

    /**
     * 金曜の授業終了時限を表示用ラベルに変換する。
     * 0/null → 授業なし、1〜6 → 「○限」。
     */
    private function fridayClassLabel($value): string
    {
        if ($value === null || $value === '') return '';
        $v = (int)$value;
        return $v === 0 ? '授業なし' : "{$v}限";
    }

    /**
     * ドライバー種別を表示用ラベルに変換する。
     * driver → ドライバー、sub_driver → サブドライバー、それ以外 → 空。
     * 車を予約する人（can_book_car）は「（車あり）」を付記する。
     */
    private function driverLabel(?string $driverType, bool $canBookCar): string
    {
        $label = '';
        if ($driverType === 'driver')          $label = 'ドライバー';
        elseif ($driverType === 'sub_driver')  $label = 'サブドライバー';

        if ($canBookCar) {
            $label = ($label === '' ? '車あり' : $label . '（車あり）');
        }
        return $label;
    }

    /**
     * シート2: チーム分け
     */
    private function buildTeamsSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $expedition,
        array $teams
    ): void {
        // A: チーム名、B: 男氏名、C: 女氏名
        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A1', 'チーム分け　' . $expedition['name']);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(18);

        $sheet->setCellValue('A2', 'チーム');
        $sheet->setCellValue('B2', '男性');
        $sheet->setCellValue('C2', '女性');
        $this->headerStyle($sheet, 'A2:C2');

        $row = 3;
        foreach ($teams as $team) {
            $members  = $team['members'] ?? [];
            $males    = array_values(array_filter($members, fn($m) => ($m['gender'] ?? '') === 'male'));
            $females  = array_values(array_filter($members, fn($m) => ($m['gender'] ?? '') !== 'male'));
            $rowCount = max(1, count($males), count($females));

            $sheet->setCellValue('A' . $row, $team['name']);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EBF3FB');

            if ($rowCount > 1) {
                $sheet->mergeCells('A' . $row . ':A' . ($row + $rowCount - 1));
                $sheet->getStyle('A' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            }

            if (empty($males) && empty($females)) {
                $sheet->setCellValue('B' . $row, '（メンバーなし）');
                $sheet->getStyle('B' . $row)->getFont()->setColor(new Color('FF888888'))->setItalic(true);
            } else {
                for ($i = 0; $i < $rowCount; $i++) {
                    $r = $row + $i;
                    if (isset($males[$i])) {
                        $sheet->setCellValue('B' . $r, $males[$i]['name_kanji'] ?? '');
                    }
                    if (isset($females[$i])) {
                        $sheet->setCellValue('C' . $r, $females[$i]['name_kanji'] ?? '');
                    }
                }
            }

            $row += $rowCount;
        }

        if ($row > 3) {
            $this->borderStyle($sheet, 'A2:C' . ($row - 1));
        }
    }

    /**
     * シート3: 車割
     */
    private function buildCarsSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $expedition,
        array $cars
    ): void {
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', '車割　' . $expedition['name']);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(5);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(18);

        foreach (['A' => '車名', 'B' => 'No.', 'C' => '役割', 'D' => '氏名'] as $col => $label) {
            $sheet->setCellValue($col . '2', $label);
        }
        $this->headerStyle($sheet, 'A2:D2');

        $roleLabel = ['driver' => 'ドライバー', 'sub_driver' => 'サブドライバー', 'passenger' => '乗客'];

        $row = 3;
        foreach ($cars as $car) {
            $members = $car['car_members'] ?? [];
            $count   = count($members);

            $sheet->setCellValue('A' . $row, $car['name']);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EBF3FB');

            if ($count > 1) {
                $sheet->mergeCells('A' . $row . ':A' . ($row + $count - 1));
                $sheet->getStyle('A' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            }

            if ($count === 0) {
                $sheet->setCellValue('C' . $row, '（乗員なし）');
                $sheet->getStyle('C' . $row)->getFont()->setColor(new Color('FF888888'))->setItalic(true);
                $row++;
            } else {
                foreach ($members as $j => $m) {
                    $sheet->setCellValue('B' . $row, $j + 1);
                    $sheet->setCellValue('C' . $row, $roleLabel[$m['role']] ?? $m['role']);
                    $sheet->setCellValue('D' . $row, $m['name_kanji'] ?? '');
                    $row++;
                }
            }
        }

        if ($row > 3) {
            $this->borderStyle($sheet, 'A2:D' . ($row - 1));
            $sheet->getStyle('B3:C' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }

    /**
     * シート4: アレルギー一覧
     */
    private function buildAllergySheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $expedition,
        array $participants
    ): void {
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'アレルギー一覧　' . $expedition['name']);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(40);

        foreach (['A' => 'No.', 'B' => '氏名', 'C' => '学年性別', 'D' => 'アレルギー内容'] as $col => $label) {
            $sheet->setCellValue($col . '2', $label);
        }
        $this->headerStyle($sheet, 'A2:D2');

        $allergies = array_values(array_filter($participants, fn($p) => !empty($p['allergy'])));

        if (empty($allergies)) {
            $sheet->mergeCells('A3:D3');
            $sheet->setCellValue('A3', 'アレルギーのある参加者はいません');
            $sheet->getStyle('A3')->getFont()->setItalic(true)->setColor(new Color('FF888888'));
            return;
        }

        $row = 3;
        foreach ($allergies as $i => $p) {
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValue('B' . $row, $p['name_kanji'] ?? '');
            $sheet->setCellValue('C' . $row, $this->gradeGenderLabel($p['grade'] ?? null, $p['gender'] ?? null));
            $sheet->setCellValue('D' . $row, $p['allergy'] ?? '');
            $sheet->getStyle('D' . $row)->getFont()->setColor(new Color('FFCC0000'));
            $sheet->getStyle('D' . $row)->getAlignment()->setWrapText(true);
            $row++;
        }

        $this->borderStyle($sheet, 'A2:D' . ($row - 1));
        $sheet->getStyle('A3:C' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    // ==================== PDF HTML 生成 ====================

    private function generatePdfHtml(array $expedition, array $participants, array $teams, array $cars): string
    {
        $h  = fn($s) => htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8');
        $name = $h($expedition['name']);
        $allergies = array_values(array_filter($participants, fn($p) => !empty($p['allergy'])));

        // ===== 参加者一覧テーブル =====
        $preCount   = count(array_filter($participants, fn($p) => $p['pre_night']));
        $lunchCount = count(array_filter($participants, fn($p) => $p['lunch']));
        $pRows = '';
        foreach ($participants as $i => $p) {
            $allergy = $p['allergy'] ? '<span class="allergy">' . $h($p['allergy']) . '</span>' : '';
            $pRows .= '<tr>'
                . '<td class="center">' . ($i + 1) . '</td>'
                . '<td>' . $h($p['name_kanji']) . '</td>'
                . '<td>' . $h($p['name_kana']) . '</td>'
                . '<td class="center">' . $h($this->gradeGenderLabel($p['grade'] ?? null, $p['gender'] ?? null)) . '</td>'
                . '<td class="center">' . ($p['pre_night'] ? '○' : '') . '</td>'
                . '<td class="center">' . ($p['lunch']     ? '○' : '') . '</td>'
                . '<td>' . $allergy . '</td>'
                . '<td>' . $h($p['address'] ?? '') . '</td>'
                . '</tr>';
        }
        $pRows .= '<tr class="total-row">'
            . '<td colspan="2" class="center"><strong>合計 ' . count($participants) . '名</strong></td>'
            . '<td colspan="2"></td>'
            . '<td class="center"><strong>' . $preCount   . '名</strong></td>'
            . '<td class="center"><strong>' . $lunchCount . '名</strong></td>'
            . '<td></td><td></td></tr>';

        // ===== チーム分けテーブル（男女2列） =====
        $teamRows = '';
        foreach ($teams as $team) {
            $members  = $team['members'] ?? [];
            $males    = array_values(array_filter($members, fn($m) => ($m['gender'] ?? '') === 'male'));
            $females  = array_values(array_filter($members, fn($m) => ($m['gender'] ?? '') !== 'male'));
            $rowCount = max(1, count($males), count($females));

            if (empty($males) && empty($females)) {
                $teamRows .= '<tr>'
                    . '<td rowspan="1" class="team-cell">' . $h($team['name']) . '</td>'
                    . '<td class="gray">（メンバーなし）</td><td></td></tr>';
            } else {
                for ($i = 0; $i < $rowCount; $i++) {
                    $mName = isset($males[$i])   ? $h($males[$i]['name_kanji']   ?? '') : '';
                    $fName = isset($females[$i]) ? $h($females[$i]['name_kanji'] ?? '') : '';
                    $teamRows .= '<tr>'
                        . ($i === 0 ? '<td rowspan="' . $rowCount . '" class="team-cell">' . $h($team['name']) . '</td>' : '')
                        . '<td>' . $mName . '</td>'
                        . '<td>' . $fName . '</td>'
                        . '</tr>';
                }
            }
        }

        // ===== 車割テーブル =====
        $carRows   = '';
        $roleLabel = ['driver' => 'ドライバー', 'sub_driver' => 'サブドライバー', 'passenger' => '乗客'];
        foreach ($cars as $car) {
            $members = $car['car_members'] ?? [];
            $count   = max(1, count($members));
            $first   = true;
            if (empty($members)) {
                $carRows .= '<tr>'
                    . '<td rowspan="1" class="team-cell">' . $h($car['name']) . '</td>'
                    . '<td></td><td></td><td class="gray">（乗員なし）</td></tr>';
            } else {
                foreach ($members as $j => $m) {
                    $carRows .= '<tr>'
                        . ($first ? '<td rowspan="' . $count . '" class="team-cell">' . $h($car['name']) . '</td>' : '')
                        . '<td class="center">' . ($j + 1) . '</td>'
                        . '<td class="center">' . $h($roleLabel[$m['role']] ?? $m['role']) . '</td>'
                        . '<td>' . $h($m['name_kanji']) . '</td>'
                        . '</tr>';
                    $first = false;
                }
            }
        }

        // ===== アレルギー一覧テーブル =====
        $aRows = '';
        if (empty($allergies)) {
            $aRows = '<tr><td colspan="4" class="gray center">アレルギーのある参加者はいません</td></tr>';
        } else {
            foreach ($allergies as $i => $p) {
                $aRows .= '<tr>'
                    . '<td class="center">' . ($i + 1) . '</td>'
                    . '<td>' . $h($p['name_kanji']) . '</td>'
                    . '<td class="center">' . $h($this->gradeGenderLabel($p['grade'] ?? null, $p['gender'] ?? null)) . '</td>'
                    . '<td class="allergy">' . $h($p['allergy']) . '</td>'
                    . '</tr>';
            }
        }

        return '<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>' . $name . ' - 遠征資料</title>
<style>
  body { font-family: "Hiragino Sans", "Yu Gothic", "Meiryo", sans-serif; font-size: 12px; margin: 20px; color: #222; }
  h1 { font-size: 18px; text-align: center; margin-bottom: 4px; }
  h2 { font-size: 14px; margin: 28px 0 6px; border-bottom: 2px solid #334; padding-bottom: 4px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
  th, td { border: 1px solid #bbb; padding: 5px 8px; font-size: 11px; }
  th { background: #d9e1f2; font-weight: bold; text-align: center; }
  .center { text-align: center; }
  .team-cell { background: #ebf3fb; font-weight: bold; vertical-align: middle; }
  .allergy { color: #c00; }
  .gray { color: #888; font-style: italic; }
  .total-row { background: #fff2cc; font-weight: bold; }
  .no-print { margin-bottom: 16px; }
  @media print {
    .no-print { display: none; }
    h2 { page-break-before: auto; }
    .page-break { page-break-before: always; }
  }
</style>
</head>
<body>
<div class="no-print">
  <button onclick="window.print()" style="padding:6px 16px;margin-right:8px;">印刷 / PDF保存</button>
  <button onclick="window.close()" style="padding:6px 16px;">閉じる</button>
</div>

<h1>' . $name . ' 遠征資料</h1>

<h2>1. 参加者一覧（' . count($participants) . '名）</h2>
<table>
  <thead><tr><th>No.</th><th>氏名</th><th>フリガナ</th><th>学年性別</th><th>前泊</th><th>昼食</th><th>アレルギー</th><th>住所</th></tr></thead>
  <tbody>' . $pRows . '</tbody>
</table>

<h2 class="page-break">2. チーム分け</h2>
<table>
  <thead>
    <tr><th>チーム</th><th>男性</th><th>女性</th></tr>
  </thead>
  <tbody>' . $teamRows . '</tbody>
</table>

<h2>3. 車割</h2>
<table>
  <thead><tr><th>車名</th><th>No.</th><th>役割</th><th>氏名</th></tr></thead>
  <tbody>' . $carRows . '</tbody>
</table>

<h2>4. アレルギー一覧（' . count($allergies) . '名）</h2>
<table>
  <thead><tr><th>No.</th><th>氏名</th><th>学年性別</th><th>アレルギー内容</th></tr></thead>
  <tbody>' . $aRows . '</tbody>
</table>
</body>
</html>';
    }

    /**
     * 大学向け参加者名簿（合宿・遠征届フォーマット）Excel出力
     * GET /api/expeditions/{id}/export/activity-meibo
     */
    public function activityMeibo(array $params): void
    {
        $expedition = Expedition::findById((int)$params['id']);
        if (!$expedition) Response::error('遠征が見つかりません', 404);

        $db = Database::getInstance();

        // 確定参加者を会員情報と結合して取得
        $participants = $db->fetchAll(
            "SELECT ep.member_id,
                    m.grade, m.gender,
                    m.name_kanji  AS display_name,
                    m.faculty     AS display_faculty,
                    m.department  AS display_department,
                    m.student_id
             FROM expedition_participants ep
             JOIN members m ON m.id = ep.member_id
             WHERE ep.expedition_id = ?
               AND ep.status = 'confirmed'
             ORDER BY m.grade, m.name_kana",
            [(int)$params['id']]
        );

        $ROWS_PER_SHEET = 25;
        $sheetCount     = max(1, (int)ceil(count($participants) / $ROWS_PER_SHEET));

        $spreadsheet = new Spreadsheet();

        for ($sheetIndex = 0; $sheetIndex < $sheetCount; $sheetIndex++) {
            $sheet = $sheetIndex === 0
                ? $spreadsheet->getActiveSheet()
                : $spreadsheet->createSheet($sheetIndex);
            $sheet->setTitle('名簿' . ($sheetIndex + 1));
            $sheetParticipants = array_slice($participants, $sheetIndex * $ROWS_PER_SHEET, $ROWS_PER_SHEET);
            $this->buildMeiboSheet($sheet, $expedition, $sheetParticipants, $sheetIndex);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $name     = preg_replace('/[\\\\\/:\*\?"<>\|]/', '_', $expedition['name']);
        $filename = '参加者名簿_' . $name . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Cache-Control: max-age=0');

        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    /**
     * 名簿シート1枚分を構築（25行/シート）
     */
    private function buildMeiboSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $expedition,
        array $participants,
        int   $sheetIndex
    ): void {
        // 列幅
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(9);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(22);
        $sheet->getColumnDimension('F')->setWidth(22);
        $sheet->getColumnDimension('G')->setWidth(18);

        // タイトル
        $row = 1;
        $sheet->setCellValue('A' . $row, '【参加者名簿】');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(22);

        // サブタイトル
        $row = 2;
        $sheet->setCellValue('A' . $row, '※所属サークルの登録が完了していることを確認の上、チェックを入れてください。');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setSize(9);
        $sheet->getRowDimension($row)->setRowHeight(14);

        // 遠征名・期間
        $row = 3;
        $startDate = new \DateTime($expedition['start_date']);
        $endDate   = new \DateTime($expedition['end_date']);
        $sheet->setCellValue('A' . $row, $expedition['name']);
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $dateStr = $startDate->format('Y年n月j日') . '〜' . $endDate->format('n月j日');
        $sheet->setCellValue('E' . $row, $dateStr);
        $sheet->mergeCells('E' . $row . ':G' . $row);
        $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension($row)->setRowHeight(16);

        // ヘッダー行
        $row = 5;
        $headerRow = $row;
        $sheet->setCellValue('B' . $row, '学　　部');
        $sheet->setCellValue('C' . $row, '学 年');
        $sheet->setCellValue('D' . $row, '学 籍 番 号');
        $sheet->setCellValue('E' . $row, '氏　　　名');
        $sheet->setCellValue('F' . $row, '備　　　考');
        $sheet->setCellValue('G' . $row, '所属サークル登録済');
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
            'font'      => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(22);

        // データ行（25行固定）
        $dataStartRow = $headerRow + 1;
        for ($i = 0; $i < 25; $i++) {
            $row       = $dataStartRow + $i;
            $globalNum = ($sheetIndex * 25) + $i + 1;

            // 5行ごとに番号を表示
            if ($globalNum % 5 === 0) {
                $sheet->setCellValue('A' . $row, $globalNum);
                $sheet->getStyle('A' . $row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A' . $row)->getFont()->setSize(8);
            }

            $p = $participants[$i] ?? null;
            if ($p) {
                $studentId = $p['student_id'] ?? '';
                $grade = '';
                if (!empty($studentId)) {
                    $parser = new StudentIdParserService();
                    $parsed = $parser->parse($studentId);
                    if ($parsed['is_valid'] && $parsed['enrollment_year'] !== null) {
                        $gradeNum = $parser->calculateGrade($parsed['enrollment_year']);
                        $grade    = $gradeNum . '年';
                    }
                }
                if ($grade === '') {
                    $gradeNum = $p['grade'] ?? null;
                    $grade    = ($gradeNum === null) ? '' : (($gradeNum == 0) ? 'OB/OG' : $gradeNum . '年');
                }

                $sheet->setCellValue('B' . $row, $p['display_faculty'] ?? '');
                $sheet->setCellValue('C' . $row, $grade);
                $sheet->setCellValue('D' . $row, $studentId);
                $sheet->setCellValue('E' . $row, $p['display_name'] ?? '');
                $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $sheet->getRowDimension($row)->setRowHeight(16);
        }

        // 枠線
        $tableEnd = $dataStartRow + 24;
        $sheet->getStyle('A' . $headerRow . ':G' . $tableEnd)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A' . $headerRow . ':G' . $headerRow)->getBorders()
            ->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);

        // 注記
        $noteRow = $tableEnd + 2;
        $sheet->setCellValue('A' . $noteRow, '【重要】早稲田大学学生補償制度の適用にあたって、「合宿・遠征届」の参加者名簿に記入した氏名は、三役による「サークル会員名簿」の提出と、各サークル員による所属サークルの登録が完了している必要があります。それを満たさない学生が活動に参加する場合はサークル会員名簿を所定の期間内に追加で提出してください。');
        $sheet->mergeCells('A' . $noteRow . ':G' . $noteRow);
        $sheet->getStyle('A' . $noteRow)->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('A' . $noteRow)->getFont()->setSize(8);
        $sheet->getRowDimension($noteRow)->setRowHeight(40);

        $noteRow++;
        $sheet->setCellValue('A' . $noteRow, 'ただし、新入生をはじめ、サークルへの入会が未定の者が参加する活動の場合は、氏名の前に（新）と付けることでサークル員と区別できるようにしておいてください（その場合、「所属サークル登録済」欄に☑は不要です）。');
        $sheet->mergeCells('A' . $noteRow . ':G' . $noteRow);
        $sheet->getStyle('A' . $noteRow)->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('A' . $noteRow)->getFont()->setSize(8);
        $sheet->getRowDimension($noteRow)->setRowHeight(30);

        // 印刷設定
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageMargins()
            ->setTop(0.75)->setBottom(0.75)->setLeft(0.7)->setRight(0.7);
    }

    /**
     * エスパジオ登録用ファイル（1シート=1チーム）Excel出力
     * GET /api/expeditions/{id}/export/espajio
     */
    public function espajio(array $params): void
    {
        $expedition = Expedition::findById((int)$params['id']);
        if (!$expedition) Response::error('遠征が見つかりません', 404);

        $db    = Database::getInstance();
        $teams = ExpeditionTeam::findByExpedition((int)$params['id']);

        // チームなしの場合は参加者全員を1チームとして扱う
        if (empty($teams)) {
            $all = $db->fetchAll(
                "SELECT ep.member_id, ep.status,
                        m.name_kanji, m.name_kana, m.gender, m.grade,
                        m.birthdate, m.address, m.phone, m.emergency_contact
                 FROM expedition_participants ep
                 JOIN members m ON m.id = ep.member_id
                 WHERE ep.expedition_id = ? AND ep.status = 'confirmed'
                 ORDER BY m.grade, m.name_kana",
                [(int)$params['id']]
            );
            $teams = [[
                'name'    => $expedition['name'],
                'members' => array_map(fn($m) => array_merge($m, ['team_role' => null]), $all),
            ]];
        } else {
            // 各チームメンバーの詳細情報を補完
            foreach ($teams as &$team) {
                $enriched = [];
                foreach ($team['members'] as $tm) {
                    $m = $db->fetch(
                        "SELECT m.name_kanji, m.name_kana, m.gender, m.grade,
                                m.birthdate, m.address, m.phone, m.emergency_contact
                         FROM members m WHERE m.id = ?",
                        [(int)$tm['member_id']]
                    );
                    if ($m) {
                        $enriched[] = array_merge($tm, $m);
                    }
                }
                $team['members'] = $enriched;
            }
            unset($team);
        }

        $spreadsheet = new Spreadsheet();

        foreach ($teams as $idx => $team) {
            $sheet = $idx === 0
                ? $spreadsheet->getActiveSheet()
                : $spreadsheet->createSheet($idx);
            $teamName = mb_substr(preg_replace('/[\\\\\/:\*\?"<>\|]/', '_', $team['name']), 0, 31);
            $sheet->setTitle($teamName ?: ('チーム' . ($idx + 1)));
            $this->buildEspajioSheet($sheet, $expedition, $team);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $name     = preg_replace('/[\\\\\/:\*\?"<>\|]/', '_', $expedition['name']);
        $filename = 'メンバー登録用ファイル_' . $name . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Cache-Control: max-age=0');

        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    /**
     * エスパジオ登録用シート1枚分を構築（1チーム）
     */
    private function buildEspajioSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $expedition,
        array $team
    ): void {
        $members = $team['members'] ?? [];

        // 列幅
        $sheet->getColumnDimension('A')->setWidth(14);  // 役割
        $sheet->getColumnDimension('B')->setWidth(16);  // 名前
        $sheet->getColumnDimension('C')->setWidth(16);  // フリガナ
        $sheet->getColumnDimension('D')->setWidth(6);   // 性別
        $sheet->getColumnDimension('E')->setWidth(6);   // 年齢
        $sheet->getColumnDimension('F')->setWidth(14);  // 生年月日
        $sheet->getColumnDimension('G')->setWidth(55);  // 住所
        $sheet->getColumnDimension('H')->setWidth(16);  // 電話番号
        $sheet->getColumnDimension('I')->setWidth(18);  // 緊急連絡先

        // タイトル行
        $row = 1;
        $sheet->setCellValue('A' . $row, 'TENNIS CAMP espajio OPEN メンバー登録用ファイル（参加者名簿兼保険名簿）');
        $sheet->mergeCells('A' . $row . ':I' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(22);

        // 登録チーム名・エントリー名・提出期限
        $row = 3;
        $sheet->setCellValue('A' . $row, '登録チーム名');
        $sheet->setCellValue('B' . $row, $team['name']);
        $sheet->mergeCells('B' . $row . ':E' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        $row = 4;
        $startDate = new \DateTime($expedition['start_date']);
        $endDate   = new \DateTime($expedition['end_date']);
        $dateStr   = $startDate->format('Y年n月j日') . '〜' . $endDate->format('n月j日');
        $sheet->setCellValue('A' . $row, 'エントリー名');
        $sheet->setCellValue('B' . $row, $expedition['name'] . '　' . $dateStr);
        $sheet->mergeCells('B' . $row . ':I' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        $row = 5;
        $sheet->setCellValue('A' . $row, '参加選手員数合計');
        $sheet->setCellValue('B' . $row, count($members) . '名');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        // ヘッダー行
        $row = 7;
        $headerRow = $row;
        $headers = ['役割', '名前', 'フリガナ', '性別', '年齢', '生年月日', '住所', '電話番号（携帯）', '緊急連絡先'];
        $cols = ['A','B','C','D','E','F','G','H','I'];
        foreach ($cols as $ci => $col) {
            $sheet->setCellValue($col . $row, $headers[$ci]);
        }
        $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
            'font'      => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(20);

        // データ行
        $dataStartRow = $headerRow + 1;
        $maxRows = max(count($members), 15); // 最低15行確保
        for ($i = 0; $i < $maxRows; $i++) {
            $row = $dataStartRow + $i;
            $p   = $members[$i] ?? null;

            // 役割ラベル
            if ($p) {
                if ($i === 0) {
                    $roleLabel = 'キャプテン';
                } elseif (($p['gender'] ?? '') === 'female') {
                    $roleLabel = 'マネージャー';
                } else {
                    $roleLabel = '選手';
                }

                // 年齢計算
                $age = '';
                if (!empty($p['birthdate'])) {
                    try {
                        $birth = new \DateTime($p['birthdate']);
                        $today = new \DateTime();
                        $age   = (string)$today->diff($birth)->y;
                    } catch (\Exception $e) {}
                }

                // 生年月日フォーマット
                $birthStr = '';
                if (!empty($p['birthdate'])) {
                    try {
                        $birthStr = (new \DateTime($p['birthdate']))->format('Y/n/j');
                    } catch (\Exception $e) {}
                }

                // 性別
                $genderLabel = ($p['gender'] ?? '') === 'male' ? '男' : (($p['gender'] ?? '') === 'female' ? '女' : '');

                $sheet->setCellValue('A' . $row, $roleLabel);
                $sheet->setCellValue('B' . $row, $p['name_kanji'] ?? '');
                $sheet->setCellValue('C' . $row, $p['name_kana']  ?? '');
                $sheet->setCellValue('D' . $row, $genderLabel);
                $sheet->setCellValue('E' . $row, $age);
                $sheet->setCellValue('F' . $row, $birthStr);
                $sheet->setCellValue('G' . $row, $p['address'] ?? '');
                $sheet->setCellValue('H' . $row, $p['phone'] ?? '');
                $sheet->setCellValue('I' . $row, $p['emergency_contact'] ?? '');

                // 中央揃え列
                foreach (['A','D','E','F'] as $c) {
                    $sheet->getStyle($c . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
                $sheet->getStyle('G' . $row)->getAlignment()->setWrapText(true);

                // キャプテン行を薄い黄色に
                if ($i === 0) {
                    $sheet->getStyle('A' . $row . ':I' . $row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFDE7');
                }
            }

            $sheet->getRowDimension($row)->setRowHeight(18);
        }

        // 枠線
        $tableEnd = $dataStartRow + $maxRows - 1;
        $sheet->getStyle('A' . $headerRow . ':I' . $tableEnd)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A' . $headerRow . ':I' . $headerRow)->getBorders()
            ->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);

        // 注記
        $noteRow = $tableEnd + 2;
        $sheet->setCellValue('A' . $noteRow, '※各登録エントリーの役職、チームと立場に基づいて参加登録情報をご入力ください。ご不明な点はこちらのページをご参考ください。');
        $sheet->mergeCells('A' . $noteRow . ':I' . $noteRow);
        $sheet->getStyle('A' . $noteRow)->getFont()->setSize(8);
        $sheet->getStyle('A' . $noteRow)->getAlignment()->setWrapText(true);
        $sheet->getRowDimension($noteRow)->setRowHeight(20);

        // 印刷設定（横向きA4）
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageMargins()
            ->setTop(0.75)->setBottom(0.75)->setLeft(0.7)->setRight(0.7);
    }
}
