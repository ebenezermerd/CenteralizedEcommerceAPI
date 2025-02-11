<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    private array $permissions = [
        'users' => ['list', 'create', 'edit', 'delete'],
        'products' => ['list', 'create', 'edit', 'delete'],
        'orders' => ['list', 'create', 'edit', 'delete', 'manage'],
        'reviews' => ['list', 'create', 'edit', 'delete', 'moderate'],
        'invoices' => ['list', 'create', 'edit', 'delete', 'download', 'send'],
        'mails' => ['list', 'create', 'edit', 'delete', 'download', 'send'],
    ];

    public function run(): void
    {
        // Create permissions first
        $allPermissions = [];
        foreach ($this->permissions as $module => $actions) {
            foreach ($actions as $action) {
                $permissionName = "{$action}_{$module}";
                DB::table('permissions')->insert([
                    'name' => $permissionName,
                    'guard_name' => 'api'
                ]);
                $allPermissions[] = $permissionName;
            }
        }

        // Define roles with their permissions
        $roles = [
            'admin' => $allPermissions, // Admin gets all permissions
            'supplier' => [
                'list_products', 'create_products', 'edit_products',
                'list_orders', 'edit_orders',
                'list_reviews', 'edit_reviews',
                'list_invoices', 'create_invoices', 'download_invoices',
                'list_mails', 'create_mails', 'edit_mails', 'delete_mails', 'download_mails', 'send_mails'
            ],
            'customer' => [
                'list_products',
                'create_reviews', 'edit_reviews',
                'list_orders',
                'list_invoices', 'download_invoices',
                'list_mails', 'download_mails', 'send_mails'
            ]
        ];

        // Create roles and assign permissions
        foreach ($roles as $roleName => $permissions) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => $roleName,
                'guard_name' => 'api' // Changed from 'web' to 'api'
            ]);

            // Assign permissions to role
            foreach ($permissions as $permission) {
                $permissionId = DB::table('permissions')
                    ->where('name', $permission)
                    ->value('id');

                if ($permissionId) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId
                    ]);
                }
            }
        }
    }
}
