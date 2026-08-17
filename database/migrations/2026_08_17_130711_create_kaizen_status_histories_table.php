<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kaizen_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kaizen_id')
                ->constrained('kaizens')
                ->restrictOnDelete();

            $table->foreignId('actor_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('transition_code', 60);
            $table->string('from_status', 40);
            $table->string('to_status', 40);

            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['kaizen_id', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kaizen_status_histories');
    }
};
