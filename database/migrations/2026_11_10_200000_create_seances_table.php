<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('film_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('salle_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->decimal('ticket_price', 8, 2);
            $table->string('status', 32)->default('scheduled');
            $table->timestamps();

            $table->index(['salle_id', 'start_at']);
            $table->index(['film_id', 'start_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seances');
    }
};
