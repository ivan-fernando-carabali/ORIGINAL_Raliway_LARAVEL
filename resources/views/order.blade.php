<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .header {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        .content {
            background: white;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .greeting {
            margin-bottom: 20px;
        }
        .order-details {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .order-details h3 {
            margin-top: 0;
            color: #1976d2;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #2c3e50;
        }
        .value {
            color: #555;
        }
        .alert-info {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .urgent {
            color: #e74c3c;
            font-weight: bold;
        }
        .notes {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .notes h4 {
            margin-top: 0;
        }
        .footer {
            text-align: center;
            color: #777;
            font-size: 12px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Nueva Orden de Compra</h1>
            <p>Orden #{{ $order_id }}</p>
        </div>

        <div class="content">
            <div class="greeting">
                <p>Estimado/a <strong>{{ $supplier_name }}</strong>,</p>

                <p>Le informamos que se ha generado una nueva orden de compra debido a
                @if($alert_type === 'sin_stock')
                    <span class="urgent">FALTA DE STOCK</span>
                @else
                    bajo nivel de stock
                @endif
                en nuestro inventario.</p>
            </div>

            @if($alert_type === 'sin_stock')
            <div class="alert-info">
                <strong>⚠️ URGENTE:</strong> Este producto está sin stock. Requerimos atención prioritaria.
            </div>
            @endif

            <div class="order-details">
                <h3>Detalles de la Orden</h3>

                <div class="detail-row">
                    <span class="label">Producto:</span>
                    <span class="value">{{ $product_name }}</span>
                </div>

                <div class="detail-row">
                    <span class="label">Cantidad solicitada:</span>
                    <span class="value"><strong>{{ $quantity }} unidades</strong></span>
                </div>

                <div class="detail-row">
                    <span class="label">Stock actual:</span>
                    <span class="value">{{ $product_stock ?? 0 }} unidades</span>
                </div>

                <div class="detail-row">
                    <span class="label">Fecha de orden:</span>
                    <span class="value">{{ $order_date->format('d/m/Y H:i') }}</span>
                </div>

                <div class="detail-row">
                    <span class="label">Estado:</span>
                    <span class="value">{{ ucfirst($order->status) }}</span>
                </div>
            </div>

            @if($notes)
            <div class="notes">
                <h4>📝 Notas adicionales:</h4>
                <p>{{ $notes }}</p>
            </div>
            @endif

            <p>Por favor, confirme la disponibilidad y tiempo estimado de entrega a la brevedad posible.</p>

            <p>Para cualquier consulta o aclaración, no dude en contactarnos.</p>

            <p style="margin-top: 30px;">
                Saludos cordiales,<br>
                <strong>Equipo de Gestión de Inventario</strong>
            </p>
        </div>

        <div class="footer">
            <p>Este es un correo automático generado por el Sistema de Gestión de Inventario</p>
            <p>Por favor no responda directamente a este correo</p>
            <p style="margin-top: 10px;">© {{ date('Y') }} Sistema de Inventario. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
