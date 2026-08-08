<?php

namespace App\Http\Middleware;

use App\Models\OrganizationalUnit;
use App\Services\OrganizationalScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationalScope
{
    public function __construct(private readonly OrganizationalScope $scope) {}

    public function handle(Request $request, Closure $next, string $parameter = 'organizationalUnit'): Response
    {
        $unit = $request->route($parameter);
        $unit = $unit instanceof OrganizationalUnit ? $unit : OrganizationalUnit::query()->findOrFail($unit);

        abort_unless($request->user() && $this->scope->allows($request->user(), $unit), 403);

        return $next($request);
    }
}
