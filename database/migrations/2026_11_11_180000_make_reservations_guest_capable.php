<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('guest_email')->nullable()->after('contact_email');
            $table->timestamp('tickets_email_sent_at')->nullable()->after('expires_at');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['guest_email', 'tickets_email_sent_at']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }
};
