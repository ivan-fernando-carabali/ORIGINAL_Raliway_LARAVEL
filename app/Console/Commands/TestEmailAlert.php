<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Alert;
use App\Models\User;
use App\Notifications\StockAlertNotification;

class TestEmailAlert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email-alert {--user_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía un correo de prueba de alerta de stock';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verificando configuración de correos...');
        $this->info('MAIL_MAILER: ' . config('mail.default'));
        $this->info('');

        // Buscar usuarios admin o empleado
        $users = User::whereHas('role', function ($query) {
            $query->whereIn('name', ['admin', 'empleado']);
        })->get();

        if ($users->isEmpty()) {
            $this->error('❌ No se encontraron usuarios con rol admin o empleado.');
            $this->info('Crea un usuario con rol admin o empleado primero.');
            return 1;
        }

        $this->info('✅ Usuarios encontrados:');
        foreach ($users as $user) {
            $this->line("   - ID: {$user->id} | {$user->name} {$user->lastname} | {$user->email}");
        }
        $this->info('');

        // Buscar una alerta de ejemplo
        $alert = Alert::with(['product', 'inventory'])->first();

        if (!$alert) {
            $this->error('❌ No se encontraron alertas en el sistema.');
            $this->info('Genera una alerta primero haciendo una salida que deje el stock bajo.');
            return 1;
        }

        $this->info("✅ Alerta de prueba encontrada: ID {$alert->id}");
        $this->info("   Producto: {$alert->product->name}");
        $this->info("   Tipo: {$alert->alert_type}");
        $this->info('');

        // Enviar notificación a cada usuario
        $this->info('📧 Enviando correos...');
        $bar = $this->output->createProgressBar($users->count());

        foreach ($users as $user) {
            try {
                $user->notify(new StockAlertNotification($alert));
                $bar->advance();
            } catch (\Exception $e) {
                $this->error("\n❌ Error enviando a {$user->email}: " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->info('');
        $this->info('');

        if (config('mail.default') === 'log') {
            $this->warn('⚠️  Los correos se guardaron en storage/logs/laravel.log');
            $this->info('Para enviar correos reales, configura MAIL_MAILER en tu archivo .env');
        } else {
            $this->info('✅ Correos enviados exitosamente!');
            $this->info('Revisa la bandeja de entrada de los usuarios.');
        }

        $this->info('');
        $this->info('📋 Ver configuración completa en: CONFIGURACION_CORREOS.md');

        return 0;
    }
}
