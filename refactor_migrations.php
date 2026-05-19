<?php

$dir = __DIR__ . '/database/migrations/schools';
$files = glob($dir . '/*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    $original = $content;
    
    // Pattern to match $table->dropUnique('name'); or $table->dropIndex('name');
    
    $content = preg_replace_callback('/\$table->dropUnique\([\'"]([a-zA-Z0-9_]+)[\'"]\);/', function($matches) {
        $name = $matches[1];
        return '$table->dropUnique(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . \'' . $name . '\');';
    }, $content);

    $content = preg_replace_callback('/\$table->dropIndex\([\'"]([a-zA-Z0-9_]+)[\'"]\);/', function($matches) {
        $name = $matches[1];
        return '$table->dropIndex(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . \'' . $name . '\');';
    }, $content);
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "Updated $file\n";
    }
}
echo "Done.\n";
