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
        Schema::table('company_emails', function (Blueprint $table) {
            if (!Schema::hasColumn('company_emails', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('email');
            }
            if (!Schema::hasColumn('company_emails', 'is_notification_recipient')) {
                $table->boolean('is_notification_recipient')->default(false)->after('is_default');
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
        Schema::table('company_emails', function (Blueprint $table) {
            if (Schema::hasColumn('company_emails', 'is_default')) {
                $table->dropColumn('is_default');
            }
            if (Schema::hasColumn('company_emails', 'is_notification_recipient')) {
                $table->dropColumn('is_notification_recipient');
            }
        });
    }
};
