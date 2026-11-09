<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('salles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('capacity')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement(
                'CREATE UNIQUE INDEX salles_name_active_unique ON salles (name) WHERE deleted_at IS NULL',
            );
        }

        if ($driver === 'mysql') {
            DB::statement(
                'ALTER TABLE salles ADD active_name VARCHAR(255) GENERATED ALWAYS AS (IF(deleted_at IS NULL, `name`, NULL)) STORED',
            );
            DB::statement(
                'CREATE UNIQUE INDEX salles_active_name_unique ON salles (active_name)',
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS salles_name_active_unique');
        }

        if ($driver === 'mysql') {
            DB::statement('DROP INDEX salles_active_name_unique ON salles');
            DB::statement('ALTER TABLE salles DROP COLUMN active_name');
        }

        Schema::dropIfExists('salles');
    }
};
