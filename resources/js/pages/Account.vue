<script setup>
import { useRouter } from 'vue-router';
import { user, logout, notify } from '../store';
import { errorMessage } from '../api';
const router = useRouter();
async function signOut() { try { await logout(); await router.push('/'); } catch(error) { notify(errorMessage(error)); } }
</script>
<template><section class="container section-space account-page"><div class="page-heading"><div class="eyebrow">YOUR OWN LITTLE CORNER</div><h1>Hello, <span class="serif accent">{{ user?.name.split(' ')[0] }}.</span></h1><p>Your favorites, your details, and every delicious order.</p></div><div class="account-layout"><nav class="account-nav" aria-label="Account navigation"><router-link to="/account" exact-active-class="selected">Overview</router-link><router-link to="/account/orders">My orders</router-link><router-link to="/account/addresses">Addresses</router-link><router-link to="/account/profile">Profile</router-link><router-link to="/account/password">Change password</router-link><router-link to="/account/notifications">Notifications</router-link><router-link v-if="user?.role === 'admin'" to="/admin">Admin dashboard ↗</router-link><button @click="signOut">Sign out</button></nav><div class="account-content"><router-view /></div></div></section></template>
