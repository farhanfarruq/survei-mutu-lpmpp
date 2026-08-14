<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegistrationController extends Controller
{
    public function options(): JsonResponse
    {
        return response()->json(['data' => [
            'programs' => OrganizationalUnit::query()
                ->with('parent:id,name')
                ->where('type', 'program')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'parent_id', 'code', 'name'])
                ->map(fn (OrganizationalUnit $program): array => [
                    'id' => $program->id,
                    'code' => $program->code,
                    'name' => $program->name,
                    'faculty_name' => $program->parent?->name,
                ]),
        ]]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge(['identity_number' => Str::upper(trim((string) $request->input('identity_number')))]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'account_type' => ['required', Rule::in(['student', 'lecturer'])],
            'identity_number' => ['required', 'string', 'min:4', 'max:50', 'regex:/^[A-Z0-9.-]+$/', Rule::unique(User::class)],
            'organizational_unit_id' => [
                'required',
                'uuid',
                Rule::exists('organizational_units', 'id')->where(fn ($query) => $query->where('type', 'program')->where('is_active', true)),
            ],
            'password' => ['required', 'confirmed', Password::default()],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'identity_number' => $validated['identity_number'],
                'account_type' => $validated['account_type'],
                'email' => Str::lower($validated['identity_number']).'@accounts.invalid',
                'password' => Hash::make($validated['password']),
                'is_active' => true,
            ]);
            $user->assignRole('respondent');
            $user->organizationalUnits()->attach($validated['organizational_unit_id'], [
                'scope_mode' => 'self',
                'is_primary' => true,
            ]);

            return $user;
        });

        return (new UserResource($user->load(['roles', 'organizationalUnits'])))
            ->response()
            ->setStatusCode(201);
    }
}
