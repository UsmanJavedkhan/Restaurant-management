<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { api, errorMessage } from '../api';
const notifications=ref([]);const unread=ref(0);const error=ref('');
async function load(){try{const {data}=await api.get('/notifications');notifications.value=data.data;unread.value=data.unread_count;error.value='';}catch(failure){error.value=errorMessage(failure);}}
async function markRead(){try{await api.patch('/notifications/read');await load();}catch(failure){error.value=errorMessage(failure);}}
onMounted(load);const timer=setInterval(load,15000);onUnmounted(()=>clearInterval(timer));
</script>
<template><div class="panel"><div class="section-heading"><h2>Notifications</h2><button class="text-button" @click="markRead">Mark all as read</button></div><p>{{ unread }} unread updates</p><div v-if="error" class="form-error" role="alert">{{ error }}</div><router-link v-for="item in notifications" :key="item.id" :to="item.data.url" class="notification-row" :class="{unread:!item.read_at}"><span>♨</span><div><strong>{{ item.data.message }}</strong><small>{{ new Date(item.created_at).toLocaleString() }}</small></div><span>→</span></router-link><p v-if="!notifications.length">We’ll keep you posted as your orders make their way through the kitchen.</p></div></template>
