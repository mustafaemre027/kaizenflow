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
        Schema::create('kaizens', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();

            $table->foreignId('creator_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('department_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('category_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('assigned_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('title');
            $table->text('current_situation');
            $table->text('proposed_situation');
            $table->text('expected_benefit');

            $table->text('actual_result')->nullable();
            $table->text('realized_benefit')->nullable();

            $table->string('status')->default('DRAFT')->index();
            $table->string('priority')->nullable();
            $table->date('target_date')->nullable()->index();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->timestamps();

            $table->index('created_at');
            $table->index(['status', 'department_id']);
            $table->index(['creator_user_id', 'created_at']);
            $table->index(['assigned_user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kaizens');
    }
};
