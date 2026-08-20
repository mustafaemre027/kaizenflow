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
        Schema::create('user_capability_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('department_id')->constrained('departments');
            $table->string('capability');
            $table->boolean('is_active')->default(true);
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['user_id', 'department_id', 'capability'], 'user_dept_cap_unique');
            $table->index(['capability', 'department_id', 'is_active'], 'cap_dept_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_capability_grants');
    }
};
