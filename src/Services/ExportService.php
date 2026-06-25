<?php
/**
 * 出力サービス（PDF/Excel）
 */

// PhpSpreadsheet用のオートローダーを読み込み
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExportService
{
    /**
     * PDF出力
     */
    public function generatePdf(int $campId): void
    {
        $calculationService = new CalculationService();
        $result = $calculationService->calculate($campId);
        $partialSchedule = $calculationService->generatePartialParticipationSchedule($campId);

        // シンプルなHTMLベースのPDF出力
        // 本番環境では TCPDF または mPDF を使用
        $html = $this->generatePdfHtml($result, $partialSchedule);

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="camp_result_' . $campId . '.html"');

        echo $html;
        exit;
    }

    /**
     * Excel出力（CSV形式で代替）
     */
    public function generateExcel(int $campId): void
    {
        $calculationService = new CalculationService();
        $result = $calculationService->calculate($campId);
        $partialSchedule = $calculationService->generatePartialParticipationSchedule($campId);

        // CSV形式で出力
        // 本番環境では PhpSpreadsheet を使用
        $csv = $this->generateCsv($result, $partialSchedule);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="camp_result_' . $campId . '.csv"');
        echo "\xEF\xBB\xBF"; // BOM for Excel UTF-8

        echo $csv;
        exit;
    }

    /**
     * 10月引退ルールを適用して学年ラベルを取得
     *
     * @param int|null $grade 学年
     * @param string|null $gender 性別
     * @return string 学年ラベル
     */
    private function getGradeLabel(?int $grade, ?string $gender): string
    {
        // 学年未設定
        if ($grade === null) {
            return '';
        }

        // OB/OG（grade=0）
        if ($grade === 0) {
            if ($gender === 'male') return 'OB';
            if ($gender === 'female') return 'OG';
            return 'OB/OG';
        }

        // 10月引退ルール: 3年生は10月以降（10月〜3月）OB扱い
        $month = (int)date('n'); // 1-12
        if ($grade === 3 && ($month >= 10 || $month <= 3)) {
            if ($gender === 'male') return 'OB';
            if ($gender === 'female') return 'OG';
            return 'OB/OG';
        }

        return $grade . '年';
    }

    /**
     * フル参加かどうか判定
     */
    private function isFullParticipation(array $participant, int $totalDays): bool
    {
        $isFullJoin = ($participant['join_day'] == 1 && $participant['join_timing'] === 'outbound_bus');
        $isFullLeave = ($participant['leave_day'] == $totalDays && $participant['leave_timing'] === 'return_bus');
        return $isFullJoin && $isFullLeave;
    }

    /**
     * 参加者をフル参加と途中参加に分類
     */
    private function categorizeParticipants(array $participants, int $totalDays): array
    {
        $fullParticipants = [];
        $partialParticipants = [];

        foreach ($participants as $p) {
            if ($this->isFullParticipation($p, $totalDays)) {
                $fullParticipants[] = $p;
            } else {
                $partialParticipants[] = $p;
            }
        }

        return [
            'full' => $fullParticipants,
            'partial' => $partialParticipants,
        ];
    }

    /**
     * PDF用HTML生成
     */
    private function generatePdfHtml(array $result, array $partialSchedule): string
    {
        $camp = $result['camp'];
        $summary = $result['summary'];
        $participants = $result['participants'];
        $totalDays = $camp['nights'] + 1;

        // 参加者を分類
        $categorized = $this->categorizeParticipants($participants, $totalDays);
        $fullParticipants = $categorized['full'];
        $partialParticipants = $categorized['partial'];

        $html = '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($camp['name']) . ' - 精算表</title>
    <style>
        body { font-family: "Hiragino Sans", "Yu Gothic", sans-serif; font-size: 12px; }
        h1 { font-size: 18px; text-align: center; }
        h2 { font-size: 14px; margin-top: 30px; border-bottom: 2px solid #333; padding-bottom: 5px; }
        .summary { background: #f5f5f5; padding: 10px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; font-size: 11px; }
        th { background: #e0e0e0; }
        .amount { text-align: right; }
        .total { font-weight: bold; background: #fff3cd; }
        .full-summary { background: #d4edda; }
        .partial-row { background: #fff; }
        .schedule-table th, .schedule-table td { padding: 3px 5px; text-align: center; font-size: 10px; }
        .schedule-table .name-col { text-align: left; white-space: nowrap; }
        .schedule-table .desc-col { text-align: left; font-size: 9px; max-width: 200px; }
        .attend { color: #198754; font-weight: bold; }
        .not-attend { color: #dc3545; }
        .page-break { page-break-before: always; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">印刷</button>
        <button onclick="window.close()">閉じる</button>
    </div>

    <h1>' . htmlspecialchars($camp['name']) . ' 精算表</h1>

    <div class="summary">
        <p>日程: ' . $camp['start_date'] . ' ～ ' . $camp['end_date'] . ' (' . $camp['nights'] . '泊' . ($camp['nights'] + 1) . '日)</p>
        <p>参加者数: ' . $summary['participant_count'] . '名（フル参加: ' . count($fullParticipants) . '名、途中参加/途中抜け: ' . count($partialParticipants) . '名）</p>
        <p>総額: ¥' . number_format($summary['total_amount']) . '</p>
        <p>平均: ¥' . number_format($summary['average_amount']) . '</p>
    </div>';

        // フル参加者セクション
        if (!empty($fullParticipants)) {
            $html .= '
    <h2>フル参加者（' . count($fullParticipants) . '名）</h2>
    <table>
        <thead>
            <tr>
                <th class="amount">負担額</th>
                <th>内訳</th>
                <th>対象者</th>
            </tr>
        </thead>
        <tbody>';

            // フル参加者の代表（最初の1人）の情報を使用
            $representative = $fullParticipants[0];
            $items = [];
            foreach ($representative['items'] as $item) {
                $items[] = $item['name'] . ': ¥' . number_format($item['amount']);
            }

            // フル参加者の名前リスト
            $names = array_map(function($p) { return htmlspecialchars($p['name']); }, $fullParticipants);

            $html .= '
            <tr class="full-summary">
                <td class="amount total">¥' . number_format($representative['total']) . '</td>
                <td><small>' . implode(', ', $items) . '</small></td>
                <td><small>' . implode('、', $names) . '</small></td>
            </tr>';

            $html .= '
        </tbody>
    </table>';
        }

        // 途中参加・途中抜けセクション
        if (!empty($partialParticipants)) {
            $html .= '
    <h2>途中参加・途中抜け（' . count($partialParticipants) . '名）</h2>
    <table>
        <thead>
            <tr>
                <th>名前</th>
                <th>参加期間</th>
                <th class="amount">負担額</th>
                <th>内訳</th>
            </tr>
        </thead>
        <tbody>';

            $joinTimingLabels = [
                'outbound_bus' => '往路バス', 'breakfast' => '朝食', 'morning' => '午前',
                'lunch' => '昼食', 'afternoon' => '午後', 'dinner' => '夕食', 'night' => '夜'
            ];
            $leaveTimingLabels = [
                'return_bus' => '復路バス', 'before_breakfast' => '朝食前', 'breakfast' => '朝食',
                'morning' => '午前', 'lunch' => '昼食', 'afternoon' => '午後', 'dinner' => '夕食', 'night' => '夜'
            ];

            foreach ($partialParticipants as $p) {
                $items = [];
                foreach ($p['items'] as $item) {
                    $items[] = $item['name'] . ': ¥' . number_format($item['amount']);
                }

                $joinLabel = ($joinTimingLabels[$p['join_timing']] ?? $p['join_timing']);
                $leaveLabel = ($leaveTimingLabels[$p['leave_timing']] ?? $p['leave_timing']);
                $period = $p['join_day'] . '日目' . $joinLabel . '～' . $p['leave_day'] . '日目' . $leaveLabel;

                $html .= '
            <tr class="partial-row">
                <td>' . htmlspecialchars($p['name']) . '</td>
                <td><small>' . $period . '</small></td>
                <td class="amount total">¥' . number_format($p['total']) . '</td>
                <td><small>' . implode(', ', $items) . '</small></td>
            </tr>';
            }

            $html .= '
        </tbody>
    </table>';
        }

        // 途中参加途中抜け一覧（スケジュール表）
        if (!empty($partialSchedule['rows'])) {
            $html .= '
    <div class="page-break"></div>
    <h2>途中参加・途中抜け スケジュール一覧</h2>
    <table class="schedule-table">
        <thead>
            <tr>
                <th rowspan="2" class="name-col">氏名</th>';

            // 日付ヘッダー
            foreach ($partialSchedule['headers'] as $dayHeader) {
                $colCount = count($dayHeader['columns']);
                $html .= '<th colspan="' . $colCount . '">' . $dayHeader['day'] . '日目</th>';
            }
            $html .= '</tr><tr>';

            // 項目ヘッダー
            foreach ($partialSchedule['headers'] as $dayHeader) {
                foreach ($dayHeader['columns'] as $col) {
                    $html .= '<th>' . htmlspecialchars($col['label']) . '</th>';
                }
            }
            $html .= '</tr></thead><tbody>';

            // 参加者行
            foreach ($partialSchedule['rows'] as $row) {
                $gradeLabel = $this->getGradeLabel($row['grade'], $row['gender']);
                $genderIcon = $row['gender'] === 'male' ? '♂' : ($row['gender'] === 'female' ? '♀' : '');

                $html .= '<tr>
                <td class="name-col">' . htmlspecialchars($row['name']) . ' <small>(' . $gradeLabel . $genderIcon . ')</small></td>';

                foreach ($partialSchedule['headers'] as $dayHeader) {
                    foreach ($dayHeader['columns'] as $col) {
                        $attends = $row['schedule'][$col['key']] ?? false;
                        $html .= '<td class="' . ($attends ? 'attend' : 'not-attend') . '">' . ($attends ? '○' : '×') . '</td>';
                    }
                }
                $html .= '</tr>';
            }

            // 集計行
            $html .= '<tr style="background: #e9ecef; font-weight: bold;">
                <td>合計</td>';
            foreach ($partialSchedule['headers'] as $dayHeader) {
                foreach ($dayHeader['columns'] as $col) {
                    $count = $partialSchedule['totals'][$col['key']] ?? 0;
                    $html .= '<td>' . $count . '</td>';
                }
            }
            $html .= '</tr></tbody></table>';
        }

        $html .= '
    <p style="text-align: center; margin-top: 40px; font-size: 10px; color: #666;">
        出力日時: ' . date('Y/m/d H:i') . '
    </p>
</body>
</html>';

        return $html;
    }

    /**
     * CSV生成
     */
    private function generateCsv(array $result, array $partialSchedule): string
    {
        $camp = $result['camp'];
        $participants = $result['participants'];
        $totalDays = $camp['nights'] + 1;

        // 参加者を分類
        $categorized = $this->categorizeParticipants($participants, $totalDays);
        $fullParticipants = $categorized['full'];
        $partialParticipants = $categorized['partial'];

        $lines = [];

        // ヘッダー
        $lines[] = $this->csvLine(['合宿名', $camp['name']]);
        $lines[] = $this->csvLine(['日程', $camp['start_date'] . '～' . $camp['end_date']]);
        $lines[] = $this->csvLine(['参加者数', count($participants) . '名']);
        $lines[] = '';

        // 全カテゴリを収集
        $categories = [];
        foreach ($participants as $p) {
            foreach ($p['items'] as $item) {
                if (!in_array($item['category'], $categories)) {
                    $categories[] = $item['category'];
                }
            }
        }

        $catNames = [
            'lodging' => '宿泊費',
            'hot_spring_tax' => '入湯税',
            'insurance' => '保険料',
            'meal_adjustment' => '食事調整',
            'bus' => 'バス代',
            'highway' => '高速代',
            'facility' => '施設利用料',
            'expense' => '雑費',
        ];

        // フル参加者セクション
        if (!empty($fullParticipants)) {
            $lines[] = $this->csvLine(['【フル参加者】', count($fullParticipants) . '名']);

            $headers = ['名前', '負担額'];
            foreach ($categories as $cat) {
                $headers[] = $catNames[$cat] ?? $cat;
            }
            $lines[] = $this->csvLine($headers);

            // フル参加者の代表（金額は同じなので最初の1人のみ詳細表示）
            $representative = $fullParticipants[0];
            $row = ['フル参加者（' . count($fullParticipants) . '名）', $representative['total']];
            foreach ($categories as $cat) {
                $catTotal = 0;
                foreach ($representative['items'] as $item) {
                    if ($item['category'] === $cat) {
                        $catTotal += $item['amount'];
                    }
                }
                $row[] = $catTotal;
            }
            $lines[] = $this->csvLine($row);
            $lines[] = '';
        }

        // 途中参加・途中抜けセクション
        if (!empty($partialParticipants)) {
            $lines[] = $this->csvLine(['【途中参加・途中抜け】', count($partialParticipants) . '名']);

            $headers = ['名前', '参加期間', '負担額'];
            foreach ($categories as $cat) {
                $headers[] = $catNames[$cat] ?? $cat;
            }
            $lines[] = $this->csvLine($headers);

            $joinTimingLabels = [
                'outbound_bus' => '往路バス', 'breakfast' => '朝食', 'morning' => '午前',
                'lunch' => '昼食', 'afternoon' => '午後', 'dinner' => '夕食', 'night' => '夜'
            ];
            $leaveTimingLabels = [
                'return_bus' => '復路バス', 'before_breakfast' => '朝食前', 'breakfast' => '朝食',
                'morning' => '午前', 'lunch' => '昼食', 'afternoon' => '午後', 'dinner' => '夕食', 'night' => '夜'
            ];

            foreach ($partialParticipants as $p) {
                $joinLabel = ($joinTimingLabels[$p['join_timing']] ?? $p['join_timing']);
                $leaveLabel = ($leaveTimingLabels[$p['leave_timing']] ?? $p['leave_timing']);
                $period = $p['join_day'] . '日目' . $joinLabel . '～' . $p['leave_day'] . '日目' . $leaveLabel;

                $row = [$p['name'], $period, $p['total']];
                foreach ($categories as $cat) {
                    $catTotal = 0;
                    foreach ($p['items'] as $item) {
                        if ($item['category'] === $cat) {
                            $catTotal += $item['amount'];
                        }
                    }
                    $row[] = $catTotal;
                }
                $lines[] = $this->csvLine($row);
            }
            $lines[] = '';
        }

        // 途中参加途中抜けスケジュール一覧
        if (!empty($partialSchedule['rows'])) {
            $lines[] = $this->csvLine(['【途中参加・途中抜け スケジュール一覧】']);

            // ヘッダー行1（日付）
            $dayHeaders = ['氏名'];
            foreach ($partialSchedule['headers'] as $dayHeader) {
                foreach ($dayHeader['columns'] as $col) {
                    $dayHeaders[] = $dayHeader['day'] . '日目';
                }
            }
            $lines[] = $this->csvLine($dayHeaders);

            // ヘッダー行2（項目）
            $colHeaders = [''];
            foreach ($partialSchedule['headers'] as $dayHeader) {
                foreach ($dayHeader['columns'] as $col) {
                    $colHeaders[] = $col['label'];
                }
            }
            $lines[] = $this->csvLine($colHeaders);

            // 参加者行
            foreach ($partialSchedule['rows'] as $row) {
                $gradeLabel = $this->getGradeLabel($row['grade'], $row['gender']);
                $genderStr = $row['gender'] === 'male' ? '男' : ($row['gender'] === 'female' ? '女' : '');
                $nameWithGrade = $row['name'] . '(' . $gradeLabel . $genderStr . ')';

                $dataRow = [$nameWithGrade];
                foreach ($partialSchedule['headers'] as $dayHeader) {
                    foreach ($dayHeader['columns'] as $col) {
                        $attends = $row['schedule'][$col['key']] ?? false;
                        $dataRow[] = $attends ? '○' : '×';
                    }
                }
                $lines[] = $this->csvLine($dataRow);
            }

            // 集計行
            $totalRow = ['合計'];
            foreach ($partialSchedule['headers'] as $dayHeader) {
                foreach ($dayHeader['columns'] as $col) {
                    $totalRow[] = $partialSchedule['totals'][$col['key']] ?? 0;
                }
            }
            $lines[] = $this->csvLine($totalRow);
        }

        return implode("\n", $lines);
    }

    /**
     * CSV行を生成（カンマやクォートをエスケープ）
     */
    private function csvLine(array $values): string
    {
        $escaped = array_map(function($val) {
            $val = (string)$val;
            // カンマ、ダブルクォート、改行が含まれる場合はクォートで囲む
            if (strpos($val, ',') !== false || strpos($val, '"') !== false || strpos($val, "\n") !== false) {
                return '"' . str_replace('"', '""', $val) . '"';
            }
            return $val;
        }, $values);
        return implode(',', $escaped);
    }

    /**
     * Excel（xlsx形式）出力
     */
    public function generateXlsx(int $campId): void
    {
        $calculationService = new CalculationService();
        $result = $calculationService->calculate($campId);
        $partialSchedule = $calculationService->generatePartialParticipationSchedule($campId);

        $camp = $result['camp'];
        $summary = $result['summary'];
        $participants = $result['participants'];
        $totalDays = $camp['nights'] + 1;

        // 参加者を分類
        $categorized = $this->categorizeParticipants($participants, $totalDays);
        $fullParticipants = $categorized['full'];
        $partialParticipants = $categorized['partial'];

        // スプレッドシート作成
        $spreadsheet = new Spreadsheet();

        // シート1: 精算表
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('精算表');

        // 基本情報
        $row = 1;
        $sheet->setCellValue('A' . $row, '合宿名');
        $sheet->setCellValue('B' . $row, $camp['name']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, '日程');
        $sheet->setCellValue('B' . $row, $camp['start_date'] . ' ～ ' . $camp['end_date'] . ' (' . $camp['nights'] . '泊' . ($camp['nights'] + 1) . '日)');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, '参加者数');
        $sheet->setCellValue('B' . $row, $summary['participant_count'] . '名（フル参加: ' . count($fullParticipants) . '名、途中参加/抜け: ' . count($partialParticipants) . '名）');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, '総額');
        $sheet->setCellValue('B' . $row, $summary['total_amount']);
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('¥#,##0');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, '平均');
        $sheet->setCellValue('B' . $row, $summary['average_amount']);
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('¥#,##0');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row += 2;

        // カテゴリ名
        $catNames = [
            'lodging' => '宿泊費',
            'hot_spring_tax' => '入湯税',
            'insurance' => '保険料',
            'meal_adjustment' => '食事調整',
            'bus' => 'バス代',
            'highway' => '高速代',
            'facility' => '施設利用料',
            'expense' => '雑費',
        ];

        // 全カテゴリを収集
        $categories = [];
        foreach ($participants as $p) {
            foreach ($p['items'] as $item) {
                if (!in_array($item['category'], $categories)) {
                    $categories[] = $item['category'];
                }
            }
        }

        // フル参加者セクション
        if (!empty($fullParticipants)) {
            $sheet->setCellValue('A' . $row, '【フル参加者】' . count($fullParticipants) . '名');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row . ':' . $this->getColumnLetter(count($categories) + 2) . $row)
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D4EDDA');
            $row++;

            // ヘッダー
            $col = 1;
            $sheet->setCellValueByColumnAndRow($col++, $row, '名前');
            $sheet->setCellValueByColumnAndRow($col++, $row, '負担額');
            foreach ($categories as $cat) {
                $sheet->setCellValueByColumnAndRow($col++, $row, $catNames[$cat] ?? $cat);
            }
            $headerRow = $row;
            $sheet->getStyle('A' . $row . ':' . $this->getColumnLetter($col - 1) . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':' . $this->getColumnLetter($col - 1) . $row)
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
            $row++;

            // フル参加者データ
            $representative = $fullParticipants[0];
            $col = 1;
            $sheet->setCellValueByColumnAndRow($col++, $row, 'フル参加者（' . count($fullParticipants) . '名）');
            $sheet->setCellValueByColumnAndRow($col++, $row, $representative['total']);
            $sheet->getStyleByColumnAndRow($col - 1, $row)->getNumberFormat()->setFormatCode('¥#,##0');

            foreach ($categories as $cat) {
                $catTotal = 0;
                foreach ($representative['items'] as $item) {
                    if ($item['category'] === $cat) {
                        $catTotal += $item['amount'];
                    }
                }
                $sheet->setCellValueByColumnAndRow($col++, $row, $catTotal);
                $sheet->getStyleByColumnAndRow($col - 1, $row)->getNumberFormat()->setFormatCode('¥#,##0');
            }
            $row += 2;
        }

        // 途中参加・途中抜けセクション
        if (!empty($partialParticipants)) {
            $sheet->setCellValue('A' . $row, '【途中参加・途中抜け】' . count($partialParticipants) . '名');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row . ':' . $this->getColumnLetter(count($categories) + 3) . $row)
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF3CD');
            $row++;

            // ヘッダー
            $col = 1;
            $sheet->setCellValueByColumnAndRow($col++, $row, '名前');
            $sheet->setCellValueByColumnAndRow($col++, $row, '参加期間');
            $sheet->setCellValueByColumnAndRow($col++, $row, '負担額');
            foreach ($categories as $cat) {
                $sheet->setCellValueByColumnAndRow($col++, $row, $catNames[$cat] ?? $cat);
            }
            $sheet->getStyle('A' . $row . ':' . $this->getColumnLetter($col - 1) . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':' . $this->getColumnLetter($col - 1) . $row)
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
            $row++;

            $joinTimingLabels = [
                'outbound_bus' => '往路バス', 'breakfast' => '朝食', 'morning' => '午前',
                'lunch' => '昼食', 'afternoon' => '午後', 'dinner' => '夕食', 'night' => '夜'
            ];
            $leaveTimingLabels = [
                'return_bus' => '復路バス', 'before_breakfast' => '朝食前', 'breakfast' => '朝食',
                'morning' => '午前', 'lunch' => '昼食', 'afternoon' => '午後', 'dinner' => '夕食', 'night' => '夜'
            ];

            foreach ($partialParticipants as $p) {
                $joinLabel = ($joinTimingLabels[$p['join_timing']] ?? $p['join_timing']);
                $leaveLabel = ($leaveTimingLabels[$p['leave_timing']] ?? $p['leave_timing']);
                $period = $p['join_day'] . '日目' . $joinLabel . '～' . $p['leave_day'] . '日目' . $leaveLabel;

                $col = 1;
                $sheet->setCellValueByColumnAndRow($col++, $row, $p['name']);
                $sheet->setCellValueByColumnAndRow($col++, $row, $period);
                $sheet->setCellValueByColumnAndRow($col++, $row, $p['total']);
                $sheet->getStyleByColumnAndRow($col - 1, $row)->getNumberFormat()->setFormatCode('¥#,##0');

                foreach ($categories as $cat) {
                    $catTotal = 0;
                    foreach ($p['items'] as $item) {
                        if ($item['category'] === $cat) {
                            $catTotal += $item['amount'];
                        }
                    }
                    $sheet->setCellValueByColumnAndRow($col++, $row, $catTotal);
                    $sheet->getStyleByColumnAndRow($col - 1, $row)->getNumberFormat()->setFormatCode('¥#,##0');
                }
                $row++;
            }
        }

        // 列幅自動調整
        foreach (range('A', $this->getColumnLetter(count($categories) + 3)) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // シート2: 途中参加・途中抜けスケジュール
        if (!empty($partialSchedule['rows'])) {
            $scheduleSheet = $spreadsheet->createSheet();
            $scheduleSheet->setTitle('途参途抜一覧');

            $row = 1;

            // 全列数を計算
            $totalCols = 1; // 氏名列
            foreach ($partialSchedule['headers'] as $dayHeader) {
                $totalCols += count($dayHeader['columns']);
            }

            // 日付ヘッダー行
            $col = 1;
            $scheduleSheet->setCellValueByColumnAndRow($col, $row, '氏名');
            $scheduleSheet->mergeCells($this->getColumnLetter($col) . $row . ':' . $this->getColumnLetter($col) . ($row + 1));
            $scheduleSheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $col++;

            foreach ($partialSchedule['headers'] as $dayHeader) {
                $colCount = count($dayHeader['columns']);
                $startCol = $col;
                $endCol = $col + $colCount - 1;

                $scheduleSheet->setCellValueByColumnAndRow($col, $row, $dayHeader['day'] . '日目');
                $scheduleSheet->mergeCells($this->getColumnLetter($startCol) . $row . ':' . $this->getColumnLetter($endCol) . $row);
                $scheduleSheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $scheduleSheet->getStyle($this->getColumnLetter($startCol) . $row . ':' . $this->getColumnLetter($endCol) . $row)
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('CCE5FF');

                $col += $colCount;
            }
            $row++;

            // 項目ヘッダー行
            $col = 2;
            foreach ($partialSchedule['headers'] as $dayHeader) {
                foreach ($dayHeader['columns'] as $colDef) {
                    $scheduleSheet->setCellValueByColumnAndRow($col, $row, $colDef['label']);
                    $scheduleSheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $col++;
                }
            }
            $scheduleSheet->getStyle('A1:' . $this->getColumnLetter($totalCols) . $row)->getFont()->setBold(true);
            $row++;

            // 参加者データ行
            foreach ($partialSchedule['rows'] as $participantRow) {
                $gradeLabel = $this->getGradeLabel($participantRow['grade'], $participantRow['gender']);
                $genderStr = $participantRow['gender'] === 'male' ? '男' : ($participantRow['gender'] === 'female' ? '女' : '');
                $nameWithGrade = $participantRow['name'] . '(' . $gradeLabel . $genderStr . ')';

                $col = 1;
                $scheduleSheet->setCellValueByColumnAndRow($col++, $row, $nameWithGrade);

                foreach ($partialSchedule['headers'] as $dayHeader) {
                    foreach ($dayHeader['columns'] as $colDef) {
                        $attends = $participantRow['schedule'][$colDef['key']] ?? false;
                        $scheduleSheet->setCellValueByColumnAndRow($col, $row, $attends ? '○' : '×');
                        $scheduleSheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                        // ○は緑、×は赤
                        if ($attends) {
                            $scheduleSheet->getStyleByColumnAndRow($col, $row)->getFont()->getColor()->setRGB('198754');
                        } else {
                            $scheduleSheet->getStyleByColumnAndRow($col, $row)->getFont()->getColor()->setRGB('DC3545');
                        }
                        $col++;
                    }
                }
                $row++;
            }

            // 集計行
            $col = 1;
            $scheduleSheet->setCellValueByColumnAndRow($col++, $row, '合計');
            $scheduleSheet->getStyleByColumnAndRow(1, $row)->getFont()->setBold(true);
            $scheduleSheet->getStyle('A' . $row . ':' . $this->getColumnLetter($totalCols) . $row)
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E9ECEF');

            foreach ($partialSchedule['headers'] as $dayHeader) {
                foreach ($dayHeader['columns'] as $colDef) {
                    $count = $partialSchedule['totals'][$colDef['key']] ?? 0;
                    $scheduleSheet->setCellValueByColumnAndRow($col, $row, $count);
                    $scheduleSheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $col++;
                }
            }

            // 列幅自動調整
            for ($i = 1; $i <= $totalCols; $i++) {
                $scheduleSheet->getColumnDimensionByColumn($i)->setAutoSize(true);
            }

            // 罫線を追加
            $scheduleSheet->getStyle('A1:' . $this->getColumnLetter($totalCols) . $row)
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        // 最初のシートをアクティブに
        $spreadsheet->setActiveSheetIndex(0);

        // 出力
        // ファイル名に使用できない文字を除去
        $safeCampName = preg_replace('/[\/\\\\:*?"<>|]/', '', $camp['name']);
        $filename = $safeCampName . '_参加費_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * 列番号からExcel列文字を取得
     */
    private function getColumnLetter(int $columnIndex): string
    {
        $letter = '';
        while ($columnIndex > 0) {
            $mod = ($columnIndex - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $columnIndex = (int)(($columnIndex - $mod) / 26);
        }
        return $letter;
    }

    /**
     * 保険加入者名簿Excel出力（マイコム形式）
     */
    public function generateInsuranceRoster(int $campId): void
    {
        $campModel = new Camp();
        $camp = $campModel->find($campId);
        if (!$camp) {
            throw new \Exception('合宿が見つかりません');
        }

        $participantModel = new Participant();
        $participants = $participantModel->getByCampId($campId);

        // 会員名簿からデータを取得（名前でマッチング）
        $memberModel = new Member();
        $allMembers = $memberModel->all();

        // 名前をキーにしてメンバー情報をマッピング
        $memberMap = [];
        foreach ($allMembers as $member) {
            // 漢字名の空白を統一（全角・半角スペースを統一）
            $normalizedName = preg_replace('/[\s　]+/u', '　', trim($member['name_kanji']));
            $memberMap[$normalizedName] = $member;
        }

        // スプレッドシート作成
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('名簿');

        // シート設定
        $sheet->getDefaultColumnDimension()->setWidth(12);

        // タイトル
        $row = 1;
        $sheet->setCellValue('A' . $row, '旅行参加者名簿 兼 保険加入者名簿');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells('A' . $row . ':T' . $row);

        // 注意書き
        $row = 4;
        $sheet->setCellValue('A' . $row, '※ご出発の3日前（店舗休業日を除く）までに必ずご提出ください');
        $row++;
        $sheet->setCellValue('A' . $row, '※氏名はフルネームでご記入ください');
        $row++;
        $sheet->setCellValue('A' . $row, '※年齢もしくは学年のどちらかを必ずご記入ください');
        $row++;
        $sheet->setCellValue('A' . $row, '※国内旅行傷害保険は①保険名簿（本紙） ②保険料のご入金 の2点確認時点で引受完了となります');
        $row += 2;

        // 基本情報セクション
        $sheet->setCellValue('A' . $row, '団体名');
        $sheet->setCellValue('C' . $row, '早稲田大学レッセフェールテニスクラブ');
        $sheet->getStyle('C' . $row)->getFont()->setBold(true);
        $sheet->setCellValue('J' . $row, '合計人数');
        $sheet->setCellValue('L' . $row, count($participants));
        $sheet->setCellValue('N' . $row, '人');
        $row++;

        // 日程
        $startDate = new \DateTime($camp['start_date']);
        $endDate = new \DateTime($camp['end_date']);
        $sheet->setCellValue('A' . $row, '合宿日程');
        $sheet->setCellValue('C' . $row, $startDate->format('Y/n/j'));
        $sheet->setCellValue('F' . $row, '～');
        $sheet->setCellValue('G' . $row, $endDate->format('Y/n/j'));
        $sheet->setCellValue('J' . $row, $camp['nights'] . '泊' . ($camp['nights'] + 1) . '日');
        $sheet->setCellValue('L' . $row, '旅行先（都道府県名）');
        $sheet->setCellValue('P' . $row, '');  // 旅行先は手動入力
        $row++;

        // 貸切バス
        $hasBus = ($camp['bus_fee_oneway'] > 0 || $camp['bus_fee_round'] > 0);
        $sheet->setCellValue('A' . $row, '当社手配貸切バス');
        $sheet->setCellValue('C' . $row, $hasBus ? 'あり' : 'なし');
        if ($hasBus) {
            // 往路・復路の人数をカウント
            $outboundCount = 0;
            $returnCount = 0;
            foreach ($participants as $p) {
                if ($p['use_outbound_bus']) $outboundCount++;
                if ($p['use_return_bus']) $returnCount++;
            }
            $sheet->setCellValue('E' . $row, '往路乗車人数');
            $sheet->setCellValue('H' . $row, $outboundCount);
            $sheet->setCellValue('J' . $row, '人');
            $sheet->setCellValue('K' . $row, '復路乗車人数');
            $sheet->setCellValue('N' . $row, $returnCount);
            $sheet->setCellValue('P' . $row, '人');
        }
        $row++;

        // 保険
        $sheet->setCellValue('A' . $row, '国内旅行傷害保険');
        $sheet->setCellValue('C' . $row, 'あり');
        $sheet->setCellValue('E' . $row, '加入タイプ');
        $sheet->setCellValue('G' . $row, 'B1(600円)');  // デフォルト値
        $sheet->setCellValue('J' . $row, '加入者数');
        $sheet->setCellValue('L' . $row, count($participants));  // 全員加入想定
        $sheet->setCellValue('N' . $row, '人');
        $sheet->setCellValue('O' . $row, '保険料');
        $sheet->setCellValue('Q' . $row, 600 * count($participants));
        $sheet->setCellValue('S' . $row, '円');
        $row += 2;

        // ヘッダー行
        $headerRow = $row;
        $sheet->setCellValue('A' . $row, 'No.');
        $sheet->setCellValue('B' . $row, '氏名');
        $sheet->mergeCells('B' . $row . ':G' . $row);
        $sheet->setCellValue('H' . $row, '性別');
        $sheet->setCellValue('J' . $row, '年齢');
        $sheet->setCellValue('L' . $row, '学年');
        $sheet->setCellValue('N' . $row, 'バス往路');
        $sheet->setCellValue('P' . $row, 'バス復路');
        $sheet->setCellValue('R' . $row, '保険加入');

        // ヘッダースタイル
        $sheet->getStyle('A' . $row . ':T' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':T' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
        $sheet->getStyle('A' . $row . ':T' . $row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row += 2;

        // 参加者データ
        $dataStartRow = $row;
        $no = 1;
        foreach ($participants as $participant) {
            // 名前を正規化してマッチング
            $normalizedName = preg_replace('/[\s　]+/u', '　', trim($participant['name']));
            $member = $memberMap[$normalizedName] ?? null;

            // 年齢計算（会員データの生年月日から）
            // 生年月日の閲覧権限がない幹部には年齢（生年月日由来）を出力しない
            $age = '';
            if ($member && !empty($member['birthdate']) && Auth::canViewMemberField('birthdate')) {
                $birthdate = new \DateTime($member['birthdate']);
                $now = new \DateTime();
                $age = $now->diff($birthdate)->y;
            }

            // 性別
            $gender = '';
            if ($member && !empty($member['gender'])) {
                $gender = $member['gender'] === 'male' ? '男' : '女';
            } elseif (!empty($participant['gender'])) {
                $gender = $participant['gender'] === 'male' ? '男' : '女';
            }

            // 学年（参加者データまたは会員データから）
            $gradeLabel = '';
            if (!empty($participant['grade'])) {
                if ($participant['grade'] === 0) {
                    $gradeLabel = ($participant['gender'] ?? '') === 'female' ? 'OG' : 'OB';
                } else {
                    $gradeLabel = $participant['grade'] . '年';
                }
            } elseif ($member && !empty($member['grade'])) {
                $gradeLabel = $member['grade'] . '年';
            }

            // 行に書き込み
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $participant['name']);
            $sheet->mergeCells('B' . $row . ':G' . $row);
            $sheet->setCellValue('H' . $row, $gender);
            $sheet->setCellValue('J' . $row, $age);
            $sheet->setCellValue('L' . $row, $gradeLabel);
            $sheet->setCellValue('N' . $row, $participant['use_outbound_bus'] ? '○' : '');
            $sheet->setCellValue('P' . $row, $participant['use_return_bus'] ? '○' : '');
            $sheet->setCellValue('R' . $row, '○');  // 全員保険加入

            // セルを中央揃え（名前以外）
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H' . $row . ':T' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
            $no++;
        }

        // 罫線を追加
        $dataEndRow = $row - 1;
        $sheet->getStyle('A' . $headerRow . ':T' . $dataEndRow)
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // 列幅調整
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(6);
        $sheet->getColumnDimension('J')->setWidth(6);
        $sheet->getColumnDimension('L')->setWidth(6);
        $sheet->getColumnDimension('N')->setWidth(8);
        $sheet->getColumnDimension('P')->setWidth(8);
        $sheet->getColumnDimension('R')->setWidth(8);

        // ファイル出力
        $safeCampName = preg_replace('/[\/\\\\:*?"<>|]/', '', $camp['name']);
        $filename = '保険加入者名簿_' . $safeCampName . '_' . date('Ymd') . '.xlsx';

        // 出力バッファをクリア
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * 合宿参加者名簿Excel出力（コスモ形式）
     */
    public function generateParticipantRosterCosmo(int $campId): void
    {
        $campModel = new Camp();
        $camp = $campModel->find($campId);
        if (!$camp) {
            throw new \Exception('合宿が見つかりません');
        }

        $participantModel = new Participant();
        $participants = $participantModel->getByCampId($campId);

        // 会員名簿からデータを取得（名前でマッチング）
        $memberModel = new Member();
        $allMembers = $memberModel->all();

        // 名前をキーにしてメンバー情報をマッピング
        $memberMap = [];
        foreach ($allMembers as $member) {
            // 漢字名の空白を統一（全角・半角スペースを統一）
            $normalizedName = preg_replace('/[\s　]+/u', '　', trim($member['name_kanji']));
            $memberMap[$normalizedName] = $member;
        }

        // スプレッドシート作成
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('保険名簿用紙');

        // タイトル行
        $row = 1;
        $sheet->setCellValue('A' . $row, '旅行傷害保険加入者名簿兼参加者名簿');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells('A' . $row . ':E' . $row);

        // 旅行期間
        $startDate = new \DateTime($camp['start_date']);
        $endDate = new \DateTime($camp['end_date']);
        $sheet->setCellValue('F' . $row, '旅行期間：');
        $sheet->setCellValue('G' . $row, $startDate->format('Y年n月j日') . '～ ' . $endDate->format('n月j日'));

        // 基本情報行
        $row = 2;
        $sheet->setCellValue('A' . $row, '学校名');
        $sheet->setCellValue('C' . $row, '団体名');
        $sheet->setCellValue('G' . $row, '保険加入人数');
        $sheet->setCellValue('H' . $row, '保険プラン');

        $row = 3;
        $sheet->setCellValue('A' . $row, '早稲田大学');
        $sheet->setCellValue('C' . $row, 'Laissez-Faire T.C.');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $sheet->setCellValue('G' . $row, count($participants));
        $sheet->setCellValue('H' . $row, '500円プラン');

        // ヘッダー行
        $row = 5;
        $sheet->setCellValue('A' . $row, '');
        $sheet->setCellValue('B' . $row, '名前(ﾌﾘｶﾞﾅ)');
        $sheet->setCellValue('C' . $row, '保険' . "\n" . '加入');
        $sheet->getStyle('C' . $row)->getAlignment()->setWrapText(true);
        $sheet->setCellValue('D' . $row, '性別');
        $sheet->setCellValue('E' . $row, '生年月日');
        $sheet->setCellValue('F' . $row, '現住所');
        $sheet->setCellValue('G' . $row, '連絡先(携帯可)');
        $sheet->setCellValue('H' . $row, 'バス乗車' . "\n" . '（往路）');
        $sheet->getStyle('H' . $row)->getAlignment()->setWrapText(true);
        $sheet->setCellValue('I' . $row, 'バス乗車' . "\n" . '（復路）');
        $sheet->getStyle('I' . $row)->getAlignment()->setWrapText(true);

        // ヘッダースタイル
        $sheet->getStyle('A' . $row . ':I' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':I' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DAEEF3');
        $sheet->getStyle('A' . $row . ':I' . $row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // 記入例
        $row = 6;
        $sheet->setCellValue('A' . $row, '記入例');
        $sheet->setCellValue('B' . $row, '山田　太郎');
        $sheet->setCellValue('C' . $row, 'なし');
        $sheet->setCellValue('D' . $row, '男');
        $sheet->setCellValue('E' . $row, '2000/1/1');
        $sheet->setCellValue('F' . $row, '渋谷区渋谷2-14-18');
        $sheet->setCellValue('G' . $row, '03-5778-0960');
        $sheet->setCellValue('H' . $row, '○');
        $sheet->setCellValue('I' . $row, '×');
        $sheet->getStyle('A' . $row . ':I' . $row)->getFont()->setItalic(true)->getColor()->setRGB('808080');

        // 参加者データ
        $row = 7;
        $no = 1;
        $headerRow = 5;
        foreach ($participants as $participant) {
            // 名前を正規化してマッチング
            $normalizedName = preg_replace('/[\s　]+/u', '　', trim($participant['name']));
            $member = $memberMap[$normalizedName] ?? null;

            // 性別
            $gender = '';
            if ($member && !empty($member['gender'])) {
                $gender = $member['gender'] === 'male' ? '男' : '女';
            } elseif (!empty($participant['gender'])) {
                $gender = $participant['gender'] === 'male' ? '男' : '女';
            }

            // 生年月日（会員データから）
            $birthdate = '';
            if ($member && !empty($member['birthdate'])) {
                $bd = new \DateTime($member['birthdate']);
                $birthdate = $bd->format('Y/n/j');
            }

            // 住所（会員データから）
            $address = '';
            if ($member && !empty($member['address'])) {
                $address = $member['address'];
            }

            // 電話番号（会員データから）
            $phone = '';
            if ($member && !empty($member['phone'])) {
                $phone = $member['phone'];
            }

            // 行に書き込み
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $participant['name']);
            $sheet->setCellValue('C' . $row, 'あり');  // 全員保険加入
            $sheet->setCellValue('D' . $row, $gender);
            // 閲覧権限のない幹部には個人情報（生年月日・住所・電話）を出力しない
            $sheet->setCellValue('E' . $row, Auth::canViewMemberField('birthdate') ? $birthdate : '');
            $sheet->setCellValue('F' . $row, Auth::canViewMemberField('address')   ? $address   : '');
            $sheet->setCellValue('G' . $row, Auth::canViewMemberField('phone')     ? $phone     : '');
            $sheet->setCellValue('H' . $row, $participant['use_outbound_bus'] ? '○' : '×');
            $sheet->setCellValue('I' . $row, $participant['use_return_bus'] ? '○' : '×');

            // セルを中央揃え（住所以外）
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $row . ':E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H' . $row . ':I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
            $no++;
        }

        // 罫線を追加
        $dataEndRow = $row - 1;
        $sheet->getStyle('A' . $headerRow . ':I' . $dataEndRow)
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // 列幅調整
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(8);
        $sheet->getColumnDimension('D')->setWidth(6);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(40);
        $sheet->getColumnDimension('G')->setWidth(16);
        $sheet->getColumnDimension('H')->setWidth(10);
        $sheet->getColumnDimension('I')->setWidth(10);

        // ファイル出力
        $safeCampName = preg_replace('/[\/\\\\:*?"<>|]/', '', $camp['name']);
        $filename = '合宿参加者名簿_' . $safeCampName . '_' . date('Ymd') . '.xlsx';

        // 出力バッファをクリア
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * 人数報告表Excel出力（コスモ形式）
     */
    public function generateHeadcountReport(int $campId): void
    {
        $campModel = new Camp();
        $camp = $campModel->find($campId);
        if (!$camp) {
            throw new \Exception('合宿が見つかりません');
        }

        $participantModel = new Participant();
        $participants = $participantModel->getByCampId($campId);

        $timeSlotModel = new TimeSlot();
        $timeSlots = $timeSlotModel->getByCampId($campId);

        // 計算サービスから途中参加・途中抜け情報を取得
        $calculationService = new CalculationService();
        $partialSchedule = $calculationService->generatePartialParticipationSchedule($campId);

        // 日ごとのデータを集計
        $totalDays = $camp['nights'] + 1;
        $startDate = new \DateTime($camp['start_date']);

        // 各日の食事人数・宿泊人数を計算
        $dailyData = [];
        for ($day = 1; $day <= $totalDays; $day++) {
            $date = clone $startDate;
            $date->modify('+' . ($day - 1) . ' days');

            $dailyData[$day] = [
                'date' => $date,
                'breakfast' => 0,
                'lunch' => 0,
                'dinner' => 0,
                'lodging' => 0,
                'male' => 0,
                'female' => 0,
                'court_am' => 0,
                'court_pm' => 0,
            ];
        }

        // 参加者ごとにカウント
        foreach ($participants as $p) {
            $joinDay = $p['join_day'];
            $leaveDay = $p['leave_day'];
            $joinTiming = $p['join_timing'];
            $leaveTiming = $p['leave_timing'];
            $gender = $p['gender'];

            for ($day = 1; $day <= $totalDays; $day++) {
                // 参加期間外はスキップ
                if ($day < $joinDay || $day > $leaveDay) {
                    continue;
                }

                // 朝食: 2日目以降、参加中であれば（参加日の翌日から、離脱日まで）
                if ($day > 1 && $day >= $joinDay && $day <= $leaveDay) {
                    // 参加日が当日の場合は朝食なし
                    if ($day > $joinDay) {
                        $dailyData[$day]['breakfast']++;
                    } elseif ($day == $joinDay && in_array($joinTiming, ['outbound_bus', 'breakfast', 'morning'])) {
                        // 1日目往路バス以外で朝食から参加の場合
                        if ($joinTiming === 'breakfast' || $joinTiming === 'morning') {
                            $dailyData[$day]['breakfast']++;
                        }
                    }
                }

                // 昼食
                if ($day >= $joinDay && $day <= $leaveDay) {
                    // 参加日のタイミングチェック
                    if ($day == $joinDay) {
                        // 昼食以前に参加していれば昼食対象
                        if (in_array($joinTiming, ['outbound_bus', 'breakfast', 'morning', 'lunch'])) {
                            // 1日目は昼食なし（往路バス移動中）
                            if ($day > 1 || $joinTiming !== 'outbound_bus') {
                                $dailyData[$day]['lunch']++;
                            }
                        }
                    } elseif ($day == $leaveDay) {
                        // 離脱日は昼食後まで滞在していれば昼食対象
                        if (!in_array($leaveTiming, ['morning', 'after_breakfast'])) {
                            $dailyData[$day]['lunch']++;
                        }
                    } else {
                        // 途中日は全員昼食対象
                        $dailyData[$day]['lunch']++;
                    }
                }

                // 夕食: 最終日以外
                if ($day < $totalDays && $day >= $joinDay && $day <= $leaveDay) {
                    if ($day == $leaveDay) {
                        // 離脱日は夕食を食べるタイミングまでいれば対象
                        if (in_array($leaveTiming, ['dinner', 'night', 'return_bus'])) {
                            $dailyData[$day]['dinner']++;
                        }
                    } else {
                        $dailyData[$day]['dinner']++;
                    }
                }

                // 宿泊: 最終日以外
                if ($day < $totalDays && $day >= $joinDay && $day <= $leaveDay) {
                    if ($day == $leaveDay) {
                        // 離脱日は宿泊対象外
                    } else {
                        $dailyData[$day]['lodging']++;
                        // 性別カウント
                        if ($gender === 'male') {
                            $dailyData[$day]['male']++;
                        } elseif ($gender === 'female') {
                            $dailyData[$day]['female']++;
                        }
                    }
                }
            }
        }

        // タイムスロットからコート面数を取得
        foreach ($timeSlots as $slot) {
            $day = $slot['day_number'];
            if ($slot['slot_type'] === 'morning' && isset($dailyData[$day])) {
                $dailyData[$day]['court_am'] = $slot['court_count'] ?? 0;
            }
            if ($slot['slot_type'] === 'afternoon' && isset($dailyData[$day])) {
                $dailyData[$day]['court_pm'] = $slot['court_count'] ?? 0;
            }
        }

        // 途中参加・途中抜けの連絡事項を生成
        $partialNotes = [];
        $joinTimingLabels = [
            'outbound_bus' => '往路バス', 'breakfast' => '朝食', 'morning' => '午前',
            'lunch' => '昼食', 'afternoon' => '午後', 'dinner' => '夕食', 'night' => '夜'
        ];
        $leaveTimingLabels = [
            'return_bus' => '復路バス', 'before_breakfast' => '朝食前', 'after_breakfast' => '朝食後',
            'morning' => '午前', 'lunch' => '昼食', 'after_lunch' => '昼食後',
            'afternoon' => '午後', 'dinner' => '夕食', 'night' => '夜'
        ];

        foreach ($participants as $p) {
            $joinDay = $p['join_day'];
            $leaveDay = $p['leave_day'];
            $joinTiming = $p['join_timing'];
            $leaveTiming = $p['leave_timing'];

            // フル参加かどうか判定
            $isFullJoin = ($joinDay == 1 && $joinTiming === 'outbound_bus');
            $isFullLeave = ($leaveDay == $totalDays && $leaveTiming === 'return_bus');

            if (!$isFullJoin || !$isFullLeave) {
                $joinLabel = $joinTimingLabels[$joinTiming] ?? $joinTiming;
                $leaveLabel = $leaveTimingLabels[$leaveTiming] ?? $leaveTiming;
                $partialNotes[] = $p['name'] . ': ' . $joinDay . '日目' . $joinLabel . '～' . $leaveDay . '日目' . $leaveLabel;
            }
        }

        // スプレッドシート作成
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('人数報告書');

        // ヘッダー部分
        $row = 2;
        $sheet->setCellValue('B' . $row, '宿泊施設名');
        $sheet->setCellValue('D' . $row, '');  // 手動入力
        $row += 2;

        $sheet->setCellValue('B' . $row, '※下記内容にて　新規・変更　人数報告致します。ご確認お願い致します。');
        $sheet->mergeCells('B' . $row . ':J' . $row);
        $row += 2;

        // 日付ヘッダー
        $dateRow = $row;
        $col = 5; // E列から
        for ($day = 1; $day <= $totalDays; $day++) {
            $date = $dailyData[$day]['date'];
            $sheet->setCellValueByColumnAndRow($col, $row, $date->format('n/j'));
            $sheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyleByColumnAndRow($col, $row)->getFont()->setBold(true);
            $col += 3; // 3列ずつ
        }
        $row += 2;

        // 食事・宿泊セクション
        $dataItems = [
            ['label' => '朝食', 'key' => 'breakfast'],
            ['label' => '昼食', 'key' => 'lunch'],
            ['label' => '夕食', 'key' => 'dinner'],
            ['label' => '宿泊', 'key' => 'lodging'],
        ];

        foreach ($dataItems as $item) {
            $sheet->setCellValue('B' . $row, $item['label']);
            $sheet->getStyle('B' . $row)->getFont()->setBold(true);

            $col = 5;
            for ($day = 1; $day <= $totalDays; $day++) {
                $value = $dailyData[$day][$item['key']];
                // 1日目朝食、最終日夕食・宿泊は×
                if ($item['key'] === 'breakfast' && $day === 1) {
                    $sheet->setCellValueByColumnAndRow($col, $row, '×');
                } elseif ($item['key'] === 'dinner' && $day === $totalDays) {
                    $sheet->setCellValueByColumnAndRow($col, $row, '×');
                } elseif ($item['key'] === 'lodging' && $day === $totalDays) {
                    $sheet->setCellValueByColumnAndRow($col, $row, '×');
                } else {
                    $sheet->setCellValueByColumnAndRow($col, $row, $value);
                }
                $sheet->setCellValueByColumnAndRow($col + 1, $row, '名');
                $sheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $col += 3;
            }
            $row += 2;
        }

        // 男女別
        $sheet->setCellValue('B' . $row, '男子');
        $col = 5;
        for ($day = 1; $day <= $totalDays; $day++) {
            if ($day < $totalDays) {
                $sheet->setCellValueByColumnAndRow($col, $row, $dailyData[$day]['male']);
            } else {
                $sheet->setCellValueByColumnAndRow($col, $row, '×');
            }
            $sheet->setCellValueByColumnAndRow($col + 1, $row, '名');
            $sheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col += 3;
        }
        $row++;

        $sheet->setCellValue('B' . $row, '女子');
        $col = 5;
        for ($day = 1; $day <= $totalDays; $day++) {
            if ($day < $totalDays) {
                $sheet->setCellValueByColumnAndRow($col, $row, $dailyData[$day]['female']);
            } else {
                $sheet->setCellValueByColumnAndRow($col, $row, '×');
            }
            $sheet->setCellValueByColumnAndRow($col + 1, $row, '名');
            $sheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col += 3;
        }
        $row += 4;

        // コート利用
        $sheet->setCellValue('B' . $row, '利用施設');
        $sheet->setCellValue('D' . $row, 'テニスコート');
        $row += 2;

        // コート日付ヘッダー
        $col = 5;
        for ($day = 1; $day <= $totalDays; $day++) {
            $date = $dailyData[$day]['date'];
            $sheet->setCellValueByColumnAndRow($col, $row, $date->format('n/j'));
            $sheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyleByColumnAndRow($col, $row)->getFont()->setBold(true);
            $col += 3;
        }
        $row += 2;

        // AM
        $sheet->setCellValue('B' . $row, 'AM');
        $col = 5;
        for ($day = 1; $day <= $totalDays; $day++) {
            $courtAm = $dailyData[$day]['court_am'];
            if ($day === 1) {
                $sheet->setCellValueByColumnAndRow($col, $row, '×');  // 1日目AMはなし
            } else {
                $sheet->setCellValueByColumnAndRow($col, $row, $courtAm > 0 ? $courtAm . '面' : '×');
            }
            $sheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col += 3;
        }
        $row += 2;

        // PM
        $sheet->setCellValue('B' . $row, 'PM');
        $col = 5;
        for ($day = 1; $day <= $totalDays; $day++) {
            $courtPm = $dailyData[$day]['court_pm'];
            if ($day === $totalDays) {
                $sheet->setCellValueByColumnAndRow($col, $row, '×');  // 最終日PMはなし
            } else {
                $sheet->setCellValueByColumnAndRow($col, $row, $courtPm > 0 ? $courtPm . '面' : '×');
            }
            $sheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col += 3;
        }
        $row += 4;

        // 連絡事項
        $sheet->setCellValue('B' . $row, '連絡事項');
        $sheet->getStyle('B' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('B' . $row, 'いつもお世話になっております。');
        $row++;

        if (!empty($partialNotes)) {
            $sheet->setCellValue('B' . $row, '【途中参加・途中抜け】');
            $row++;
            foreach ($partialNotes as $note) {
                $sheet->setCellValue('B' . $row, $note);
                $row++;
            }
        }

        $sheet->setCellValue('B' . $row, '宜しくお願い致します。');

        // 列幅調整
        $sheet->getColumnDimension('A')->setWidth(3);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(3);
        $sheet->getColumnDimension('D')->setWidth(12);
        for ($i = 5; $i <= 5 + ($totalDays * 3); $i++) {
            $sheet->getColumnDimensionByColumn($i)->setWidth(6);
        }

        // ファイル出力
        $safeCampName = preg_replace('/[\/\\\\:*?"<>|]/', '', $camp['name']);
        $filename = '人数報告表_' . $safeCampName . '_' . date('Ymd') . '.xlsx';

        // 出力バッファをクリア
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * マイコム形式の人数報告書を生成
     */
    public function generateHeadcountReportMycom(int $campId): void
    {
        $campModel = new Camp();
        $camp = $campModel->find($campId);
        if (!$camp) {
            throw new \Exception('合宿が見つかりません');
        }

        $participantModel = new Participant();
        $participants = $participantModel->getByCampId($campId);

        $timeSlotModel = new TimeSlot();
        $timeSlots = $timeSlotModel->getByCampId($campId);

        // 会員情報を取得
        $memberModel = new Member();
        $allMembers = $memberModel->all();
        $memberMap = [];
        foreach ($allMembers as $member) {
            $normalizedName = preg_replace('/[\s　]+/u', '　', trim($member['name_kanji']));
            $memberMap[$normalizedName] = $member;
        }

        // 日程計算
        $startDate = new \DateTime($camp['start_date']);
        $totalDays = $camp['nights'] + 1;

        // 日ごとの人数を集計
        $dailyData = [];
        for ($day = 1; $day <= $totalDays; $day++) {
            $dailyData[$day] = [
                'date' => (clone $startDate)->modify('+' . ($day - 1) . ' days'),
                'breakfast' => 0,
                'lunch' => 0,
                'dinner' => 0,
                'lodging' => 0,
                'grade_gender' => [
                    '1男' => 0, '1女' => 0,
                    '2男' => 0, '2女' => 0,
                    '3男' => 0, '3女' => 0,
                    '4男' => 0, '4女' => 0,
                    'OB' => 0, 'OG' => 0,
                    '先生' => 0,
                ],
                'court_am' => 0,
                'court_pm' => 0,
                'court_kikaku' => 0,
                'court_night' => 0,
            ];
        }

        // タイムスロットからコート数を取得
        foreach ($timeSlots as $slot) {
            $dayNum = (int)$slot['day_number'];
            if (!isset($dailyData[$dayNum])) continue;

            $courtCount = (int)($slot['court_count'] ?? 0);
            if ($slot['activity_type'] === 'tennis' || $slot['activity_type'] === 'gym') {
                if ($slot['slot_type'] === 'morning') {
                    $dailyData[$dayNum]['court_am'] = $courtCount;
                } elseif ($slot['slot_type'] === 'afternoon') {
                    $dailyData[$dayNum]['court_pm'] = $courtCount;
                }
            }
        }

        // 参加者ごとに日ごとの参加状況を集計
        foreach ($participants as $p) {
            // 会員情報を取得
            $normalizedName = preg_replace('/[\s　]+/u', '　', trim($p['name']));
            $member = $memberMap[$normalizedName] ?? null;

            // 学年・性別を決定
            $grade = (int)($p['grade'] ?? 0);
            $gender = $p['gender'] ?? ($member['gender'] ?? 'male');

            // 学年・性別キーを作成
            $gradeGenderKey = '';
            if ($grade >= 1 && $grade <= 4) {
                $gradeGenderKey = $grade . ($gender === 'female' ? '女' : '男');
            } elseif ($grade === 0) {
                $gradeGenderKey = $gender === 'female' ? 'OG' : 'OB';
            } else {
                $gradeGenderKey = '先生';
            }

            // 参加者から直接取得
            $joinDay = (int)$p['join_day'];
            $leaveDay = (int)$p['leave_day'];
            $joinTiming = $p['join_timing'];
            $leaveTiming = $p['leave_timing'];

            for ($day = 1; $day <= $totalDays; $day++) {
                // 参加期間外はスキップ
                if ($day < $joinDay || $day > $leaveDay) {
                    continue;
                }

                // 朝食: 2日目以降
                if ($day > 1) {
                    if ($day > $joinDay) {
                        $dailyData[$day]['breakfast']++;
                    } elseif ($day == $joinDay && in_array($joinTiming, ['outbound_bus', 'breakfast', 'morning'])) {
                        if ($joinTiming === 'breakfast' || $joinTiming === 'morning') {
                            $dailyData[$day]['breakfast']++;
                        }
                    }
                }

                // 昼食
                if ($day == $joinDay) {
                    if (in_array($joinTiming, ['outbound_bus', 'breakfast', 'morning', 'lunch'])) {
                        if ($day > 1 || $joinTiming !== 'outbound_bus') {
                            $dailyData[$day]['lunch']++;
                        }
                    }
                } elseif ($day == $leaveDay) {
                    if (!in_array($leaveTiming, ['morning', 'after_breakfast'])) {
                        $dailyData[$day]['lunch']++;
                    }
                } else {
                    $dailyData[$day]['lunch']++;
                }

                // 夕食: 最終日以外
                if ($day < $totalDays) {
                    if ($day == $leaveDay) {
                        if (in_array($leaveTiming, ['dinner', 'night', 'return_bus'])) {
                            $dailyData[$day]['dinner']++;
                        }
                    } else {
                        $dailyData[$day]['dinner']++;
                    }
                }

                // 宿泊: 最終日以外、離脱日以外
                if ($day < $totalDays && $day != $leaveDay) {
                    $dailyData[$day]['lodging']++;
                    if (isset($dailyData[$day]['grade_gender'][$gradeGenderKey])) {
                        $dailyData[$day]['grade_gender'][$gradeGenderKey]++;
                    }
                }
            }
        }

        // Excel作成
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('人数報告書');

        // ヘッダー情報
        $row = 1;
        $sheet->setCellValue('A' . $row, '案件CD');
        $sheet->setCellValue('B' . $row, '00061121');
        $sheet->setCellValue('D' . $row, '提出日');
        $sheet->setCellValue('E' . $row, date('Y/n/j'));
        $sheet->setCellValue('G' . $row, '記入者名');
        $row += 2;

        $sheet->setCellValue('A' . $row, '団体名');
        $sheet->setCellValue('B' . $row, '早稲田大学レッセフェールテニスクラブ');
        $row++;

        $sheet->setCellValue('A' . $row, '合宿日程');
        $endDate = (clone $startDate)->modify('+' . ($totalDays - 1) . ' days');
        $sheet->setCellValue('B' . $row, $startDate->format('Y/n/j') . '～' . $endDate->format('n/j'));
        $row++;

        $sheet->setCellValue('A' . $row, '契約人数');
        $sheet->setCellValue('B' . $row, count($participants) . '名');
        $row++;

        $sheet->setCellValue('A' . $row, '宿泊施設');
        $row += 3;

        // 食事数ヘッダー
        $sheet->setCellValue('A' . $row, '食事数');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        // 日付ヘッダー
        $col = 3;
        for ($day = 1; $day <= $totalDays; $day++) {
            $date = $dailyData[$day]['date'];
            $sheet->setCellValueByColumnAndRow($col, $row, $date->format('n/j'));
            $sheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyleByColumnAndRow($col, $row)->getFont()->setBold(true);
            $col++;
        }
        $row++;

        // 朝/昼/夕
        $mealItems = [
            ['label' => '朝', 'key' => 'breakfast'],
            ['label' => '昼', 'key' => 'lunch'],
            ['label' => '夕', 'key' => 'dinner'],
        ];

        foreach ($mealItems as $meal) {
            $sheet->setCellValue('B' . $row, $meal['label']);
            $col = 3;
            for ($day = 1; $day <= $totalDays; $day++) {
                $value = $dailyData[$day][$meal['key']];
                if ($meal['key'] === 'breakfast' && $day === 1) {
                    $sheet->setCellValueByColumnAndRow($col, $row, '-');
                } elseif ($meal['key'] === 'dinner' && $day === $totalDays) {
                    $sheet->setCellValueByColumnAndRow($col, $row, '-');
                } else {
                    $sheet->setCellValueByColumnAndRow($col, $row, $value);
                }
                $sheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $col++;
            }
            $row++;
        }
        $row++;

        // 宿泊人数
        $sheet->setCellValue('A' . $row, '宿泊人数');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        // 日付ヘッダー再度
        $col = 3;
        for ($day = 1; $day <= $totalDays; $day++) {
            $date = $dailyData[$day]['date'];
            $sheet->setCellValueByColumnAndRow($col, $row, $date->format('n/j'));
            $sheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyleByColumnAndRow($col, $row)->getFont()->setBold(true);
            $col++;
        }
        $row++;

        // 学年・性別別
        $gradeGenderLabels = ['1男', '1女', '2男', '2女', '3男', '3女', '4男', '4女', 'OB', 'OG', '先生'];
        foreach ($gradeGenderLabels as $label) {
            $sheet->setCellValue('B' . $row, $label);
            $col = 3;
            for ($day = 1; $day <= $totalDays; $day++) {
                if ($day === $totalDays) {
                    $sheet->setCellValueByColumnAndRow($col, $row, '-');
                } else {
                    $value = $dailyData[$day]['grade_gender'][$label];
                    $sheet->setCellValueByColumnAndRow($col, $row, $value > 0 ? $value : '');
                }
                $sheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $col++;
            }
            $row++;
        }

        // 合計行
        $sheet->setCellValue('B' . $row, '合計');
        $sheet->getStyle('B' . $row)->getFont()->setBold(true);
        $col = 3;
        for ($day = 1; $day <= $totalDays; $day++) {
            if ($day === $totalDays) {
                $sheet->setCellValueByColumnAndRow($col, $row, '-');
            } else {
                $sheet->setCellValueByColumnAndRow($col, $row, $dailyData[$day]['lodging']);
            }
            $sheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyleByColumnAndRow($col, $row)->getFont()->setBold(true);
            $col++;
        }
        $row += 2;

        // 利用施設
        $sheet->setCellValue('A' . $row, '利用施設');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        // 日付ヘッダー
        $col = 3;
        for ($day = 1; $day <= $totalDays; $day++) {
            $date = $dailyData[$day]['date'];
            $sheet->setCellValueByColumnAndRow($col, $row, $date->format('n/j'));
            $sheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyleByColumnAndRow($col, $row)->getFont()->setBold(true);
            $col++;
        }
        $row++;

        // AM/PM/企画/夜間
        $facilityItems = [
            ['label' => 'AM', 'key' => 'court_am'],
            ['label' => 'PM', 'key' => 'court_pm'],
            ['label' => '企画', 'key' => 'court_kikaku'],
            ['label' => '夜間', 'key' => 'court_night'],
        ];

        foreach ($facilityItems as $item) {
            $sheet->setCellValue('B' . $row, $item['label']);
            $col = 3;
            for ($day = 1; $day <= $totalDays; $day++) {
                $value = $dailyData[$day][$item['key']];
                // 1日目AMはなし、最終日PMはなし
                if ($item['key'] === 'court_am' && $day === 1) {
                    $sheet->setCellValueByColumnAndRow($col, $row, '-');
                } elseif ($item['key'] === 'court_pm' && $day === $totalDays) {
                    $sheet->setCellValueByColumnAndRow($col, $row, '-');
                } else {
                    $sheet->setCellValueByColumnAndRow($col, $row, $value > 0 ? $value : '');
                }
                $sheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $col++;
            }
            $row++;
        }
        $row++;

        // 備考
        $sheet->setCellValue('A' . $row, '備考');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        // 列幅調整
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(8);
        for ($i = 3; $i <= 3 + $totalDays; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setWidth(8);
        }

        // ファイル出力
        $safeCampName = preg_replace('/[\/\\\\:*?"<>|]/', '', $camp['name']);
        $filename = '人数報告書_' . $safeCampName . '_' . date('Ymd') . '.xlsx';

        // 出力バッファをクリア
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * アレルギーリストPDF出力
     */
    public function generateAllergyListPdf(int $campId): void
    {
        $campModel = new Camp();
        $camp = $campModel->find($campId);
        if (!$camp) {
            throw new \Exception('合宿が見つかりません');
        }

        $participantModel = new Participant();
        $participants = $participantModel->getByCampId($campId);

        // アレルギーあり参加者のみ抽出
        $allergyParticipants = array_filter($participants, function($p) {
            return !empty($p['allergy']) && trim($p['allergy']) !== '';
        });

        $campName = htmlspecialchars($camp['name']);
        $printDate = date('Y年m月d日');
        $count = count($allergyParticipants);

        $html = '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>' . $campName . ' - アレルギーリスト</title>
    <style>
        body { font-family: "Hiragino Sans", "Yu Gothic", "Meiryo", sans-serif; font-size: 12px; margin: 20px; }
        h1 { font-size: 18px; text-align: center; margin-bottom: 5px; }
        .meta { text-align: center; color: #555; font-size: 11px; margin-bottom: 20px; }
        .summary { background: #fff8e1; border: 1px solid #f0c060; padding: 10px 15px; margin-bottom: 20px; border-radius: 4px; }
        .summary p { margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #aaa; padding: 8px 10px; text-align: left; font-size: 11px; vertical-align: top; }
        th { background: #ffe082; font-weight: bold; }
        tr:nth-child(even) { background: #fffde7; }
        .no-allergy { text-align: center; color: #888; padding: 30px; }
        .no-print { margin-bottom: 15px; }
        @media print {
            .no-print { display: none; }
            body { margin: 10px; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">印刷</button>
        <button onclick="window.close()">閉じる</button>
    </div>

    <h1>' . $campName . ' アレルギーリスト</h1>
    <p class="meta">出力日: ' . $printDate . '</p>
';

        if ($count === 0) {
            $html .= '<p class="no-allergy">アレルギーのある参加者はいません。</p>';
        } else {
            $html .= '
    <div class="summary">
        <p>アレルギーのある参加者: <strong>' . $count . '名</strong></p>
    </div>
    <table>
        <thead>
            <tr>
                <th style="width:30px;">No.</th>
                <th style="width:120px;">名前</th>
                <th style="width:60px;">学年</th>
                <th>アレルギー内容</th>
            </tr>
        </thead>
        <tbody>';

            $i = 1;
            foreach ($allergyParticipants as $p) {
                $gradeLabel = $this->getGradeLabel(
                    $p['grade'] !== null ? (int)$p['grade'] : null,
                    $p['gender']
                );
                $html .= '
            <tr>
                <td>' . $i++ . '</td>
                <td>' . htmlspecialchars($p['name']) . '</td>
                <td>' . htmlspecialchars($gradeLabel) . '</td>
                <td>' . htmlspecialchars($p['allergy']) . '</td>
            </tr>';
            }

            $html .= '
        </tbody>
    </table>';
        }

        $html .= '
</body>
</html>';

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="allergy_list_' . $campId . '.html"');
        echo $html;
        exit;
    }

    /**
     * 合宿・遠征届 参加者名簿 Excel出力
     * 早稲田大学学生補償制度対応フォーマット
     */
    public function generateActivityMeibo(int $campId): void
    {
        $campModel = new Camp();
        $camp = $campModel->find($campId);
        if (!$camp) {
            throw new \Exception('合宿が見つかりません');
        }

        // 参加者データを会員情報と合わせて取得（申込経由の参加者は会員情報をJOIN）
        $db = Database::getInstance();
        $participants = $db->fetchAll(
            "SELECT p.*,
                    COALESCE(ca.edited_name_kanji, m.name_kanji, p.name) AS display_name,
                    COALESCE(ca.edited_faculty, m.faculty)               AS display_faculty,
                    COALESCE(ca.edited_department, m.department)         AS display_department,
                    m.student_id
             FROM participants p
             LEFT JOIN camp_applications ca ON ca.participant_id = p.id
                                           AND ca.camp_id = ?
                                           AND ca.status != 'cancelled'
             LEFT JOIN members m ON ca.member_id = m.id
             WHERE p.camp_id = ?
             ORDER BY p.grade, p.name",
            [$campId, $campId]
        );

        $ROWS_PER_SHEET = 25;
        $totalParticipants = count($participants);
        $sheetCount = max(1, (int)ceil($totalParticipants / $ROWS_PER_SHEET));

        $spreadsheet = new Spreadsheet();

        for ($sheetIndex = 0; $sheetIndex < $sheetCount; $sheetIndex++) {
            if ($sheetIndex === 0) {
                $sheet = $spreadsheet->getActiveSheet();
            } else {
                $sheet = $spreadsheet->createSheet($sheetIndex);
            }
            $pageNum = $sheetIndex + 1;
            $sheet->setTitle('名簿' . $pageNum);

            $sheetParticipants = array_slice($participants, $sheetIndex * $ROWS_PER_SHEET, $ROWS_PER_SHEET);
            $this->buildMeiboSheet($sheet, $camp, $sheetParticipants, $sheetIndex);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $campName = preg_replace('/[\\\\\/:\*\?"<>\|]/', '_', $camp['name']);
        $filename  = '参加者名簿_' . $campName . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * 名簿シート1枚分を構築（25行/シート）
     */
    private function buildMeiboSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $camp,
        array $participants,
        int   $sheetIndex
    ): void {
        // --- 列幅 ---
        $sheet->getColumnDimension('A')->setWidth(5);    // 番号
        $sheet->getColumnDimension('B')->setWidth(22);   // 学部
        $sheet->getColumnDimension('C')->setWidth(9);    // 学年
        $sheet->getColumnDimension('D')->setWidth(14);   // 学籍番号
        $sheet->getColumnDimension('E')->setWidth(22);   // 氏名
        $sheet->getColumnDimension('F')->setWidth(22);   // 備考
        $sheet->getColumnDimension('G')->setWidth(18);   // 所属サークル登録済

        // --- タイトル ---
        $row = 1;
        $sheet->setCellValue('A' . $row, '【参加者名簿】');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A' . $row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(22);

        // --- サブタイトル ---
        $row = 2;
        $sheet->setCellValue('A' . $row,
            '※所属サークルの登録が完了していることを確認の上、チェックを入れてください。');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setSize(9);
        $sheet->getRowDimension($row)->setRowHeight(14);

        // --- 合宿名・期間 ---
        $row = 3;
        $startDate = new \DateTime($camp['start_date']);
        $endDate   = new \DateTime($camp['end_date']);
        $sheet->setCellValue('A' . $row, $camp['name']);
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        $dateStr = $startDate->format('Y年n月j日') . '〜' . $endDate->format('n月j日');
        $sheet->setCellValue('E' . $row, $dateStr);
        $sheet->mergeCells('E' . $row . ':G' . $row);
        $sheet->getStyle('E' . $row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension($row)->setRowHeight(16);

        // --- ヘッダー行 ---
        $row = 5;
        $headerRow = $row;
        $sheet->setCellValue('A' . $row, '');
        $sheet->setCellValue('B' . $row, '学　　部');
        $sheet->setCellValue('C' . $row, '学 年');
        $sheet->setCellValue('D' . $row, '学 籍 番 号');
        $sheet->setCellValue('E' . $row, '氏　　　名');
        $sheet->setCellValue('F' . $row, '備　　　考');
        $sheet->setCellValue('G' . $row, '所属サークル登録済');

        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
            'font'      => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9D9D9'],
            ],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(22);

        // --- データ行（25行固定） ---
        $dataStartRow = $headerRow + 1;
        for ($i = 0; $i < 25; $i++) {
            $row = $dataStartRow + $i;
            $globalNum = ($sheetIndex * 25) + $i + 1;  // 全体通し番号

            // 5行ごとに番号を右端に表示
            if ($globalNum % 5 === 0) {
                $sheet->setCellValue('A' . $row, $globalNum);
                $sheet->getStyle('A' . $row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A' . $row)->getFont()->setSize(8);
            }

            $participant = $participants[$i] ?? null;
            if ($participant) {
                $faculty   = $participant['display_faculty'] ?? '';
                $studentId = $participant['student_id'] ?? '';

                // 学籍番号から学年を推測（StudentIdParserService を利用）
                $grade = '';
                if (!empty($studentId)) {
                    $parser = new StudentIdParserService();
                    $parsed = $parser->parse($studentId);
                    if ($parsed['is_valid'] && $parsed['enrollment_year'] !== null) {
                        $gradeNum = $parser->calculateGrade($parsed['enrollment_year']);
                        $grade = $gradeNum . '年';
                    }
                }
                // 学籍番号から推測できない場合は保存済み学年にフォールバック
                if ($grade === '') {
                    $gradeNum = $participant['grade'];
                    $grade    = ($gradeNum === null) ? '' : (($gradeNum === 0) ? 'OB/OG' : $gradeNum . '年');
                }
                $name     = $participant['display_name'] ?? $participant['name'];

                $sheet->setCellValue('B' . $row, $faculty);
                $sheet->setCellValue('C' . $row, $grade);
                $sheet->setCellValue('D' . $row, $studentId);
                $sheet->setCellValue('E' . $row, $name);
                // F列（備考）・G列（チェックボックス）は空白のまま手書き

                // 学年・番号は中央揃え
                $sheet->getStyle('C' . $row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D' . $row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $sheet->getRowDimension($row)->setRowHeight(16);
        }

        // --- 表全体に枠線 ---
        $tableEnd = $dataStartRow + 24;
        $sheet->getStyle('A' . $headerRow . ':G' . $tableEnd)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // ヘッダー下を太線
        $sheet->getStyle('A' . $headerRow . ':G' . $headerRow)->getBorders()
            ->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);

        // --- 注記 ---
        $noteRow = $tableEnd + 2;
        $note1 = '【重要】早稲田大学学生補償制度の適用にあたって、「合宿・遠征届」の参加者名簿に記入した氏名は、三役による「サークル会員名簿」の提出と、各サークル員による所属サークルの登録が完了している必要があります。それを満たさない学生が活動に参加する場合はサークル会員名簿を所定の期間内に追加で提出してください。';
        $sheet->setCellValue('A' . $noteRow, $note1);
        $sheet->mergeCells('A' . $noteRow . ':G' . $noteRow);
        $sheet->getStyle('A' . $noteRow)->getAlignment()
            ->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('A' . $noteRow)->getFont()->setSize(8);
        $sheet->getRowDimension($noteRow)->setRowHeight(40);

        $noteRow++;
        $note2 = 'ただし、新入生をはじめ、サークルへの入会が未定の者が参加する活動の場合は、氏名の前に（新）と付けることでサークル員と区別できるようにしておいてください（その場合、「所属サークル登録済」欄に☑は不要です）。';
        $sheet->setCellValue('A' . $noteRow, $note2);
        $sheet->mergeCells('A' . $noteRow . ':G' . $noteRow);
        $sheet->getStyle('A' . $noteRow)->getAlignment()
            ->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('A' . $noteRow)->getFont()->setSize(8);
        $sheet->getRowDimension($noteRow)->setRowHeight(30);

        // --- ページ設定（印刷用）---
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()
            ->setTop(0.75)->setBottom(0.75)
            ->setLeft(0.7)->setRight(0.7);
    }
}
