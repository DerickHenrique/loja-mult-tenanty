<?php

return [
    'default' => 'single',
    'deprecations' => ['channel' => 'null', 'trace' => false],
    'channels' => [
        'single' => ['driver' => 'single', 'path' => storage_path('logs/laravel.log'), 'level' => 'debug', 'replace_placeholders' => true],
        'null' => ['driver' => 'monolog', 'handler' => Monolog\Handler\NullHandler::class],
    ],
];
