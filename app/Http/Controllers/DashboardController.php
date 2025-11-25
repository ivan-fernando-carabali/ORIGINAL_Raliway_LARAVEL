<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\Output;
use App\Models\Entry;

class DashboardController extends Controller
{
    /**
     * 📊 Resumen general del panel de inventario
     */
    public function summary(): JsonResponse
    {
        // 📦 Producto con más stock (desde inventarios)
        $productoStock = Inventory::with('product:id,name')
            ->select('id', 'product_id', 'stock')
            ->orderByDesc('stock')
            ->first();

        $productoConMasStock = $productoStock ? [
            'nombre' => $productoStock->product->name ?? '—',
            'cantidad' => $productoStock->stock ?? 0,
        ] : [
            'nombre' => '—',
            'cantidad' => 0,
        ];

        // 🚚 Producto con más salidas (según tabla outputs)
        $productoSalida = Output::selectRaw('product_id, COUNT(*) as cantidad')
            ->groupBy('product_id')
            ->orderByDesc('cantidad')
            ->with('product:id,name')
            ->first();

        $productoConMasSalida = $productoSalida ? [
            'nombre' => $productoSalida->product->name ?? '—',
            'cantidad' => $productoSalida->cantidad ?? 0,
        ] : [
            'nombre' => '—',
            'cantidad' => 0,
        ];

        // 📈 Estadísticas de entradas/salidas por mes
        $mesActual = now()->month;
        $mesAnterior = now()->subMonth()->month;

        $entradasMesActual = Entry::whereMonth('created_at', $mesActual)->count();
        $entradasMesAnterior = Entry::whereMonth('created_at', $mesAnterior)->count();

        $salidasMesActual = Output::whereMonth('created_at', $mesActual)->count();
        $salidasMesAnterior = Output::whereMonth('created_at', $mesAnterior)->count();

        // 📤 Respuesta final para el dashboard
        return response()->json([
            'productoStock' => $productoConMasStock,
            'productoSalida' => $productoConMasSalida,
            'entradasMesActual' => $entradasMesActual,
            'entradasMesAnterior' => $entradasMesAnterior,
            'salidasMesActual' => $salidasMesActual,
            'salidasMesAnterior' => $salidasMesAnterior,
        ]);
    }
}


