<?php
$files = [
    'b:\Projects PHP\School Project\elimuGrid\elimuGrid\database\migrations\schools\2024_07_21_093657_version1_4_0.php',
    'b:\Projects PHP\School Project\elimuGrid\elimuGrid\database\migrations\schools\2024_11_14_125437_version_1_5_0.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "Fixing $file...\n";
        $content = file_get_contents($file);
        $content = str_replace('\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . ', '', $content);
        file_put_contents($file, $content);
    } else {
        echo "File not found: $file\n";
    }
}
