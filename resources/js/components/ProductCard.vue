<script setup>
import { foodImage, money, addToCart } from "../store";
import { useRouter } from 'vue-router';
const router = useRouter();
defineProps({ product: { type: Object, required: true } });
</script>
<template>
    <article class="product-card">
        <div class="product-photo">
            <img
                :src="foodImage(product.image)"
                :alt="product.name"
                loading="lazy"
            /><span class="food-tag">{{ product.tag }}</span
            ><span class="rating">{{ product.preparationTime }} min</span>
        </div>
        <div class="product-body">
            <span class="product-category">{{ product.category }}</span>
            <h3><router-link :to="`/products/${product.id}`">{{ product.name }}</router-link></h3>
            <p>{{ product.description }}</p>
            <div class="product-bottom">
                <strong><small v-if="product.variations?.length">From </small>{{ money(product.variations?.length ? Math.min(...product.variations.map(size=>size.price)) : product.discountPrice ?? product.price) }}<del v-if="!product.variations?.length && product.discountPrice !== null">{{ money(product.price) }}</del></strong
                ><button
                    class="add-button"
                    @click="product.variations?.length ? router.push(`/products/${product.id}`) : addToCart(product)"
                    :disabled="product.available === false"
                    :aria-label="product.variations?.length ? `Customize ${product.name}` : `Add ${product.name} to bag`"
                >
                    +
                </button>
            </div>
            <router-link :to="`/products/${product.id}`" class="product-detail-link">{{ product.available === false ? 'Currently unavailable · View details' : 'View details & extras ↗' }}</router-link>
        </div>
    </article>
</template>
