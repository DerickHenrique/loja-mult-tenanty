<?php

return [
    'name' => 'Demonstração da Plataforma',
    'env' => 'local',
    'debug' => false,
    'url' => 'http://teste1.local',
    'timezone' => 'America/Sao_Paulo',
    'locale' => 'pt_BR',
    'fallback_locale' => 'pt_BR',
    'faker_locale' => 'pt_BR',
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [],
    'maintenance' => ['driver' => 'file', 'store' => 'database'],
];
