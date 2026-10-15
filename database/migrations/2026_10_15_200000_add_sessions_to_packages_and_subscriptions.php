<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('packages')) {
            Schema::table('packages', function (Blueprint $table) {
                if (!Schema::hasColumn('packages', 'sessions')) {
                    $table->integer('sessions')->nullable()->after('cycle_id')->comment('Number of sessions or null for unlimited');
                }
            });
        }

        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (!Schema::hasColumn('subscriptions', 'total_sessions')) {
                    $table->integer('total_sessions')->nullable()->after('interval')->comment('Total allowed sessions');
                }
                if (!Schema::hasColumn('subscriptions', 'remaining_sessions')) {
                    $table->integer('remaining_sessions')->nullable()->after('total_sessions')->comment('Remaining sessions left to attend');
                }
                if (!Schema::hasColumn('subscriptions', 'sessions_used')) {
                    $table->integer('sessions_used')->default(0)->after('remaining_sessions')->comment('Number of attended sessions');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('packages')) {
            Schema::table('packages', function (Blueprint $table) {
                if (Schema::hasColumn('packages', 'sessions')) {
                    $table->dropColumn('sessions');
                }
            });
        }

        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $columns = array_filter(['total_sessions', 'remaining_sessions', 'sessions_used'], function ($col) {
                    return Schema::hasColumn('subscriptions', $col);
                });
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
