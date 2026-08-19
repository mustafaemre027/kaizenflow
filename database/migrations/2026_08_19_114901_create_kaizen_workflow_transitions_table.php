<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kaizen_workflow_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kaizen_workflow_instance_id')->constrained()->restrictOnDelete();
            $table->foreignId('kaizen_id')->constrained()->restrictOnDelete();
            $table->foreignId('from_stage_id')->nullable()->constrained('approval_stages')->restrictOnDelete();
            $table->foreignId('to_stage_id')->nullable()->constrained('approval_stages')->restrictOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('action');
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kaizen_workflow_transitions');
    }
};
