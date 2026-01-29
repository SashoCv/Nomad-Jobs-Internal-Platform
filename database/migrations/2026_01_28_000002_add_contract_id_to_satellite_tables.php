<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add contract_id to files table
        Schema::table('files', function (Blueprint $table) {
            $table->foreignId('contract_id')
                ->nullable()
                ->after('candidate_id')
                ->constrained('candidate_contracts')
                ->nullOnDelete();
            $table->index('contract_id', 'idx_files_contract');
        });

        // Add contract_id to statushistories table
        Schema::table('statushistories', function (Blueprint $table) {
            $table->foreignId('contract_id')
                ->nullable()
                ->after('candidate_id')
                ->constrained('candidate_contracts')
                ->nullOnDelete();
            $table->index('contract_id', 'idx_statushistories_contract');
        });

        // Add contract_id to agent_candidates table
        Schema::table('agent_candidates', function (Blueprint $table) {
            $table->foreignId('contract_id')
                ->nullable()
                ->after('candidate_id')
                ->constrained('candidate_contracts')
                ->nullOnDelete();
            $table->index('contract_id', 'idx_agent_candidates_contract');
        });

        // Add contract_id to invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('contract_id')
                ->nullable()
                ->after('candidate_id')
                ->constrained('candidate_contracts')
                ->nullOnDelete();
            $table->index('contract_id', 'idx_invoices_contract');
        });

        // Add contract_id to invoice_company_candidates table (if exists)
        if (Schema::hasTable('invoice_company_candidates')) {
            Schema::table('invoice_company_candidates', function (Blueprint $table) {
                $table->foreignId('contract_id')
                    ->nullable()
                    ->constrained('candidate_contracts')
                    ->nullOnDelete();
                $table->index('contract_id', 'idx_invoice_company_candidates_contract');
            });
        }

        // Add contract_id to arrivals table
        Schema::table('arrivals', function (Blueprint $table) {
            $table->foreignId('contract_id')
                ->nullable()
                ->after('candidate_id')
                ->constrained('candidate_contracts')
                ->nullOnDelete();
            $table->index('contract_id', 'idx_arrivals_contract');
        });

        // Add contract_id to candidate_visas table
        Schema::table('candidate_visas', function (Blueprint $table) {
            $table->foreignId('contract_id')
                ->nullable()
                ->after('candidate_id')
                ->constrained('candidate_contracts')
                ->nullOnDelete();
            $table->index('contract_id', 'idx_candidate_visas_contract');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove from candidate_visas
        Schema::table('candidate_visas', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
            $table->dropIndex('idx_candidate_visas_contract');
            $table->dropColumn('contract_id');
        });

        // Remove from arrivals
        Schema::table('arrivals', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
            $table->dropIndex('idx_arrivals_contract');
            $table->dropColumn('contract_id');
        });

        // Remove from invoice_company_candidates
        if (Schema::hasTable('invoice_company_candidates') && Schema::hasColumn('invoice_company_candidates', 'contract_id')) {
            Schema::table('invoice_company_candidates', function (Blueprint $table) {
                $table->dropForeign(['contract_id']);
                $table->dropIndex('idx_invoice_company_candidates_contract');
                $table->dropColumn('contract_id');
            });
        }

        // Remove from invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
            $table->dropIndex('idx_invoices_contract');
            $table->dropColumn('contract_id');
        });

        // Remove from agent_candidates
        Schema::table('agent_candidates', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
            $table->dropIndex('idx_agent_candidates_contract');
            $table->dropColumn('contract_id');
        });

        // Remove from statushistories
        Schema::table('statushistories', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
            $table->dropIndex('idx_statushistories_contract');
            $table->dropColumn('contract_id');
        });

        // Remove from files
        Schema::table('files', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
            $table->dropIndex('idx_files_contract');
            $table->dropColumn('contract_id');
        });
    }
};
