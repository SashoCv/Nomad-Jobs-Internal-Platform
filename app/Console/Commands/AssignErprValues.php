<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Candidate;

class AssignErprValues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'candidates:assign-erpr';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign ERPR values to candidates with indefinite contracts based on contract period';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Processing candidates from database...");

        $candidates = Candidate::where('contractType', 'indefinite')->get();

        if ($candidates->isEmpty()) {
            $this->info("No candidates found with 'indefinite' contract type");
            return Command::SUCCESS;
        }

        $processedCount = 0;

        foreach ($candidates as $candidate) {
            $erprValue = $this->determineErprValue($candidate->contractPeriod ?? '');

            if ($erprValue) {
                // Add erpr field to the candidate (assuming you have this column)
                $candidate->contractType = $erprValue;
                $candidate->save();

                $processedCount++;

                $this->line("ID: {$candidate->id}, Name: {$candidate->fullName}, Contract Period: '{$candidate->contractPeriod}' -> {$erprValue}");
            } else {
                $this->warn("Could not determine ERPR for candidate ID: {$candidate->id}, Contract Period: '{$candidate->contractPeriod}'");
            }
        }

        $this->info("Processed {$processedCount} candidates with indefinite contracts");

        return Command::SUCCESS;
    }

    private function determineErprValue(string $contractPeriod): ?string
    {
        // Handle empty/null contract period -> ERPR 1
        if (empty($contractPeriod)) {
            return 'ERPR 1';
        }

        $period = strtolower(trim($contractPeriod));
        
        // Handle special cases: comma, dot, or "null" string -> ERPR 1
        if (in_array($period, [',', '.', 'null'])) {
            return 'ERPR 1';
        }

        // Handle "6 meseca" or similar month periods -> ERPR 1
        if (preg_match('/^\d+\s*(месец|месеца|месеци|month|months|м\.?)/u', $period)) {
            return 'ERPR 1';
        }

        // Handle single dates like "15.03.2024" -> ERPR 1
        if (preg_match('/^\d{1,2}\.\d{1,2}\.\d{4}$/', $period)) {
            return 'ERPR 1';
        }

        // Handle "1 година" variations
        if (preg_match('/^1\s*(година|години|year|г\.?)(?:\s|,|$)/u', $period)) {
            return 'ERPR 1';
        }

        // Handle "2 години" variations
        if (preg_match('/^2\s*(година|години|year|г\.?)(?:\s|,|$)/u', $period)) {
            return 'ERPR 2';
        }

        // Handle "3 години" variations
        if (preg_match('/^3\s*(година|години|year|г\.?)(?:\s|,|$)/u', $period)) {
            return 'ERPR 3';
        }

        // Handle numeric values only
        if (preg_match('/^(\d+)(?:\s|,|$)/', $period, $matches)) {
            $number = (int)$matches[1];
            switch ($number) {
                case 1:
                    return 'ERPR 1';
                case 2:
                    return 'ERPR 2';
                case 3:
                    return 'ERPR 3';
            }
        }

        // Handle date ranges - calculate years between dates
        if ($this->isDateRange($contractPeriod)) {
            $years = $this->calculateYearsBetweenDates($contractPeriod);
            if ($years) {
                switch ($years) {
                    case 1:
                        return 'ERPR 1';
                    case 2:
                        return 'ERPR 2';
                    case 3:
                        return 'ERPR 3';
                }
            }
        }

        return null;
    }

    private function isDateRange(string $period): bool
    {
        // Check for date range patterns like "18.12.2024-13.12.2026", "18.12.24-05.12.27" or "10.12.2023 09.03.2024"
        return preg_match('/\d{1,2}\.\d{1,2}\.(\d{4}|\d{2})[\s\-–]+\d{1,2}\.\d{1,2}\.(\d{4}|\d{2})/', $period);
    }

    private function calculateYearsBetweenDates(string $period): ?int
    {
        // Extract dates from various formats - support both 4-digit and 2-digit years
        if (preg_match('/(\d{1,2}\.\d{1,2}\.(\d{4}|\d{2}))[\s\-–]+(\d{1,2}\.\d{1,2}\.(\d{4}|\d{2}))/', $period, $matches)) {
            try {
                $startDateStr = $matches[1];
                $endDateStr = $matches[3];
                
                // Handle 2-digit years by converting to 4-digit
                $startDateStr = preg_replace('/(\d{1,2}\.\d{1,2}\.)(\d{2})$/', '$1 20$2', $startDateStr);
                $endDateStr = preg_replace('/(\d{1,2}\.\d{1,2}\.)(\d{2})$/', '$1 20$2', $endDateStr);
                
                $startDate = \DateTime::createFromFormat('d.m.Y', $startDateStr);
                $endDate = \DateTime::createFromFormat('d.m.Y', $endDateStr);

                if ($startDate && $endDate) {
                    $interval = $startDate->diff($endDate);
                    $years = $interval->y;
                    
                    // If less than 1 year but more than 0 months, consider it 1 year
                    if ($years == 0 && $interval->m > 0) {
                        $years = 1;
                    }
                    
                    return $years;
                }
            } catch (\Exception $e) {
                // If date parsing fails, return null
            }
        }

        return null;
    }
}
