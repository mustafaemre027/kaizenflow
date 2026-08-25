<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$res = DB::select("SELECT AUTO_INCREMENT FROM information_schema.tables WHERE table_name = 'approval_workflows' AND table_schema = DATABASE()");
echo $res[0]->AUTO_INCREMENT;
