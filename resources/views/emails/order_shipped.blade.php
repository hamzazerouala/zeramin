<x-mail::message>
# Votre commande est en route 🚚

La commande **{{ $order->order_number }}** vient d'être expédiée.

@if ($order->aliexpress_tracking_id)
**Numéro de suivi :** {{ $order->aliexpress_tracking_id }}
@endif

@if ($order->tracking_url)
<x-mail::button :url="$order->tracking_url">
Suivre mon colis
</x-mail::button>
@endif

Merci pour votre confiance,<br>
{{ config('app.name') }}
</x-mail::message>
