<?php
/**
 * 物販エクスポートコントローラー
 * Excel（注文一覧+集計表）およびPDF（印刷用HTML）出力
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MerchandiseExportController
{
    public function __construct()
    {
        Auth::requirePermission('merchandise');
    }

    /**
     * Excel 出力（注文一覧 + 色×サイズ集計）
     * GET /api/merchandise/{id}/export/xlsx
     */
    public function xlsx(array $params): void
    {
        $merch = Merchandise::findById((int)$params['id']);
        if (!$merch) Response::error('商品が見つかりません', 404);

        $orders  = $this->getOrdersForMerchandise((int)$params['id'], $_GET['status'] ?? null);
        $summary = MerchandiseOrder::summaryByMerchandise((int)$params['id']);

        $spreadsheet = new Spreadsheet();

        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('注文一覧');
        $this->buildOrdersSheet($sheet1, $merch, $orders);

        $sheet2 = $spreadsheet->createSheet(1);
        $sheet2->setTitle('集計');
        $this->buildSummarySheet($sheet2, $merch, $summary);

        $spreadsheet->setActiveSheetIndex(0);

        $name     = preg_replace('/[\\\\\/:\*\?"<>\|]/', '_', $merch['name']);
        $filename = '物販注文一覧_' . $name . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Cache-Control: max-age=0');

        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    /**
     * PDF 出力（印刷用HTML）
     * GET /api/merchandise/{id}/export/pdf
     */
    public function pdf(array $params): void
    {
        $merch = Merchandise::findById((int)$params['id']);
        if (!$merch) Response::error('商品が見つかりません', 404);

        $orders  = $this->getOrdersForMerchandise((int)$params['id'], $_GET['status'] ?? null);
        $summary = MerchandiseOrder::summaryByMerchandise((int)$params['id']);

        header('Content-Type: text/html; charset=utf-8');
        echo $this->generatePdfHtml($merch, $orders, $summary);
        exit;
    }

    // ==================== ヘルパー ====================

    /**
     * 指定商品を含む注文に絞り込む
     */
    private function getOrdersForMerchandise(int $merchandise_id, ?string $status = null): array
    {
        $orders = MerchandiseOrder::findAll($status);
        $filtered = [];
        foreach ($orders as $o) {
            $items = array_values(array_filter(
                $o['items'],
                fn($it) => (int)$it['merchandise_id'] === $merchandise_id
            ));
            if (!empty($items)) {
                $o['items']       = $items;
                $o['merch_total'] = array_sum(array_map(fn($it) => (int)$it['subtotal'], $items));
                $o['merch_qty']   = array_sum(array_map(fn($it) => (int)$it['quantity'], $items));
                $filtered[]       = $o;
            }
        }
        return $filtered;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'paid'      => '入金済',
            'cancelled' => 'キャンセル',
            default     => '未入金',
        };
    }

    private function headerStyle(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font'      => ['bold' => true],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E1F2']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
    }

    private function borderStyle(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);
    }

    /**
     * 集計マトリクスを構築
     * 戻り値: ['colors' => [...], 'sizes' => [...], 'grid' => {color: {size: {qty, amount}}}, 'rowTotals', 'colTotals', 'grandQty', 'grandAmount']
     */
    private function buildSummaryGrid(array $merch, array $summary): array
    {
        $colors = array_map(fn($c) => $c['color_name'], $merch['colors'] ?? []);
        $sizes  = array_map(fn($s) => $s['size_name'],  $merch['sizes']  ?? []);

        foreach ($summary as $s) {
            $cn = $s['color_name'] ?: '-';
            $sn = $s['size_name']  ?: '-';
            if (!in_array($cn, $colors)) $colors[] = $cn;
            if (!in_array($sn, $sizes))  $sizes[]  = $sn;
        }

        $grid       = [];
        $rowTotals  = [];
        $colTotals  = [];
        $grandQty   = 0;
        $grandAmt   = 0;

        foreach ($summary as $s) {
            $cn = $s['color_name'] ?: '-';
            $sn = $s['size_name']  ?: '-';
            $grid[$cn][$sn] = [
                'qty'    => (int)$s['total_quantity'],
                'amount' => (int)$s['total_amount'],
            ];
        }

        foreach ($colors as $cn) {
            $rowTotals[$cn] = ['qty' => 0, 'amount' => 0];
            foreach ($sizes as $sn) {
                $cell = $grid[$cn][$sn] ?? null;
                if ($cell) {
                    $rowTotals[$cn]['qty']    += $cell['qty'];
                    $rowTotals[$cn]['amount'] += $cell['amount'];
                    $colTotals[$sn]['qty']    = ($colTotals[$sn]['qty']    ?? 0) + $cell['qty'];
                    $colTotals[$sn]['amount'] = ($colTotals[$sn]['amount'] ?? 0) + $cell['amount'];
                    $grandQty += $cell['qty'];
                    $grandAmt += $cell['amount'];
                }
            }
        }

        return [
            'colors'     => $colors,
            'sizes'      => $sizes,
            'grid'       => $grid,
            'rowTotals'  => $rowTotals,
            'colTotals'  => $colTotals,
            'grandQty'   => $grandQty,
            'grandAmount'=> $grandAmt,
        ];
    }

    // ==================== Excel シート構築 ====================

    /**
     * シート1: 注文一覧
     */
    private function buildOrdersSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $merch,
        array $orders
    ): void {
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', '注文一覧　' . $merch['name']);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $cols = [
            'A' => '注文日',
            'B' => '購入者',
            'C' => '会員',
            'D' => '色',
            'E' => 'サイズ',
            'F' => '数量',
            'G' => '小計',
            'H' => '入金状態',
        ];
        foreach ($cols as $col => $label) {
            $sheet->setCellValue($col . '2', $label);
        }
        $this->headerStyle($sheet, 'A2:H2');

        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(8);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(8);
        $sheet->getColumnDimension('F')->setWidth(8);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(12);

        $row = 3;
        $totalQty   = 0;
        $totalAmount = 0;

        foreach ($orders as $o) {
            $items    = $o['items'] ?? [];
            $rowCount = max(1, count($items));
            $createdAt = !empty($o['created_at']) ? date('Y/m/d H:i', strtotime($o['created_at'])) : '';

            // 注文ごとに購入者情報をマージ表示
            $sheet->setCellValue('A' . $row, $createdAt);
            $sheet->setCellValue('B' . $row, $o['buyer_name'] ?? '');
            $sheet->setCellValue('C' . $row, !empty($o['member_id']) ? '○' : '');
            $sheet->setCellValue('H' . $row, $this->statusLabel($o['payment_status'] ?? 'unpaid'));

            if ($rowCount > 1) {
                $sheet->mergeCells('A' . $row . ':A' . ($row + $rowCount - 1));
                $sheet->mergeCells('B' . $row . ':B' . ($row + $rowCount - 1));
                $sheet->mergeCells('C' . $row . ':C' . ($row + $rowCount - 1));
                $sheet->mergeCells('H' . $row . ':H' . ($row + $rowCount - 1));
                $sheet->getStyle('A' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('B' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('C' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('H' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            }

            foreach ($items as $i => $it) {
                $r = $row + $i;
                $sheet->setCellValue('D' . $r, $it['color_name'] ?? '');
                $sheet->setCellValue('E' . $r, $it['size_name']  ?? '');
                $sheet->setCellValue('F' . $r, (int)$it['quantity']);
                $sheet->setCellValue('G' . $r, (int)$it['subtotal']);
                $sheet->getStyle('G' . $r)->getNumberFormat()->setFormatCode('¥#,##0');

                if ($o['payment_status'] === 'cancelled') {
                    $sheet->getStyle('A' . $r . ':H' . $r)->getFont()->setStrikethrough(true)->setColor(new Color('FF888888'));
                } else {
                    $totalQty    += (int)$it['quantity'];
                    $totalAmount += (int)$it['subtotal'];
                }
            }

            $row += $rowCount;
        }

        if ($row > 3) {
            $this->borderStyle($sheet, 'A2:H' . ($row - 1));
            $sheet->getStyle('C3:F' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G3:G' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('H3:H' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // 合計行
        $sheet->setCellValue('E' . $row, '合計');
        $sheet->setCellValue('F' . $row, $totalQty);
        $sheet->setCellValue('G' . $row, $totalAmount);
        $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('¥#,##0');
        $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':H' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');
        $sheet->getStyle('A' . $row . ':H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /**
     * シート2: 集計（色×サイズのクロス表）
     */
    private function buildSummarySheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $merch,
        array $summary
    ): void {
        $g = $this->buildSummaryGrid($merch, $summary);
        $colors = $g['colors'];
        $sizes  = $g['sizes'];

        $totalCols = count($sizes) + 2; // 色名列 + サイズ列 + 合計
        $lastColIdx = $totalCols;
        $lastCol    = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIdx);

        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->setCellValue('A1', '色×サイズ 集計　' . $merch['name']);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        // ヘッダー: A2 = 色＼サイズ、B2..= サイズ名、最後 = 合計
        $sheet->setCellValue('A2', '色 ＼ サイズ');
        foreach ($sizes as $i => $sn) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
            $sheet->setCellValue($col . '2', $sn);
        }
        $sheet->setCellValue($lastCol . '2', '合計');
        $this->headerStyle($sheet, 'A2:' . $lastCol . '2');

        $sheet->getColumnDimension('A')->setWidth(16);
        for ($i = 0; $i < count($sizes); $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
            $sheet->getColumnDimension($col)->setWidth(8);
        }
        $sheet->getColumnDimension($lastCol)->setWidth(10);

        $row = 3;
        foreach ($colors as $cn) {
            $sheet->setCellValue('A' . $row, $cn);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EBF3FB');

            foreach ($sizes as $i => $sn) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
                $cell = $g['grid'][$cn][$sn] ?? null;
                $sheet->setCellValue($col . $row, $cell ? $cell['qty'] : '');
            }
            $sheet->setCellValue($lastCol . $row, $g['rowTotals'][$cn]['qty'] ?: '');
            $sheet->getStyle($lastCol . $row)->getFont()->setBold(true);
            $sheet->getStyle($lastCol . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');
            $row++;
        }

        // フッター行: 列合計
        $sheet->setCellValue('A' . $row, '合計');
        foreach ($sizes as $i => $sn) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
            $sheet->setCellValue($col . $row, $g['colTotals'][$sn]['qty'] ?? '');
        }
        $sheet->setCellValue($lastCol . $row, $g['grandQty']);
        $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');

        $this->borderStyle($sheet, 'A2:' . $lastCol . $row);
        $sheet->getStyle('B3:' . $lastCol . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // 合計金額行
        $row += 2;
        $sheet->setCellValue('A' . $row, '合計金額');
        $sheet->setCellValue('B' . $row, $g['grandAmount']);
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('¥#,##0');
        $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
    }

    // ==================== PDF HTML 生成 ====================

    private function generatePdfHtml(array $merch, array $orders, array $summary): string
    {
        $h    = fn($s) => htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8');
        $name = $h($merch['name']);
        $g    = $this->buildSummaryGrid($merch, $summary);

        // ===== 集計表 =====
        $headSizes = '';
        foreach ($g['sizes'] as $sn) {
            $headSizes .= '<th>' . $h($sn) . '</th>';
        }
        $summaryRows = '';
        foreach ($g['colors'] as $cn) {
            $cells = '';
            foreach ($g['sizes'] as $sn) {
                $cell = $g['grid'][$cn][$sn] ?? null;
                $cells .= '<td class="center">' . ($cell ? $cell['qty'] : '<span class="gray">-</span>') . '</td>';
            }
            $summaryRows .= '<tr>'
                . '<td class="color-cell">' . $h($cn) . '</td>'
                . $cells
                . '<td class="total-cell center">' . ($g['rowTotals'][$cn]['qty'] ?: '-') . '</td>'
                . '</tr>';
        }
        $colTotalCells = '';
        foreach ($g['sizes'] as $sn) {
            $colTotalCells .= '<th class="center">' . ($g['colTotals'][$sn]['qty'] ?? '-') . '</th>';
        }

        // ===== 注文一覧 =====
        $totalQty    = 0;
        $totalAmount = 0;
        $orderRows   = '';
        foreach ($orders as $o) {
            $items    = $o['items'] ?? [];
            $rowCount = max(1, count($items));
            $first    = true;
            $createdAt = !empty($o['created_at']) ? date('Y/m/d H:i', strtotime($o['created_at'])) : '';
            $statusCls = $o['payment_status'] === 'paid' ? 'paid' : ($o['payment_status'] === 'cancelled' ? 'cancelled' : 'unpaid');

            foreach ($items as $i => $it) {
                $isCancelled = $o['payment_status'] === 'cancelled';
                if (!$isCancelled) {
                    $totalQty    += (int)$it['quantity'];
                    $totalAmount += (int)$it['subtotal'];
                }
                $orderRows .= '<tr class="' . $statusCls . '">'
                    . ($first ? '<td rowspan="' . $rowCount . '" class="middle">' . $h($createdAt) . '</td>' : '')
                    . ($first ? '<td rowspan="' . $rowCount . '" class="middle">' . $h($o['buyer_name'] ?? '') . (!empty($o['member_id']) ? ' <span class="badge">会員</span>' : '') . '</td>' : '')
                    . '<td>' . $h($it['color_name'] ?? '') . '</td>'
                    . '<td class="center">' . $h($it['size_name'] ?? '') . '</td>'
                    . '<td class="center">' . (int)$it['quantity'] . '</td>'
                    . '<td class="right">¥' . number_format((int)$it['subtotal']) . '</td>'
                    . ($first ? '<td rowspan="' . $rowCount . '" class="middle center status-' . $statusCls . '">' . $this->statusLabel($o['payment_status'] ?? 'unpaid') . '</td>' : '')
                    . '</tr>';
                $first = false;
            }
        }

        if (empty($orders)) {
            $orderRows = '<tr><td colspan="7" class="gray center">注文がありません</td></tr>';
        }

        return '<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>' . $name . ' 物販注文一覧</title>
<style>
  body { font-family: "Hiragino Sans", "Yu Gothic", "Meiryo", sans-serif; font-size: 12px; margin: 20px; color: #222; }
  h1 { font-size: 18px; text-align: center; margin-bottom: 4px; }
  h2 { font-size: 14px; margin: 24px 0 6px; border-bottom: 2px solid #334; padding-bottom: 4px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
  th, td { border: 1px solid #bbb; padding: 5px 8px; font-size: 11px; }
  th { background: #d9e1f2; font-weight: bold; text-align: center; }
  .center { text-align: center; }
  .right  { text-align: right; }
  .middle { vertical-align: middle; }
  .color-cell { background: #ebf3fb; font-weight: bold; }
  .total-cell { background: #fff2cc; font-weight: bold; }
  .gray { color: #888; }
  .badge { display: inline-block; background: #e7f3ff; color: #0a6ebd; border-radius: 3px; padding: 1px 4px; font-size: 9px; }
  .total-row { background: #fff2cc; font-weight: bold; }
  .status-paid     { color: #107c10; font-weight: bold; }
  .status-unpaid   { color: #c89400; font-weight: bold; }
  .status-cancelled{ color: #888; text-decoration: line-through; }
  tr.cancelled td { color: #999; text-decoration: line-through; }
  .no-print { margin-bottom: 16px; }
  @media print {
    .no-print { display: none; }
    h2 { page-break-before: auto; }
  }
</style>
</head>
<body>
<div class="no-print">
  <button onclick="window.print()" style="padding:6px 16px;margin-right:8px;">印刷 / PDF保存</button>
  <button onclick="window.close()" style="padding:6px 16px;">閉じる</button>
</div>

<h1>' . $name . ' 物販注文一覧</h1>
<div class="center">出力日時: ' . date('Y年n月j日 G:i') . '</div>

<h2>色×サイズ 集計</h2>
<table>
  <thead>
    <tr><th rowspan="2">色 ＼ サイズ</th><th colspan="' . count($g['sizes']) . '">サイズ別 数量</th><th rowspan="2">合計</th></tr>
    <tr>' . $headSizes . '</tr>
  </thead>
  <tbody>' . $summaryRows . '</tbody>
  <tfoot>
    <tr class="total-row">
      <th>合計</th>' . $colTotalCells . '<th class="center">' . $g['grandQty'] . '</th>
    </tr>
  </tfoot>
</table>
<div class="right" style="margin-bottom: 16px;">合計金額: <strong>¥' . number_format($g['grandAmount']) . '</strong></div>

<h2>注文一覧</h2>
<table>
  <thead>
    <tr>
      <th style="width: 14%;">注文日時</th>
      <th style="width: 22%;">購入者</th>
      <th style="width: 16%;">色</th>
      <th style="width: 10%;">サイズ</th>
      <th style="width: 8%;">数量</th>
      <th style="width: 14%;">小計</th>
      <th style="width: 16%;">入金状態</th>
    </tr>
  </thead>
  <tbody>' . $orderRows . '</tbody>
  <tfoot>
    <tr class="total-row">
      <td colspan="4" class="center">合計</td>
      <td class="center">' . $totalQty . '</td>
      <td class="right">¥' . number_format($totalAmount) . '</td>
      <td></td>
    </tr>
  </tfoot>
</table>

<div style="margin-top: 24px; font-size: 10px; color: #888;">
  ※ キャンセル済み注文は合計に含まれていません
</div>

</body>
</html>';
    }
}
