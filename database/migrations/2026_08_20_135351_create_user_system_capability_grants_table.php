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
        $driver = \Illuminate\Support\Facades\DB::getDriverName();

        if ($driver === 'sqlite') {
            \Illuminate\Support\Facades\DB::statement("
                CREATE TABLE user_system_capability_grants (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    capability VARCHAR(255) NOT NULL,
                    granted_by_user_id INTEGER,
                    is_active TINYINT(1) DEFAULT 1 NOT NULL,
                    created_at DATETIME,
                    updated_at DATETIME,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
                    FOREIGN KEY (granted_by_user_id) REFERENCES users(id) ON DELETE RESTRICT,
                    CONSTRAINT user_system_capability_grants_user_id_capability_unique UNIQUE (user_id, capability),
                    CONSTRAINT chk_user_system_capability_scope CHECK (capability IN (
                        'organization.view',
                        'organization.manage',
                        'approval_configuration.view',
                        'approval_configuration.manage',
                        'authorization.manage'
                    ))
                )
            ");
        } else {
            Schema::create('user_system_capability_grants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                $table->string('capability');
                $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['user_id', 'capability']);
            });

            \Illuminate\Support\Facades\DB::statement("
                ALTER TABLE user_system_capability_grants 
                ADD CONSTRAINT chk_user_system_capability_scope 
                CHECK (capability IN (
                    'organization.view',
                    'organization.manage',
                    'approval_configuration.view',
                    'approval_configuration.manage',
                    'authorization.manage'
                ))
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_system_capability_grants');
    }
};
