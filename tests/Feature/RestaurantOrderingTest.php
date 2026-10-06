<?php

namespace Tests\Feature;

use App\Mail\OrderReceipt;
use App\Mail\WelcomeCustomer;
use App\Models\Address;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\DeliveryArea;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductVariation;
use App\Models\RestaurantSetting;
use App\Models\User;
use App\Services\RestaurantService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class RestaurantOrderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        RestaurantSetting::factory()->create(['minimum_order' => 0, 'tax_percentage' => 10]);
    }

    private function customer(): User
    {
        return User::factory()->create();
    }

    private function admin(): User
    {
        $user = $this->customer();
        $user->forceFill(['role' => 'admin'])->save();

        return $user;
    }

    private function payload(Product $product): array
    {
        return ['items' => [['id' => $product->id, 'quantity' => 2, 'addons' => []]], 'order_type' => 'pickup', 'customer_name' => 'Test Customer', 'customer_email' => 'customer@example.test', 'customer_phone' => '03001234567', 'payment_method' => 'cash_on_pickup', 'request_id' => (string) Str::uuid()];
    }

    public function test_checkout_recalculates_prices_and_is_idempotent(): void
    {
        $user = $this->customer();
        $product = Product::factory()->create(['price' => 500, 'discount_price' => 400]);
        $payload = $this->payload($product);
        $payload['total'] = 1;
        $payload['items'][0]['price'] = 1;
        $this->actingAs($user)->postJson('/api/v1/orders', $payload)->assertCreated()->assertJsonPath('data.total', 880)->assertJsonPath('data.payment_status', 'pending');
        $this->postJson('/api/v1/orders', $payload)->assertSuccessful();
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('order_items', ['unit_price' => 400, 'total' => 800]);
        Mail::assertQueued(OrderReceipt::class, 1);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_delivery_uses_area_fee_discount_cap_and_tax(): void
    {
        $product = Product::factory()->create(['price' => 1000, 'discount_price' => null]);
        $area = DeliveryArea::factory()->create(['fee' => 150]);
        $coupon = Coupon::factory()->create(['minimum_order' => 0, 'maximum_discount' => 300]);
        $payload = $this->payload($product);
        $payload['order_type'] = 'delivery';
        $payload['delivery_area_id'] = $area->id;
        $payload['coupon_code'] = $coupon->code;
        $payload['payment_method'] = 'cash_on_delivery';
        $payload['delivery_address'] = ['address' => '12 Sample Street'];
        $this->actingAs($this->customer())->postJson('/api/v1/orders', $payload)->assertCreated()->assertJsonPath('data.total', 2020)->assertJsonPath('data.discount', 300);
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'used_count' => 1]);
    }

    public function test_unavailable_dishes_and_unknown_customizations_are_rejected(): void
    {
        $product = Product::factory()->create(['is_available' => false]);
        $payload = $this->payload($product);
        $this->actingAs($this->customer())->postJson('/api/v1/orders', $payload)->assertUnprocessable();
        $product->update(['is_available' => true]);
        $payload['items'][0]['addons'] = ['Imaginary extra'];
        $this->postJson('/api/v1/orders', $payload)->assertUnprocessable();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_same_extra_on_different_sizes_is_valid_but_duplicate_extra_is_rejected(): void
    {
        $product = Product::factory()->create();
        ProductVariation::factory()->create(['product_id' => $product->id, 'name' => 'Small', 'price' => 300]);
        ProductVariation::factory()->create(['product_id' => $product->id, 'name' => 'Large', 'price' => 600]);
        ProductAddon::factory()->create(['product_id' => $product->id, 'name' => 'Cheese', 'price' => 50]);
        $payload = $this->payload($product);
        $payload['items'] = [['id' => $product->id, 'quantity' => 1, 'variation' => 'Small', 'addons' => ['Cheese']], ['id' => $product->id, 'quantity' => 1, 'variation' => 'Large', 'addons' => ['Cheese']]];
        $this->actingAs($this->customer())->postJson('/api/v1/orders/quote', $payload)->assertOk()->assertJsonPath('data.total', 1100);
        $payload['items'][0]['addons'] = ['Cheese', 'Cheese'];
        $this->postJson('/api/v1/orders/quote', $payload)->assertUnprocessable();
    }

    public function test_closed_restaurant_and_exhausted_coupon_prevent_orders(): void
    {
        $product = Product::factory()->create();
        $payload = $this->payload($product);
        RestaurantSetting::query()->update(['accepting_orders' => false]);
        $this->actingAs($this->customer())->postJson('/api/v1/orders', $payload)->assertUnprocessable();
        RestaurantSetting::query()->update(['accepting_orders' => true]);
        $coupon = Coupon::factory()->create(['usage_limit' => 1, 'used_count' => 1, 'minimum_order' => 0]);
        $payload['coupon_code'] = $coupon->code;
        $this->postJson('/api/v1/orders', $payload)->assertUnprocessable();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_customer_cannot_access_another_account_or_admin_functions(): void
    {
        $owner = $this->customer();
        $order = Order::factory()->create(['user_id' => $owner->id]);
        $address = Address::factory()->create(['user_id' => $owner->id]);
        $this->actingAs($this->customer())->getJson('/api/v1/orders/'.$order->id)->assertNotFound();
        $this->deleteJson('/api/v1/addresses/'.$address->id)->assertNotFound();
        $this->getJson('/api/v1/admin/reports')->assertForbidden();
        $this->assertDatabaseHas('addresses', ['id' => $address->id]);
    }

    public function test_registration_cannot_grant_admin_role_and_disabled_customer_cannot_login(): void
    {
        $this->postJson('/api/v1/register', ['name' => 'New Customer', 'email' => 'new@example.test', 'phone' => '03001234567', 'password' => 'Password123', 'password_confirmation' => 'Password123', 'role' => 'admin', 'is_active' => false])->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'new@example.test', 'role' => 'customer', 'is_active' => true]);
        $this->postJson('/api/v1/logout')->assertOk();
        $this->customer()->forceFill(['email' => 'disabled@example.test', 'password' => 'Password123', 'is_active' => false])->save();
        $this->postJson('/api/v1/login', ['email' => 'disabled@example.test', 'password' => 'Password123'])->assertUnprocessable();
    }

    public function test_status_workflow_and_cash_records_are_consistent(): void
    {
        $order = Order::factory()->create(['order_type' => 'pickup', 'total' => 550]);
        $order->payment()->create(['payment_method' => 'cash_on_pickup', 'amount' => 550, 'payment_status' => 'pending']);
        $this->actingAs($this->admin())->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['order_status' => 'completed'])->assertUnprocessable();
        foreach (['confirmed', 'preparing', 'ready', 'completed'] as $status) {
            $this->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['order_status' => $status])->assertOk();
        }$this->patchJson('/api/v1/admin/orders/'.$order->id.'/payment', ['payment_status' => 'paid'])->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'order_status' => 'completed', 'payment_status' => 'paid']);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'payment_status' => 'paid', 'amount' => 550]);
    }

    public function test_admin_can_create_update_and_soft_delete_products(): void
    {
        $category = Category::factory()->create();
        $payload = ['category_id' => $category->id, 'name' => 'Sample Dish', 'slug' => 'sample-dish', 'description' => 'A freshly prepared dish', 'price' => 500, 'discount_price' => 450, 'preparation_time' => 20, 'is_active' => true, 'is_available' => true, 'is_featured' => false, 'is_deal' => false, 'variations' => [['name' => 'Small', 'price' => 300, 'is_active' => true]], 'addons' => [['name' => 'Cheese', 'price' => 50, 'is_active' => true]]];
        $id = $this->actingAs($this->admin())->postJson('/api/v1/admin/products', $payload)->assertCreated()->json('data.id');
        $payload['price'] = 600;
        $this->putJson('/api/v1/admin/products/'.$id, $payload)->assertOk();
        $this->assertDatabaseHas('products', ['id' => $id, 'price' => 600]);
        $this->deleteJson('/api/v1/admin/categories/'.$category->id)->assertUnprocessable();
        $this->deleteJson('/api/v1/admin/products/'.$id)->assertOk();
        $this->assertSoftDeleted('products', ['id' => $id]);
        $this->getJson('/api/v1/products/'.$id)->assertNotFound();
    }

    public function test_admin_report_uses_restaurant_local_date_and_excludes_cancelled_sales(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 5)->setTime(12, 0));
        RestaurantSetting::query()->update(['timezone' => 'Asia/Karachi']);
        Order::factory()->create(['created_at' => '2026-10-04 22:00:00', 'order_status' => 'completed', 'total' => 700]);
        Order::factory()->create(['created_at' => '2026-10-05 03:00:00', 'order_status' => 'cancelled', 'total' => 500]);
        $this->actingAs($this->admin())->getJson('/api/v1/admin/reports?from=2026-10-05&to=2026-10-05')->assertOk()->assertJsonPath('data.revenue', 700)->assertJsonPath('data.orders', 2)->assertJsonPath('data.daily_sales.0.date', '2026-10-05');
    }

    public function test_saved_cart_can_be_cleared_and_is_private(): void
    {
        $user = $this->customer();
        $product = Product::factory()->create();
        $this->actingAs($user)->putJson('/api/v1/cart', ['items' => [['id' => $product->id, 'quantity' => 2, 'price' => 1]]])->assertOk();
        $this->getJson('/api/v1/cart')->assertJsonPath('items.0.quantity', 2)->assertJsonMissingPath('items.0.price');
        $this->actingAs($this->customer())->getJson('/api/v1/cart')->assertJsonPath('items', []);
        $this->actingAs($user)->putJson('/api/v1/cart', ['items' => []])->assertOk()->assertJsonPath('items', []);
    }

    public function test_saved_addresses_keep_one_default_and_only_owner_can_update(): void
    {
        $user = $this->customer();
        $payload = ['label' => 'Home', 'address' => '12 Sample Street', 'city' => 'Lahore', 'area' => 'Model Town', 'phone' => '03001234567', 'is_default' => true];
        $id = $this->actingAs($user)->postJson('/api/v1/addresses', $payload)->assertCreated()->json('data.id');
        $payload['label'] = 'Office';
        $this->postJson('/api/v1/addresses', $payload)->assertCreated();
        $this->assertDatabaseHas('addresses', ['id' => $id, 'is_default' => false]);
        $this->assertSame(1, $user->addresses()->where('is_default', true)->count());
        $this->actingAs($this->customer())->putJson('/api/v1/addresses/'.$id, $payload)->assertNotFound();
    }

    public function test_password_reset_and_password_change_require_valid_credentials(): void
    {
        $user = $this->customer();
        $token = Password::createToken($user);
        $payload = ['email' => $user->email, 'token' => 'invalid', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'];
        $this->postJson('/api/v1/reset-password', $payload)->assertUnprocessable();
        $payload['token'] = $token;
        $this->postJson('/api/v1/reset-password', $payload)->assertOk();
        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
        $this->actingAs($user->fresh())->putJson('/api/v1/password', ['current_password' => 'incorrect', 'password' => 'AnotherPassword123', 'password_confirmation' => 'AnotherPassword123'])->assertUnprocessable();
    }

    public function test_admin_coupon_can_have_expiry_without_start_date_and_images_reject_non_images(): void
    {
        $this->actingAs($this->admin())->postJson('/api/v1/admin/coupons', ['code' => 'SAVE20', 'discount_type' => 'percentage', 'discount_value' => 20, 'minimum_order' => 0, 'start_date' => null, 'expiry_date' => '2027-01-01', 'is_active' => true])->assertCreated();
        Storage::fake('public');
        $this->postJson('/api/v1/admin/images', ['image' => UploadedFile::fake()->create('script.php', 1, 'application/x-php')])->assertUnprocessable();
        $path = $this->postJson('/api/v1/admin/images', ['image' => UploadedFile::fake()->image('food.jpg')])->assertCreated()->json('image');
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $path));
    }

    public function test_overnight_hours_and_disabled_days_are_enforced_in_restaurant_timezone(): void
    {
        $settings = RestaurantSetting::first();
        $hours = array_fill(0, 7, ['enabled' => false, 'open' => '12:00', 'close' => '23:00']);
        $hours[1] = ['enabled' => true, 'open' => '20:00', 'close' => '02:00'];
        $settings->update(['opening_hours' => $hours, 'timezone' => 'Asia/Karachi']);
        $service = app(RestaurantService::class);
        $this->assertTrue($service->isOpen($settings, CarbonImmutable::parse('2026-10-06 01:30', 'Asia/Karachi')));
        $this->assertFalse($service->isOpen($settings, CarbonImmutable::parse('2026-10-06 02:00', 'Asia/Karachi')));
        $this->assertFalse($service->isOpen($settings, CarbonImmutable::parse('2026-10-05 19:59', 'Asia/Karachi')));
    }

    public function test_contact_and_reservation_requests_reach_staff_and_past_reservations_are_rejected(): void
    {
        $this->postJson('/api/v1/contact', ['name' => 'Guest', 'email' => 'guest@example.test', 'message' => 'Do you have vegetarian options?'])->assertCreated();
        $this->postJson('/api/v1/reservations', ['name' => 'Guest', 'phone' => '03001234567', 'date' => now()->subDay()->toDateString(), 'time' => '19:00', 'guests' => 2])->assertUnprocessable();
        $id = $this->postJson('/api/v1/reservations', ['name' => 'Guest', 'phone' => '03001234567', 'date' => now()->addDay()->toDateString(), 'time' => '19:00', 'guests' => 2])->assertCreated()->json('data.id');
        $this->actingAs($this->admin())->patchJson('/api/v1/admin/reservations/'.$id, ['status' => 'confirmed'])->assertOk();
        $this->getJson('/api/v1/admin/messages')->assertOk()->assertJsonPath('data.0.name', 'Guest');
    }

    public function test_queued_email_templates_render_order_details_and_customer_names(): void
    {
        $welcome = new WelcomeCustomer('Preview Customer', 'Ember & Oak');
        $this->assertStringContainsString('Preview Customer', $welcome->render());
        $receipt = new OrderReceipt(['order_number' => 'ORD-TEST-123', 'status' => 'confirmed', 'total' => 550, 'items' => [['name' => 'Test Burger', 'quantity' => 1, 'total' => 550]]]);
        $this->assertStringContainsString('ORD-TEST-123', $receipt->render());
        $this->assertStringContainsString('Test Burger', $receipt->render());
    }

    public function test_disabled_sessions_expire_and_guest_order_access_requires_login(): void
    {
        $this->getJson('/api/v1/orders')->assertUnauthorized();
        $user = $this->customer();
        $user->forceFill(['is_active' => false])->save();
        $this->actingAs($user)->getJson('/api/v1/session')->assertOk()->assertJsonPath('user', null);
        $this->assertGuest();
    }
}
