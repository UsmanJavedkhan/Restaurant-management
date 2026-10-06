<script setup>
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { authenticate, user, settings } from '../store';
import { api, errorMessage, updateCsrf } from '../api';
const props = defineProps({ mode: { type: String, default: 'login' } });
const route = useRoute();
const router = useRouter();
const form = ref({ name: '', email: String(route.query.email || ''), phone: '', password: '', password_confirmation: '', remember: false });
const busy = ref(false);
const error = ref('');
const message = ref('');
const title = computed(() => ({ login: 'Welcome back.', admin: 'Welcome to the kitchen.', register: 'A seat at our table.', forgot: 'Let’s get you back in.', reset: 'A fresh start.' })[props.mode]);
async function submit() {
    busy.value = true; error.value = ''; message.value = '';
    try {
        if (props.mode === 'forgot') {
            message.value = (await api.post('/forgot-password', { email: form.value.email })).data.message;
        } else if (props.mode === 'reset') {
            message.value = (await api.post('/reset-password', { ...form.value, token: route.params.token })).data.message;
            form.value.password = ''; form.value.password_confirmation = '';
        } else {
            await authenticate(props.mode === 'register' ? '/register' : '/login', form.value);
            if (props.mode === 'admin' && user.value.role !== 'admin') { error.value = 'This account does not have administrator access.'; return; }
            const target = typeof route.query.redirect === 'string' && /^\/(?!\/)/.test(route.query.redirect) ? route.query.redirect : user.value.role === 'admin' ? '/admin' : '/account';
            await router.push(target);
        }
    } catch (failure) { error.value = errorMessage(failure); }
    finally { busy.value = false; }
}
</script>
<template>
    <section class="container section-space auth-page">
        <div class="auth-art"><div class="eyebrow">GOOD FOOD. GREAT COMPANY.</div><h1>A little more<br><span class="serif">you.</span></h1><p>Your favorites, your orders, your own little corner of {{ settings.name }}.</p><span class="auth-symbol">✳</span></div>
        <div class="panel auth-panel"><div class="eyebrow">{{ mode === 'admin' ? 'RESTAURANT ADMINISTRATION' : `YOUR ${settings.name.toUpperCase()} ACCOUNT` }}</div><h2>{{ title }}</h2><p>{{ mode === 'forgot' ? 'We’ll email you a link to reset your password.' : mode === 'reset' ? 'Choose a password with at least eight characters, including a letter and a number.' : mode === 'register' ? 'Make ordering your favorites even easier.' : 'Sign in to pick up where you left off.' }}</p>
            <div v-if="error" class="form-error" role="alert">{{ error }}</div><div v-if="message" class="form-success" role="status">{{ message }}</div>
            <form @submit.prevent="submit" class="form-grid">
                <label v-if="mode === 'register'" class="full-width">Full name<input v-model="form.name" required maxlength="80" autocomplete="name"></label>
                <label class="full-width">Email<input v-model="form.email" type="email" required autocomplete="email"></label>
                <label v-if="mode === 'register'" class="full-width">Phone<input v-model="form.phone" type="tel" required pattern="[+0-9 ()-]{7,20}" autocomplete="tel"></label>
                <label v-if="mode !== 'forgot'" class="full-width">Password<input v-model="form.password" type="password" :autocomplete="['login','admin'].includes(mode) ? 'current-password' : 'new-password'" required :minlength="['register','reset'].includes(mode) ? 8 : 1"></label>
                <label v-if="['register','reset'].includes(mode)" class="full-width">Confirm password<input v-model="form.password_confirmation" type="password" autocomplete="new-password" required minlength="8"></label>
                <div v-if="['login','admin'].includes(mode)" class="auth-options full-width"><label class="check-label"><input v-model="form.remember" type="checkbox"> Remember me</label><router-link to="/forgot-password">Forgot password?</router-link></div>
                <button class="button full-width" :disabled="busy" type="submit">{{ busy ? 'Please wait…' : mode === 'register' ? 'Create account' : mode === 'forgot' ? 'Send reset link' : mode === 'reset' ? 'Reset password' : 'Sign in' }} <span>→</span></button>
            </form>
            <p v-if="mode === 'register'" class="auth-footnote">Already a regular? <router-link to="/login">Sign in</router-link></p><p v-else class="auth-footnote"><router-link :to="mode === 'forgot' || mode === 'reset' ? '/login' : '/register'">{{ mode === 'forgot' || mode === 'reset' ? 'Back to sign in' : 'New around here? Create an account' }}</router-link></p>
        </div>
    </section>
</template>
