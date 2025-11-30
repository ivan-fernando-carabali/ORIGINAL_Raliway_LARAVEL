<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    /**
     * Obtener todas las unidades
     */
    public function index()
    {
        try {
            $units = Unit::orderBy('name')->get();
            
            return response()->json([
                'success' => true,
                'data' => $units
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener unidades:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las unidades'
            ], 500);
        }
    }

    /**
     * Crear una nueva unidad
     */
    public function store(Request $request)
    {
        try {
            Log::info('📥 Datos recibidos para crear unidad:', $request->all());

            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('units', 'name')
                ],
                'abbr' => [
                    'required',
                    'string',
                    'max:10',
                    Rule::unique('units', 'abbr')
                ],
            ], [
                'name.required' => 'El nombre de la unidad es obligatorio',
                'name.unique' => 'Ya existe una unidad con este nombre',
                'abbr.required' => 'La abreviación es obligatoria',
                'abbr.unique' => 'Ya existe una unidad con esta abreviación',
            ]);

            $unit = Unit::create($validated);

            Log::info('✅ Unidad creada exitosamente:', ['unit' => $unit]);

            return response()->json([
                'success' => true,
                'message' => 'Unidad creada exitosamente',
                'data' => $unit
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('⚠️ Error de validación al crear unidad:', ['errors' => $e->errors()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ Error al crear unidad:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la unidad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar una unidad específica
     */
    public function show(Unit $unit)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $unit
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener unidad:', ['message' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la unidad'
            ], 500);
        }
    }

    /**
     * Actualizar una unidad
     */
    public function update(Request $request, Unit $unit)
    {
        try {
            Log::info('📝 Actualizando unidad:', [
                'unit_id' => $unit->id,
                'data' => $request->all()
            ]);

            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('units', 'name')->ignore($unit->id)
                ],
                'abbr' => [
                    'required',
                    'string',
                    'max:10',
                    Rule::unique('units', 'abbr')->ignore($unit->id)
                ],
            ], [
                'name.required' => 'El nombre de la unidad es obligatorio',
                'name.unique' => 'Ya existe una unidad con este nombre',
                'abbr.required' => 'La abreviación es obligatoria',
                'abbr.unique' => 'Ya existe una unidad con esta abreviación',
            ]);

            $unit->update($validated);

            Log::info('✅ Unidad actualizada exitosamente:', ['unit' => $unit]);

            return response()->json([
                'success' => true,
                'message' => 'Unidad actualizada exitosamente',
                'data' => $unit
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('⚠️ Error de validación al actualizar unidad:', ['errors' => $e->errors()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar unidad:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la unidad'
            ], 500);
        }
    }

    /**
     * Eliminar una unidad
     */
    public function destroy(Unit $unit)
    {
        try {
            // Verificar si la unidad está en uso
            $productsCount = $unit->products()->count();
            
            if ($productsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede eliminar la unidad porque está siendo utilizada por {$productsCount} producto(s)"
                ], 409);
            }

            $unitName = $unit->name;
            $unit->delete();

            Log::info('✅ Unidad eliminada exitosamente:', ['unit_name' => $unitName]);

            return response()->json([
                'success' => true,
                'message' => 'Unidad eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar unidad:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la unidad'
            ], 500);
        }
    }
}