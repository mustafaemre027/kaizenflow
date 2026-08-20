<?php

Artisan::call('migrate:fresh');
echo json_encode(DB::select("SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name IN ('user_capability_grants', 'user_system_capability_grants')"))."\n";
echo json_encode(DB::select("PRAGMA table_info('user_capability_grants')"))."\n";
echo json_encode(DB::select("PRAGMA foreign_key_list('user_capability_grants')"))."\n";
echo json_encode(DB::select("PRAGMA index_list('user_capability_grants')"))."\n";
echo json_encode(DB::select("PRAGMA index_info('user_dept_cap_unique')"))."\n";
echo json_encode(DB::select("PRAGMA index_info('cap_dept_active_idx')"))."\n";
echo json_encode(DB::select("PRAGMA table_info('user_system_capability_grants')"))."\n";
echo json_encode(DB::select("PRAGMA foreign_key_list('user_system_capability_grants')"))."\n";
echo json_encode(DB::select("PRAGMA index_list('user_system_capability_grants')"))."\n";
echo json_encode(DB::select("PRAGMA index_info('user_system_capability_grants_user_id_capability_unique')"))."\n";
