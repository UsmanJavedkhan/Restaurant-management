<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->text('address');
            $table->string('city');
            $table->string('area');
            $table->string('landmark')->nullable();
            $table->string('phone', 30);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('items');
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->uuid('request_id');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 30);
            $table->json('delivery_address')->nullable();
            $table->string('order_type', 20);
            foreach (['subtotal', 'discount', 'delivery_fee', 'tax', 'total'] as $field) {
                $table->decimal($field, 12, 2)->default(0);
            }
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('coupon_code')->nullable();
            $table->string('payment_method', 30);
            $table->string('payment_status', 20)->default('pending');
            $table->string('order_status', 30)->default('pending');
            $table->text('customer_note')->nullable();
            $table->json('status_history');
            $table->timestamps();
            $table->unique(['user_id', 'request_id']);
            $table->index(['order_status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('image')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->string('variation_name')->nullable();
            $table->decimal('variation_price', 12, 2)->nullable();
            $table->json('addons');
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('payment_method', 30);
            $table->decimal('amount', 12, 2);
            $table->string('payment_status', 20)->default('pending');
            $table->string('transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 30);
            $table->date('date');
            $table->string('time', 5);
            $table->unsignedInteger('guests');
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['contact_messages', 'reservations', 'coupon_usages', 'payments', 'order_items', 'orders', 'carts', 'addresses'] as $name) {
            Schema::dropIfExists($name);
        }
    }
};
