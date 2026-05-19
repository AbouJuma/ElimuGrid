<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\School;

echo "Total Schools: " . School::count() . "\n";
foreach (School::all() as $school) {
    echo "ID: {$school->id}, Name: {$school->name}, Status: {$school->status}\n";
}
