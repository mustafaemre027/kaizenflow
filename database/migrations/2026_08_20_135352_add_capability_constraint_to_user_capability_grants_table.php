<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement("
                CREATE TABLE user_capability_grants_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    department_id INTEGER NOT NULL,
                    capability VARCHAR(255) NOT NULL,
                    is_active TINYINT(1) DEFAULT 1 NOT NULL,
                    granted_by_user_id INTEGER,
                    created_at DATETIME,
                    updated_at DATETIME,
                    FOREIGN KEY (user_id) REFERENCES users(id),
                    FOREIGN KEY (department_id) REFERENCES departments(id),
                    FOREIGN KEY (granted_by_user_id) REFERENCES users(id),
                    CONSTRAINT user_dept_cap_unique UNIQUE (user_id, department_id, capability),
                    CONSTRAINT chk_user_department_capability_scope CHECK (capability IN (
                        'kaizen.implementation.assign',
                        'kaizen.implementation.start',
                        'kaizen.implementation.complete'
                    ))
                )
            ");

            DB::statement('INSERT INTO user_capability_grants_new SELECT * FROM user_capability_grants');
            DB::statement('DROP TABLE user_capability_grants');
            DB::statement('ALTER TABLE user_capability_grants_new RENAME TO user_capability_grants');
            DB::statement('CREATE INDEX cap_dept_active_idx ON user_capability_grants (capability, department_id, is_active)');
        } else {
            DB::statement("
                ALTER TABLE user_capability_grants 
                ADD CONSTRAINT chk_user_department_capability_scope 
                CHECK (capability IN (
                    'kaizen.implementation.assign',
                    'kaizen.implementation.start',
                    'kaizen.implementation.complete'
                ))
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('
                CREATE TABLE user_capability_grants_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    department_id INTEGER NOT NULL,
                    capability VARCHAR(255) NOT NULL,
                    is_active TINYINT(1) DEFAULT 1 NOT NULL,
                    granted_by_user_id INTEGER,
                    created_at DATETIME,
                    updated_at DATETIME,
                    FOREIGN KEY (user_id) REFERENCES users(id),
                    FOREIGN KEY (department_id) REFERENCES departments(id),
                    FOREIGN KEY (granted_by_user_id) REFERENCES users(id),
                    CONSTRAINT user_dept_cap_unique UNIQUE (user_id, department_id, capability)
                )
            ');

            DB::statement('INSERT INTO user_capability_grants_new SELECT * FROM user_capability_grants');
            DB::statement('DROP TABLE user_capability_grants');
            DB::statement('ALTER TABLE user_capability_grants_new RENAME TO user_capability_grants');
            DB::statement('CREATE INDEX cap_dept_active_idx ON user_capability_grants (capability, department_id, is_active)');
        } else {
            DB::statement('ALTER TABLE user_capability_grants DROP CONSTRAINT chk_user_department_capability_scope');
        }
    }
};
