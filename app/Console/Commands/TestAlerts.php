<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Inventory;
use App\Models\Alert;
use App\Services\AlertService;

class TestAlerts extends Command
{
    /**
     * El nombre y firma del comando.
     *
     * @var string
     */
    protected $signature = 'alerts:test';

    /**
     * Descripción del comando.
     *
     * @var string
     */
    protected $description = 'Prueba el sistema de alertas simulando diferentes escenarios de stock';

    /**
     * Ejecuta el comando.
     */
    public function handle(AlertService $alertService)
    {
        $this->info('🔍 Iniciando prueba del sistema de alertas...');

        $inventories = collect([
            ['product_id' => 1, 'stock' => 0, 'min_stock' => 5],   // sin stock
            ['product_id' => 2, 'stock' => 3, 'min_stock' => 5],   // bajo stock
            ['product_id' => 3, 'stock' => 10, 'min_stock' => 5],  // normal
        ]);

        foreach ($inventories as $data) {
            $inventory = Inventory::where('product_id', $data['product_id'])->first();

            if (!$inventory) {
                $this->warn("⚠️ No existe inventario para el producto ID {$data['product_id']}, saltando...");
                continue;
            }

            $inventory->min_stock = $data['min_stock'];
            $inventory->stock = $data['stock'];

            $this->line("\n🧩 Probando Inventario ID {$inventory->id} (Producto {$inventory->product_id})");
            $this->line("   → Stock: {$inventory->stock}, Mínimo: {$inventory->min_stock}");

            $alertService->checkStock($inventory);
        }

        $alerts = Alert::select('id', 'product_id', 'alert_type', 'status', 'message')->get();

        $this->newLine();
        $this->info("📋 Resultados de las alertas generadas:\n");

        foreach ($alerts as $alert) {
            $this->line("• Producto #{$alert->product_id}: [{$alert->alert_type}] {$alert->status}");
            $this->line("  🗒  {$alert->message}");
        }

        $this->newLine();
        $this->info('✅ Prueba de alertas completada correctamente.');
    }
}
