<?php

Artisan::call('migrate:fresh');
Artisan::call('migrate:rollback', ['--step' => 2]);
echo json_encode(DB::select("SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name IN ('user_capability_grants', 'user_system_capability_grants')"))."\n";
