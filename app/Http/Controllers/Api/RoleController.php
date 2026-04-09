<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        return RoleResource::collection(Role::with('permissions')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|unique:roles',
        ]);

        $role = Role::create($validated);

        return new RoleResource($role);
    }

    public function show(Role $role)
    {
        return new RoleResource($role->load('permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'nom' => 'required|string|unique:roles,nom,' . $role->id,
        ]);

        $role->update($validated);

        return new RoleResource($role);
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return response()->json(null, 204);
    }

    public function syncPermissions(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->permissions()->sync($validated['permissions']);

        return new RoleResource($role->load('permissions'));
    }
}
