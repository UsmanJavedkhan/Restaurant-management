<script setup>
import { ref, watch } from 'vue';
import { cartCount, user, settings, isOpen, logout, notify } from '../store';
import { errorMessage } from '../api';
import { useRoute, useRouter } from 'vue-router';
defineEmits(['book']);
const route=useRoute();const mobileOpen=ref(false);
const router = useRouter();
const signingOut = ref(false);
watch(()=>route.fullPath,()=>{mobileOpen.value=false;});
const links=[{to:'/',label:'Home'},{to:'/menu',label:'Our menu'},{to:'/deals',label:'Deals'},{to:'/about',label:'About us'},{to:'/contact',label:'Contact'}];
async function signOut() {
    if (signingOut.value) return;
    signingOut.value = true;
    try {
        await logout();
        mobileOpen.value = false;
        await router.push('/login');
        notify('You have been logged out.');
    } catch (error) {
        notify(errorMessage(error));
    } finally {
        signingOut.value = false;
    }
}
</script>
<template>
    <div class="announcement">Good food. Great company. <span>Made fresh, every day.</span><span class="announcement-right">{{ isOpen ? 'OPEN FOR ORDERS' : 'CURRENTLY CLOSED' }}</span></div>
    <header class="site-header">
        <div class="container nav-inner">
            <router-link to="/" class="brand" :aria-label="`${settings.name} home`">
                <img v-if="settings.logo" :src="settings.logo" alt="" class="brand-logo">
                <span v-else class="brand-icon">♨</span>
                <template v-if="settings.name === 'Ember & Oak'">ember<span class="brand-amp">&</span>oak<span class="brand-dot">.</span></template>
                <template v-else>{{ settings.name }}</template>
            </router-link>
            <nav class="desktop-nav" aria-label="Main navigation">
                <router-link v-for="link in links" :key="link.to" :to="link.to" :class="{active:route.path===link.to}">{{ link.label }}</router-link>
            </nav>
            <div class="nav-actions" :class="{ 'signed-in-actions': user }">
                <router-link to="/menu?focus=search" class="nav-search" aria-label="Search menu">⌕</router-link>
                <router-link :to="user?.role === 'admin' ? '/admin' : user ? '/account' : '/login'" class="account-link">{{ user?.role === 'admin' ? 'Dashboard' : user ? 'My account' : 'Sign in' }}</router-link>
                <button v-if="user" type="button" class="nav-logout" :disabled="signingOut" @click="signOut">{{ signingOut ? 'Logging out…' : 'Log out' }}</button>
                <router-link to="/cart" class="cart-link" aria-label="Shopping bag"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 7h14l1 14H4L5 7Z"/><path d="M8 8V6a4 4 0 0 1 8 0v2"/></svg><span>{{ cartCount }}</span></router-link>
                <button class="button button-small book-nav" @click="$emit('book')">Book a table ↗</button>
                <button class="mobile-toggle" @click="mobileOpen=!mobileOpen" :aria-expanded="mobileOpen" aria-controls="mobile-navigation" aria-label="Toggle navigation">☰</button>
            </div>
        </div>
        <nav v-if="mobileOpen" id="mobile-navigation" class="mobile-nav" aria-label="Mobile navigation">
            <router-link v-for="link in links" :key="link.to" :to="link.to">{{ link.label }}</router-link>
            <router-link to="/menu?focus=search">Search</router-link>
            <router-link :to="user ? '/account' : '/login'">{{ user ? 'My account' : 'Sign in / Register' }}</router-link>
            <router-link v-if="user?.role === 'admin'" to="/admin">Admin dashboard</router-link>
            <router-link v-if="!user" to="/admin/login">Admin sign in</router-link>
            <button v-if="user" type="button" :disabled="signingOut" @click="signOut">{{ signingOut ? 'Logging out…' : 'Log out' }}</button>
            <button @click="mobileOpen=false;$emit('book')">Book a table ↗</button>
        </nav>
    </header>
</template>

<style scoped>
.nav-logout { font-size: 12px; white-space: nowrap; color: #a74727; padding: 8px 0; }
.nav-logout:hover { text-decoration: underline; }
.nav-logout:disabled { opacity: .6; cursor: wait; }
.nav-logout:focus-visible { outline: 2px solid currentColor; outline-offset: 4px; border-radius: 2px; }
@media (max-width: 1100px) { .signed-in-actions .book-nav { display: none; } }
@media (max-width: 800px) { .nav-logout { display: none; } }
</style>
