<script setup>
import { cartItems, changeQuantity, money, foodImage } from "../store";
</script>
<template>
    <div class="cart-items">
        <article v-for="item in cartItems" :key="item.key" class="cart-item">
            <img :src="foodImage(item.image, 300)" :alt="item.name" />
            <div class="cart-item-info">
                <span class="product-category">{{ item.category }}</span>
                <h3>{{ item.name }}</h3>
                <p v-if="item.customization" class="cart-customization">{{ item.customization }}</p>
                <span>{{ money(item.price) }}</span
                ><button
                    class="remove-button"
                    @click="changeQuantity(item.key, 0)"
                >
                    Remove
                </button>
            </div>
            <div class="quantity-control">
                <button
                    @click="changeQuantity(item.key, item.quantity - 1)"
                    :aria-label="`Decrease ${item.name} quantity`"
                >
                    −</button
                ><span>{{ item.quantity }}</span
                ><button
                    @click="changeQuantity(item.key, item.quantity + 1)"
                    :disabled="item.quantity >= 20"
                    :aria-label="`Increase ${item.name} quantity`"
                >
                    +
                </button>
            </div>
            <strong>{{ money(item.price * item.quantity) }}</strong>
        </article>
    </div>
</template>
