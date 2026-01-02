<x-mail::message>
# {{ __('Hello') }} {{ $order->user->name }},

{{-- Dynamic Message Based on Status --}}
@switch($order->status->value)
    @case('pending')
        {{ __('Thank you for your order! We have received it and are processing it.') }}
        @break
    @case('paid')
        {{ __('We have received your payment. Your order is being prepared.') }}
        @break
    @case('shipped')
        {{ __('Great news! Your order has been shipped.') }}
        @break
    @case('completed')
        {{ __('Your order has been completed. Thank you for shopping with us!') }}
        @break
    @case('cancelled')
        {{ __('Your order has been cancelled.') }}
        @break
@endswitch

{{-- Order Summary Table --}}
<x-mail::table>
| {{ __('Product') }} | {{ __('Qty') }} | {{ __('Price') }} |
| :--- | :---: | :---: |
@foreach($order->products as $product)
| {{ $product->getTranslation('name', app()->getLocale()) }} | {{ $product->pivot->quantity }} | {{ number_format($product->pivot->unit_price / 100, 2) }} € |
@endforeach
</x-mail::table>

**{{ __('Total') }}: {{ number_format($order->total_price / 100, 2) }} €**

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>