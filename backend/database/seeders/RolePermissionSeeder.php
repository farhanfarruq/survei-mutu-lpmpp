<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'admin.panel.access',
            'system.status.view',
            'system.horizon.view',
            'organization.scope.all',
            'organizational-units.view',
            'organizational-units.create',
            'organizational-units.update',
            'organizational-units.delete',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.update',
            'permissions.view',
            'template.read',
            'template.create',
            'template.update',
            'template.delete',
            'validation.create',
            'validation.read',
            'validation.update',
            'validation.approve',
            'campaign.read',
            'campaign.create',
            'campaign.update',
            'campaign.delete',
            'campaign.review',
            'campaign.approve',
            'campaign.publish',
            'population.manage',
            'analysis.execute',
            'analysis.read',
            'analysis.release',
            'report.read',
            'report.create',
            'report.export',
            'report.approve',
            'ai.config',
            'ai.execute',
            'ai.read',
            'ai.review',
            'notification.read',
            'finding.read',
            'finding.create',
            'finding.update',
            'action.create',
            'action.read',
            'action.update',
            'action.verify',
            'follow-up.dashboard.read',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            'super_admin' => $permissions,
            'admin_lpmpp' => array_values(array_diff($permissions, [
                'organization.scope.all',
                'permissions.view',
                'roles.create',
                'roles.update',
                'system.horizon.view',
                'validation.approve',
                'campaign.approve',
                'analysis.release',
                'report.approve',
                'ai.review',
                'action.verify',
            ])),
            'leader' => ['system.status.view', 'organizational-units.view', 'campaign.read', 'report.read', 'report.export', 'ai.read', 'notification.read', 'finding.read', 'action.read', 'follow-up.dashboard.read'],
            'reviewer' => ['admin.panel.access', 'organizational-units.view', 'template.read', 'validation.create', 'validation.read', 'validation.update', 'validation.approve', 'campaign.read', 'campaign.approve', 'analysis.read', 'analysis.release', 'report.read', 'report.approve', 'ai.read', 'ai.review', 'notification.read', 'finding.read', 'action.read', 'action.verify', 'follow-up.dashboard.read'],
            'analyst' => ['organizational-units.view', 'campaign.read', 'analysis.execute', 'analysis.read', 'report.read', 'report.create', 'report.export', 'ai.execute', 'ai.read', 'notification.read', 'finding.read', 'finding.create', 'finding.update', 'action.create', 'action.read', 'follow-up.dashboard.read'],
            'pic' => ['organizational-units.view', 'notification.read', 'finding.read', 'action.read', 'action.update'],
            'verifier' => ['organizational-units.view', 'notification.read', 'finding.read', 'action.read', 'action.verify'],
            'respondent' => ['notification.read'],
        ];

        foreach ($roles as $name => $grants) {
            Role::findOrCreate($name, 'web')->syncPermissions($grants);
        }
    }
}
