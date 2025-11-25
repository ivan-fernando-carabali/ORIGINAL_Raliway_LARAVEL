<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel; // if using maatwebsite
use Illuminate\Support\Facades\View;


class ReportController extends Controller
{
    // Movements by product between dates (entries/exits)
    public function productMovements(Request $request)
{
    $from = $request->input('from');
    $to = $request->input('to');

    // Validar fechas (opcional)
    $useDates = $from && $to && strtotime($from) && strtotime($to);

    // 📥 Entradas
    $entriesQuery = DB::table('entries')
        ->join('products', 'entries.product_id', '=', 'products.id')
        ->select(
            'products.id',
            'products.name',
            DB::raw('SUM(entries.quantity) as total_entradas')
        )
        ->when($useDates, function ($q) use ($from, $to) {
            $q->whereBetween('entries.created_at', [$from, $to]);
        })
        ->groupBy('products.id', 'products.name');

    // 📤 Salidas
    $exitsQuery = DB::table('product_exits')
        ->join('products', 'product_exits.product_id', '=', 'products.id')
        ->select(
            'products.id',
            'products.name',
            DB::raw('SUM(product_exits.quantity) as total_salidas')
        )
        ->when($useDates, function ($q) use ($from, $to) {
            $q->whereBetween('product_exits.created_at', [$from, $to]);
        })
        ->groupBy('products.id', 'products.name');

    // 🔗 Unir ambas (LEFT JOIN)
    $entriesSql = $entriesQuery->toSql();

    $combined = DB::table(DB::raw("({$entriesSql}) as e"))
        ->mergeBindings($entriesQuery)
        ->leftJoinSub($exitsQuery, 'x', 'e.id', '=', 'x.id')
        ->select(
            'e.name',
            DB::raw('COALESCE(e.total_entradas, 0) as total_entradas'),
            DB::raw('COALESCE(x.total_salidas, 0) as total_salidas')
        )
        ->orderBy('e.name', 'asc')
        ->get();

    return response()->json($combined);
}


    // Alerts report (assumes you have alerts table)
    public function alertReport(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');

        $query = DB::table('alerts')
            ->select('type', DB::raw('COUNT(*) as total'))
            ->when($from && $to, function ($q) use ($from, $to) {
                $q->whereBetween('created_at', [$from, $to]);
            })
            ->groupBy('type');

        $data = $query->get();

        return response()->json($data);
    }

    // Inventory status (current stock per product - sample)
public function inventoryReport(Request $request)
{
    // Calculamos las entradas por producto
    $queryEntries = DB::table('entries')
        ->select('product_id', DB::raw('SUM(quantity) as entradas'))
        ->groupBy('product_id');

    // Calculamos las salidas por producto
    $queryExits = DB::table('product_exits')
        ->select('product_id', DB::raw('SUM(quantity) as salidas'))
        ->groupBy('product_id');

    // Unimos productos con entradas y salidas + agregamos referencia y lote
    $result = DB::table('products as p')
        ->leftJoinSub($queryEntries, 'e', 'p.id', '=', 'e.product_id')
        ->leftJoinSub($queryExits, 'x', 'p.id', '=', 'x.product_id')
        ->select(
            'p.id',
            'p.name as producto',
            'p.reference as referencia',
            'p.batch as lote',
            DB::raw('COALESCE(e.entradas, 0) as entradas'),
            DB::raw('COALESCE(x.salidas, 0) as salidas'),
            DB::raw('COALESCE(e.entradas, 0) - COALESCE(x.salidas, 0) as stock')
        )
        ->orderBy('p.name', 'asc')
        ->get();

    return response()->json($result);
}


    // Export products report to Excel (uses maatwebsite/excel if installed)
    public function exportProductsExcel(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');

        // reuse productMovements logic to get collection
        $data = $this->productMovements($request)->getData();

        // If maatwebsite installed, return Excel download. We'll create export on the fly:
        $rows = [];
        $rows[] = ['Producto', 'Entradas', 'Salidas'];
        foreach ($data as $d) {
            $rows[] = [$d->name, $d->total_entradas, $d->total_salidas];
        }

        $filename = 'productos_movimientos_' . date('Ymd_His') . '.csv';
        $handle = fopen('php://memory', 'w');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fseek($handle, 0);

        return response()->stream(function() use ($handle) {
            fpassthru($handle);
        }, 200, [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename={$filename}",
        ]);
    }

    // Export products report to PDF (basic server-side PDF using dompdf)
    public function exportProductsPdf(Request $request)
    {
        $data = $this->productMovements($request)->getData();
        $pdf = PDF::loadView('reports.products_pdf', ['rows' => $data]);
        return $pdf->download('productos_movimientos_'.date('Ymd_His').'.pdf');
    }
}
