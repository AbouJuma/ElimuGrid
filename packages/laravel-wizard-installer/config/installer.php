<?php

use dacoto\LaravelWizardInstaller\InstallHelper;

return [
    'icon' => '/images/default/icon.png',

    'background' => '/images/default/background.jpg',

    'support_url' => 'https://help.dacoto.com/',

    'server' => [
        'php' => [
            'name' => 'PHP Version',
            'version' => '>= 8.0.0',
            'check' => [InstallHelper::class, 'checkPhpVersion']
        ],
        'pdo' => [
            'name' => 'PDO',
            'check' => [InstallHelper::class, 'checkPdo']
        ],
        'mbstring' => [
            'name' => 'Mbstring extension',
            'check' => [InstallHelper::class, 'checkMbstring']
        ],
        'fileinfo' => [
            'name' => 'Fileinfo extension',
            'check' => [InstallHelper::class, 'checkFileinfo']
        ],
        'openssl' => [
            'name' => 'OpenSSL extension',
            'check' => [InstallHelper::class, 'checkOpenssl']
        ],
        'tokenizer' => [
            'name' => 'Tokenizer extension',
            'check' => [InstallHelper::class, 'checkTokenizer']
        ],
        'json' => [
            'name' => 'Json extension',
            'check' => [InstallHelper::class, 'checkJson']
        ],
        'curl' => [
            'name' => 'Curl extension',
            'check' => [InstallHelper::class, 'checkCurl']
        ]
    ],

    'folders' => [
        'storage.framework' => [
            'name' => base_path().DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'framework',
            'check' => [InstallHelper::class, 'checkStorageFramework']
        ],
        'storage.logs' => [
            'name' => base_path().DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'logs',
            'check' => [InstallHelper::class, 'checkStorageLogs']
        ],
        'storage.cache' => [
            'name' => base_path().DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'cache',
            'check' => [InstallHelper::class, 'checkBootstrapCache']
        ],
    ],

    'database' => [
        'seeders' => false
    ],

    'commands' => [
        'install:create-default-languages',
        'install:create-default-user-roles',
        'install:create-default-users',
        'install:create-settings-keys'
    ],

    'admin_area' => [
        'user' => [
            'email' => 'admin@admin.com',
            'password' => '12345678'
        ]
    ]
];
