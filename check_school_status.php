<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$school = DB::table('schools')->where('code', 'SCH202656')->first();

if ($school) {
    echo 'School ID: ' . $school->id . PHP_EOL;
    echo 'School Status: ' . $school->status . PHP_EOL;
    echo 'School Code: ' . $school->code . PHP_EOL;
} else {
    echo 'School not found with code SCH202656' . PHP_EOL;
}
