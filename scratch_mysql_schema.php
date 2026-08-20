<?php

echo 'DB: '.json_encode(DB::select('SELECT DATABASE() AS d')[0]->d)."\n";
echo 'Create 1: '.json_encode(DB::select('SHOW CREATE TABLE user_capability_grants'))."\n";
echo 'Create 2: '.json_encode(DB::select('SHOW CREATE TABLE user_system_capability_grants'))."\n";
echo 'Table Constraints: '.json_encode(DB::select("SELECT CONSTRAINT_NAME, CONSTRAINT_TYPE FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = 'kaizenflow_test' AND TABLE_NAME IN ('user_capability_grants', 'user_system_capability_grants')"))."\n";
echo 'Ref Constraints: '.json_encode(DB::select("SELECT CONSTRAINT_NAME, UPDATE_RULE, DELETE_RULE, TABLE_NAME, REFERENCED_TABLE_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = 'kaizenflow_test' AND TABLE_NAME IN ('user_capability_grants', 'user_system_capability_grants')"))."\n";
echo 'Stats: '.json_encode(DB::select("SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = 'kaizenflow_test' AND TABLE_NAME IN ('user_capability_grants', 'user_system_capability_grants')"))."\n";
