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
     * This migration:
     * 1. Migrates any companies missing from company_emails table
     * 2. Drops the legacy email column from companies table
     */
    public function up(): void
    {
        // First, migrate any companies that have email but no record in company_emails
        $companiesWithoutEmails = DB::table('companies')
            ->leftJoin('company_emails', function ($join) {
                $join->on('companies.id', '=', 'company_emails.company_id')
                    ->whereNull('company_emails.deleted_at');
            })
            ->whereNull('company_emails.id')
            ->whereNull('companies.deleted_at')
            ->whereNotNull('companies.email')
            ->where('companies.email', '!=', '')
            ->select('companies.id', 'companies.email')
            ->get();

        foreach ($companiesWithoutEmails as $company) {
            DB::table('company_emails')->insert([
                'company_id' => $company->id,
                'email' => $company->email,
                'is_default' => true,
                'is_notification_recipient' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Now drop the email column
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the email column
        Schema::table('companies', function (Blueprint $table) {
            $table->string('email')->after('address')->default('');
        });

        // Restore email from company_emails default email
        $companies = DB::table('companies')
            ->whereNull('deleted_at')
            ->get();

        foreach ($companies as $company) {
            $defaultEmail = DB::table('company_emails')
                ->where('company_id', $company->id)
                ->where('is_default', true)
                ->whereNull('deleted_at')
                ->first();

            if ($defaultEmail) {
                DB::table('companies')
                    ->where('id', $company->id)
                    ->update(['email' => $defaultEmail->email]);
            }
        }
    }
};
