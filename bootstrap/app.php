<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->isMethod('GET')
                && ! $request->expectsJson()
                && ! $request->is('livewire/*')) {
                return redirect()->to(auth()->check() ? '/dashboard' : '/dashboard/login');
            }

            return null;
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return redirect()->guest('/dashboard/login');
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof NotFoundHttpException || $e instanceof AuthenticationException) {
                return null;
            }
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                return null;
            }
            if ($request->expectsJson() || $request->is('livewire/*')) {
                return null;
            }

            if (app()->environment('production')) {
                return response()->view('errors.500', [], 500);
            }

            return null;
        });
    })->create();
