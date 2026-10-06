<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable();
            $table->string('role', 20)->default('customer');
            $table->boolean('is_active')->default(true);
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('image')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('discount_price', 12, 2)->nullable();
            $table->unsignedInteger('preparation_time')->default(20);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deal')->default(false);
            $table->json('deal_contents')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        foreach (['product_variations', 'product_addons'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->decimal('price', 12, 2);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['product_id', 'name']);
            });
        }
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('discount_type', 20);
            $table->decimal('discount_value', 12, 2);
            $table->decimal('minimum_order', 12, 2)->default(0);
            $table->decimal('maximum_discount', 12, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('delivery_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('city');
            $table->decimal('fee', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('restaurant_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Ember & Oak');
            $table->string('logo')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('currency', 3)->default('PKR');
            $table->string('timezone')->default('Asia/Karachi');
            $table->decimal('minimum_order', 12, 2)->default(700);
            $table->decimal('delivery_charge', 12, 2)->default(150);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->boolean('accepting_orders')->default(true);
            $table->json('opening_hours');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['restaurant_settings', 'delivery_areas', 'coupons', 'product_addons', 'product_variations', 'products', 'categories'] as $name) {
            Schema::dropIfExists($name);
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['phone', 'role', 'is_active']));
    }
};
