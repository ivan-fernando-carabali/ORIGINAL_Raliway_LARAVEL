<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\Inventory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixAlertsClassification extends Command
{
    protected $signature = 'alerts:fix-classification';
    protected $description = 'Corrige la clasificación de alertas mal etiquetadas';

    public function handle(): int
    {
        $this->info('🔧 Iniciando corrección de clasificación de alertas...');

        DB::beginTransaction();

        try {
            // Obtener todas las alertas activas con inventario y producto
            $alerts = Alert::with(['inventory.product'])
                ->where('status', Alert::STATUS_ACTIVE)
                ->get();

            $fixed = 0;
            $alreadyCorrect = 0;

            foreach ($alerts as $alert) {
                if (!$alert->inventory || !$alert->inventory->product) {
                    continue;
                }

                $currentStock = $alert->inventory->stock;
                $minStock = $alert->inventory->product->min_stock;
                $correctType = $this->determineCorrectType($currentStock, $minStock);

                // Si el tipo es diferente al actual, corregir
                if ($correctType && $alert->alert_type !== $correctType) {
                    $oldType = $alert->alert_type;
                    $alert->update([
                        'alert_type' => $correctType,
                        'message' => $this->generateMessage(
                            $alert->inventory->product,
                            $currentStock,
                            $correctType
                        )
                    ]);

                    $this->line("  ✓ Alerta #{$alert->id}: {$oldType} → {$correctType} (Stock: {$currentStock})");
                    $fixed++;
                } else {
                    $alreadyCorrect++;
                }
            }

            DB::commit();

            $this->newLine();
            $this->info("✅ Corrección completada:");
            $this->table(
                ['Concepto', 'Cantidad'],
                [
                    ['Alertas corregidas', $fixed],
                    ['Alertas ya correctas', $alreadyCorrect],
                    ['Total procesadas', $fixed + $alreadyCorrect],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    private function determineCorrectType(int $currentStock, int $minStock): ?string
    {
        if ($currentStock == 0) {
            return Alert::TYPE_OUT_OF_STOCK;
        }

        if ($currentStock > 0 && $currentStock < $minStock) {
            return Alert::TYPE_LOW_STOCK;
        }

        return null;
    }

    private function generateMessage($product, int $stock, string $alertType): string
    {
        if ($alertType === Alert::TYPE_OUT_OF_STOCK) {
            return "El producto '{$product->name}' está sin stock (0 unidades).";
        }

        return "El producto '{$product->name}' tiene stock bajo ({$stock} unidades disponibles).";
    }
}
