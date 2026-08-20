<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kaizen_workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kaizen_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('approval_workflow_id')->constrained()->restrictOnDelete();
            $table->foreignId('current_stage_id')->nullable()->constrained('approval_stages')->restrictOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kaizen_workflow_instances');
    }
};
