<?php

namespace App\Notifications;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class StockAlertNotification extends Notification
{
    use Queueable;

    public Alert $alert;

    public function __construct(Alert $alert)
    {
        $this->alert = $alert;
    }

    /**
     * Canales por los que se enviará la notificación
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * ✉️ Contenido del correo
     */
    public function toMail($notifiable)
    {
        $inventory = $this->alert->inventory;
        $productName = $inventory?->product?->name ?? 'Producto desconocido';
        $stock = $inventory->stock ?? 'N/A';
        $minStock = $inventory->min_stock ?? 'N/A';
        $message = $this->alert->message ?? 'Se ha generado una alerta de stock.';
        $alertTypeLabel = $this->getAlertTypeLabel($this->alert->alert_type);

        return (new MailMessage)
            ->subject("⚠️ Alerta de Stock: {$productName}")
            ->greeting("Hola **" . ($notifiable->name ?? 'Administrador') . "**,")
            ->line("Se ha detectado un problema con el inventario del producto **{$productName}**.")
            ->line('')
            ->line("📦 **Existencias actuales:** {$stock} unidades")
            ->line("🔻 **Stock mínimo permitido:** {$minStock} unidades")
            ->line("⚠️ **Tipo de alerta:** {$alertTypeLabel}")
            ->line('')
            ->line("💬 **Detalle:** {$message}")
            ->line('')
            ->action('🔍 Ver alerta en el sistema', $this->getAlertUrl())
            ->line('')
            ->line('Por favor, revise el inventario y proceda con el reabastecimiento si es necesario.')
            ->line('Puede hacer clic en el botón de arriba para ver los detalles completos de la alerta y solicitar reabastecimiento.')
            ->salutation('Atentamente, **Smart Inventory**');
    }

    /**
     * 📥 Representación de la notificación para base de datos
     */
    public function toArray($notifiable)
    {
        $inventory = $this->alert->inventory;
        $productName = $inventory?->product?->name ?? 'Producto desconocido';
        $alertTypeLabel = $this->getAlertTypeLabel($this->alert->alert_type);

        return [
            'alert_id'   => $this->alert->id,
            'product'    => $productName,
            'message'    => $this->alert->message,
            'stock'      => $inventory->stock ?? null,
            'min_stock'  => $inventory->min_stock ?? null,
            'alert_type' => $this->alert->alert_type,  // critical / low_stock
            'level'      => $alertTypeLabel,          // para mostrar bonito
            'status'     => $this->alert->status,
            'created_at' => $this->alert->created_at,
        ];
    }

    /**
     * 🔹 Convierte alert_type en un label legible
     */
    private function getAlertTypeLabel(?string $type): string
    {
        return match($type) {
            'sin_stock'   => '🚫 Sin Stock',
            'bajo_stock'  => '📉 Stock Bajo',
            'critical'    => '🚨 Stock Crítico',
            'low_stock'   => '📊 Stock Bajo',
            default       => '⚠️ Alerta de Inventario',
        };
    }

    /**
     * 🔗 Genera la URL para ver la alerta específica en el sistema
     */
    private function getAlertUrl(): string
    {
        // Obtener la URL del frontend desde la configuración o usar una por defecto
        // Angular corre en el puerto 4200 por defecto
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:4200'));
        
        // Construir la URL con el ID de la alerta como parámetro de query
        return rtrim($frontendUrl, '/') . '/dashboard/alertas?alerta=' . $this->alert->id;
    }
}
