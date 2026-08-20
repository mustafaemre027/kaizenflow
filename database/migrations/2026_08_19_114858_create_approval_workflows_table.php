<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedInteger('version');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['code', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_workflows');
    }
};
