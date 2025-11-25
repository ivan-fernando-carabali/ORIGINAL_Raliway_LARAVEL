<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Pedido</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 650px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header p {
            margin: 8px 0 0;
            font-size: 14px;
            opacity: 0.95;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .message {
            font-size: 15px;
            color: #555;
            line-height: 1.7;
            margin-bottom: 30px;
        }
        .order-details {
            background: #f8fafc;
            border-left: 4px solid #667eea;
            padding: 25px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .order-details h2 {
            margin: 0 0 20px;
            font-size: 20px;
            color: #2c3e50;
            font-weight: 700;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #64748b;
            font-size: 14px;
        }
        .detail-value {
            color: #1e293b;
            font-weight: 500;
            font-size: 14px;
        }
        .highlight {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
            border-left: 4px solid #f59e0b;
        }
        .highlight strong {
            color: #92400e;
            font-size: 16px;
        }
        .alert-info {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 14px;
            color: #7f1d1d;
        }
        .footer {
            background: #f8fafc;
            padding: 25px 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            margin: 5px 0;
            font-size: 13px;
            color: #64748b;
        }
        .footer strong {
            color: #1e293b;
        }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            background: #dbeafe;
            color: #1e40af;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }
        @media only screen and (max-width: 600px) {
            .container {
                margin: 10px;
            }
            .content {
                padding: 25px 20px;
            }
            .detail-row {
                flex-direction: column;
            }
            .detail-value {
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📦 Nueva Orden de Pedido</h1>
            <p>Orden #{{ $order->id }} | {{ now()->format('d/m/Y H:i') }}</p>
        </div>

        <!-- Content -->
        <div class="content">
            <p class="greeting">Estimado/a <strong>{{ $supplier->name }}</strong>,</p>

            <p class="message">
                Le informamos que hemos generado una nueva orden de pedido para reabastecer nuestro inventario.
                A continuación, encontrará los detalles completos de la solicitud.
            </p>

            <!-- Order Details -->
            <div class="order-details">
                <h2>📋 Detalles de la Orden</h2>

                <div class="detail-row">
                    <span class="detail-label">🏷️ Producto:</span>
                    <span class="detail-value">{{ $product->name }}</span>
                </div>

                @if($product->reference)
                <div class="detail-row">
                    <span class="detail-label">🔢 Referencia:</span>
                    <span class="detail-value">{{ $product->reference }}</span>
                </div>
                @endif

                <div class="detail-row">
                    <span class="detail-label">📊 Cantidad Solicitada:</span>
                    <span class="detail-value"><strong style="color: #667eea;">{{ $order->quantity }} unidades</strong></span>
                </div>

                @if($alert && $alert->inventory)
                <div class="detail-row">
                    <span class="detail-label">📦 Lote:</span>
                    <span class="detail-value">{{ $alert->inventory->lot_number ?? 'N/A' }}</span>
                </div>
                @endif

                <div class="detail-row">
                    <span class="detail-label">📅 Fecha de Solicitud:</span>
                    <span class="detail-value">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                </div>

                @if($user)
                <div class="detail-row">
                    <span class="detail-label">👤 Solicitado por:</span>
                    <span class="detail-value">{{ $user->name }}</span>
                </div>
                @endif

                @if($order->notes)
                <div class="detail-row">
                    <span class="detail-label">📝 Notas:</span>
                    <span class="detail-value">{{ $order->notes }}</span>
                </div>
                @endif
            </div>

            <!-- Highlight -->
            <div class="highlight">
                <strong>⚡ Solicitud urgente:</strong> Esta orden fue generada automáticamente debido a un nivel crítico de stock en nuestro inventario.
            </div>

            <!-- Alert Info -->
            @if($alert)
            <div class="alert-info">
                <strong>🚨 Motivo de la solicitud:</strong><br>
                {{ $alert->message }}
            </div>
            @endif

            <p class="message">
                Por favor, confirme la recepción de esta orden y proporcione una fecha estimada de entrega
                respondiendo a este correo electrónico.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>{{ config('app.name', 'Sistema de Inventario') }}</strong></p>
            <p>Este es un correo automático generado por nuestro sistema de gestión de inventario.</p>
            <p>Para cualquier consulta, por favor contacte a nuestro departamento de compras.</p>
        </div>
    </div>
</body>
</html>
