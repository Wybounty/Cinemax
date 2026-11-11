<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('method', 32)->default('simulation');
            $table->string('status', 32)->default('pending');
            $table->string('transaction_reference', 64)->nullable()->unique();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();

            $table->index(['reservation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
