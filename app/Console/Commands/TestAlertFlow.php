<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Alert;
use App\Models\User;
use App\Notifications\StockAlertNotification;

class TestAlertFlow extends Command
{
    protected $signature = 'test:alert-flow {alert_id?}';
    protected $description = 'Prueba el flujo completo de alertas: envía correo y muestra la URL';

    public function handle()
    {
        $this->info('🧪 Probando flujo completo de alertas...');
        $this->info('');

        // Buscar alerta
        $alertId = $this->argument('alert_id');
        
        if ($alertId) {
            $alert = Alert::with(['product.suppliers', 'inventory'])->find($alertId);
        } else {
            $alert = Alert::with(['product.suppliers', 'inventory'])->first();
        }

        if (!$alert) {
            $this->error('❌ No se encontró ninguna alerta.');
            $this->info('Genera una alerta primero haciendo una salida que deje el stock bajo.');
            return 1;
        }

        $this->info("✅ Alerta encontrada:");
        $this->line("   ID: {$alert->id}");
        $this->line("   Producto: {$alert->product->name}");
        $this->line("   Tipo: {$alert->alert_type}");
        $this->line("   Stock actual: {$alert->inventory->stock}");
        $this->line("   Stock mínimo: {$alert->inventory->min_stock}");
        $this->info('');

        // Buscar usuario
        $user = User::whereHas('role', function ($query) {
            $query->whereIn('name', ['admin', 'empleado']);
        })->first();

        if (!$user) {
            $this->error('❌ No se encontró ningún usuario admin/empleado.');
            return 1;
        }

        $this->info("📧 Enviando correo a: {$user->email}");
        
        try {
            $user->notify(new StockAlertNotification($alert));
            $this->info('✅ Correo enviado exitosamente!');
        } catch (\Exception $e) {
            $this->error("❌ Error al enviar correo: " . $e->getMessage());
            return 1;
        }

        $this->info('');
        $this->info('🔗 URL de la alerta en el sistema:');
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:4200'));
        $alertUrl = rtrim($frontendUrl, '/') . '/dashboard/alertas?alerta=' . $alert->id;
        $this->line("   {$alertUrl}");
        $this->info('');

        $this->info('📋 Pasos siguientes:');
        $this->line('1. Revisa tu correo: ' . $user->email);
        $this->line('2. Haz clic en "🔍 Ver alerta en el sistema"');
        $this->line('3. Deberías ser redirigido a la página de alertas');
        $this->line('4. La alerta debería resaltarse automáticamente');
        $this->line('5. Podrás hacer clic en "🛒 Reabastecer"');

        return 0;
    }
}


