<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Alert;
use App\Models\Product;

class CheckAlertSuppliers extends Command
{
    protected $signature = 'check:alert-suppliers';
    protected $description = 'Verifica los proveedores asociados a productos de alertas';

    public function handle()
    {
        $this->info('🔍 Verificando alertas y proveedores...');
        $this->info('');

        $alerts = Alert::with(['product.suppliers'])->get();

        if ($alerts->isEmpty()) {
            $this->warn('No se encontraron alertas en el sistema.');
            return 0;
        }

        $this->info("Total de alertas: {$alerts->count()}");
        $this->info('');

        foreach ($alerts as $alert) {
            $product = $alert->product;
            $suppliersCount = $product->suppliers->count();
            
            $this->line("Alerta ID: {$alert->id}");
            $this->line("  Producto: {$product->name} (ID: {$product->id})");
            $this->line("  Proveedores asociados: {$suppliersCount}");
            
            if ($suppliersCount > 0) {
                foreach ($product->suppliers as $supplier) {
                    $this->line("    - {$supplier->name} ({$supplier->email})");
                }
            } else {
                $this->warn("    ⚠️  Sin proveedores asociados");
            }
            
            $this->line('');
        }

        return 0;
    }
}


