<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { products, foodImage, money, addToCart, loadProduct } from '../store';
import { errorMessage } from '../api';

const route = useRoute();
const product = computed(() => products.find(item => String(item.id) === route.params.id));
const selectedSize = ref('');
const selectedExtras = ref([]);
const quantity = ref(1);
const loading = ref(false);
const loadError = ref('');
watch(() => route.params.id, async id => {
    loading.value = true;
    loadError.value = '';
    try { await loadProduct(id); } catch (error) { loadError.value = errorMessage(error); }
    finally { loading.value = false; }
}, { immediate: true });
const sizes = computed(() => product.value?.variations || []);
const extras = computed(() => product.value?.addons || []);
const selectedVariation = computed(() => sizes.value.find(size => size.name === selectedSize.value));
const unitPrice = computed(() => (selectedVariation.value?.price ?? product.value?.discountPrice ?? product.value?.price ?? 0) + extras.value.filter(extra => selectedExtras.value.includes(extra.name)).reduce((sum, extra) => sum + extra.price, 0));

watch(product, () => {
    selectedSize.value = sizes.value[0]?.name || '';
    selectedExtras.value = [];
    quantity.value = 1;
}, { immediate: true });

function addConfiguredItem() {
    if (!product.value || product.value.available === false) return;
    addToCart(product.value, {
        variation: selectedSize.value,
        addons: selectedExtras.value,
        quantity: quantity.value,
    });
}
</script>

<template>
    <section class="container section-space">
        <router-link to="/menu" class="text-button">← Back to the menu</router-link>
        <div v-if="loadError" class="form-error" role="alert">{{ loadError }}</div>
        <div v-if="product && !loadError" class="product-detail-layout">
            <img class="product-detail-image" :src="foodImage(product.image, 1200)" :alt="product.name">
            <div class="product-detail-copy">
                <div class="eyebrow">{{ product.category }} · MADE TO ORDER</div>
                <h1>{{ product.name }}</h1>
                <p>{{ product.description }}</p>
                <ul v-if="product.isDeal" class="feature-list"><li v-for="item in product.dealContents" :key="item">{{ item }}</li></ul>
                <div class="detail-price"><strong>{{ money(unitPrice) }}</strong><del v-if="product.discountPrice">{{ money(product.price) }}</del><span>{{ product.preparationTime || 20 }}–{{ (product.preparationTime || 20) + 10 }} min</span></div>
                <p v-if="product.available === false" class="availability-message">Currently unavailable. Please explore our other dishes.</p>
                <form @submit.prevent="addConfiguredItem">
                    <fieldset v-if="sizes.length" class="option-group">
                        <legend>Choose your size</legend>
                        <label v-for="size in sizes" :key="size.name" class="option-row"><span><input v-model="selectedSize" type="radio" name="size" :value="size.name" required> {{ size.name }}</span><strong>{{ money(size.price) }}</strong></label>
                    </fieldset>
                    <fieldset v-if="extras.length" class="option-group">
                        <legend>Make it yours <small>Optional extras</small></legend>
                        <label v-for="extra in extras" :key="extra.name" class="option-row"><span><input v-model="selectedExtras" type="checkbox" :value="extra.name"> {{ extra.name }}</span><strong>+ {{ money(extra.price) }}</strong></label>
                    </fieldset>
                    <div class="detail-actions"><label>Quantity<select v-model.number="quantity"><option v-for="count in 20" :key="count" :value="count">{{ count }}</option></select></label><button class="button" type="submit" :disabled="product.available === false">Add to bag · {{ money(unitPrice * quantity) }} <span>+</span></button></div>
                </form>
                <p class="detail-allergy">Have an allergy? Include it in your checkout notes and contact the restaurant before ordering.</p>
            </div>
        </div>
        <div v-else class="empty-state"><h2>{{ loading ? 'Fresh from our kitchen…' : 'This dish isn’t on the menu.' }}</h2><router-link v-if="!loading" to="/menu" class="button">Explore the menu ↗</router-link></div>
    </section>
</template>
