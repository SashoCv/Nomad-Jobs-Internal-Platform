<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permission;
use App\Models\Role;

class ManagePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:manage 
                          {action : Action to perform (create, assign, remove, list)}
                          {--name= : Name of the permission}
                          {--slug= : Slug of the permission}
                          {--description= : Description of the permission}
                          {--module= : Module of the permission}
                          {--role= : Role ID or name}
                          {--permission= : Permission slug for assign/remove}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage permissions - create, assign to roles, remove from roles, list permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'create':
                return $this->createPermission();
            case 'assign':
                return $this->assignPermission();
            case 'remove':
                return $this->removePermission();
            case 'list':
                return $this->listPermissions();
            default:
                $this->error("Unknown action: {$action}");
                $this->info("Available actions: create, assign, remove, list");
                return 1;
        }
    }

    private function createPermission()
    {
        $name = $this->option('name') ?: $this->ask('Permission name');
        $slug = $this->option('slug') ?: $this->ask('Permission slug (e.g., module.action)');
        $description = $this->option('description') ?: $this->ask('Permission description (optional)', '');
        $module = $this->option('module') ?: $this->ask('Module name (optional)', '');

        if (Permission::where('slug', $slug)->exists()) {
            $this->error("Permission with slug '{$slug}' already exists!");
            return 1;
        }

        $permission = Permission::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'module' => $module,
        ]);

        $this->info("✅ Permission created successfully!");
        $this->table(
            ['ID', 'Name', 'Slug', 'Module', 'Description'],
            [[$permission->id, $permission->name, $permission->slug, $permission->module, $permission->description]]
        );

        // Ask if user wants to assign to roles immediately
        if ($this->confirm('Do you want to assign this permission to roles now?')) {
            $this->assignPermissionToRoles($permission);
        }

        return 0;
    }

    private function assignPermission()
    {
        $permissionSlug = $this->option('permission') ?: $this->ask('Permission slug');
        $roleId = $this->option('role') ?: $this->ask('Role ID or name');

        $permission = Permission::where('slug', $permissionSlug)->first();
        if (!$permission) {
            $this->error("Permission with slug '{$permissionSlug}' not found!");
            return 1;
        }

        $role = is_numeric($roleId) ? Role::find($roleId) : Role::where('roleName', $roleId)->first();
        if (!$role) {
            $this->error("Role '{$roleId}' not found!");
            return 1;
        }

        if ($role->permissions()->where('permission_id', $permission->id)->exists()) {
            $this->warn("Role '{$role->roleName}' already has permission '{$permission->name}'");
            return 1;
        }

        $role->permissions()->attach($permission->id);
        $this->info("✅ Permission '{$permission->name}' assigned to role '{$role->roleName}'");

        return 0;
    }

    private function removePermission()
    {
        $permissionSlug = $this->option('permission') ?: $this->ask('Permission slug');
        $roleId = $this->option('role') ?: $this->ask('Role ID or name');

        $permission = Permission::where('slug', $permissionSlug)->first();
        if (!$permission) {
            $this->error("Permission with slug '{$permissionSlug}' not found!");
            return 1;
        }

        $role = is_numeric($roleId) ? Role::find($roleId) : Role::where('roleName', $roleId)->first();
        if (!$role) {
            $this->error("Role '{$roleId}' not found!");
            return 1;
        }

        if (!$role->permissions()->where('permission_id', $permission->id)->exists()) {
            $this->warn("Role '{$role->roleName}' doesn't have permission '{$permission->name}'");
            return 1;
        }

        $role->permissions()->detach($permission->id);
        $this->info("✅ Permission '{$permission->name}' removed from role '{$role->roleName}'");

        return 0;
    }

    private function listPermissions()
    {
        $roleId = $this->option('role');
        
        if ($roleId) {
            // List permissions for specific role
            $role = is_numeric($roleId) ? Role::find($roleId) : Role::where('roleName', $roleId)->first();
            if (!$role) {
                $this->error("Role '{$roleId}' not found!");
                return 1;
            }

            $permissions = $role->permissions()->get();
            $this->info("Permissions for role '{$role->roleName}' (ID: {$role->id}):");
            
            if ($permissions->isEmpty()) {
                $this->warn("No permissions assigned to this role.");
                return 0;
            }

            $this->table(
                ['ID', 'Name', 'Slug', 'Module', 'Description'],
                $permissions->map(function ($p) {
                    return [$p->id, $p->name, $p->slug, $p->module, $p->description];
                })->toArray()
            );
        } else {
            // List all permissions with role assignments
            $permissions = Permission::with('roles')->get();
            $this->info("All permissions in the system:");
            
            $permissionData = $permissions->map(function ($permission) {
                $roleNames = $permission->roles->pluck('roleName')->join(', ');
                return [
                    $permission->id,
                    $permission->name,
                    $permission->slug,
                    $permission->module,
                    $roleNames ?: 'No roles assigned'
                ];
            })->toArray();

            $this->table(
                ['ID', 'Name', 'Slug', 'Module', 'Assigned Roles'],
                $permissionData
            );
        }

        return 0;
    }

    private function assignPermissionToRoles($permission)
    {
        $roles = Role::all();
        
        $this->info("Available roles:");
        foreach ($roles as $role) {
            $this->line("  {$role->id}. {$role->roleName}");
        }

        $selectedRoles = $this->ask('Enter role IDs separated by comma (e.g., 1,2,3)');
        $roleIds = array_map('trim', explode(',', $selectedRoles));

        foreach ($roleIds as $roleId) {
            $role = Role::find($roleId);
            if ($role) {
                $role->permissions()->attach($permission->id);
                $this->info("✅ Assigned to role '{$role->roleName}'");
            } else {
                $this->error("❌ Role ID {$roleId} not found");
            }
        }
    }
}