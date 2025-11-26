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
        // 📦 Producto con más stock (calculado desde entradas y salidas)
        // Calcular stock real: sumar todas las entradas y restar todas las salidas por producto
        $entradasPorProducto = Entry::selectRaw('product_id, SUM(quantity) as total_entradas')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $salidasPorProducto = Output::selectRaw('product_id, SUM(quantity) as total_salidas')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        // Calcular stock por producto
        $stockPorProducto = [];
        $productosIds = $entradasPorProducto->keys()->merge($salidasPorProducto->keys())->unique();

        foreach ($productosIds as $productId) {
            $entradaData = $entradasPorProducto->get($productId);
            $salidaData = $salidasPorProducto->get($productId);
            $entradas = $entradaData ? ($entradaData->total_entradas ?? 0) : 0;
            $salidas = $salidaData ? ($salidaData->total_salidas ?? 0) : 0;
            $stock = $entradas - $salidas;
            
            if ($stock > 0) {
                $stockPorProducto[$productId] = $stock;
            }
        }

        // Obtener el producto con más stock
        $productIdConMasStock = null;
        $maxStock = 0;
        foreach ($stockPorProducto as $productId => $stock) {
            if ($stock > $maxStock) {
                $maxStock = $stock;
                $productIdConMasStock = $productId;
            }
        }

        $productoConMasStock = [
            'nombre' => '—',
            'cantidad' => 0,
            'total_entradas' => 0,
            'total_salidas' => 0,
            'veces_salio' => 0,
        ];

        if ($productIdConMasStock) {
            $producto = Product::find($productIdConMasStock);
            $entradaData = $entradasPorProducto->get($productIdConMasStock);
            $salidaData = $salidasPorProducto->get($productIdConMasStock);
            $entradas = $entradaData ? ($entradaData->total_entradas ?? 0) : 0;
            $salidas = $salidaData ? ($salidaData->total_salidas ?? 0) : 0;
            $vecesSalio = Output::where('product_id', $productIdConMasStock)->count();

            $productoConMasStock = [
                'nombre' => $producto ? $producto->name : '—',
                'cantidad' => $maxStock,
                'total_entradas' => $entradas,
                'total_salidas' => $salidas,
                'veces_salio' => $vecesSalio,
            ];
        }

        // 🚚 Producto con más salidas (cantidad total de unidades salidas)
        $productoSalida = Output::selectRaw('product_id, SUM(quantity) as cantidad_total, COUNT(*) as veces_salio')
            ->groupBy('product_id')
            ->orderByDesc('cantidad_total')
            ->with('product:id,name')
            ->first();

        $productoConMasSalida = [
            'nombre' => '—',
            'cantidad' => 0,
            'veces_salio' => 0,
            'cantidad_total' => 0,
        ];

        if ($productoSalida) {
            $productoConMasSalida = [
                'nombre' => $productoSalida->product->name ?? '—',
                'cantidad' => $productoSalida->cantidad_total ?? 0,
                'veces_salio' => $productoSalida->veces_salio ?? 0,
                'cantidad_total' => $productoSalida->cantidad_total ?? 0,
            ];
        }

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


