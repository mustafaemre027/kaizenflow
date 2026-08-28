<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kaizen_benefits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kaizen_id')
                ->constrained('kaizens')
                ->cascadeOnDelete();

            $table->foreignId('benefit_type_id')
                ->constrained('benefit_types')
                ->restrictOnDelete();

            $table->decimal('expected_value', 15, 4)->nullable();
            $table->text('expected_note')->nullable();

            $table->decimal('realized_value', 15, 4)->nullable();
            $table->text('realized_note')->nullable();

            $table->timestamps();

            $table->unique(['kaizen_id', 'benefit_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kaizen_benefits');
    }
};
