<?php

use App\Exceptions\DomainRuleViolation;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsureOrganizationalScope;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\RequestId;
use App\Support\ApiProblem;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->append(RequestId::class);
        $middleware->alias([
            'active' => EnsureActiveUser::class,
            'guest' => RedirectIfAuthenticated::class,
            'org.scope' => EnsureOrganizationalScope::class,
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => $request->is('api/*'));

        $exceptions->render(function (DomainRuleViolation $exception, Request $request) {
            return $request->is('api/*')
                ? ApiProblem::response($request, $exception->status, $exception->ruleCode, 'Permintaan tidak dapat diproses', $exception->getMessage(), [], $exception->headers)
                : null;
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $errors = collect($exception->errors())->flatMap(fn (array $messages, string $field) => collect($messages)->map(fn (string $message) => [
                'pointer' => '/'.str_replace('.', '/', $field),
                'code' => 'validation',
                'detail' => $message,
            ]))->values()->all();

            return ApiProblem::response($request, 422, 'validation_failed', 'Validasi gagal', 'Periksa field yang ditandai.', $errors);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            return $request->is('api/*')
                ? ApiProblem::response($request, 401, 'unauthenticated', 'Autentikasi diperlukan', 'Sesi tidak tersedia atau sudah berakhir.')
                : null;
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            return $request->is('api/*')
                ? ApiProblem::response($request, 403, 'forbidden', 'Akses ditolak', 'Anda tidak memiliki izin untuk tindakan ini.')
                : null;
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            return $request->is('api/*')
                ? ApiProblem::response($request, 404, 'not_found', 'Data tidak ditemukan', 'Resource tidak tersedia dalam scope Anda.')
                : null;
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->is('api/*') || ! in_array($exception->getStatusCode(), [403, 404, 429], true)) {
                return null;
            }

            return ApiProblem::response(
                $request,
                $exception->getStatusCode(),
                match ($exception->getStatusCode()) {
                    403 => 'forbidden', 404 => 'not_found', 429 => 'rate_limited'
                },
                match ($exception->getStatusCode()) {
                    403 => 'Akses ditolak', 404 => 'Data tidak ditemukan', 429 => 'Terlalu banyak permintaan'
                },
                match ($exception->getStatusCode()) {
                    403 => 'Anda tidak memiliki izin untuk tindakan ini.', 404 => 'Resource tidak tersedia dalam scope Anda.', 429 => 'Coba kembali setelah jeda singkat.'
                },
            );
        });
    })->create();
