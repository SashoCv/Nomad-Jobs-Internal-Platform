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
        Schema::table('candidates', function (Blueprint $table) {
            // Physical info
            if (!Schema::hasColumn('candidates', 'height')) {
                $table->integer('height')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('candidates', 'weight')) {
                $table->integer('weight')->nullable()->after('height');
            }

            // Health
            if (!Schema::hasColumn('candidates', 'chronic_diseases')) {
                $table->text('chronic_diseases')->nullable()->after('weight');
            }

            // Visa
            if (!Schema::hasColumn('candidates', 'country_of_visa_application')) {
                $table->string('country_of_visa_application')->nullable()->after('chronic_diseases');
            }

            // Driving license
            if (!Schema::hasColumn('candidates', 'has_driving_license')) {
                $table->boolean('has_driving_license')->default(false)->after('country_of_visa_application');
            }
            if (!Schema::hasColumn('candidates', 'driving_license_category')) {
                $table->string('driving_license_category')->nullable()->after('has_driving_license');
            }
            if (!Schema::hasColumn('candidates', 'driving_license_expiry')) {
                $table->date('driving_license_expiry')->nullable()->after('driving_license_category');
            }
            if (!Schema::hasColumn('candidates', 'driving_license_country')) {
                $table->string('driving_license_country')->nullable()->after('driving_license_expiry');
            }

            // Languages
            if (!Schema::hasColumn('candidates', 'english_level')) {
                $table->enum('english_level', ['none', 'elementary', 'average', 'advanced'])->default('none')->after('driving_license_country');
            }
            if (!Schema::hasColumn('candidates', 'russian_level')) {
                $table->enum('russian_level', ['none', 'elementary', 'average', 'advanced'])->default('none')->after('english_level');
            }
            if (!Schema::hasColumn('candidates', 'other_language')) {
                $table->string('other_language')->nullable()->after('russian_level');
            }
            if (!Schema::hasColumn('candidates', 'other_language_level')) {
                $table->enum('other_language_level', ['none', 'elementary', 'average', 'advanced'])->nullable()->after('other_language');
            }

            // Children info (for marital status)
            if (!Schema::hasColumn('candidates', 'children_info')) {
                $table->string('children_info')->nullable();
            }
        });

        // Add responsibilities to experiences table
        Schema::table('experiences', function (Blueprint $table) {
            if (!Schema::hasColumn('experiences', 'responsibilities')) {
                $table->text('responsibilities')->nullable()->after('position');
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
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn([
                'height',
                'weight',
                'chronic_diseases',
                'country_of_visa_application',
                'has_driving_license',
                'driving_license_category',
                'driving_license_expiry',
                'driving_license_country',
                'english_level',
                'russian_level',
                'other_language',
                'other_language_level',
                'children_info',
            ]);
        });

        Schema::table('experiences', function (Blueprint $table) {
            $table->dropColumn('responsibilities');
        });
    }
};
