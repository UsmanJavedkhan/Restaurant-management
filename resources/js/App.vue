<script setup>
import { ref, computed } from 'vue';
import Navbar from './components/Navbar.vue';
import Footer from './components/Footer.vue';
import ReservationModal from './components/ReservationModal.vue';
import { notification, bootstrap, ready, connectionError } from './store';
import { onMounted } from 'vue';
import { useRoute } from 'vue-router';
const route=useRoute();
const adminWorkspace=computed(()=>route.meta.admin);
const bookingOpen = ref(false);
onMounted(()=>{if(!ready.value)bootstrap();});
</script>
<template>
    <Navbar v-if="!adminWorkspace" @book="bookingOpen = true" />
    <main v-if="ready"><router-view @book="bookingOpen = true" /></main>
    <main v-else class="container empty-state" aria-live="polite"><h2>{{ connectionError ? 'We couldn’t load the restaurant.' : 'Setting the table…' }}</h2><p>{{ connectionError || 'Your next favorite is on its way.' }}</p><button v-if="connectionError" class="button" @click="bootstrap">Try again</button></main>
    <Footer v-if="!adminWorkspace" @book="bookingOpen = true" />
    <ReservationModal v-if="bookingOpen" @close="bookingOpen = false" />
    <Transition name="toast"><div v-if="notification" class="toast" role="status"><span>✓</span> {{ notification }}</div></Transition>
</template>
