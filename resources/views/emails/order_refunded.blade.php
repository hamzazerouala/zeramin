<x-mail::message>
# Remboursement de votre commande

La commande **{{ $order->order_number }}** a été remboursée.

@if ($reason)
**Motif :** {{ $reason }}
@endif

Le montant de **{{ number_format($order->total_amount, 2) }} {{ $order->currency }}** sera recrédité sous quelques jours ouvrés.

Toutes nos excuses pour la gêne occasionnée,<br>
{{ config('app.name') }}
</x-mail::message>
