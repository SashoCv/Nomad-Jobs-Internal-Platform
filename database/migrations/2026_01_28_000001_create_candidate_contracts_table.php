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
        Schema::create('candidate_contracts', function (Blueprint $table) {
            $table->id();

            // Core relationships
            $table->foreignId('candidate_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('statuses')->nullOnDelete();
            $table->foreignId('type_id')->nullable()->constrained('types')->nullOnDelete();

            // Contract details
            $table->string('contract_type', 50);  // '90days', '9months', 'indefinite'
            $table->string('contract_period', 100)->nullable();
            $table->integer('contract_period_number')->default(1);
            $table->string('contract_extension_period', 100)->nullable();
            $table->date('start_contract_date')->nullable();
            $table->date('end_contract_date')->nullable();
            $table->date('contract_period_date')->nullable();

            // Employment details
            $table->decimal('salary', 10, 2)->nullable();
            $table->string('working_time', 100)->nullable();
            $table->string('working_days', 100)->nullable();
            $table->string('address_of_work', 255)->nullable();
            $table->string('name_of_facility', 255)->nullable();
            $table->foreignId('company_adresses_id')->nullable()->constrained('company_adresses')->nullOnDelete();
            $table->string('dossier_number', 100)->nullable();

            // Tracking fields
            $table->string('quartal', 50)->nullable();
            $table->string('seasonal', 50)->nullable();
            $table->foreignId('case_id')->nullable()->constrained('cases')->nullOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('added_by')->nullable();
            $table->date('date')->nullable();  // Application/registration date
            $table->text('notes')->nullable();

            // Status tracking
            $table->boolean('is_active')->default(true);

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('candidate_id', 'idx_candidate_contracts_candidate');
            $table->index('company_id', 'idx_candidate_contracts_company');
            $table->index('end_contract_date', 'idx_candidate_contracts_end_date');
            $table->index('is_active', 'idx_candidate_contracts_is_active');
            $table->index('type_id', 'idx_candidate_contracts_type');
            $table->unique(['candidate_id', 'contract_period_number'], 'unique_candidate_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_contracts');
    }
};
