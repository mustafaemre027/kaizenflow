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
        Schema::table('kaizens', function (Blueprint $table) {
            $table->text('expected_benefit')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kaizens', function (Blueprint $table) {
            $table->text('expected_benefit')->nullable(false)->change();
        });
    }
};
