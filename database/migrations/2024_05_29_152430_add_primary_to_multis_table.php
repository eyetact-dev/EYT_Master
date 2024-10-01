<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('multis', function (Blueprint $table) {

            $table->text('primary')->nullable();
            $table->text('secondary')->nullable();
            $table->text('fixed_value')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('multis', function (Blueprint $table) {
            //
        });
    }
};
