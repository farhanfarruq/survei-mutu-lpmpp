<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated extends Middleware
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        if ($request->is('api/*')) {
            foreach ($guards ?: [null] as $guard) {
                if (Auth::guard($guard)->check()) {
                    return response()->noContent();
                }
            }
        }

        return parent::handle($request, $next, ...$guards);
    }
}
