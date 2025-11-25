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

        // Додади foreign keys во посебен блок (само ако колоните постојат)
        Schema::table('company_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('company_jobs', 'administrative_position_id')) {
                // Провери дали foreign key веќе постои
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $foreignKeys = $sm->listTableForeignKeys('company_jobs');
                $fkExists = false;
                foreach ($foreignKeys as $foreignKey) {
                    if ($foreignKey->getName() === 'company_jobs_admin_pos_fk') {
                        $fkExists = true;
                        break;
                    }
                }

                if (!$fkExists) {
                    $table->foreign('administrative_position_id', 'company_jobs_admin_pos_fk')
                          ->references('id')
                          ->on('administrative_positions')
                          ->onDelete('set null');
                }
            }

            if (Schema::hasColumn('company_jobs', 'country_id')) {
                // Провери дали foreign key веќе постои
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $foreignKeys = $sm->listTableForeignKeys('company_jobs');
                $fkExists = false;
                foreach ($foreignKeys as $foreignKey) {
                    if ($foreignKey->getName() === 'company_jobs_country_fk') {
                        $fkExists = true;
                        break;
                    }
                }

                if (!$fkExists) {
                    $table->foreign('country_id', 'company_jobs_country_fk')
                          ->references('id')
                          ->on('countries')
                          ->onDelete('set null');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_jobs', function (Blueprint $table) {
            // Провери и избриши foreign keys
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $foreignKeys = $sm->listTableForeignKeys('company_jobs');

            foreach ($foreignKeys as $foreignKey) {
                if ($foreignKey->getName() === 'company_jobs_admin_pos_fk') {
                    $table->dropForeign('company_jobs_admin_pos_fk');
                }
                if ($foreignKey->getName() === 'company_jobs_country_fk') {
                    $table->dropForeign('company_jobs_country_fk');
                }
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
