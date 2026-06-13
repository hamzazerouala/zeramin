<x-mail::message>
# Merci pour votre commande !

Votre commande **{{ $order->order_number }}** a bien été enregistrée.

<x-mail::table>
| Produit | Qté | Prix |
|:--------|:---:|-----:|
@foreach ($order->items as $item)
| {{ $item->title_snapshot }} | {{ $item->quantity }} | {{ number_format($item->subtotal, 2) }} {{ $order->currency }} |
@endforeach
</x-mail::table>

**Sous-total :** {{ number_format($order->subtotal, 2) }} {{ $order->currency }}
**Livraison :** {{ number_format($order->shipping_cost, 2) }} {{ $order->currency }}
**Total :** {{ number_format($order->total_amount, 2) }} {{ $order->currency }}

Nous vous préviendrons dès l'expédition.

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
