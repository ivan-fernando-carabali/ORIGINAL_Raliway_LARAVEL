<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    // Obtener todas las categorías
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Category::all()
        ]);
    }

    // Crear una nueva categoría
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:categories,name'
            ], [
                'name.required' => 'El nombre de la categoría es obligatorio',
                'name.unique' => 'Ya existe una categoría con ese nombre',
                'name.max' => 'El nombre de la categoría no puede exceder 255 caracteres'
            ]);

            $category = Category::create([
                'name' => $request->input('name')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Categoría creada exitosamente',
                'data' => $category
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Inicializar categorías con las del frontend
    public function init(Request $request)
    {
        try {
            $categories = $request->input('categories', []);

            foreach ($categories as $cat) {
                Category::firstOrCreate([
                    'name' => $cat['name']
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Categorías inicializadas correctamente',
                'data' => Category::all()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Sincronizar categorías (similar a init, pero borra y reemplaza)
    public function sync(Request $request)
    {
        try {
            $categories = $request->input('categories', []);

            // Vaciar la tabla antes de insertar
            Category::truncate();

            foreach ($categories as $cat) {
                Category::create([
                    'name' => $cat['name']
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Categorías sincronizadas correctamente',
                'data' => Category::all()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Obtener una categoría específica
    public function show($id)
    {
        try {
            $category = Category::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}
