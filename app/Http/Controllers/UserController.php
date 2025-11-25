<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function index()
    {
        return response()->json($this->userService->getAll());
    }

    public function show($id)
    {
        return response()->json($this->userService->getById($id));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'lastname' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'nullable|exists:roles,id'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'lastname' => $validated['lastname'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role_id' => $validated['role_id'] ?? null,
        ]);

        $user->load('role');
        return response()->json($user, 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'lastname' => 'sometimes|string',
            'email' => "sometimes|email|unique:users,email,{$id}",
            'password' => 'nullable|string|min:6',
            'role_id' => 'nullable|exists:roles,id'
        ]);

        $user = $this->userService->updateUser($id, $validated);
        $user->load('role');
        return response()->json($user);
    }

    public function destroy($id)
    {
        $this->userService->delete($id);
        return response()->json(null, 204);
    }

    public function profile(Request $request)
    {
        $user = $request->user()->load('role');
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'image' => $user->image,
            'image_url' => $user->image ? url('storage/' . $user->image) : null,
            'role' => [
                'id' => $user->role->id,
                'name' => $user->role->name
            ]
        ]);
    }

    // Actualizar imagen del usuario autenticado
    public function updateImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB máximo
        ]);

        $user = $request->user();

        // Eliminar imagen anterior si existe
        if ($user->image && Storage::disk('public')->exists($user->image)) {
            Storage::disk('public')->delete($user->image);
        }

        // Guardar nueva imagen
        $imagePath = $request->file('image')->store('users', 'public');
        $user->image = $imagePath;
        $user->save();

        $user->load('role');

        return response()->json([
            'message' => 'Imagen actualizada correctamente',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'image' => $user->image,
                'image_url' => $user->image ? url('storage/' . $user->image) : null,
                'role' => [
                    'id' => $user->role->id,
                    'name' => $user->role->name
                ]
            ]
        ]);
    }

    // Eliminar imagen del usuario autenticado
    public function deleteImage(Request $request)
    {
        $user = $request->user();

        // Eliminar imagen del almacenamiento
        if ($user->image && Storage::disk('public')->exists($user->image)) {
            Storage::disk('public')->delete($user->image);
        }

        // Eliminar referencia en la base de datos
        $user->image = null;
        $user->save();

        $user->load('role');

        return response()->json([
            'message' => 'Imagen eliminada correctamente',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'image' => null,
                'image_url' => null,
                'role' => [
                    'id' => $user->role->id,
                    'name' => $user->role->name
                ]
            ]
        ]);
    }
}