<?php

namespace App\Http\Controllers;

use App\Services\ProductExitService;
use Illuminate\Http\Request;

class ProductExitController extends Controller
{
    protected $productExitService;

    public function __construct(ProductExitService $productExitService)
    {
        $this->productExitService = $productExitService;
    }

    /**
     * 📄 Listar todas las salidas
     */
    public function index()
    {
        return $this->productExitService->getAllExits();
    }

    /**
     * ➕ Crear nueva salida
     */
    public function store(Request $request)
    {
        return $this->productExitService->createExit($request);
    }

    /**
     * 🔍 Mostrar una salida
     */
    public function show($id)
    {
        return $this->productExitService->getExitById($id);
    }

    /**
     * ✏️ Actualizar una salida
     */
    public function update(Request $request, $id)
    {
        return $this->productExitService->updateExit($request, $id);
    }

    /**
     * 📊 Resumen de salidas (para dashboard)
     */
    public function summary()
    {
        return $this->productExitService->getSummary();
    }

    /**
     * 🗑️ Eliminar una salida
     */
    public function destroy($id)
    {
        return $this->productExitService->deleteExit($id);
    }

    /**
     * 📦 Listas para selects (productos, usuarios)
     */
    public function formData()
    {
        return $this->productExitService->getFormData();
    }
}
