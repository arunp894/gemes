<?php

namespace App\Http\Controllers;

use App\Services\SaleImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class SaleImportController extends Controller
{
    public function __construct(private SaleImportService $importService) {}

    /* ─── Upload form ──────────────────────────────────────────────── */

    public function showUploadForm(): View
    {
        return view('sales.import');
    }

    /* ─── Preview (dry-run, no DB writes) ─────────────────────────── */

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            $path   = $request->file('file')->store('sale-imports/preview', 'local');
            $result = $this->importService->parse(Storage::disk('local')->path($path));
            session(['sale_import_preview_path' => $path]);

            return response()->json(['ok' => true, 'result' => $result]);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'message' => 'Could not read file: ' . $e->getMessage()], 422);
        }
    }

    /* ─── Confirm import ───────────────────────────────────────────── */

    public function confirm(Request $request): JsonResponse
    {
        $previewPath = session('sale_import_preview_path');

        if (! $previewPath || ! Storage::disk('local')->exists($previewPath)) {
            return response()->json([
                'ok'      => false,
                'message' => 'Session expired or no file found. Please re-upload.',
            ], 422);
        }

        try {
            $fullPath = Storage::disk('local')->path($previewPath);
            $result   = $this->importService->import($fullPath);

            Storage::disk('local')->delete($previewPath);
            session()->forget('sale_import_preview_path');

            return response()->json(['ok' => true, 'result' => $result]);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'message' => 'Import failed: ' . $e->getMessage()], 422);
        }
    }

    /* ─── Template download (generated on the fly) ─────────────────── */

    public function downloadTemplate(): Response
    {
        $spreadsheet = $this->buildTemplateSpreadsheet();

        // Write to a temp stream
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return response($content, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="sale_import_template.xlsx"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /* ─── Template builder ─────────────────────────────────────────── */

    private function buildTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        $this->buildImportSheet($spreadsheet->getActiveSheet());
        $spreadsheet->getActiveSheet()->setTitle('Import Template');

        $instructionSheet = $spreadsheet->createSheet();
        $instructionSheet->setTitle('Instructions');
        $this->buildInstructionSheet($instructionSheet);

        $spreadsheet->setActiveSheetIndex(0);
        return $spreadsheet;
    }

    private function buildImportSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws): void
    {
        // ── Colour constants ──
        $navyHex  = '1A3C5E';
        $blueHex  = '2C6FAC';
        $grayHex  = '6C757D';
        $reqBg    = 'EBF3FB';
        $optBg    = 'F5F5F5';
        $noteBg   = 'FFF9E6';

        // ── Column definitions: [header, width, required, example, note] ──
        $cols = [
            ['sale_date',         14, true,  '2024-06-15',          'Date of sale (YYYY-MM-DD)'],
            ['customer_name',     22, true,  'John Smith',           'Full name of customer'],
            ['customer_email',    26, false, 'john@example.com',     'Email – used to match existing customer'],
            ['channel_code',      16, true,  'ebay',                 'ebay / pos / website / catawiki'],
            ['location_code',     16, true,  'LOC-0001',             'Your Paces location code'],
            ['external_ref',      18, false, '15487',               'eBay Sales Record # or platform ref'],
            ['external_order_id', 18, false, '28-12345-67890',       'Platform order ID (if any)'],
            ['shipping_charge',   18, false, '6.99',                 'Total shipping for order (INR)'],
            ['note',              28, false, 'Shipped via DHL',      'Order-level notes'],
            ['payment_method',    18, false, 'bank_transfer',        'cash / card / upi / bank_transfer / cheque'],
            ['sku',               18, true,  '18924-WES',            'Product SKU (Custom Label on eBay)'],
            ['qty',                8, true,  '1',                    'Quantity sold'],
            ['unit_price_inr',    18, true,  '287.68',               'Selling price per unit in INR'],
            ['tax_percent',       14, false, '0',                    'Tax % on this line (default 0)'],
            ['discount_percent',  18, false, '0',                    'Discount % on this line (default 0)'],
            ['line_note',         28, false, 'IGI certified',        'Notes for this product line'],
        ];

        $totalCols = count($cols);
        $lastCol   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);

        // ── Row 1: title ──
        $ws->mergeCells("A1:{$lastCol}1");
        $ws->setCellValue('A1', 'Paces – Sale Import Template');
        $ws->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Arial'],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $navyHex]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getRowDimension(1)->setRowHeight(28);

        // ── Row 2: required / optional legend ──
        $reqEnd = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(10); // cols A–J = order-level
        $optEnd = $lastCol;
        $ws->mergeCells("A2:{$reqEnd}2");
        $ws->setCellValue('A2', '  Required columns');
        $ws->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => $navyHex], 'name' => 'Arial'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DDEEFF']],
        ]);
        $nextCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(11);
        $ws->mergeCells("{$nextCol}2:{$optEnd}2");
        $ws->setCellValue("{$nextCol}2", '  Optional columns');
        $ws->getStyle("{$nextCol}2")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '555555'], 'name' => 'Arial'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $optBg]],
        ]);
        $ws->getRowDimension(2)->setRowHeight(18);

        // ── Row 3: headers ──
        foreach ($cols as $i => [$hdr, $width, $req]) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $ws->getColumnDimension($colLetter)->setWidth($width);
            $ws->setCellValue("{$colLetter}3", $hdr);
            $ws->getStyle("{$colLetter}3")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $req ? $blueHex : $grayHex]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
            ]);
        }
        $ws->getRowDimension(3)->setRowHeight(30);

        // ── Row 4: notes row ──
        foreach ($cols as $i => [$hdr, $width, $req, $example, $note]) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $ws->setCellValue("{$colLetter}4", $note);
            $ws->getStyle("{$colLetter}4")->applyFromArray([
                'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '555555'], 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $noteBg]],
                'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
            ]);
        }
        $ws->getRowDimension(4)->setRowHeight(40);

        // ── Rows 5-8: example data ──
        $examples = [
            ['2024-06-15', 'John Smith',    'john@example.com',  'ebay', 'LOC-0001', '15487', '28-12345', '223.68', 'Via DHL',  'bank_transfer', '18924-WES', '1', '287.68', '0', '0', ''],
            ['2024-06-15', 'John Smith',    'john@example.com',  'ebay', 'LOC-0001', '15487', '28-12345', '',       '',         '',              '27399-ROS', '1', '2335.68','0', '0', 'IGI certified'],
            ['2024-06-16', 'Priya Sharma',  'priya@example.com', 'pos',  'LOC-0001', '',      '',         '0',      '',         'cash',          'GEM-00142', '2', '4500.00','0', '5', 'Bulk discount'],
            ['2024-06-17', 'Walk-in',       '',                  'pos',  'LOC-0001', '',      '',         '0',      '',         'cash',          'TAN-00089', '1', '8900.00','3', '0', ''],
        ];

        foreach ($examples as $ri => $vals) {
            $row = $ri + 5;
            $bg  = ($ri % 2 === 0) ? 'FFFFFF' : 'F0F7FF';
            foreach ($vals as $ci => $val) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1);
                $ws->setCellValue("{$colLetter}{$row}", $val);
                $ws->getStyle("{$colLetter}{$row}")->applyFromArray([
                    'font'      => ['size' => 10, 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
                ]);
            }
            $ws->getRowDimension($row)->setRowHeight(18);
        }

        $ws->freezePane('A5');
        $ws->setAutoFilter("A3:{$lastCol}3");
    }

    private function buildInstructionSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws): void
    {
        $ws->getColumnDimension('A')->setWidth(5);
        $ws->getColumnDimension('B')->setWidth(22);
        $ws->getColumnDimension('C')->setWidth(60);

        $navyHex = '1A3C5E';
        $blueHex = '2C6FAC';
        $ambrHex = 'B45309';

        $rows = [
            // [type, col_b, col_c, bg]
            ['hdr',  'How to use this template',        '',                                                                              $navyHex],
            ['body', 'Step 1', 'Fill the "Import Template" sheet. Keep header rows 1–4. Start data from row 5.',                         null],
            ['body', 'Step 2', 'Each row = one product line. Repeat order fields (date, customer, channel…) on every line of the same order.', null],
            ['body', 'Step 3', 'Lines are grouped by external_ref (or by date + email when external_ref is blank).',                     null],
            ['body', 'Step 4', 'Upload the file at  Sales → Import  in the Paces admin.',                                               null],
            ['body', 'Step 5', 'Review the preview table, check errors, then click Confirm Import.',                                     null],
            ['gap',  '',       '',                                                                                                        null],
            ['hdr',  'Order-level fields (repeat on every line of the same order)', '',                                                  $blueHex],
            ['body', 'sale_date',         'Required. Date the sale occurred. Format: YYYY-MM-DD.',                                       null],
            ['body', 'customer_name',     'Required. Will match an existing customer or create a walk-in.',                              null],
            ['body', 'customer_email',    'Optional but recommended. Primary match key for customers.',                                  null],
            ['body', 'channel_code',      'Required. Must match a Paces channel code (ebay, pos, website, catawiki).',                   null],
            ['body', 'location_code',     'Required. Must match a Paces location code (e.g. LOC-0001).',                                null],
            ['body', 'external_ref',      'Optional. eBay Sales Record # or other platform reference. Used for duplicate detection.',    null],
            ['body', 'external_order_id', 'Optional. eBay Order ID.',                                                                   null],
            ['body', 'shipping_charge',   'Optional. Total shipping for the order in INR. Put only on the FIRST line.',                  null],
            ['body', 'note',              'Optional. Order-level note.',                                                                 null],
            ['body', 'payment_method',    'Optional. Defaults to bank_transfer. Values: cash, card, upi, bank_transfer, cheque.',        null],
            ['gap',  '',       '',                                                                                                        null],
            ['hdr',  'Line-level fields (one per product)',  '',                                                                         $blueHex],
            ['body', 'sku',               'Required. Must exactly match your product SKU in Paces.',                                     null],
            ['body', 'qty',               'Required. Quantity sold. Must be ≥ 1.',                                                       null],
            ['body', 'unit_price_inr',    'Required. Selling price per unit in INR.',                                                    null],
            ['body', 'tax_percent',       'Optional. Tax % on this line. Default 0.',                                                    null],
            ['body', 'discount_percent',  'Optional. Discount % on this line. Default 0.',                                              null],
            ['body', 'line_note',         'Optional. Notes for this specific product line.',                                             null],
            ['gap',  '',       '',                                                                                                        null],
            ['hdr',  'Important notes',  '',                                                                                             $ambrHex],
            ['body', 'Stock check',      'Import posts each sale and deducts stock. If a SKU has insufficient stock the entire order is rejected.', null],
            ['body', 'Duplicate check',  'Rows with an external_ref already in Paces for that channel are SKIPPED automatically.',       null],
            ['body', 'Customer lookup',  'Match order: exact email → exact name → create new walk-in customer.',                         null],
            ['body', 'Currency',         'All prices must be in INR. If your eBay export shows USD, use the SOLD FOR  (INR) column.',    null],
            ['body', 'Multi-line orders','All rows sharing the same external_ref are imported as ONE sale with multiple lines.',          null],
        ];

        $row = 2;
        foreach ($rows as [$type, $b, $c, $bg]) {
            if ($type === 'gap') {
                $row++;
                continue;
            }

            $ws->setCellValue("B{$row}", $b);
            $ws->setCellValue("C{$row}", $c);

            if ($type === 'hdr') {
                $ws->mergeCells("B{$row}:C{$row}");
                $ws->getStyle("B{$row}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                ]);
                $ws->getRowDimension($row)->setRowHeight(22);
            } else {
                $thinBorder = ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']];
                $ws->getStyle("B{$row}")->applyFromArray([
                    'font'      => ['bold' => true,  'size' => 10, 'name' => 'Arial'],
                    'borders'   => ['allBorders' => $thinBorder],
                ]);
                $ws->getStyle("C{$row}")->applyFromArray([
                    'font'      => ['bold' => false, 'size' => 10, 'name' => 'Arial'],
                    'alignment' => ['wrapText' => true],
                    'borders'   => ['allBorders' => $thinBorder],
                ]);
                $ws->getRowDimension($row)->setRowHeight(28);
            }

            $row++;
        }
    }
}
