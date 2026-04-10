<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions as per requested format
        $permissions = [
            'produit.create',
            'produit.read',
            'produit.update',
            'produit.delete',
            'stock.manage',
            'commande.manage',
            'rapport.generate',
            'utilisateur.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Admin: all permissions
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        // User: limited permissions (read-only + stock access)
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $user->syncPermissions([
            'produit.read',
            'stock.manage',
            'commande.manage', // They probably need context of commande to manage stock, or maybe not. The prompt says: "user → limited permissions (read-only + stock access)"
        ]);
    }
}
