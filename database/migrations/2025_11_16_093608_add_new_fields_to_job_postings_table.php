<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('company_jobs', 'administrative_position_id')) {
                $table->unsignedBigInteger('administrative_position_id')->nullable()->after('position_id');
            }

            if (!Schema::hasColumn('company_jobs', 'real_position')) {
                $table->string('real_position')->nullable()->after('administrative_position_id');
            }

            if (!Schema::hasColumn('company_jobs', 'country_id')) {
                $table->unsignedBigInteger('country_id')->nullable()->after('countryOfOrigin');
            }
        });

        // Додади foreign keys во посебен блок - користи try/catch за да се справиме со веќе постоечки FK
        try {
            Schema::table('company_jobs', function (Blueprint $table) {
                if (Schema::hasColumn('company_jobs', 'administrative_position_id')) {
                    $table->foreign('administrative_position_id', 'company_jobs_admin_pos_fk')
                          ->references('id')
                          ->on('administrative_positions')
                          ->onDelete('set null');
                }
            });
        } catch (\Exception $e) {
            // Foreign key веќе постои, продолжи
        }

        try {
            Schema::table('company_jobs', function (Blueprint $table) {
                if (Schema::hasColumn('company_jobs', 'country_id')) {
                    $table->foreign('country_id', 'company_jobs_country_fk')
                          ->references('id')
                          ->on('countries')
                          ->onDelete('set null');
                }
            });
        } catch (\Exception $e) {
            // Foreign key веќе постои, продолжи
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_jobs', function (Blueprint $table) {
            // Избриши foreign keys - користи try/catch за безбедност
            try {
                $table->dropForeign('company_jobs_admin_pos_fk');
            } catch (\Exception $e) {
                // Foreign key не постои, продолжи
            }

            try {
                $table->dropForeign('company_jobs_country_fk');
            } catch (\Exception $e) {
                // Foreign key не постои, продолжи
            }

            // Провери и избриши колони
            if (Schema::hasColumn('company_jobs', 'administrative_position_id')) {
                $table->dropColumn('administrative_position_id');
            }
            if (Schema::hasColumn('company_jobs', 'real_position')) {
                $table->dropColumn('real_position');
            }
            // Не го бришеме country_id бидејќи можеби постои од порано
            // if (Schema::hasColumn('company_jobs', 'country_id')) {
            //     $table->dropColumn('country_id');
            // }
        });
    }
};
