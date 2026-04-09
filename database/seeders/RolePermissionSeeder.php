<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Define all permissions
        $permissionsList = [
            'manage_users'       => 'Gérer les utilisateurs et rôles',
            'delete_products'    => 'Supprimer produits et catégories',
            'manage_movements'   => 'Ajouter/modifier des mouvements de stock',
            'create_orders'      => 'Créer des commandes',
            'manage_alerts'      => 'Configurer les alertes de stock bas',
            'view_alerts'        => 'Voir les alertes de stock bas',
            'view_analytics'     => 'Consulter les analyses et prédictions',
            'manage_suppliers'   => 'Gérer les fournisseurs',
        ];

        foreach ($permissionsList as $nom) {
            Permission::updateOrCreate(['nom' => $nom]);
        }

        // ─── Admin ────────────────────────────────────────────────────────────
        // Gets ALL permissions
        $admin = Role::updateOrCreate(['nom' => 'Admin']);
        $admin->permissions()->sync(Permission::all()->pluck('id'));

        // ─── Utilisateur (Simple User) ────────────────────────────────────────
        // manage_movements, create_orders, view_alerts only
        $utilisateur = Role::updateOrCreate(['nom' => 'Utilisateur']);
        $utilisateur->permissions()->sync(
            Permission::whereIn('nom', [
                'Ajouter/modifier des mouvements de stock',
                'Créer des commandes',
                'Voir les alertes de stock bas',
            ])->pluck('id')
        );
    }
}
