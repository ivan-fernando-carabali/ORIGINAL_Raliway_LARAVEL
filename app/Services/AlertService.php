<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Inventory;
use App\Models\User;
use App\Notifications\StockAlertNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class AlertService
{
    /**
     * Verifica el stock de un inventario y crea/actualiza alertas según corresponda
     */
    public function checkStock(Inventory $inventory): void
    {
        $product = $inventory->product;

        if (!$product) {
            Log::warning("Inventario {$inventory->id} sin producto asociado");
            return;
        }

        $currentStock = $inventory->stock;
        // ✅ Asegurar que min_stock nunca sea null
        $minStock = (int) ($inventory->min_stock ?? 0);

        $alertType = $this->determineAlertType($currentStock, $minStock);

        DB::transaction(function () use ($product, $inventory, $currentStock, $alertType) {
            $this->processAlert($product, $inventory, $currentStock, $alertType);
        });
    }

    /**
     * Determina el tipo de alerta según el stock actual
     */
    private function determineAlertType(int $currentStock, int $minStock): ?string
    {
        if ($currentStock == 0) {
            return Alert::TYPE_OUT_OF_STOCK;
        }

        if ($currentStock > 0 && $currentStock < $minStock) {
            return Alert::TYPE_LOW_STOCK;
        }

        return null;
    }

    /**
     * Procesa la creación, actualización o resolución de alertas
     */
    private function processAlert($product, Inventory $inventory, int $currentStock, ?string $alertType): void
    {
        // OPTIMIZADO: Query más eficiente con select específico
        $activeAlert = Alert::where('product_id', $product->id)
            ->where('status', Alert::STATUS_NEW)
            ->select('id', 'product_id', 'status') // Solo campos necesarios
            ->first();

        // OPTIMIZADO: Verificar si hay una alerta recién resuelta (query más eficiente)
        $recentlyResolvedOrderAlert = Alert::where('product_id', $product->id)
            ->where('status', Alert::STATUS_RESOLVED)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', now()->subMinutes(5))
            ->where('message', 'like', '%Resuelta por ingreso físico%')
            ->select('id') // Solo necesitamos saber si existe
            ->limit(1) // Limitar a 1 para optimizar
            ->exists();

        if ($alertType) {
            // Si hay una alerta recién resuelta con estado "orden_enviada", no crear nueva alerta
            if ($recentlyResolvedOrderAlert) {
                Log::info("⚠️ No se crea nueva alerta para producto {$product->id} porque hay una alerta recién resuelta por ingreso físico");
                return;
            }

            // Crear o actualizar alerta
            $message = $this->generateMessage($product, $currentStock, $alertType);

            $alert = Alert::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'status' => Alert::STATUS_NEW
                ],
                [
                    'inventory_id' => $inventory->id,
                    'alert_type' => $alertType,
                    'message' => $message,
                    'date' => now(),
                    'resolved_at' => null,
                ]
            );

            // 📩 Enviar notificación a administradores y empleados
            $this->notifyUsers($alert);

        } elseif ($activeAlert) {
            // Resolver alerta si el stock se normalizó
            $this->autoResolveAlert($activeAlert, $product, $currentStock);
        }
    }

    /**
     * 📢 Envía la notificación a empleados y administradores
     */
    private function notifyUsers(Alert $alert): void
    {
        try {
            // Ejecutar envío de correos directamente (ya estamos en segundo plano desde OutputController)
            // Aumentar timeout para dar tiempo suficiente al envío de correos
            set_time_limit(15); // Máximo 15 segundos para envío de correos
            $this->sendAlertEmails($alert);
        } catch (\Exception $e) {
            Log::error("❌ Error general al enviar notificación de alerta {$alert->id}: {$e->getMessage()}");
            Log::error('❌ Stack trace: ' . $e->getTraceAsString());
        }
    }

    /**
     * Envía los correos de alerta (método separado para poder ejecutarse en segundo plano)
     */
    private function sendAlertEmails(Alert $alert): void
    {
        try {
            Log::info('📧 ========== INICIANDO ENVÍO DE EMAIL DE ALERTA ==========');
            Log::info('📧 Alerta ID: ' . $alert->id);
            
            // OPTIMIZADO: Query más eficiente con eager loading
            $users = User::with('role')
                ->whereHas('role', function ($query) {
                    $query->whereIn('name', ['admin', 'empleado']);
                })
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->select('id', 'name', 'email') // Solo campos necesarios
                ->get();

            Log::info('📧 Usuarios encontrados para notificar: ' . $users->count());

            foreach ($users as $user) {
                try {
                    Log::info('📧 Enviando notificación de alerta a: ' . $user->email);
                    
                    // Cargar la alerta con relaciones necesarias para el correo
                    $alertWithRelations = $alert->loadMissing(['inventory.product']);
                    
                    // Enviar notificación directamente (síncrono)
                    $user->notify(new StockAlertNotification($alertWithRelations));
                    
                    Log::info('✅ Notificación de alerta enviada exitosamente a: ' . $user->email);
                } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                    Log::error('❌ Error SMTP (Symfony) enviando notificación de alerta a ' . $user->email . ': ' . $e->getMessage());
                } catch (\Swift_TransportException $e) {
                    Log::error('❌ Error SMTP (Swift) enviando notificación de alerta a ' . $user->email . ': ' . $e->getMessage());
                } catch (\Exception $e) {
                    Log::error('❌ Error enviando notificación de alerta a ' . $user->email . ': ' . $e->getMessage());
                    Log::error('❌ Stack trace: ' . $e->getTraceAsString());
                }
            }
            
            Log::info('📧 ========== FIN ENVÍO DE EMAIL DE ALERTA ==========');

        } catch (\Exception $e) {
            Log::error("❌ Error en sendAlertEmails para alerta {$alert->id}: {$e->getMessage()}");
        }
    }


    /**
     * Resuelve automáticamente una alerta cuando el stock se normaliza
     */
    private function autoResolveAlert(Alert $alert, $product, int $currentStock): void
    {
        $alert->update([
            'status' => Alert::STATUS_RESOLVED,
            'message' => "El stock del producto '{$product->name}' se ha normalizado ({$currentStock} unidades).",
            'resolved_at' => now(),
        ]);
    }

    /**
     * Resuelve alertas pendientes relacionadas con un producto cuando se realiza un ingreso físico
     */
    public function resolvePendingAlertsForProduct(int $productId): void
    {
        // Buscar alertas con estado "activa", "pendiente" y "orden_enviada"
        $pendingAlerts = Alert::where('product_id', $productId)
            ->whereIn('status', [Alert::STATUS_NEW, Alert::STATUS_ACTIVE, Alert::STATUS_ORDER_SENT])
            ->get();

        Log::info("🔍 [EntryService] Buscando alertas para producto {$productId}. Encontradas: " . $pendingAlerts->count());
        
        if ($pendingAlerts->isEmpty()) {
            Log::info("ℹ️ [EntryService] No se encontraron alertas pendientes o con orden enviada para el producto {$productId}");
            return;
        }

        foreach ($pendingAlerts as $alert) {
            $previousStatus = $alert->status;
            $alertId = $alert->id;
            
            Log::info("🔄 [EntryService] Resolviendo alerta ID {$alertId} - Estado actual: {$previousStatus}");
            
            $alert->update([
                'status' => Alert::STATUS_RESOLVED,
                'message' => $alert->message . ' (Resuelta por ingreso físico)',
                'resolved_at' => now(),
            ]);
            
            // Verificar que se actualizó correctamente
            $alert->refresh();
            Log::info("✅ [EntryService] Alerta ID {$alertId} actualizada. Estado anterior: {$previousStatus}, Estado nuevo: {$alert->status}");

            $statusLabel = $previousStatus === Alert::STATUS_ORDER_SENT ? 'orden enviada' : 'pendiente';
            Log::info("✅ Alerta {$statusLabel} ID {$alertId} resuelta automáticamente por ingreso físico del producto {$productId}.");
        }
    }

    /**
     * Genera el mensaje de alerta apropiado
     */
    private function generateMessage($product, int $stock, string $alertType): string
    {
        if ($alertType === Alert::TYPE_OUT_OF_STOCK) {
            return "El producto '{$product->name}' está sin stock (0 unidades).";
        }

        return "El producto '{$product->name}' tiene stock bajo ({$stock} unidades disponibles).";
    }

    /**
     * Resuelve manualmente una alerta
     */
    public function resolveAlert(int $id): Alert
    {
        $alert = Alert::findOrFail($id);

        if ($alert->status === Alert::STATUS_RESOLVED) {
            return $alert;
        }

        $alert->update([
            'status' => Alert::STATUS_RESOLVED,
            'message' => $alert->message . ' (Resuelta manualmente)',
            'resolved_at' => now(),
        ]);

        return $alert->fresh(['product', 'inventory']);
    }

    /**
     * Obtiene alertas con filtros opcionales
     */
    public function getAlerts(array $filters = []): Collection
    {
        $query = Alert::with(['product.suppliers', 'inventory']);

        if (!empty($filters['alert_type'])) {
            $query->where('alert_type', $filters['alert_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        return $query->orderBy('date', 'desc')
                     ->orderBy('created_at', 'desc')
                     ->get();
    }

    /**
     * Verifica el stock de todo el inventario
     */
    public function checkAllInventory(): void
    {
        $inventories = Inventory::with('product')->get();

        foreach ($inventories as $inventory) {
            try {
                $this->checkStock($inventory);
            } catch (\Exception $e) {
                Log::error("Error al verificar inventario {$inventory->id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Obtiene estadísticas de alertas
     */
    public function getStats(): array
    {
        return [
            'total'        => Alert::count(),
            'active'       => Alert::active()->count(),
            'resolved'     => Alert::resolved()->count(),
            'low_stock'    => Alert::lowStock()->count(),
            'out_of_stock' => Alert::outOfStock()->count(),
            'today'        => Alert::whereDate('date', today())->count(),
        ];
    }
}
