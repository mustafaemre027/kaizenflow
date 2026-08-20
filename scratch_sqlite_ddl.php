<?php

Artisan::call('migrate:fresh');
echo json_encode(DB::select("SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name IN ('user_system_capability_grants', 'user_capability_grants')"));
