<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_stage_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_stage_id')->constrained()->restrictOnDelete();
            $table->foreignId('approval_group_id')->constrained()->restrictOnDelete();
            $table->string('scope')->default('GLOBAL'); // GLOBAL or DEPARTMENT
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['approval_stage_id', 'approval_group_id'], 'stage_group_unique');
            $table->index(['approval_stage_id', 'is_active'], 'stage_assignment_stage_active');
            $table->index(['approval_group_id', 'is_active'], 'stage_assignment_group_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_stage_assignments');
    }
};
