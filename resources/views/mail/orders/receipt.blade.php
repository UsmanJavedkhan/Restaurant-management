<x-mail::message>
# Your order update

Order **{{ $details['order_number'] }}** is **{{ str_replace('_', ' ', $details['status']) }}**.

@foreach ($details['items'] as $item)
- {{ $item['quantity'] }} × {{ $item['name'] }} — {{ number_format($item['total'], 2) }}
@endforeach

**Total: {{ number_format($details['total'], 2) }}**

Sign in to your restaurant account to view delivery details and track progress.

Thank you for ordering with us.
</x-mail::message>
