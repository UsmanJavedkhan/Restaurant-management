<x-mail::message>
# Welcome, {{ $customerName }}!

Your {{ $restaurantName }} account is ready. Explore the menu, save your addresses, and follow each order from our kitchen to your table.

<x-mail::button :url="url('/menu')">Explore the menu</x-mail::button>

See you soon,<br>
{{ $restaurantName }}
</x-mail::message>
