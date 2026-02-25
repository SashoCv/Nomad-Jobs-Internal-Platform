<?php

namespace App\Exports;

use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class AgentInvoicesExport implements FromView, WithColumnWidths, WithStyles, WithEvents
{
    protected $data;
    protected $totalSum = 0;
    protected $totalInvoiced = 0;
    protected $totalNotInvoiced = 0;
    protected $totalPaid = 0;

    public function __construct($data)
    {
        $this->data = $data;
        $this->calculateTotals();
    }

    protected function calculateTotals()
    {
        foreach ($this->data as $invoice) {
            $this->totalSum += $invoice->price;

            switch ($invoice->invoiceStatus) {
                case 'invoiced':
                    $this->totalInvoiced += $invoice->price;
                    break;
                case 'not_invoiced':
                    $this->totalNotInvoiced += $invoice->price;
                    break;
                case 'paid':
                    $this->totalPaid += $invoice->price;
                    break;
            }
        }
    }

    public function view(): View
    {
        return view('agentInvoicesExport', [
            'invoices' => $this->data,
            'totalSum' => $this->totalSum,
            'totalInvoiced' => $this->totalInvoiced,
            'totalNotInvoiced' => $this->totalNotInvoiced,
            'totalPaid' => $this->totalPaid,
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 28,  // Candidate (Cyrillic)
            'C' => 28,  // Candidate (Latin)
            'D' => 30,  // Company
            'E' => 25,  // Agent
            'F' => 30,  // Service Type
            'G' => 20,  // Status Name
            'H' => 14,  // Date
            'I' => 16,  // Amount
            'J' => 18,  // Invoice Status
            'K' => 16,  // Invoice Number
            'L' => 35,  // Notes
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $lastCol = 'L';

        // Company Header (row 1)
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'NOMAD JOBS BULGARIA');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 20, 'name' => 'Calibri', 'color' => ['rgb' => '1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(35);

        // Report Title (row 2)
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', 'НАЧИСЛЕНИЯ АГЕНТИ / AGENT INVOICES REPORT');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'name' => 'Calibri', 'color' => ['rgb' => '2E5266']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F1F5']],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(30);

        // Date Generated (row 3)
        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->setCellValue('A3', 'Генерирано на / Generated on: ' . date('d.m.Y H:i'));
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['italic' => true, 'size' => 11, 'name' => 'Calibri', 'color' => ['rgb' => '666666']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // Column Headers (row 4)
        $sheet->getStyle("A4:{$lastCol}4")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Calibri'],
            'fill' => ['fillType' => Fill::FILL_GRADIENT_LINEAR, 'rotation' => 90, 'startColor' => ['rgb' => '1F4E79'], 'endColor' => ['rgb' => '2E5266']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '1F4E79']]],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(35);

        // Data Rows
        $dataEndRow = $highestRow - 5; // 5 summary rows (empty + 4 data)
        if ($dataEndRow > 4) {
            $sheet->getStyle("A5:{$lastCol}{$dataEndRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D3D3D3']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'font' => ['name' => 'Calibri', 'size' => 11],
            ]);

            // Alternating row colors
            for ($row = 5; $row <= $dataEndRow; $row++) {
                if (($row - 5) % 2 == 1) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F7FBFC']],
                    ]);
                }
                $sheet->getRowDimension($row)->setRowHeight(40);
            }

            // Column alignments
            $sheet->getStyle("A5:A{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H5:H{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I5:I{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J5:J{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("K5:K{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Format amount column
            $sheet->getStyle("I5:I{$dataEndRow}")->getNumberFormat()->setFormatCode('#,##0.00 "€"');
            $sheet->getStyle("I5:I{$dataEndRow}")->getFont()->setBold(true);
        }

        // Summary Section
        $summaryStartRow = $highestRow - 4;
        $sheet->mergeCells("A{$summaryStartRow}:{$lastCol}{$summaryStartRow}");
        $sheet->setCellValue("A{$summaryStartRow}", 'ОБОБЩЕНИЕ / SUMMARY');
        $sheet->getStyle("A{$summaryStartRow}:{$lastCol}{$summaryStartRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'name' => 'Calibri', 'color' => ['rgb' => '1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_GRADIENT_LINEAR, 'rotation' => 90, 'startColor' => ['rgb' => 'B3D1F2'], 'endColor' => ['rgb' => 'E1F0FF']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '1F4E79']]],
        ]);
        $sheet->getRowDimension($summaryStartRow)->setRowHeight(40);

        // Summary rows styling
        $invoicedRow = $highestRow - 3;
        $notInvoicedRow = $highestRow - 2;
        $paidRow = $highestRow - 1;
        $totalRow = $highestRow;

        for ($summaryRow = $invoicedRow; $summaryRow <= $totalRow; $summaryRow++) {
            $sheet->getStyle("A{$summaryRow}:{$lastCol}{$summaryRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 12, 'name' => 'Calibri', 'color' => ['rgb' => 'FFFFFF']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '1F4E79']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension($summaryRow)->setRowHeight(35);
        }

        // Format summary amounts
        $sheet->getStyle("I{$invoicedRow}:I{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.00 "€"');
        $sheet->getStyle("I{$invoicedRow}:I{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Invoiced - Green
        $sheet->getStyle("A{$invoicedRow}:{$lastCol}{$invoicedRow}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_GRADIENT_LINEAR, 'rotation' => 90, 'startColor' => ['rgb' => '28A745'], 'endColor' => ['rgb' => '34CE57']],
            'font' => ['bold' => true, 'size' => 12, 'name' => 'Calibri', 'color' => ['rgb' => 'FFFFFF']],
        ]);

        // Not Invoiced - Orange
        $sheet->getStyle("A{$notInvoicedRow}:{$lastCol}{$notInvoicedRow}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_GRADIENT_LINEAR, 'rotation' => 90, 'startColor' => ['rgb' => 'FF8C00'], 'endColor' => ['rgb' => 'FFA500']],
            'font' => ['bold' => true, 'size' => 12, 'name' => 'Calibri', 'color' => ['rgb' => 'FFFFFF']],
        ]);

        // Paid - Teal
        $sheet->getStyle("A{$paidRow}:{$lastCol}{$paidRow}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_GRADIENT_LINEAR, 'rotation' => 90, 'startColor' => ['rgb' => '17A2B8'], 'endColor' => ['rgb' => '20C9E0']],
            'font' => ['bold' => true, 'size' => 12, 'name' => 'Calibri', 'color' => ['rgb' => 'FFFFFF']],
        ]);

        // Total - Dark Blue
        $sheet->getStyle("A{$totalRow}:{$lastCol}{$totalRow}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_GRADIENT_LINEAR, 'rotation' => 90, 'startColor' => ['rgb' => '1F4E79'], 'endColor' => ['rgb' => '2C5F8C']],
            'font' => ['bold' => true, 'size' => 14, 'name' => 'Calibri', 'color' => ['rgb' => 'FFFFFF']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '1F4E79']]],
        ]);

        // Outer border
        $sheet->getStyle("A1:{$lastCol}{$highestRow}")->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '1F4E79']]],
        ]);

        // Freeze panes & print settings
        $sheet->freezePane('A5');
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);

        return $sheet;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $dataEndRow = $highestRow - 5;

                if ($dataEndRow > 4) {
                    for ($row = 5; $row <= $dataEndRow; $row++) {
                        $cellValue = strtoupper($sheet->getCell('J' . $row)->getCalculatedValue());

                        if (str_contains($cellValue, 'PAID')) {
                            $sheet->getStyle('J' . $row)->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4EDDA']],
                                'font' => ['bold' => true, 'color' => ['rgb' => '155724']],
                            ]);
                        } elseif (str_contains($cellValue, 'NOT INVOICED')) {
                            $sheet->getStyle('J' . $row)->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3CD']],
                                'font' => ['bold' => true, 'color' => ['rgb' => '856404']],
                            ]);
                        } elseif (str_contains($cellValue, 'INVOICED')) {
                            $sheet->getStyle('J' . $row)->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'CCE5FF']],
                                'font' => ['bold' => true, 'color' => ['rgb' => '004085']],
                            ]);
                        } elseif (str_contains($cellValue, 'REJECTED')) {
                            $sheet->getStyle('J' . $row)->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8D7DA']],
                                'font' => ['bold' => true, 'color' => ['rgb' => '721C24']],
                            ]);
                        }
                    }
                }
            },
        ];
    }
}
