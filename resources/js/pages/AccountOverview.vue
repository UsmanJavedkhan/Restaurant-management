<script setup>
import { onMounted, ref } from 'vue';
import { api, errorMessage } from '../api';
import { money, statusLabel } from '../store';
const orders = ref([]);
const total = ref(0);
const error = ref('');
onMounted(async () => { try { const { data } = await api.get('/orders'); orders.value = data.data.slice(0,3); total.value = data.meta.total; } catch(failure) { error.value = errorMessage(failure); } });
</script>
<template><div class="panel"><div class="section-heading"><h2>Welcome to your table.</h2><router-link class="text-button" to="/menu">Order something good ↗</router-link></div><p>You’ve placed {{ total }} orders with us. Here’s what’s cooking.</p><div v-if="error" class="form-error" role="alert">{{ error }}</div><div v-for="order in orders" :key="order.id" class="account-order-row"><div><router-link :to="`/account/orders/${order.id}`">{{ order.order_number }}</router-link><small>{{ new Date(order.created_at).toLocaleDateString() }}</small></div><span class="status-badge" :data-status="order.order_status">{{ statusLabel(order.order_status) }}</span><strong>{{ money(order.total) }}</strong></div><div v-if="!orders.length && !error" class="empty-inline">Your first favorite is waiting. <router-link to="/menu">Explore the menu ↗</router-link></div></div></template>
