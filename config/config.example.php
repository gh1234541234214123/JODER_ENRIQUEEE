<?php
// Copy this file to config/config.php and fill in real values.
// config/config.php is gitignored and must NEVER be committed.
//
// The correct block is selected automatically in config/bootstrap.php based on
// the request host (localhost/127.0.0.1 -> 'local', anything else -> 'production').

return [
    'local' => [
        'db' => [
            'host' => 'localhost',
            'name' => 'joder',
            'user' => 'root',
            'pass' => '',
        ],
        'debug' => true,
        'site_name' => 'JODER_ENRIQUEEE',
        'session_driver' => 'database', // 'database' (needs the `sesion` table) or 'file'
    ],

    'production' => [
        'db' => [
            // InfinityFree: host looks like sqlXXX.infinityfree.com,
            // name/user are prefixed, e.g. if0_12345678_joder / if0_12345678
            'host' => 'sqlXXX.infinityfree.com',
            'name' => 'if0_XXXXXXXX_joder',
            'user' => 'if0_XXXXXXXX',
            'pass' => 'YOUR_REAL_PASSWORD',
        ],
        'debug' => false,
        'site_name' => 'JODER_ENRIQUEEE',
        'session_driver' => 'database', // 'database' or 'file'
    ],
];
