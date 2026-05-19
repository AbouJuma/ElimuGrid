<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (DB::table('schools')->get() as $s) {
    echo $s->id . ' | ' . $s->database_name . PHP_EOL;
}
