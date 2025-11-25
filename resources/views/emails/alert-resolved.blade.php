
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta Resuelta</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .alert-box {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-label {
            font-weight: bold;
            color: #6b7280;
        }
        .info-value {
            color: #111827;
        }
        .footer {
            background: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Alerta Resuelta</h1>
            <p style="margin: 10px 0 0 0;">El stock se ha normalizado</p>
        </div>

        <div class="content">
            <p>Hola,</p>

            <p>Te informamos que la siguiente alerta ha sido <strong>resuelta</strong>:</p>

            <div class="alert-box">
                <h3 style="margin: 0 0 10px 0; color: #059669;">{{ $alert->product->name }}</h3>
                <p style="margin: 0;">{{ $alert->message }}</p>
            </div>

            <div style="margin: 20px 0;">
                <div class="info-row">
                    <span class="info-label">Producto:</span>
                    <span class="info-value">{{ $alert->product->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Stock Actual:</span>
                    <span class="info-value">{{ $alert->inventory->stock ?? 'N/A' }} unidades</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Estado:</span>
                    <span class="info-value">
                        <span class="badge badge-success">RESUELTA</span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Resuelta el:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($alert->resolved_at)->locale('es')->isoFormat('D [de] MMMM [de] YYYY [a las] H:mm:ss') }}</span>
                </div>
            </div>

            <p style="margin-top: 20px; color: #059669; font-weight: bold;">
                ✓ El producto ya cuenta con stock suficiente.
            </p>
        </div>

        <div class="footer">
            <p>Este es un correo automático del sistema de gestión de inventario.</p>
            <p>Por favor no responder a este correo.</p>
        </div>
    </div>
</body>
</html>
