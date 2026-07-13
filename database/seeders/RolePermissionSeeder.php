<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Court-domain permissions owned by this foundation sub-project.
     * Later sub-projects append their own permissions in their own seeders.
     *
     * @var array<int, string>
     */
    private array $allPermissions = [
        'courts.viewAny',
        'courts.view',
        'courts.create',
        'courts.update',
        'courts.delete',
        'courts.assignStaff',
        'bookings.viewAny',
        'bookings.view',
        'bookings.create',
        'bookings.update',
        'bookings.delete',
        'schedules.manage',
        'reports.view',
        'users.viewAny',
        'users.view',
    ];

    /**
     * Seed roles and permissions. Idempotent — safe to re-run.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->allPermissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $superAdmin = Role::findOrCreate(RoleName::SuperAdmin->value);
        $admin = Role::findOrCreate(RoleName::Admin->value);
        $staff = Role::findOrCreate(RoleName::Staff->value);
        Role::findOrCreate(RoleName::Customer->value);

        // Super-admin is granted everything via Gate::before, but we still sync
        // the concrete permissions so its capabilities are inspectable.
        $superAdmin->syncPermissions(Permission::all());

        $admin->syncPermissions($this->allPermissions);

        $staff->syncPermissions([
            'courts.viewAny',
            'courts.view',
            'bookings.viewAny',
            'bookings.view',
            'bookings.update',
            'schedules.manage',
            'reports.view',
        ]);

        // Customer holds no management permissions in this sub-project.

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
