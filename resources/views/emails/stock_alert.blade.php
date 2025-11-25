<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alerta de Stock</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 30px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background: #fff; border-radius: 10px; padding: 20px; margin: auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <tr>
            <td style="text-align: center;">
                <h2 style="color: #d9534f;">⚠️ Alerta de Inventario</h2>
            </td>
        </tr>

        <tr>
            <td>
                <p style="font-size: 16px;">Estimado usuario,</p>
                <p style="font-size: 15px;">
                    Se ha detectado una alerta de stock en el sistema:
                </p>

                <ul style="font-size: 15px; color: #333;">
                    <li><strong>Producto:</strong> {{ $productName }}</li>
                    <li><strong>Tipo de alerta:</strong> {{ strtoupper(str_replace('_', ' ', $alertType)) }}</li>
                    <li><strong>Mensaje:</strong> {{ $message }}</li>
                    <li><strong>Fecha:</strong>{{ \Carbon\Carbon::parse($alert->resolved_at)->locale('es')->isoFormat('D [de] MMMM [de] YYYY [a las] H:mm:ss') }}</li>
                    <li><strong>Estado:</strong> {{ ucfirst($status) }}</li>
                </ul>

                <p style="font-size: 15px;">
                    Por favor, revise el inventario y tome las acciones necesarias.
                </p>

                <p style="font-size: 15px;">Saludos,<br><strong>Smart Inventory</strong></p>
            </td>
        </tr>

        <tr>
            <td style="text-align: center; font-size: 12px; color: #888; padding-top: 20px;">
                © {{ date('Y') }} Smart Inventory. Todos los derechos reservados.
            </td>
        </tr>
    </table>
</body>
</html>
