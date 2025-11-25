<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Најди го admin корисникот (marin@nomadpartners.bg)
        $adminUser = DB::table('users')
            ->where('email', 'marin@nomadpartners.bg')
            ->first();

        if (!$adminUser) {
            echo "Admin user marin@nomadpartners.bg not found!\n";
            return;
        }

        $adminId = $adminUser->id;

        // Земи ги сите agent_candidates што имаат nomad_office_id и candidate_id
        $agentCandidates = DB::table('agent_candidates')
            ->whereNotNull('nomad_office_id')
            ->whereNotNull('candidate_id')
            ->whereNull('deleted_at')
            ->get(['id', 'candidate_id', 'nomad_office_id']);

        $insertedCount = 0;
        $skippedCount = 0;

        foreach ($agentCandidates as $agentCandidate) {
            // Провери дали веќе постои запис за овој кандидат
            $existing = DB::table('asign_candidate_to_nomad_offices')
                ->where('candidate_id', $agentCandidate->candidate_id)
                ->first();

            if ($existing) {
                $skippedCount++;
                continue;
            }

            // Провери дали кандидатот постои
            $candidateExists = DB::table('candidates')
                ->where('id', $agentCandidate->candidate_id)
                ->exists();

            if (!$candidateExists) {
                $skippedCount++;
                continue;
            }

            // Додади нов запис во asign_candidate_to_nomad_offices
            DB::table('asign_candidate_to_nomad_offices')->insert([
                'admin_id' => $adminId,
                'nomad_office_id' => $agentCandidate->nomad_office_id,
                'candidate_id' => $agentCandidate->candidate_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $insertedCount++;
        }

        echo "Migration completed!\n";
        echo "Inserted: {$insertedCount} records\n";
        echo "Skipped: {$skippedCount} records\n";
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Не бришеме ништо при rollback за да не изгубиме податоци
        echo "Rollback: No action taken to preserve data\n";
    }
};
