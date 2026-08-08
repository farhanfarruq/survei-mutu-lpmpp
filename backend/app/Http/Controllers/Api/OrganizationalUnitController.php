<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationalUnitResource;
use App\Models\OrganizationalUnit;
use App\Services\OrganizationalScope;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationalUnitController extends Controller
{
    public function index(Request $request, OrganizationalScope $scope): AnonymousResourceCollection
    {
        $this->authorize('viewAny', OrganizationalUnit::class);

        $units = OrganizationalUnit::query()
            ->whereIn('id', $scope->accessibleUnitIds($request->user()))
            ->orderBy('code')
            ->get();

        return OrganizationalUnitResource::collection($units);
    }

    public function show(OrganizationalUnit $organizationalUnit): OrganizationalUnitResource
    {
        $this->authorize('view', $organizationalUnit);

        return new OrganizationalUnitResource($organizationalUnit);
    }
}
