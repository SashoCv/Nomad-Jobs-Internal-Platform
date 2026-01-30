<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        $nameToSlugMap = [
            '90 дни' => '90days',
            '9 месеца' => '9months',
            'ЕРПР 1' => 'erpr1',
            'ЕРПР 2' => 'erpr2',
            'ЕРПР 3' => 'erpr3',
        ];

        foreach ($nameToSlugMap as $name => $slug) {
            DB::table('candidate_contracts')
                ->where('contract_type', $name)
                ->update(['contract_type' => $slug]);

            DB::table('candidates')
                ->where('contractType', $name)
                ->update(['contractType' => $slug]);
        }

        $indefiniteContracts = DB::table('candidate_contracts')
            ->where('contract_type', 'indefinite')
            ->get();

        $processedCount = 0;

        foreach ($indefiniteContracts as $contract) {
            $erprSlug = $this->determineErprFromPeriod($contract->contract_period);

            DB::table('candidate_contracts')
                ->where('id', $contract->id)
                ->update(['contract_type' => $erprSlug]);

            $processedCount++;
        }

        Log::info("Migrated {$processedCount} legacy 'indefinite' contracts to ERPR slugs");

        $indefiniteCandidates = DB::table('candidates')
            ->where('contractType', 'indefinite')
            ->get();

        foreach ($indefiniteCandidates as $candidate) {
            $erprSlug = $this->determineErprFromPeriod($candidate->contractPeriod ?? null);

            DB::table('candidates')
                ->where('id', $candidate->id)
                ->update(['contractType' => $erprSlug]);
        }
    }

    private function determineErprFromPeriod(?string $contractPeriod): string
    {
        if (empty($contractPeriod) || $contractPeriod === 'null') {
            return 'erpr1';
        }

        $period = trim($contractPeriod);

        if ($period === ',' || $period === '.') {
            return 'erpr1';
        }

        if (preg_match('/месец|month/iu', $period)) {
            return 'erpr1';
        }

        if (preg_match('/^\d{1,2}\.\d{1,2}\.\d{2,4}$/', $period)) {
            return 'erpr1';
        }

        if (preg_match('/1\s*годин/iu', $period)) {
            return 'erpr1';
        }

        if (preg_match('/2\s*годин/iu', $period)) {
            return 'erpr2';
        }

        if (preg_match('/3\s*годин/iu', $period)) {
            return 'erpr3';
        }

        if (preg_match('/^[123]$/', $period)) {
            return 'erpr' . $period;
        }

        $years = $this->calculateYearsFromDateRange($period);

        if ($years !== null) {
            if ($years <= 1) {
                return 'erpr1';
            }

            if ($years <= 2) {
                return 'erpr2';
            }

            return 'erpr3';
        }

        return 'erpr1';
    }

    private function calculateYearsFromDateRange(string $period): ?int
    {
        if (! preg_match('/(\d{1,2})\.(\d{1,2})\.(\d{2,4})[\s\-]+(\d{1,2})\.(\d{1,2})\.(\d{2,4})/', $period, $matches)) {
            return null;
        }

        try {
            $startYear = strlen($matches[3]) === 2 ? '20' . $matches[3] : $matches[3];
            $endYear = strlen($matches[6]) === 2 ? '20' . $matches[6] : $matches[6];

            $startDate = Carbon::createFromFormat('d.m.Y', "{$matches[1]}.{$matches[2]}.{$startYear}");
            $endDate = Carbon::createFromFormat('d.m.Y', "{$matches[4]}.{$matches[5]}.{$endYear}");

            $years = $startDate->diffInYears($endDate);

            $remainingMonths = $startDate->copy()->addYears($years)->diffInMonths($endDate);

            if ($remainingMonths > 0) {
                $years++;
            }

            return max(1, $years);
        } catch (\Exception $e) {
            return null;
        }
    }
};
