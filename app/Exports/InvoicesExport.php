<?php

namespace App\Exports;

use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\Wizard;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InvoicesExport implements FromView, WithColumnWidths, WithStyles, WithEvents
{
    protected $data;
    protected $totalSum = 0;
    protected $totalInvoiced = 0;
    protected $totalNotInvoiced = 0;
    protected $totalRejected = 0;
    
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
                case 'rejected':
                    $this->totalRejected += $invoice->price;
                    break;
            }
        }
    }

    public function view(): View
    {
        return view('invoicesExport', [
            'invoices' => $this->data,
            'totalSum' => $this->totalSum,
            'totalInvoiced' => $this->totalInvoiced,
            'totalNotInvoiced' => $this->totalNotInvoiced,
            'totalRejected' => $this->totalRejected,
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,  // ID
            'B' => 30,  // Candidate (Cyrillic)
            'C' => 30,  // Candidate (Latin)
            'D' => 35,  // Company
            'E' => 50,  // Service Type (increased for long names)
            'F' => 20,  // Status
            'G' => 15,  // Date
            'H' => 18,  // Amount (BGN)
            'I' => 20,  // Invoice Status
            'J' => 40,  // Notes
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        
        // Company Header Section (rows 1-2)
        $sheet->mergeCells('A1:J1');
        $sheet->setCellValue('A1', 'NOMAD JOBS BULGARIA');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 20,
                'name' => 'Calibri',
                'color' => ['rgb' => '1F4E79'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(35);
        
        // Report Title
        $sheet->mergeCells('A2:J2');
        $sheet->setCellValue('A2', 'ЛИСТА НА ФАКТУРИ / INVOICE REPORT');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'name' => 'Calibri',
                'color' => ['rgb' => '2E5266'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F1F5'],
            ],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(30);
        
        // Date Generated
        $sheet->mergeCells('A3:J3');
        $sheet->setCellValue('A3', 'Генерирано на / Generated on: ' . date('d.m.Y H:i'));
        $sheet->getStyle('A3')->applyFromArray([
            'font' => [
                'italic' => true,
                'size' => 11,
                'name' => 'Calibri',
                'color' => ['rgb' => '666666'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(20);
        
        // Column Headers
        $sheet->getStyle('A4:J4')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF'],
                'name' => 'Calibri',
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
                'rotation' => 90,
                'startColor' => ['rgb' => '1F4E79'],
                'endColor' => ['rgb' => '2E5266'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '1F4E79'],
                ],
            ],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(35);

        // Data Rows Styling
        if ($highestRow > 4) {
            // Base styling for all data rows
            $sheet->getStyle('A5:J' . ($highestRow - 4))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'D3D3D3'],
                    ],
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'font' => [
                    'name' => 'Calibri',
                    'size' => 11,
                ],
            ]);

            // Alternating row colors
            for ($row = 5; $row <= $highestRow - 4; $row++) {
                if (($row - 5) % 2 == 1) {
                    $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F7FBFC'],
                        ],
                    ]);
                }
                $sheet->getRowDimension($row)->setRowHeight(40);
            }

            // Column-specific alignments
            $sheet->getStyle('A5:A' . ($highestRow - 4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G5:G' . ($highestRow - 4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H5:H' . ($highestRow - 4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('I5:I' . ($highestRow - 4))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            // Format amount column
            $sheet->getStyle('H5:H' . ($highestRow - 4))->getNumberFormat()->setFormatCode('#,##0.00 "лв."');
            
            // Bold amount column
            $sheet->getStyle('H5:H' . ($highestRow - 4))->getFont()->setBold(true);
        }
        
        // Summary Section Styling - Calculate correct row positions
        // The blade template has: data rows + empty row + 4 summary rows (invoiced, not invoiced, rejected, total)
        $summaryStartRow = $highestRow - 4; // Position for "SUMMARY" header (inserted by PHP, not in Blade)
        
        // Summary header with enhanced styling
        $sheet->mergeCells('A' . $summaryStartRow . ':J' . $summaryStartRow);
        $sheet->setCellValue('A' . $summaryStartRow, 'ОБОБЩЕНИЕ / SUMMARY');
        $sheet->getStyle('A' . $summaryStartRow . ':J' . $summaryStartRow)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'name' => 'Calibri',
                'color' => ['rgb' => '1F4E79'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
                'rotation' => 90,
                'startColor' => ['rgb' => 'B3D1F2'],
                'endColor' => ['rgb' => 'E1F0FF'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '1F4E79'],
                ],
            ],
        ]);
        $sheet->getRowDimension($summaryStartRow)->setRowHeight(40);
        
        // Apply styling to actual summary rows from Blade template
        // Summary header is inserted above the actual summary data
        $invoicedRow = $highestRow - 3;      // Фактурирано / Invoiced row (from Blade)
        $notInvoicedRow = $highestRow - 2;   // Нефактурирано / Not Invoiced row (from Blade)
        $rejectedRow = $highestRow - 1;      // Отхвърлено / Rejected row (from Blade)
        $totalRow = $highestRow;             // ОБЩО / TOTAL row (from Blade)
        
        // Base styling for all summary rows
        for ($summaryRow = $invoicedRow; $summaryRow <= $totalRow; $summaryRow++) {
            $sheet->getStyle('A' . $summaryRow . ':J' . $summaryRow)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'name' => 'Calibri',
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '1F4E79'],
                    ],
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ]);
            $sheet->getRowDimension($summaryRow)->setRowHeight(35);
        }
        
        // Format summary amounts
        $sheet->getStyle('H' . ($summaryStartRow + 1) . ':H' . $highestRow)->getNumberFormat()->setFormatCode('#,##0.00 "лв."');
        $sheet->getStyle('H' . ($summaryStartRow + 1) . ':H' . $highestRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        
        // Color code summary rows with gradient effects - Apply to correct rows
        // Invoiced - Professional Green Gradient
        $sheet->getStyle('A' . $invoicedRow . ':J' . $invoicedRow)->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
                'rotation' => 90,
                'startColor' => ['rgb' => '28A745'],
                'endColor' => ['rgb' => '34CE57'],
            ],
            'font' => [
                'bold' => true,
                'size' => 12,
                'name' => 'Calibri',
                'color' => ['rgb' => 'FFFFFF'],
            ],
        ]);
            
        // Not Invoiced - Professional Orange Gradient  
        $sheet->getStyle('A' . $notInvoicedRow . ':J' . $notInvoicedRow)->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
                'rotation' => 90,
                'startColor' => ['rgb' => 'FF8C00'],
                'endColor' => ['rgb' => 'FFA500'],
            ],
            'font' => [
                'bold' => true,
                'size' => 12,
                'name' => 'Calibri',
                'color' => ['rgb' => 'FFFFFF'],
            ],
        ]);
            
        // Rejected - Professional Red Gradient
        $sheet->getStyle('A' . $rejectedRow . ':J' . $rejectedRow)->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
                'rotation' => 90,
                'startColor' => ['rgb' => 'DC3545'],
                'endColor' => ['rgb' => 'E74C3C'],
            ],
            'font' => [
                'bold' => true,
                'size' => 12,
                'name' => 'Calibri',
                'color' => ['rgb' => 'FFFFFF'],
            ],
        ]);
            
        // Total - Professional Dark Blue Gradient
        $sheet->getStyle('A' . $totalRow . ':J' . $totalRow)->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
                'rotation' => 90,
                'startColor' => ['rgb' => '1F4E79'],
                'endColor' => ['rgb' => '2C5F8C'],
            ],
            'font' => [
                'bold' => true,
                'size' => 14,
                'name' => 'Calibri',
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                    'color' => ['rgb' => '1F4E79'],
                ],
            ],
        ]);
        
        // Add outer border to entire document - excluding the problematic yellow border
        $sheet->getStyle('A1:J' . $highestRow)->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '1F4E79'],
                ],
            ],
        ]);

        // Freeze panes
        $sheet->freezePane('A5');
        
        // Set print settings
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.75);
        $sheet->getPageMargins()->setRight(0.25);
        $sheet->getPageMargins()->setLeft(0.25);
        $sheet->getPageMargins()->setBottom(0.75);

        return $sheet;
    }
    
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Apply conditional formatting to both Status and Invoice Status columns
                $highestRow = $sheet->getHighestRow();
                if ($highestRow > 4) {
                    // Apply direct styling to Invoice Status cells based on content
                    for ($row = 5; $row <= $highestRow - 4; $row++) {
                        $cellValue = $sheet->getCell('I' . $row)->getCalculatedValue();
                        
                        if (strpos(strtoupper($cellValue), 'INVOICED') !== false && strpos(strtoupper($cellValue), 'NOT') === false) {
                            // Invoiced - Green
                            $sheet->getStyle('I' . $row)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'D4EDDA'],
                                ],
                                'font' => [
                                    'bold' => true,
                                    'color' => ['rgb' => '155724'],
                                ],
                            ]);
                        } elseif (strpos(strtoupper($cellValue), 'NOT INVOICED') !== false) {
                            // Not Invoiced - Yellow
                            $sheet->getStyle('I' . $row)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'FFF3CD'],
                                ],
                                'font' => [
                                    'bold' => true,
                                    'color' => ['rgb' => '856404'],
                                ],
                            ]);
                        } elseif (strpos(strtoupper($cellValue), 'REJECTED') !== false) {
                            // Rejected - Red
                            $sheet->getStyle('I' . $row)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F8D7DA'],
                                ],
                                'font' => [
                                    'bold' => true,
                                    'color' => ['rgb' => '721C24'],
                                ],
                            ]);
                        }
                    }
                    
                    // Status Column (Column F) - Different color scheme for status names
                    
                    // Common positive statuses - Light blue
                    $positiveStatuses = ['Пристигнал', 'Завършен', 'Получен', 'Одобрен', 'Активен'];
                    foreach ($positiveStatuses as $status) {
                        $conditionalPositive = new Conditional();
                        $conditionalPositive->setConditionType(Conditional::CONDITION_CONTAINSTEXT);
                        $conditionalPositive->setOperatorType(Conditional::OPERATOR_CONTAINSTEXT);
                        $conditionalPositive->setText($status);
                        $conditionalPositive->getStyle()->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('CCE5FF');
                        $conditionalPositive->getStyle()->getFont()->getColor()->setRGB('0066CC');
                        $conditionalPositive->getStyle()->getFont()->setBold(true);
                        
                        $statusConditionalStyles = $sheet->getStyle('F5:F' . ($highestRow - 4))->getConditionalStyles();
                        $statusConditionalStyles[] = $conditionalPositive;
                        $sheet->getStyle('F5:F' . ($highestRow - 4))->setConditionalStyles($statusConditionalStyles);
                    }
                    
                    // Pending/In Progress statuses - Orange
                    $pendingStatuses = ['В процес', 'Чакащ', 'Изпратен', 'В изчакване'];
                    foreach ($pendingStatuses as $status) {
                        $conditionalPending = new Conditional();
                        $conditionalPending->setConditionType(Conditional::CONDITION_CONTAINSTEXT);
                        $conditionalPending->setOperatorType(Conditional::OPERATOR_CONTAINSTEXT);
                        $conditionalPending->setText($status);
                        $conditionalPending->getStyle()->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('FFE0B3');
                        $conditionalPending->getStyle()->getFont()->getColor()->setRGB('CC6600');
                        $conditionalPending->getStyle()->getFont()->setBold(true);
                        
                        $statusConditionalStyles = $sheet->getStyle('F5:F' . ($highestRow - 4))->getConditionalStyles();
                        $statusConditionalStyles[] = $conditionalPending;
                        $sheet->getStyle('F5:F' . ($highestRow - 4))->setConditionalStyles($statusConditionalStyles);
                    }
                    
                    // Negative statuses - Light red
                    $negativeStatuses = ['Отказан', 'Неуспешен', 'Отхвърлен', 'Проблем'];
                    foreach ($negativeStatuses as $status) {
                        $conditionalNegative = new Conditional();
                        $conditionalNegative->setConditionType(Conditional::CONDITION_CONTAINSTEXT);
                        $conditionalNegative->setOperatorType(Conditional::OPERATOR_CONTAINSTEXT);
                        $conditionalNegative->setText($status);
                        $conditionalNegative->getStyle()->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('FFD6D6');
                        $conditionalNegative->getStyle()->getFont()->getColor()->setRGB('CC0000');
                        $conditionalNegative->getStyle()->getFont()->setBold(true);
                        
                        $statusConditionalStyles = $sheet->getStyle('F5:F' . ($highestRow - 4))->getConditionalStyles();
                        $statusConditionalStyles[] = $conditionalNegative;
                        $sheet->getStyle('F5:F' . ($highestRow - 4))->setConditionalStyles($statusConditionalStyles);
                    }
                }
            },
        ];
    }
}
