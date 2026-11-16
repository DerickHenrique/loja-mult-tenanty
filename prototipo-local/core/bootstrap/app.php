<?php

use App\Http\Middleware\ResolveLocalTenant;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prependToGroup('web', ResolveLocalTenant::class);
        $middleware->appendToGroup('web', HandleInertiaRequests::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // O tratamento padrão do Laravel é suficiente para a demonstração.
    })
    ->create();
