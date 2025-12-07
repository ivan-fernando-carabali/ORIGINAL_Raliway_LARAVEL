<?php

namespace App\Services;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class EmailService
{
    /**
     * Enviar email de orden al proveedor
     *
     * @param Order $order
     * @return array
     */
    public function sendOrderEmail(Order $order): array
    {
        try {
            // Cargar relaciones necesarias
            $order->load(['product', 'supplier', 'alert']);

            // Validar que exista el proveedor y su email
            if (!$order->supplier) {
                return [
                    'success' => false,
                    'message' => 'Proveedor no encontrado'
                ];
            }

            if (!$order->supplier->email) {
                return [
                    'success' => false,
                    'message' => 'El proveedor no tiene email configurado'
                ];
            }

            // Validar que exista el producto
            if (!$order->product) {
                return [
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ];
            }

            // Preparar datos para el email
            $data = [
                'order' => $order,
                'supplier_name' => $order->supplier->name,
                'supplier_email' => $order->supplier->email,
                'product_name' => $order->product->name,
                'quantity' => $order->quantity,
                'notes' => $order->notes,
                'order_date' => $order->order_date,
                'order_id' => $order->id,
                'product_stock' => $order->product->stock,
                'alert_type' => $order->alert ? $order->alert->alert_type : null,
            ];

            // Enviar el email
            Mail::send('emails.order', $data, function ($message) use ($order, $data) {
                $message->to($order->supplier->email, $order->supplier->name)
                    ->subject("Nueva Orden de Compra #{$order->id} - {$data['product_name']}")
                    ->from(
                        Config::get('mail.from.address'),
                        Config::get('mail.from.name')
                    );
            });

            // Marcar email como enviado
            $order->markEmailAsSent();

            Log::info("Email de orden enviado exitosamente", [
                'order_id' => $order->id,
                'supplier_email' => $order->supplier->email,
                'product_name' => $order->product->name,
                'timestamp' => now()
            ]);

            return [
                'success' => true,
                'message' => 'Email enviado exitosamente',
                'email' => $order->supplier->email
            ];

        } catch (\Exception $e) {
            Log::error("Error al enviar email de orden", [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error al enviar email: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generar preview del email (para testing)
     *
     * @param Order $order
     * @return string
     */
    public function getEmailPreview(Order $order): string
    {
        try {
            $order->load(['product', 'supplier', 'alert']);

            $data = [
                'order' => $order,
                'supplier_name' => $order->supplier->name,
                'product_name' => $order->product->name,
                'quantity' => $order->quantity,
                'notes' => $order->notes,
                'order_date' => $order->order_date,
                'order_id' => $order->id,
                'product_stock' => $order->product->stock,
                'alert_type' => $order->alert ? $order->alert->alert_type : null,
            ];

            return view('emails.order', $data)->render();

        } catch (\Exception $e) {
            Log::error("Error al generar preview de email", [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return "Error al generar preview: " . $e->getMessage();
        }
    }

    /**
     * Enviar emails en lote (para múltiples órdenes)
     *
     * @param array $orderIds
     * @return array
     */
    public function sendBulkEmails(array $orderIds): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'total' => count($orderIds)
        ];

        foreach ($orderIds as $orderId) {
            try {
                $order = Order::with(['product', 'supplier', 'alert'])->find($orderId);

                if (!$order) {
                    $results['failed'][] = [
                        'order_id' => $orderId,
                        'error' => 'Orden no encontrada'
                    ];
                    continue;
                }

                $result = $this->sendOrderEmail($order);

                if ($result['success']) {
                    $results['success'][] = [
                        'order_id' => $orderId,
                        'email' => $result['email']
                    ];
                } else {
                    $results['failed'][] = [
                        'order_id' => $orderId,
                        'error' => $result['message']
                    ];
                }

            } catch (\Exception $e) {
                $results['failed'][] = [
                    'order_id' => $orderId,
                    'error' => $e->getMessage()
                ];
            }
        }

        Log::info("Envío de emails en lote completado", [
            'total' => $results['total'],
            'success' => count($results['success']),
            'failed' => count($results['failed'])
        ]);

        return $results;
    }

    /**
     * Verificar configuración de email
     *
     * @return array
     */
    public function checkEmailConfiguration(): array
    {
        try {
            $config = [
                'mailer' => Config::get('mail.mailer'),
                'host' => Config::get('mail.host'),
                'port' => Config::get('mail.port'),
                'encryption' => Config::get('mail.encryption'),
                'from_address' => Config::get('mail.from.address'),
                'from_name' => Config::get('mail.from.name'),
            ];

            $isConfigured = !empty($config['host']) &&
                           !empty($config['from_address']);

            return [
                'success' => true,
                'configured' => $isConfigured,
                'config' => $config
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al verificar configuración: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Enviar email de prueba
     *
     * @param string $testEmail
     * @return array
     */
    public function sendTestEmail(string $testEmail): array
    {
        try {
            Mail::raw('Este es un email de prueba del Sistema de Inventario', function ($message) use ($testEmail) {
                $message->to($testEmail)
                    ->subject('Email de Prueba - Sistema de Inventario')
                    ->from(
                        Config::get('mail.from.address'),
                        Config::get('mail.from.name')
                    );
            });

            Log::info("Email de prueba enviado", [
                'email' => $testEmail,
                'timestamp' => now()
            ]);

            return [
                'success' => true,
                'message' => 'Email de prueba enviado exitosamente',
                'email' => $testEmail
            ];

        } catch (\Exception $e) {
            Log::error("Error al enviar email de prueba", [
                'email' => $testEmail,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error al enviar email de prueba: ' . $e->getMessage()
            ];
        }
    }
}
