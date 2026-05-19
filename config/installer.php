<?php

use dacoto\LaravelWizardInstaller\InstallHelper;

return [
    'icon' => 'assets/horizontal-logo.svg',

    //    'background' => 'assets/logo.svg',

    'support_url' => 'https://join.skype.com/invite/xWFoykc1gnN6',

    'server' => [
        'php'       => [
            'name'    => 'PHP Version',
            'version' => '>= 8.1.0',
            'check'   => [InstallHelper::class, 'checkPhpVersion']
        ],
        'pdo'       => [
            'name'  => 'PDO',
            'check' => [InstallHelper::class, 'checkPdo']
        ],
        'mbstring'  => [
            'name'  => 'Mbstring extension',
            'check' => [InstallHelper::class, 'checkMbstring']
        ],
        'fileinfo'  => [
            'name'  => 'Fileinfo extension',
            'check' => [InstallHelper::class, 'checkFileinfo']
        ],
        'openssl'   => [
            'name'  => 'OpenSSL extension',
            'check' => [InstallHelper::class, 'checkOpenssl']
        ],
        'tokenizer' => [
            'name'  => 'Tokenizer extension',
            'check' => [InstallHelper::class, 'checkTokenizer']
        ],
        'json'      => [
            'name'  => 'Json extension',
            'check' => [InstallHelper::class, 'checkJson']
        ],
        'curl'      => [
            'name'  => 'Curl extension',
            'check' => [InstallHelper::class, 'checkCurl']
        ],
        'zip'       => [
            'name'  => 'Zip extension',
            'check' => [InstallHelper::class, 'checkZip']
        ]
    ],

    'folders' => [
        'storage.framework' => [
            'name'  => base_path() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework',
            'check' => [InstallHelper::class, 'checkStorageFramework']
        ],
        'storage.logs'      => [
            'name'  => base_path() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs',
            'check' => [InstallHelper::class, 'checkStorageLogs']
        ],
        'storage.cache'     => [
            'name'  => base_path() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache',
            'check' => [InstallHelper::class, 'checkBootstrapCache']
        ],
    ],

    'database' => [
        'seeders' => false
    ],

    'commands' => [
        'db:seed --class=InstallationSeeder',
        'db:seed --class=AddSuperAdminSeeder',
    ],

    'admin_area' => [
        'user' => [
            'email'    => 'superadmin@gmail.com',
            'password' => 'superadmin'
        ]
    ]
];
