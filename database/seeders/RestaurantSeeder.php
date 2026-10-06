<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\DeliveryArea;
use App\Models\Product;
use App\Models\RestaurantSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RestaurantSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Burgers', 'Broast', 'Pizza', 'Shawarma', 'Sandwiches', 'Wraps', 'Fries', 'Nuggets', 'Chicken', 'Rice', 'Drinks', 'Desserts', 'Sides', 'Deals'] as $index => $name) {
            Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'sort_order' => $index, 'is_active' => true]);
        }
        $foods = [
            ['The Signature Burger', 'Burgers', 850, 'Flame-grilled beef, aged cheddar, crisp greens & our secret sauce.', 'photo-1568901346375-23c9450c58cd'],
            ['Crispy Chicken Broast', 'Broast', 950, 'Golden, perfectly seasoned chicken with fries & garlic dip.', 'photo-1626082927389-6cd097cdc6ec'],
            ['Loaded Cheese Fries', 'Sides', 450, 'Crispy skin-on fries, melted cheese & a little extra happiness.', 'photo-1573080496219-bb080dd4f877'],
            ['Classic Margherita', 'Pizza', 1200, 'Fresh mozzarella, tomato sauce & basil on a stone-baked crust.', 'photo-1574071318508-1cdbab80d002'],
            ['Smoky BBQ Burger', 'Burgers', 1050, 'Juicy beef, smoky barbecue sauce, cheddar & caramelized onions.', 'photo-1550547660-d9450f859349'],
            ['Fresh Lime Cooler', 'Drinks', 280, 'Fresh lime, mint & sparkling water. Your refreshing little escape.', 'photo-1513558161293-cdaf765edfd7'],
            ['Chocolate Brownie', 'Desserts', 550, 'Warm chocolate brownie, vanilla ice cream & chocolate drizzle.', 'photo-1606313564200-e75d5e30476c'],
            ['Garden Fresh Salad', 'Sides', 580, 'Seasonal greens, cherry tomatoes & our lemon herb dressing.', 'photo-1512621776951-a57141f2eefd'],
            ['Family Feast', 'Deals', 2500, 'Two signature burgers, two fries and two lime coolers. A little something for everyone.', 'photo-1568901346375-23c9450c58cd'],
        ];
        foreach ($foods as $index => [$name, $category, $price, $description, $image]) {
            $product = Product::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'category_id' => Category::where('name', $category)->firstOrFail()->id, 'price' => $price, 'description' => $description, 'image' => $image, 'is_featured' => $index < 4, 'is_deal' => $category === 'Deals', 'discount_price' => $category === 'Deals' ? 1999 : null, 'deal_contents' => $category === 'Deals' ? ['2 Signature Burgers', '2 Fries', '2 Lime Coolers'] : [], 'preparation_time' => $category === 'Pizza' ? 25 : 15]);
            if ($product->wasRecentlyCreated) {
                if ($category === 'Pizza') {
                    $product->variations()->createMany([['name' => 'Small', 'price' => 700], ['name' => 'Medium', 'price' => 1200], ['name' => 'Large', 'price' => 1500]]);
                }
                if (in_array($category, ['Burgers', 'Broast', 'Sides', 'Pizza'], true)) {
                    $product->addons()->createMany([['name' => 'Extra cheese', 'price' => 100], ['name' => 'Extra sauce', 'price' => 50], ['name' => 'Jalapeños', 'price' => 50]]);
                }
            }
        }
        RestaurantSetting::firstOrCreate(['id' => 1], ['name' => 'Ember & Oak', 'opening_hours' => array_fill(0, 7, ['enabled' => true, 'open' => '12:00', 'close' => '23:00'])]);
        foreach ([['Johar Town', 150], ['Model Town', 180], ['DHA', 250]] as [$name, $fee]) {
            DeliveryArea::firstOrCreate(['name' => $name], ['city' => 'Lahore', 'fee' => $fee]);
        }
        Coupon::firstOrCreate(['code' => 'WELCOME20'], ['discount_type' => 'percentage', 'discount_value' => 20, 'minimum_order' => 1500, 'maximum_discount' => 500, 'expiry_date' => now()->endOfYear()->toDateString(), 'usage_limit' => 1000]);
    }
}
