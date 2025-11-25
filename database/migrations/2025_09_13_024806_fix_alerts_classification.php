<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1️⃣ Actualizar todos los registros de "resuelto" a "resuelta"
        $updated = DB::table('alerts')
            ->where('status', 'resuelto')
            ->update(['status' => 'resuelta']);

        echo "✅ {$updated} alertas actualizadas: resuelto → resuelta\n";

        // 2️⃣ Corregir alertas de "bajo_stock" que en realidad tienen stock = 0
        DB::statement("
            UPDATE alerts a
            INNER JOIN inventories i ON a.inventory_id = i.id
            SET a.alert_type = 'sin_stock',
                a.message = CONCAT(
                    'El producto \"',
                    (SELECT name FROM products WHERE id = a.product_id),
                    '\" está sin stock (0 unidades).'
                )
            WHERE a.alert_type = 'bajo_stock'
              AND i.stock = 0
              AND a.status = 'pendiente'
        ");

        echo "✅ Alertas corregidas: bajo_stock → sin_stock donde stock = 0\n";
    }

    public function down(): void
    {
        // Revertir el cambio de estatus "resuelta" → "resuelto"
        $reverted = DB::table('alerts')
            ->where('status', 'resuelta')
            ->update(['status' => 'resuelto']);

        echo "↩️ {$reverted} alertas revertidas: resuelta → resuelto\n";

        // No se revierte la corrección de alertas bajo_stock → sin_stock
    }
};
