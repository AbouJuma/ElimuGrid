<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    |
    | Intervention Image supports "GD Library" and "Imagick" to process images
    | internally. You may choose one of them according to your PHP
    | configuration. By default PHP's "GD Library" implementation is used.
    |
    | Supported: "gd", "imagick"
    |
    */
    'driver' => 'gd', // Force GD driver to avoid imagick issues
    // Disable intervention auto-orientation to prevent network calls
    'auto_orientation' => false,
    // Disable intervention auto-optimization to prevent network calls  
    'auto_optimize' => false,
    // Configure GD to use less memory
    'gd' => [
        'jpeg_quality' => 75,
        'png_compression_level' => 6
    ]

];
