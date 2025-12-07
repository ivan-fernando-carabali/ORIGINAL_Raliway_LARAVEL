<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Alert;
use App\Mail\SupplierOrderMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $orders = Order::with(['product', 'supplier', 'alert', 'inventory'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'status' => 'success',
                'message' => 'Listado de órdenes obtenido correctamente',
                'data' => $orders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener órdenes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created order from alert.
     */
    public function store(Request $request)
    {
        try {
            // Validar datos
            $validated = $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'inventory_id' => 'nullable|integer|exists:inventories,id',
                'supplier_id' => 'nullable|integer|exists:suppliers,id',
                'quantity' => 'required|integer|min:1',
                'alert_id' => 'nullable|integer|exists:alerts,id',
                'user_id' => 'required|integer|exists:users,id',
                'supplier_email' => 'nullable|email',
                'date' => 'nullable|date', // Aceptar date del request o usar fecha actual
                'status' => 'nullable|string|in:pendiente,enviado,recibido,cancelado',
            ]);

            // Preparar datos para crear la orden
            // Asegurar que date siempre tenga un valor válido en formato Y-m-d
            $dateValue = now()->format('Y-m-d'); // Valor por defecto
            if (!empty($validated['date'])) {
                // Validar y normalizar el formato de fecha
                $inputDate = $validated['date'];
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $inputDate)) {
                    $dateValue = $inputDate;
                } else {
                    // Intentar convertir otros formatos
                    try {
                        $carbonDate = \Carbon\Carbon::parse($inputDate);
                        $dateValue = $carbonDate->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Si falla, usar fecha actual
                        $dateValue = now()->format('Y-m-d');
                    }
                }
            }
            
            // Preparar array de datos asegurando que date esté presente
            $orderData = [
                'product_id' => $validated['product_id'],
                'inventory_id' => $validated['inventory_id'] ?? null,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'quantity' => $validated['quantity'],
                'alert_id' => $validated['alert_id'] ?? null,
                'user_id' => $validated['user_id'],
                'supplier_email' => $validated['supplier_email'] ?? null,
                'dep_buy_id' => null, // Campo nullable según migración
                'date' => $dateValue, // SIEMPRE debe tener valor en formato Y-m-d
                'status' => $validated['status'] ?? 'pendiente',
            ];
            
            // Validar que date esté presente antes de crear
            if (empty($orderData['date'])) {
                $orderData['date'] = now()->format('Y-m-d');
            }
            
            Log::info('📦 Creando orden con datos:', $orderData);
            Log::info('📅 Valor de date (string): ' . $dateValue . ' (tipo: ' . gettype($dateValue) . ')');
            Log::info('📅 Valor de date en orderData: ' . ($orderData['date'] ?? 'NO DEFINIDO'));
            
            // Crear la orden usando DB::table directamente para asegurar que date se guarde correctamente
            try {
                // VALIDACIÓN FINAL: Asegurar que date nunca sea null o vacío
                $finalDate = $orderData['date'];
                if (empty($finalDate) || !is_string($finalDate)) {
                    $finalDate = now()->format('Y-m-d');
                    Log::warning('⚠️ Campo date estaba vacío o inválido, usando fecha actual: ' . $finalDate);
                }
                
                // Validar formato de fecha una vez más
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $finalDate)) {
                    $finalDate = now()->format('Y-m-d');
                    Log::warning('⚠️ Formato de date inválido, usando fecha actual: ' . $finalDate);
                }
                
                // Preparar array de inserción con date garantizado
                $insertData = [
                    'product_id' => $orderData['product_id'],
                    'inventory_id' => $orderData['inventory_id'],
                    'supplier_id' => $orderData['supplier_id'],
                    'quantity' => $orderData['quantity'],
                    'alert_id' => $orderData['alert_id'],
                    'user_id' => $orderData['user_id'],
                    'supplier_email' => $orderData['supplier_email'],
                    'dep_buy_id' => $orderData['dep_buy_id'],
                    'date' => $finalDate, // Campo date GARANTIZADO con valor válido
                    'status' => $orderData['status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                Log::info('📋 Datos finales para inserción:', $insertData);
                Log::info('📅 Valor final de date: ' . $finalDate . ' (tipo: ' . gettype($finalDate) . ')');
                
                // Verificar una vez más que date esté presente y sea válido
                if (!isset($insertData['date']) || empty($insertData['date'])) {
                    $insertData['date'] = now()->format('Y-m-d');
                    Log::warning('⚠️ Campo date faltante en insertData, usando fecha actual: ' . $insertData['date']);
                }
                
                // Usar DB::table para insertar directamente y evitar problemas con el modelo
                try {
                    $orderId = DB::table('orders')->insertGetId($insertData);
                    Log::info('✅ Orden creada exitosamente con ID: ' . $orderId);
                } catch (\Illuminate\Database\QueryException $qe) {
                    // Si el error es específicamente sobre el campo date, intentar con SQL directo
                    if (strpos($qe->getMessage(), "Field 'date'") !== false) {
                        Log::error('❌ Error específico con campo date, intentando inserción con SQL directo');
                        // Intentar inserción con SQL directo incluyendo explícitamente el campo date
                        $sql = "INSERT INTO `orders` (`product_id`, `inventory_id`, `supplier_id`, `quantity`, `alert_id`, `user_id`, `supplier_email`, `dep_buy_id`, `date`, `status`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        DB::insert($sql, [
                            $insertData['product_id'],
                            $insertData['inventory_id'],
                            $insertData['supplier_id'],
                            $insertData['quantity'],
                            $insertData['alert_id'],
                            $insertData['user_id'],
                            $insertData['supplier_email'],
                            $insertData['dep_buy_id'],
                            $insertData['date'], // Campo date explícitamente incluido
                            $insertData['status'],
                            $insertData['created_at'],
                            $insertData['updated_at']
                        ]);
                        $orderId = DB::getPdo()->lastInsertId();
                        Log::info('✅ Orden creada exitosamente con SQL directo, ID: ' . $orderId);
                    } else {
                        throw $qe;
                    }
                }
                
                // Cargar la orden usando el modelo para tener acceso a las relaciones
                $order = Order::with(['product', 'supplier', 'alert', 'inventory', 'user'])->find($orderId);
                
                if (!$order) {
                    throw new \Exception('No se pudo cargar la orden después de crearla');
                }
            } catch (\Exception $e) {
                Log::error('❌ Error al crear orden: ' . $e->getMessage());
                Log::error('📋 Datos que se intentaron guardar: ' . json_encode($orderData));
                Log::error('📋 Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }

            // Intentar resolver proveedor si no vino
            if (!$order->supplier_id) {
                $product = Product::with('suppliers')->find($validated['product_id']);
                if ($product && $product->suppliers->count() > 0) {
                    $order->supplier_id = $product->suppliers->first()->id;
                    $order->save();
                    $order->load('supplier');
                }
            }

            // Determinar email del proveedor (priorizar email del request, luego el del proveedor)
            $supplierEmail = null;
            if ($order->supplier_email) {
                $supplierEmail = $order->supplier_email;
            } elseif ($order->supplier && $order->supplier->email) {
                $supplierEmail = $order->supplier->email;
            }

            // Enviar email al proveedor si existe email
            $emailSent = false;
            $emailError = null;
            
            if ($supplierEmail) {
                try {
                    Log::info('📧 ========== INICIANDO ENVÍO DE EMAIL ==========');
                    Log::info('📧 Destinatario: ' . $supplierEmail);
                    Log::info('📧 Orden ID: ' . $order->id);
                    
                    // Verificar configuración de correo
                    $mailConfig = [
                        'MAIL_MAILER' => env('MAIL_MAILER', 'not set'),
                        'MAIL_HOST' => env('MAIL_HOST', 'not set'),
                        'MAIL_PORT' => env('MAIL_PORT', 'not set'),
                        'MAIL_USERNAME' => env('MAIL_USERNAME', 'not set'),
                        'MAIL_ENCRYPTION' => env('MAIL_ENCRYPTION', 'not set'),
                        'MAIL_FROM_ADDRESS' => env('MAIL_FROM_ADDRESS', 'not set'),
                        'MAIL_FROM_NAME' => env('MAIL_FROM_NAME', 'not set'),
                    ];
                    Log::info('📧 Configuración de correo (env):', $mailConfig);
                    
                    // Verificar configuración cargada
                    Log::info('📧 Configuración de correo (config):');
                    Log::info('  - default: ' . config('mail.default'));
                    Log::info('  - host: ' . config('mail.mailers.smtp.host'));
                    Log::info('  - port: ' . config('mail.mailers.smtp.port'));
                    Log::info('  - encryption: ' . config('mail.mailers.smtp.encryption'));
                    Log::info('  - username: ' . (config('mail.mailers.smtp.username') ? 'SET' : 'NOT SET'));
                    Log::info('  - from.address: ' . config('mail.from.address'));
                    Log::info('  - from.name: ' . config('mail.from.name'));
                    
                    // Verificar si el Mailable está en cola
                    $mailable = new SupplierOrderMail($order);
                    if ($mailable instanceof \Illuminate\Contracts\Queue\ShouldQueue) {
                        Log::warning('⚠️ El Mailable está en cola. Verifica que el queue worker esté corriendo.');
                    }
                    
                    // Intentar enviar el correo
                    Log::info('📧 Enviando correo...');
                    Mail::to($supplierEmail)->send($mailable);
                    Log::info('✅ Email de orden enviado exitosamente a: ' . $supplierEmail);
                    $emailSent = true;
                    
                    // Actualizar estado a 'enviado' si se envió email correctamente
                    $order->status = 'enviado';
                    $order->sent_at = now();
                    $order->save();
                    Log::info('✅ Estado de orden actualizado a "enviado"');
                } catch (\Swift_TransportException $e) {
                    $emailError = 'Error de transporte SMTP: ' . $e->getMessage();
                    Log::error('❌ Error SMTP enviando email de orden a ' . $supplierEmail . ': ' . $emailError);
                    Log::error('❌ Código de error: ' . $e->getCode());
                    Log::error('❌ Stack trace: ' . $e->getTraceAsString());
                } catch (\Exception $e) {
                    $emailError = $e->getMessage();
                    Log::error('❌ Error general enviando email de orden a ' . $supplierEmail . ': ' . $emailError);
                    Log::error('❌ Clase de excepción: ' . get_class($e));
                    Log::error('❌ Stack trace: ' . $e->getTraceAsString());
                    // No fallar la orden si el email falla, pero mantener estado 'pendiente'
                }
                Log::info('📧 ========== FIN ENVÍO DE EMAIL ==========');
            } else {
                Log::warning('⚠️ No se envió email: no hay email de proveedor disponible');
                Log::info('⚠️ supplier_email en orden: ' . ($order->supplier_email ?? 'null'));
                Log::info('⚠️ supplier->email: ' . ($order->supplier->email ?? 'null'));
            }


            // Marcar alerta como pendiente (orden enviada) si existe
            if ($order->alert) {
                $order->alert->update([
                    'status' => Alert::STATUS_ACTIVE, // 'pendiente' = orden enviada
                    'resolved_at' => null
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Orden creada exitosamente',
                'data' => $order,
                'email_sent' => $emailSent,
                'email_address' => $supplierEmail ?? null,
                'email_error' => $emailError ?? null
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creando orden: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al crear la orden',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $order = Order::with(['product', 'supplier', 'alert', 'inventory'])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Orden encontrada',
                'data' => $order
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Orden no encontrada'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $order = Order::findOrFail($id);

            $validated = $request->validate([
                'status' => 'sometimes|string|in:pendiente,enviado,recibido,cancelado',
                'quantity' => 'sometimes|integer|min:1',
            ]);

            $order->update($validated);
            $order->load(['product', 'supplier', 'alert', 'inventory']);

            return response()->json([
                'status' => 'success',
                'message' => 'Orden actualizada correctamente',
                'data' => $order
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Orden no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar la orden',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $order = Order::findOrFail($id);
            $order->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Orden eliminada correctamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Orden no encontrada'
            ], 404);
        }
    }

    /**
     * Get orders by supplier
     */
    public function bySupplier($supplierId)
    {
        try {
            $orders = Order::where('supplier_id', $supplierId)
                ->with(['product', 'alert', 'inventory'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'status' => 'success',
                'data' => $orders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener órdenes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get orders by product
     */
    public function byProduct($productId)
    {
        try {
            $orders = Order::where('product_id', $productId)
                ->with(['supplier', 'alert', 'inventory'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'status' => 'success',
                'data' => $orders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener órdenes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics
     */
    public function statistics()
    {
        try {
            $stats = [
                'total' => Order::count(),
                'pending' => Order::where('status', 'pendiente')->count(),
                'sent' => Order::where('status', 'enviado')->count(),
                'completed' => Order::where('status', 'recibido')->count(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update status
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $order = Order::findOrFail($id);

            $validated = $request->validate([
                'status' => 'required|string|in:pendiente,enviado,recibido,cancelado'
            ]);

            $order->update(['status' => $validated['status']]);
            $order->load(['product', 'supplier', 'alert', 'inventory']);

            return response()->json([
                'status' => 'success',
                'message' => 'Estado de orden actualizado',
                'data' => $order
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Estado inválido',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resend email
     */
    public function resendEmail($id)
    {
        try {
            $order = Order::with(['supplier', 'product'])->findOrFail($id);

            if (!$order->supplier || !$order->supplier->email) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se puede reenviar el email: orden sin proveedor o email'
                ], 400);
            }

            Mail::to($order->supplier->email)->send(new SupplierOrderMail($order));

            Log::info('Email reenviado para orden: ' . $order->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Email reenviado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al reenviar email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
