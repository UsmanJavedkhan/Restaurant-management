<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { api, errorMessage } from '../api';
import { money, statusLabel, reorder } from '../store';
const orders = ref([]);
const page = ref(1);
const lastPage = ref(1);
const busy = ref(false);
const error = ref('');
const router = useRouter();
async function load() { busy.value = true; try { const { data } = await api.get('/orders',{params:{page:page.value}}); orders.value = data.data; lastPage.value = data.meta.last_page; error.value = ''; } catch(failure) { error.value = errorMessage(failure); } finally { busy.value = false; } }
async function orderAgain(order) { busy.value = true; try { await reorder(order); await router.push('/cart'); } finally { busy.value = false; } }
onMounted(load);
</script>
<template><div class="panel"><div class="section-heading"><h2>My orders</h2><button class="text-button" @click="load" :disabled="busy">Refresh ↻</button></div><p>Your delicious history, all in one place.</p><div v-if="error" class="form-error" role="alert">{{ error }}</div><div v-for="order in orders" :key="order.id" class="order-history-card"><div class="account-order-row"><div><router-link :to="`/account/orders/${order.id}`">{{ order.order_number }}</router-link><small>{{ new Date(order.created_at).toLocaleString() }} · {{ statusLabel(order.order_type) }}</small></div><span class="status-badge" :data-status="order.order_status">{{ statusLabel(order.order_status) }}</span><strong>{{ money(order.total) }}</strong></div><p>{{ order.items.map(item => `${item.quantity} × ${item.product_name}`).join(', ') }}</p><div class="row-actions"><router-link :to="`/account/orders/${order.id}`" class="text-button">View & track order →</router-link><button v-if="['completed','delivered'].includes(order.order_status)" class="text-button" @click="orderAgain(order)" :disabled="busy">Order again ↗</button></div></div><p v-if="!orders.length && !busy">No orders yet. Your favorites are waiting on the menu.</p><div class="pagination"><button :disabled="page <= 1 || busy" @click="page--; load()">← Previous</button><span>{{ page }} / {{ lastPage }}</span><button :disabled="page >= lastPage || busy" @click="page++; load()">Next →</button></div></div></template>
