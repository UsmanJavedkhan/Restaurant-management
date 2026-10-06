<?php

namespace App\Services;

use App\Mail\OrderReceipt;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\DeliveryArea;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public const STATUSES = ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'completed', 'cancelled'];

    public function __construct(private RestaurantService $restaurant) {}

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function quote(array $data, bool $lock = false): array
    {
        $settings = $this->restaurant->settings();
        if (! $this->restaurant->isOpen($settings)) {
            $this->fail('restaurant', 'Restaurant is currently closed. Please order during opening hours.');
        }
        $query = Product::with(['category', 'variations', 'addons'])->whereIn('id', array_column($data['items'], 'id'))->orderBy('id');
        if ($lock) {
            $query->lockForUpdate();
        }
        $products = $query->get()->keyBy('id');
        $items = [];
        $subtotal = 0;
        $quantities = [];
        foreach ($data['items'] as $index => $item) {
            $product = $products->get($item['id']);
            if (! $product || ! $product->is_active || ! $product->is_available || ! $product->category->is_active) {
                $this->fail('items', 'A selected dish is no longer available. Please update your bag.');
            }
            $variation = null;
            $activeVariations = $product->variations->where('is_active', true);
            if ($activeVariations->isNotEmpty()) {
                $variation = $activeVariations->firstWhere('name', $item['variation'] ?? '');
                if (! $variation) {
                    $this->fail("items.$index.variation", 'Please select a valid size for '.$product->name.'.');
                }
            } elseif (! empty($item['variation'])) {
                $this->fail("items.$index.variation", 'This dish does not have that size.');
            }
            $addonNames = $item['addons'] ?? [];
            $addons = $product->addons->where('is_active', true)->whereIn('name', $addonNames);
            if ($addons->count() !== count($addonNames)) {
                $this->fail("items.$index.addons", 'An extra is no longer available. Please update your selection.');
            }
            sort($addonNames);
            $key = json_encode([$product->id, $variation?->name, $addonNames]);
            $quantities[$key] = ($quantities[$key] ?? 0) + $item['quantity'];
            if ($quantities[$key] > 20) {
                $this->fail('items', 'Maximum 20 of each dish configuration per order.');
            }
            $price = (int) round((float) ($variation?->price ?? $product->discount_price ?? $product->price) * 100);
            $addonSnapshot = [];
            foreach ($addons as $addon) {
                $price += (int) round((float) $addon->price * 100);
                $addonSnapshot[] = ['name' => $addon->name, 'price' => (float) $addon->price];
            }
            $total = $price * $item['quantity'];
            $subtotal += $total;
            $items[] = ['product_id' => $product->id, 'product_name' => $product->name, 'image' => $product->image, 'quantity' => $item['quantity'], 'unit_price' => $price / 100, 'variation_name' => $variation?->name, 'variation_price' => $variation?->price, 'addons' => $addonSnapshot, 'total' => $total / 100];
        }
        $area = null;
        $delivery = 0;
        if ($data['order_type'] === 'delivery') {
            if ($subtotal < (int) round((float) $settings->minimum_order * 100)) {
                $this->fail('minimum_order', 'Minimum delivery order is Rs. '.number_format((float) $settings->minimum_order, 2).'. Add more to your bag or choose pickup.');
            }
            $area = DeliveryArea::where('is_active', true)->find($data['delivery_area_id'] ?? null);
            if (! $area) {
                $this->fail('delivery_area_id', 'Please choose a supported delivery area.');
            }
            $delivery = (int) round((float) ($area->fee ?? $settings->delivery_charge) * 100);
        }
        $coupon = null;
        $discount = 0;
        if (! empty($data['coupon_code'])) {
            $couponQuery = Coupon::where('code', Str::upper(trim($data['coupon_code'])));
            if ($lock) {
                $couponQuery->lockForUpdate();
            }
            $coupon = $couponQuery->first();
            $today = now($settings->timezone)->toDateString();
            if (! $coupon || ! $coupon->is_active || ($coupon->start_date && $coupon->start_date->toDateString() > $today) || ($coupon->expiry_date && $coupon->expiry_date->toDateString() < $today) || ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit)) {
                $this->fail('coupon_code', 'This coupon is invalid, expired, or has reached its usage limit.');
            }
            if ($subtotal < (int) round((float) $coupon->minimum_order * 100)) {
                $this->fail('coupon_code', 'This coupon requires a subtotal of Rs. '.number_format((float) $coupon->minimum_order, 2).'.');
            }
            $discount = $coupon->discount_type === 'percentage' ? (int) round($subtotal * (float) $coupon->discount_value / 100) : (int) round((float) $coupon->discount_value * 100);
            if ($coupon->maximum_discount !== null) {
                $discount = min($discount, (int) round((float) $coupon->maximum_discount * 100));
            }
            $discount = min($discount, $subtotal);
        }
        $tax = (int) round(($subtotal - $discount) * (float) $settings->tax_percentage / 100);

        return ['items' => $items, 'subtotal' => $subtotal / 100, 'discount' => $discount / 100, 'delivery_fee' => $delivery / 100, 'tax' => $tax / 100, 'total' => ($subtotal - $discount + $delivery + $tax) / 100, 'coupon_id' => $coupon?->id, 'coupon_code' => $coupon?->code, 'area' => $area ? ['area' => $area->name, 'city' => $area->city] : null];
    }

    /** @param array<string, mixed> $data */
    public function create(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data): Order {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $existing = Order::where('user_id', $user->id)->where('request_id', $data['request_id'])->first();
            if ($existing) {
                return $existing->load(['items', 'payment']);
            }
            $expectedPayment = $data['order_type'] === 'delivery' ? 'cash_on_delivery' : 'cash_on_pickup';
            if ($data['payment_method'] !== $expectedPayment) {
                $this->fail('payment_method', 'Please select the cash payment method matching your order type.');
            }
            $quote = $this->quote($data, true);
            $order = Order::create([
                ...collect($quote)->only(['subtotal', 'discount', 'delivery_fee', 'tax', 'total', 'coupon_id', 'coupon_code'])->all(),
                'order_number' => 'ORD-'.now()->format('Ymd').'-'.Str::upper((string) Str::ulid()),
                'user_id' => $user->id, 'request_id' => $data['request_id'],
                'customer_name' => $data['customer_name'], 'customer_email' => $data['customer_email'], 'customer_phone' => $data['customer_phone'],
                'delivery_address' => $data['order_type'] === 'delivery' ? [...($data['delivery_address'] ?? []), ...$quote['area']] : null,
                'order_type' => $data['order_type'], 'payment_method' => $expectedPayment,
                'order_status' => 'pending', 'payment_status' => 'pending', 'customer_note' => $data['customer_note'] ?? null,
                'status_history' => [['status' => 'pending', 'at' => now()->toIso8601String()]],
            ]);
            $order->items()->createMany($quote['items']);
            $order->payment()->create(['payment_method' => $expectedPayment, 'amount' => $quote['total'], 'payment_status' => 'pending']);
            if ($quote['coupon_id']) {
                Coupon::whereKey($quote['coupon_id'])->increment('used_count');
                DB::table('coupon_usages')->insert(['coupon_id' => $quote['coupon_id'], 'order_id' => $order->id, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
            }
            Cart::where('user_id', $user->id)->update(['items' => json_encode([])]);
            $order->load(['items', 'payment']);
            $this->notify($order);

            return $order;
        }, 3);
    }

    public function notify(Order $order): void
    {
        $order->loadMissing(['user', 'items']);
        $order->user->notify(new OrderUpdated($order, false));
        $admins = User::where('role', 'admin')->where('is_active', true)->get();
        foreach ($admins as $admin) {
            $admin->notify(new OrderUpdated($order, true));
        }
        $payload = ['order_number' => $order->order_number, 'status' => $order->order_status, 'total' => (float) $order->total, 'items' => $order->items->map(fn ($item) => ['name' => $item->product_name, 'quantity' => $item->quantity, 'total' => (float) $item->total])->all()];
        if ($order->order_status === 'pending' && $admins->isNotEmpty()) {
            Mail::to($admins->pluck('email')->all())->queue((new OrderReceipt($payload))->afterCommit());
        }
        Mail::to($order->customer_email)->queue((new OrderReceipt($payload))->afterCommit());
    }

    public function changeStatus(Order $order, string $status): Order
    {
        return DB::transaction(function () use ($order, $status): Order {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            $transitions = ['pending' => ['confirmed', 'cancelled'], 'confirmed' => ['preparing', 'cancelled'], 'preparing' => ['ready', 'cancelled'], 'ready' => $order->order_type === 'delivery' ? ['out_for_delivery', 'cancelled'] : ['completed', 'cancelled'], 'out_for_delivery' => ['delivered', 'cancelled'], 'delivered' => ['completed'], 'completed' => [], 'cancelled' => []];
            if (! in_array($status, $transitions[$order->order_status], true)) {
                $this->fail('order_status', 'That status change is not allowed for this order.');
            }
            $history = $order->status_history;
            $history[] = ['status' => $status, 'at' => now()->toIso8601String()];
            $order->update(['order_status' => $status, 'status_history' => $history]);
            $this->notify($order);

            return $order->load(['items', 'payment']);
        }, 3);
    }
}
