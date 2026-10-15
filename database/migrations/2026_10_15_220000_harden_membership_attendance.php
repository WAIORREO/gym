<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('frozen_at')->nullable();
            $table->foreignId('renewed_from_id')->nullable()->unique()->constrained('subscriptions');
        });
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions');
            $table->index(['user_id','branch_id','checked_in_at'], 'attendance_member_branch_time');
        });
    }
    public function down() {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
            $table->dropIndex('attendance_member_branch_time');
            $table->dropColumn('subscription_id');
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['renewed_from_id']);
            $table->dropUnique(['renewed_from_id']);
            $table->dropColumn(['renewed_from_id', 'frozen_at']);
        });
    }
};
