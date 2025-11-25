<x-mail::message>
# 📦 Solicitud de Reabastecimiento

Estimado/a **{{ $order->supplier->name ?? 'Proveedor' }}**,

Le solicitamos el reabastecimiento del siguiente producto:

<x-mail::panel>
**📦 Producto:** {{ $order->product->name }}  
**🔢 Cantidad:** {{ $order->quantity }} unidades  
**📊 Código de barras:** {{ $order->product->codigo_de_barras ?? $order->product->reference ?? 'N/D' }}  
**📅 Fecha solicitud:** {{ $order->created_at->format('d/m/Y H:i') }}  
**👤 Solicitado por:** {{ $order->user->name ?? 'N/D' }} {{ $order->user->lastname ?? '' }}
</x-mail::panel>

Quedamos atentos a su confirmación y a la fecha estimada de entrega.

Saludos cordiales,  
**{{ config('app.name') }}**
</x-mail::message>
