<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

final class ResolveLocalTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());
        $file = config('tenants.'.$host);
        if (!is_string($file) || !is_file($file)) {
            return response('<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Domínio não configurado</title><body style="font:16px system-ui;max-width:42rem;margin:12vh auto;padding:1rem;color:#263238"><h1>Este domínio não está configurado</h1><p>O endereço solicitado não está na lista local de lojas autorizadas.</p></body></html>', 404, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        $tenant = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($tenant)
            || !preg_match('/^[a-z0-9_-]+$/', $tenant['id'] ?? '')
            || !is_string($tenant['name'] ?? null)
            || !is_array($tenant['theme'] ?? null)
            || !is_array($tenant['content'] ?? null)) {
            abort(500, 'Configuração local de tenant inválida.');
        }
        foreach (['primary', 'accent', 'surface'] as $color) {
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $tenant['theme'][$color] ?? '')) {
                abort(500, 'Tema local inválido.');
            }
        }
        foreach (['label'] as $field) {
            if (!is_string($tenant['theme'][$field] ?? null)) {
                abort(500, 'Tema local inválido.');
            }
        }
        foreach (['eyebrow', 'title', 'description', 'body'] as $field) {
            if (!is_string($tenant['content'][$field] ?? null)) {
                abort(500, 'Conteúdo local inválido.');
            }
        }
        if (!is_string($tenant['brand_mark'] ?? null)) {
            abort(500, 'Marca local inválida.');
        }
        $runtime = '/var/www/tenants/'.$tenant['id'].'/runtime';
        $data = '/var/www/tenants/'.$tenant['id'].'/data';
        foreach ([$runtime.'/cache', $runtime.'/sessions', $runtime.'/views', $runtime.'/logs', $runtime.'/tmp', $data] as $dir) {
            if (!is_dir($dir)) { mkdir($dir, 0775, true); }
        }
        ini_set('sys_temp_dir', $runtime.'/tmp');

        app()->useStoragePath($runtime);
        config([
            'database.connections.tenant.database' => $data.'/store.sqlite',
            'session.files' => $runtime.'/sessions',
            'session.cookie' => 'demo_'.$tenant['id'].'_session',
            'cache.stores.file.path' => $runtime.'/cache',
            'cache.stores.file.lock_path' => $runtime.'/cache',
            'view.compiled' => $runtime.'/views',
            'logging.channels.single.path' => $runtime.'/logs/laravel.log',
        ]);

        $request->attributes->set('tenant', $tenant);
        View::share('tenant', $tenant);
        return $next($request);
    }
}
