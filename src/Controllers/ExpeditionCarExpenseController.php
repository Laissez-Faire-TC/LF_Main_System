<?php
/**
 * 遠征レンタカー清算コントローラー
 * 管理者向け: GET/DELETE /api/expeditions/{id}/car-expenses
 *             GET /api/expeditions/{id}/car-expenses/settlement
 *             GET /api/expeditions/{id}/car-expenses/export/xlsx
 *             GET /api/expeditions/{id}/car-expenses/export/pdf
 * 会員向け:   POST /api/member/expedition/{id}/car-expense
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ExpeditionCarExpenseController
{
    /**
     * 申請一覧取得（管理者）
     * GET /api/expeditions/{id}/car-expenses
     */
    public function index(array $params): void
    {
        Auth::requirePermission('expeditions');
        $expenses = ExpeditionCarExpense::findByExpedition((int)$params['id']);
        Response::success($expenses);
    }

    /**
     * 申請削除（管理者）
     * DELETE /api/expeditions/{id}/car-expenses/{eid}
     */
    public function destroy(array $params): void
    {
        Auth::requirePermission('expeditions');
        ExpeditionCarExpense::delete((int)$params['eid']);
        Response::success(['message' => '削除しました']);
    }

    /**
     * 清算計算（管理者）
     * GET /api/expeditions/{id}/car-expenses/settlement
     */
    public function settlement(array $params): void
    {
        Auth::requirePermission('expeditions');
        $data = $this->calcSettlement((int)$params['id']);
        Response::success($data);
    }

    /**
     * Excel出力
     * GET /api/expeditions/{id}/car-expenses/export/xlsx
     */
    public function exportXlsx(array $params): void
    {
        Auth::requirePermission('expeditions');
        $expeditionId = (int)$params['id'];
        $expedition   = Expedition::findById($expeditionId);
        if (!$expedition) Response::error('遠征が見つかりません', 404);

        $data = $this->calcSettlement($expeditionId);

        $spreadsheet = new Spreadsheet();

        // シート1: 申請一覧
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('申請一覧');
        $this->buildExpenseSheet($sheet1, $expedition, $data);

        // シート2: 清算一覧
        $sheet2 = $spreadsheet->createSheet(1);
        $sheet2->setTitle('清算一覧');
        $this->buildSettlementSheet($sheet2, $expedition, $data);

        $spreadsheet->setActiveSheetIndex(0);

        $name     = preg_replace('/[\\\\\/:\*\?"<>\|]/', '_', $expedition['name']);
        $filename = 'レンタカー清算_' . $name . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Cache-Control: max-age=0');

        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    /**
     * PDF出力（印刷用HTML）
     * GET /api/expeditions/{id}/car-expenses/export/pdf
     */
    public function exportPdf(array $params): void
    {
        Auth::requirePermission('expeditions');
        $expeditionId = (int)$params['id'];
        $expedition   = Expedition::findById($expeditionId);
        if (!$expedition) Response::error('遠征が見つかりません', 404);

        $data = $this->calcSettlement($expeditionId);

        header('Content-Type: text/html; charset=utf-8');
        echo $this->generatePdfHtml($expedition, $data);
        exit;
    }

    /**
     * 会員による費用申請（新規・更新）
     * POST /api/member/expedition/{id}/car-expense
     */
    public function memberSubmit(array $params): void
    {
        if (empty($_SESSION['member_authenticated'])) {
            Response::error('ログインが必要です', 401, 'UNAUTHORIZED');
            return;
        }

        $memberId     = (int)$_SESSION['member_id'];
        $expeditionId = (int)$params['id'];

        $expedition = Expedition::findById($expeditionId);
        if (!$expedition) {
            Response::error('遠征が見つかりません', 404, 'NOT_FOUND');
            return;
        }

        // 申請期限チェック
        if (!empty($expedition['expense_deadline']) && $expedition['expense_deadline'] < date('Y-m-d')) {
            Response::error('申請期限を過ぎています', 400, 'DEADLINE_PASSED');
            return;
        }

        // 確定参加者チェック
        $participants = ExpeditionParticipant::findByExpedition($expeditionId);
        $isParticipant = false;
        foreach ($participants as $p) {
            if ((int)$p['member_id'] === $memberId && $p['status'] === 'confirmed') {
                $isParticipant = true;
                break;
            }
        }
        if (!$isParticipant) {
            Response::error('参加登録が確認できません', 403, 'NOT_PARTICIPANT');
            return;
        }

        $body = Request::json();
        ExpeditionCarExpense::upsert($expeditionId, $memberId, $body);
        Response::success(['message' => '申請しました']);
    }

    // ==================== 内部ヘルパー ====================

    /**
     * 清算計算（参加者全員の支払い・返金・未申請を算出）
     */
    private function calcSettlement(int $expeditionId): array
    {
        // 車に割り当てられている member_id を取得
        $db             = Database::getInstance();
        $carMemberRows  = $db->fetchAll(
            "SELECT DISTINCT ecm.member_id
               FROM expedition_car_members ecm
               JOIN expedition_cars ec ON ec.id = ecm.car_id
              WHERE ec.expedition_id = ?",
            [$expeditionId]
        );
        $carMemberIds = array_column($carMemberRows, 'member_id');

        $participants = ExpeditionParticipant::findByExpedition($expeditionId);
        // 確定済み かつ 車割に含まれている参加者のみ清算対象
        $confirmed = array_values(array_filter(
            $participants,
            fn($p) => $p['status'] === 'confirmed' && in_array((int)$p['member_id'], array_map('intval', $carMemberIds))
        ));

        $expenses = ExpeditionCarExpense::findByExpedition($expeditionId);

        // member_id → 費用合計・内訳マップ
        $expenseMap = [];
        foreach ($expenses as $e) {
            $total = (int)$e['rental_fee'] + (int)$e['gas_fee'] + (int)$e['highway_fee'] + (int)$e['other_fee'];
            $expenseMap[(int)$e['member_id']] = array_merge($e, ['total' => $total]);
        }

        $grandTotal = array_sum(array_column($expenseMap, 'total'));
        $n          = count($confirmed);
        $share      = $n > 0 ? $grandTotal / $n : 0;

        $settlement = [];
        foreach ($confirmed as $p) {
            $mid  = (int)$p['member_id'];
            $exp  = $expenseMap[$mid] ?? null;
            $paid = $exp ? $exp['total'] : 0;
            $bal  = (int)round($paid - $share);

            $settlement[] = [
                'member_id'    => $mid,
                'name_kanji'   => $p['name_kanji'] ?? '',
                'name_kana'    => $p['name_kana']  ?? '',
                'paid'         => $paid,
                'rental_fee'   => $exp ? (int)$exp['rental_fee']  : 0,
                'gas_fee'      => $exp ? (int)$exp['gas_fee']     : 0,
                'highway_fee'  => $exp ? (int)$exp['highway_fee'] : 0,
                'other_fee'    => $exp ? (int)$exp['other_fee']   : 0,
                'share'        => (int)round($share),
                'balance'      => $bal,   // 正=受取、負=支払
                'has_expense'  => $exp !== null,
                'in_settlement' => true,
            ];
        }

        // 受取順に並び替え（多く受け取る人が先）
        usort($settlement, fn($a, $b) => $b['balance'] - $a['balance']);

        // 清算対象外（確定参加者だが車割なし）
        $excluded = [];
        foreach ($participants as $p) {
            if ($p['status'] !== 'confirmed') continue;
            if (in_array((int)$p['member_id'], array_map('intval', $carMemberIds))) continue;
            $excluded[] = [
                'member_id'    => (int)$p['member_id'],
                'name_kanji'   => $p['name_kanji'] ?? '',
                'name_kana'    => $p['name_kana']  ?? '',
                'in_settlement' => false,
            ];
        }

        return [
            'settlement'        => $settlement,
            'excluded'          => $excluded,
            'grand_total'       => $grandTotal,
            'share'             => (int)round($share),
            'participant_count' => $n,
            'submitted_count'   => count($expenseMap),
        ];
    }

    /**
     * ヘッダースタイル適用
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

    private function borderStyle(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);
    }

    /**
     * Excelシート1: 申請一覧
     */
    private function buildExpenseSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $expedition,
        array $data
    ): void {
        $settlement = $data['settlement'];

        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', '申請一覧　' . $expedition['name']);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $cols = ['A' => '氏名', 'B' => 'レンタカー代', 'C' => 'ガソリン代', 'D' => '高速料金', 'E' => 'その他', 'F' => '合計', 'G' => '申請', 'H' => '備考'];
        foreach ($cols as $col => $label) {
            $sheet->setCellValue($col . '2', $label);
        }
        $this->headerStyle($sheet, 'A2:H2');

        $sheet->getColumnDimension('A')->setWidth(18);
        foreach (['B','C','D','E','F'] as $c) $sheet->getColumnDimension($c)->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(8);
        $sheet->getColumnDimension('H')->setWidth(25);

        $row = 3;
        foreach ($settlement as $s) {
            $exp = $data['settlement'];
            $sheet->setCellValue('A' . $row, $s['name_kanji']);
            $sheet->setCellValue('B' . $row, $s['rental_fee']  > 0 ? $s['rental_fee']  : '');
            $sheet->setCellValue('C' . $row, $s['gas_fee']     > 0 ? $s['gas_fee']     : '');
            $sheet->setCellValue('D' . $row, $s['highway_fee'] > 0 ? $s['highway_fee'] : '');
            $sheet->setCellValue('E' . $row, $s['other_fee']   > 0 ? $s['other_fee']   : '');
            $sheet->setCellValue('F' . $row, $s['paid']);
            $sheet->setCellValue('G' . $row, $s['has_expense'] ? '済' : '未');

            if (!$s['has_expense']) {
                $sheet->getStyle('G' . $row)->getFont()->setColor(new Color('FF888888'))->setItalic(true);
            }
            // 金額列は右寄せ・通貨フォーマット
            foreach (['B','C','D','E','F'] as $c) {
                if ($sheet->getCell($c . $row)->getValue() !== '') {
                    $sheet->getStyle($c . $row)->getNumberFormat()->setFormatCode('¥#,##0');
                }
            }
            $sheet->getStyle('F' . $row)->getFont()->setBold(true);
            $row++;
        }

        // 合計行
        $sheet->setCellValue('A' . $row, '合計');
        $sheet->setCellValue('F' . $row, $data['grand_total']);
        $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('¥#,##0');
        $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':H' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');

        $row++;
        $sheet->setCellValue('A' . $row, '一人あたり負担額');
        $sheet->setCellValue('F' . $row, $data['share']);
        $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('¥#,##0');
        $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setItalic(true)->setColor(new Color('FF555555'));

        if ($row > 3) $this->borderStyle($sheet, 'A2:H' . ($row - 1));
        $sheet->getStyle('B3:F' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('G3:G' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /**
     * Excelシート2: 清算一覧
     */
    private function buildSettlementSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $expedition,
        array $data
    ): void {
        $settlement = $data['settlement'];

        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', '清算一覧　' . $expedition['name']);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        foreach (['A' => '氏名', 'B' => '支払い合計', 'C' => '一人あたり負担', 'D' => '精算額'] as $col => $label) {
            $sheet->setCellValue($col . '2', $label);
        }
        $this->headerStyle($sheet, 'A2:D2');

        $sheet->getColumnDimension('A')->setWidth(18);
        foreach (['B','C','D'] as $c) $sheet->getColumnDimension($c)->setWidth(16);

        $row = 3;
        foreach ($settlement as $s) {
            $sheet->setCellValue('A' . $row, $s['name_kanji']);
            $sheet->setCellValue('B' . $row, $s['paid']);
            $sheet->setCellValue('C' . $row, $s['share']);
            $sheet->setCellValue('D' . $row, $s['balance']);

            $sheet->getStyle('B' . $row . ':D' . $row)->getNumberFormat()->setFormatCode('¥#,##0;[Red]-¥#,##0');

            if ($s['balance'] > 0) {
                // 受取（緑）
                $sheet->getStyle('D' . $row)->getFont()->setColor(new Color('FF006400'))->setBold(true);
            } elseif ($s['balance'] < 0) {
                // 支払（赤）
                $sheet->getStyle('D' . $row)->getFont()->setColor(new Color('FFCC0000'))->setBold(true);
            }

            if (!$s['has_expense']) {
                $sheet->getStyle('A' . $row)->getFont()->setColor(new Color('FF888888'));
            }

            $row++;
        }

        if ($row > 3) $this->borderStyle($sheet, 'A2:D' . ($row - 1));
        $sheet->getStyle('B3:D' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // 凡例
        $row++;
        $sheet->setCellValue('A' . $row, '※精算額が正の値 → 受け取る　負の値 → 支払う');
        $sheet->getStyle('A' . $row)->getFont()->setItalic(true)->setColor(new Color('FF555555'));
    }

    /**
     * PDF用HTML生成
     */
    private function generatePdfHtml(array $expedition, array $data): string
    {
        $h   = fn($s) => htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8');
        $fmt = fn($n) => '¥' . number_format((int)$n);
        $name = $h($expedition['name']);
        $settlement = $data['settlement'];

        // 申請一覧テーブル
        $expRows = '';
        $totalRental = $totalGas = $totalHighway = $totalOther = $totalPaid = 0;
        foreach ($settlement as $s) {
            $totalRental  += $s['rental_fee'];
            $totalGas     += $s['gas_fee'];
            $totalHighway += $s['highway_fee'];
            $totalOther   += $s['other_fee'];
            $totalPaid    += $s['paid'];
            $expRows .= '<tr' . ($s['has_expense'] ? '' : ' class="no-expense"') . '>'
                . '<td>' . $h($s['name_kanji']) . '</td>'
                . '<td class="num">' . ($s['rental_fee']  > 0 ? $fmt($s['rental_fee'])  : '—') . '</td>'
                . '<td class="num">' . ($s['gas_fee']     > 0 ? $fmt($s['gas_fee'])     : '—') . '</td>'
                . '<td class="num">' . ($s['highway_fee'] > 0 ? $fmt($s['highway_fee']) : '—') . '</td>'
                . '<td class="num">' . ($s['other_fee']   > 0 ? $fmt($s['other_fee'])   : '—') . '</td>'
                . '<td class="num bold">' . ($s['paid'] > 0 ? $fmt($s['paid']) : '—') . '</td>'
                . '<td class="center ' . ($s['has_expense'] ? '' : 'gray') . '">' . ($s['has_expense'] ? '済' : '未') . '</td>'
                . '</tr>';
        }
        $expRows .= '<tr class="total-row">'
            . '<td><strong>合計</strong></td>'
            . '<td class="num">' . $fmt($totalRental)  . '</td>'
            . '<td class="num">' . $fmt($totalGas)     . '</td>'
            . '<td class="num">' . $fmt($totalHighway) . '</td>'
            . '<td class="num">' . $fmt($totalOther)   . '</td>'
            . '<td class="num bold">' . $fmt($totalPaid) . '</td>'
            . '<td></td></tr>';

        // 清算一覧テーブル
        $setRows = '';
        foreach ($settlement as $s) {
            $cls = $s['balance'] > 0 ? 'receive' : ($s['balance'] < 0 ? 'pay' : '');
            $label = $s['balance'] > 0
                ? '受取 ' . $fmt($s['balance'])
                : ($s['balance'] < 0 ? '支払 ' . $fmt(abs($s['balance'])) : '清算なし');
            $setRows .= '<tr' . ($s['has_expense'] ? '' : ' class="no-expense"') . '>'
                . '<td>' . $h($s['name_kanji']) . '</td>'
                . '<td class="num">' . ($s['paid'] > 0 ? $fmt($s['paid']) : '—') . '</td>'
                . '<td class="num">' . $fmt($s['share']) . '</td>'
                . '<td class="num bold ' . $cls . '">' . $label . '</td>'
                . '</tr>';
        }

        return '<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>' . $name . ' - レンタカー清算</title>
<style>
  body { font-family: "Hiragino Sans","Yu Gothic","Meiryo",sans-serif; font-size:12px; margin:20px; color:#222; }
  h1 { font-size:17px; text-align:center; margin-bottom:4px; }
  h2 { font-size:13px; margin:24px 0 6px; border-bottom:2px solid #334; padding-bottom:3px; }
  table { width:100%; border-collapse:collapse; margin-bottom:10px; }
  th, td { border:1px solid #bbb; padding:5px 8px; font-size:11px; }
  th { background:#d9e1f2; font-weight:bold; text-align:center; }
  .num { text-align:right; }
  .center { text-align:center; }
  .bold { font-weight:bold; }
  .gray { color:#888; }
  .total-row { background:#fff2cc; font-weight:bold; }
  .no-expense td { color:#888; }
  .receive { color:#006400; font-weight:bold; }
  .pay { color:#cc0000; font-weight:bold; }
  .no-print { margin-bottom:14px; }
  @media print { .no-print { display:none; } }
</style>
</head>
<body>
<div class="no-print">
  <button onclick="window.print()" style="padding:6px 16px;margin-right:8px;">印刷 / PDF保存</button>
  <button onclick="window.close()" style="padding:6px 16px;">閉じる</button>
</div>
<h1>' . $name . ' レンタカー清算</h1>
<p style="text-align:center;color:#555;font-size:11px;">参加者 ' . $data['participant_count'] . '名 ／ 一人あたり負担 ' . $fmt($data['share']) . ' ／ 申請総額 ' . $fmt($data['grand_total']) . '</p>

<h2>1. 費用申請一覧</h2>
<table>
  <thead><tr><th>氏名</th><th>レンタカー代</th><th>ガソリン代</th><th>高速料金</th><th>その他</th><th>合計</th><th>申請</th></tr></thead>
  <tbody>' . $expRows . '</tbody>
</table>

<h2>2. 清算一覧</h2>
<table>
  <thead><tr><th>氏名</th><th>支払い合計</th><th>一人あたり負担</th><th>精算額</th></tr></thead>
  <tbody>' . $setRows . '</tbody>
</table>
<p style="font-size:10px;color:#555;">※精算額「受取」→ お金を受け取る　「支払」→ お金を支払う　「清算なし」→ 過不足なし</p>
</body>
</html>';
    }
}
