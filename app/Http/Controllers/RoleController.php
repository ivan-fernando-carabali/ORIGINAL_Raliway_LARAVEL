<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    protected $roleService;

   public function __construct(RoleService $roleService)
{
    $this->roleService = $roleService;

    // Middleware solo para métodos CRUD
    $this->middleware(['auth:sanctum', 'role:admin'])->except(['getRolesForRegister']);
}


    public function index()
    {
        return response()->json($this->roleService->getAll());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'description' => 'nullable|string'
        ]);

        return response()->json($this->roleService->create($validated), 201);
    }

    public function show($id)
    {
        return response()->json($this->roleService->getById($id));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => "sometimes|string|unique:roles,name,{$id}",
            'description' => 'nullable|string'
        ]);

        return response()->json($this->roleService->update($id, $validated));
    }

    public function destroy($id)
    {
        $this->roleService->delete($id);
        return response()->json(null, 204);
    }


    // ✅ Roles públicos para registro
    public function getRolesForRegister()
    {
        // Solo los roles que un usuario puede elegir al registrarse
        $allowedRoles = ['empleado', 'invitado'];
        $roles = $this->roleService->getAll()->whereIn('name', $allowedRoles)->values();
        return response()->json($roles);
    }
}
