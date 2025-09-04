<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Database\Seeders\PermissionDefinitions;
use Database\Seeders\Permissions\GeneralManagerPermissions;
use Database\Seeders\Permissions\ManagerPermissions;
use Database\Seeders\Permissions\CompanyUserPermissions;
use Database\Seeders\Permissions\AgentPermissions;
use Database\Seeders\Permissions\CompanyOwnerPermissions;
use Database\Seeders\Permissions\OfficePermissions;
use Database\Seeders\Permissions\HRPermissions;
use Database\Seeders\Permissions\OfficeManagerPermissions;
use Database\Seeders\Permissions\RecruitersPermissions;
use Database\Seeders\Permissions\FinancePermissions;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = PermissionDefinitions::getAllPermissions();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], $permission);
        }

        $this->assignPermissionsToRoles();
    }

    private function assignPermissionsToRoles()
    {
        $rolePermissions = [
            Role::GENERAL_MANAGER => GeneralManagerPermissions::getPermissions(),
            Role::MANAGER => ManagerPermissions::getPermissions(),
            Role::COMPANY_USER => CompanyUserPermissions::getPermissions(),
            Role::AGENT => AgentPermissions::getPermissions(),
            Role::COMPANY_OWNER => CompanyOwnerPermissions::getPermissions(),
            Role::OFFICE => OfficePermissions::getPermissions(),
            Role::HR => HRPermissions::getPermissions(),
            Role::OFFICE_MANAGER => OfficeManagerPermissions::getPermissions(),
            Role::RECRUITERS => RecruitersPermissions::getPermissions(),
            Role::FINANCE => FinancePermissions::getPermissions(),
        ];

        foreach ($rolePermissions as $roleId => $permissions) {
            $role = Role::find($roleId);
            if ($role) {
                $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
                $role->permissions()->sync($permissionIds);
            }
        }
    }
}
