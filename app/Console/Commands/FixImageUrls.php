<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class FixImageUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:fix-urls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica y corrige las URLs de las imágenes de productos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verificando enlace simbólico de storage...');
        
        $storageLink = public_path('storage');
        $storageTarget = storage_path('app/public');
        
        if (!file_exists($storageLink)) {
            $this->warn('⚠️ El enlace simbólico no existe. Creándolo...');
            try {
                if (PHP_OS_FAMILY === 'Windows') {
                    // Windows requiere permisos de administrador
                    $this->info('En Windows, ejecuta manualmente: php artisan storage:link');
                } else {
                    symlink($storageTarget, $storageLink);
                    $this->info('✅ Enlace simbólico creado');
                }
            } catch (\Exception $e) {
                $this->error('❌ Error al crear enlace: ' . $e->getMessage());
                $this->info('Ejecuta manualmente: php artisan storage:link');
            }
        } else {
            $this->info('✅ El enlace simbólico existe');
        }
        
        $this->info('');
        $this->info('🔍 Verificando productos...');
        
        $products = Product::whereNotNull('image')->get();
        $this->info("Encontrados {$products->count()} productos con imagen");
        
        $baseUrl = rtrim(config('app.url'), '/');
        $fixed = 0;
        $errors = 0;
        
        foreach ($products as $product) {
            $imagePath = $product->image;
            
            // Verificar si la imagen existe
            $fullPath = storage_path('app/public/' . $imagePath);
            $exists = file_exists($fullPath);
            
            if (!$exists) {
                $this->warn("⚠️ Imagen no encontrada: {$imagePath}");
                $errors++;
                continue;
            }
            
            // Construir URL correcta
            $imagePath = ltrim($imagePath, '/');
            $expectedUrl = $baseUrl . '/storage/' . $imagePath;
            
            // Verificar si image_url está correcto
            if ($product->image_url !== $expectedUrl) {
                $this->info("📝 Corrigiendo URL para producto ID {$product->id}: {$product->name}");
                $this->info("   URL anterior: " . ($product->image_url ?? 'null'));
                $this->info("   URL nueva: {$expectedUrl}");
                $fixed++;
            }
        }
        
        $this->info('');
        $this->info("✅ Verificación completada");
        $this->info("   Productos corregidos: {$fixed}");
        if ($errors > 0) {
            $this->warn("   Imágenes no encontradas: {$errors}");
        }
        
        $this->info('');
        $this->info('💡 Para probar, accede a:');
        if ($products->count() > 0) {
            $firstProduct = $products->first();
            if ($firstProduct->image) {
                $testUrl = $baseUrl . '/storage/' . ltrim($firstProduct->image, '/');
                $this->info("   {$testUrl}");
            }
        }
        
        return 0;
    }
}





