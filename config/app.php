<?php
// config/app.php

return [
    'name' => 'Workspace',
    'env' => getenv('APP_ENV') ?: 'development',
    'debug' => getenv('APP_DEBUG') !== 'false',
    'url' => getenv('APP_URL') ?: 'http://localhost:8000',
    'storage' => [
        'base' => realpath(__DIR__ . '/../storage') ?: (__DIR__ . '/../storage'),
        'uploads' => realpath(__DIR__ . '/../storage/uploads') ?: (__DIR__ . '/../storage/uploads'),
        'exports' => realpath(__DIR__ . '/../storage/exports') ?: (__DIR__ . '/../storage/exports'),
        'temporary' => realpath(__DIR__ . '/../storage/temporary') ?: (__DIR__ . '/../storage/temporary'),
    ],
    'session' => [
        'name' => 'VAULT_SESSID',
        'lifetime' => 7200, // 2 hours
        'secure' => false, // Set true if HTTPS in production
        'httponly' => true,
        'samesite' => 'Lax',
    ],
    'limits' => [
        'max_upload_size' => 100 * 1024 * 1024, // 100 MB per file
        'default_user_quota' => 1024 * 1024 * 1024, // 1 GB
        'export_token_ttl' => 86400, // 24 hours
        'rate_limit_login' => 5, // 5 attempts per 15 minutes
        'rate_limit_decay' => 900,
    ]
];
