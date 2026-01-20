<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\CompanyEmail;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_emails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('email');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_notification_recipient')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // Seed existing emails
        $companies = Company::whereNotNull('companyEmail')->get();
        foreach ($companies as $company) {
            if (!empty($company->companyEmail)) {
                DB::table('company_emails')->insert([
                    'company_id' => $company->id,
                    'email' => $company->companyEmail,
                    'is_default' => true,
                    'is_notification_recipient' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_emails');
    }
};
