<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Entry;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class FixProductSuppliers extends Command
{
    protected $signature = 'fix:product-suppliers';
    protected $description = 'Asocia proveedores a productos basándose en las entradas existentes';

    public function handle()
    {
        $this->info('🔧 Asociando proveedores a productos desde entradas...');
        $this->info('');

        // Obtener todas las entradas con proveedor
        $entries = Entry::whereNotNull('supplier_id')
            ->with(['product', 'supplier'])
            ->get()
            ->groupBy('product_id');

        $fixed = 0;
        $skipped = 0;

        foreach ($entries as $productId => $productEntries) {
            $product = Product::find($productId);
            
            if (!$product) {
                continue;
            }

            $this->line("Producto: {$product->name} (ID: {$productId})");
            
            foreach ($productEntries as $entry) {
                $supplierId = $entry->supplier_id;
                
                // Verificar si ya está asociado
                $isAssociated = DB::table('product_supplier')
                    ->where('product_id', $productId)
                    ->where('supplier_id', $supplierId)
                    ->exists();
                
                if (!$isAssociated) {
                    $product->suppliers()->attach($supplierId, [
                        'unit_cost' => 0, // Valor por defecto
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $this->info("  ✅ Asociado con proveedor: {$entry->supplier->name}");
                    $fixed++;
                } else {
                    $this->line("  ℹ️  Ya asociado con: {$entry->supplier->name}");
                    $skipped++;
                }
            }
            
            $this->line('');
        }

        $this->info("✅ Proceso completado!");
        $this->info("   Asociaciones creadas: {$fixed}");
        $this->info("   Ya existentes: {$skipped}");

        return 0;
    }
}

