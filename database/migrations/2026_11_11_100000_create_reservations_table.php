<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('seance_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('reference', 64)->unique();
            $table->unsignedSmallInteger('quantity');
            $table->decimal('total_amount', 10, 2);
            $table->string('status', 32)->default('pending');
            $table->dateTime('reserved_at');
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();

            $table->index(['seance_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
