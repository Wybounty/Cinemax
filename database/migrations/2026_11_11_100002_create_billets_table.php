<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('ticket_code', 64)->unique();
            $table->string('status', 32)->default('valid');
            $table->dateTime('issued_at');
            $table->timestamps();

            $table->index(['reservation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billets');
    }
};
