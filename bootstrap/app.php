<?php

use App\Http\Middleware\JwtAdminMiddleware;
use App\Http\Middleware\JwtAppAdminMiddleware;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
      web: __DIR__.'/../routes/web.php',
      apiPrefix: 'api/',
      api: __DIR__.'/../routes/api.php',
      commands: __DIR__.'/../routes/console.php',
      health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
      $middleware->appendToGroup('jwt', [JwtMiddleware::class]);
      $middleware->appendToGroup('jwt:admin', [JwtAdminMiddleware::class]);
      $middleware->appendToGroup('jwt:admin:app', [JwtAppAdminMiddleware::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
