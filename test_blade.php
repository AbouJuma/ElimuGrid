<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Blade Directive ===\n\n";

// Get the Blade compiler
$blade = app('blade.compiler');

// Test the directive
$code = $blade->compileString('@hasFeatureAccess("Transport Management")');
echo "Compiled output:\n";
echo $code . "\n";

// Check if it contains PHP echo (runtime)
if (strpos($code, '<?php echo') !== false) {
    echo "\n✓ Directive now outputs PHP for runtime evaluation!\n";
} else {
    echo "\n✗ Directive still returns static value\n";
}

echo "\n=== Done ===\n";
