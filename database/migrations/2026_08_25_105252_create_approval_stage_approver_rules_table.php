<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('approval_stage_approver_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_stage_id')->unique()->constrained('approval_stages')->restrictOnDelete();

            // We use string and add check constraints below for better cross-database compatibility with exact logic
            $table->string('capability');
            $table->string('scope_source');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add CHECK constraints for capability/scope-source combinations
        // SQLite and MySQL syntax compatible CHECK
        $checkConstraint = "
            (capability = 'kaizen.opex_review' AND scope_source = 'SYSTEM') OR
            (capability = 'kaizen.department_approve' AND scope_source = 'KAIZEN_DEPARTMENT') OR
            (capability = 'kaizen.board_approve' AND scope_source = 'SYSTEM')
        ";

        if (DB::getDriverName() === 'sqlite') {
            // SQLite does not support ALTER TABLE ADD CONSTRAINT CHECK for existing tables,
            // but we can create the table with the constraint if we write raw SQL.
            // Since Laravel schema builder doesn't let us inject arbitrary CHECK constraints on create table easily without DB::statement,
            // the safest cross-db way in Laravel 11 for SQLite is to rebuild the table or just use DB::statement for table creation.
            // Wait, Laravel 11 DOES support check constraints on table creation via raw expressions? No, but let's just do a hack-free approach: we use DB::statement for the CHECK constraint. Oh wait, SQLite ignores ALTER TABLE ADD CONSTRAINT CHECK.
            // Actually, in Laravel we can just do:
            // But I will drop and recreate it for sqlite with raw SQL? No, let's just use raw SQL for SQLite check.
            // Wait, we can't alter sqlite. Let's just create it with a check constraint in the Schema builder? No native way in Laravel Blueprint.

            // Let me drop the table and recreate it with raw SQL for SQLite to ensure it works perfectly for tests.
            Schema::dropIfExists('approval_stage_approver_rules');
            DB::statement("
                CREATE TABLE approval_stage_approver_rules (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    approval_stage_id INTEGER NOT NULL UNIQUE,
                    capability VARCHAR(255) NOT NULL,
                    scope_source VARCHAR(255) NOT NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at DATETIME,
                    updated_at DATETIME,
                    CONSTRAINT fk_rule_stage FOREIGN KEY (approval_stage_id) REFERENCES approval_stages(id) ON DELETE RESTRICT,
                    CONSTRAINT rule_capability_scope_check CHECK ($checkConstraint)
                )
            ");
        } else {
            DB::statement("ALTER TABLE approval_stage_approver_rules ADD CONSTRAINT rule_capability_scope_check CHECK ($checkConstraint)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_stage_approver_rules');
    }
};
