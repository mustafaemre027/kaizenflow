<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_workflow_id')->constrained()->restrictOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedInteger('sequence');
            $table->boolean('is_final')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['approval_workflow_id', 'code']);
            $table->unique(['approval_workflow_id', 'sequence']);

            $table->index(['approval_workflow_id', 'is_active', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_stages');
    }
};
