<script setup>
import ProductCard from "../components/ProductCard.vue";
import { products, foodImage, categories, settings } from "../store";
defineEmits(["book"]);
</script>
<template>
    <section class="hero container">
        <div class="hero-copy">
            <div class="eyebrow"><span></span> FRESHLY MADE. FULL OF SOUL.</div>
            <h1>
                A little fire.<br />A lot of
                <span class="serif accent">flavor.</span>
            </h1>
            <p>
                Flame-grilled favorites, comforting classics, and good times.<br
                    class="desktop-break"
                />
                Pull up a chair. We’ll take care of the rest.
            </p>
            <div class="hero-actions">
                <router-link to="/menu" class="button"
                    >Explore our menu <span>↗</span></router-link
                ><button class="text-button" @click="$emit('book')">
                    Book a table <span>→</span>
                </button>
            </div>
            <div class="social-proof"><div><div class="stars">♨ <strong>Made fresh, every time.</strong></div><p>Easy pickup · Delivery to your neighborhood</p></div></div>
        </div>
        <div class="hero-visual">
            <img
                class="hero-image"
                :src="foodImage('photo-1550547660-d9450f859349', 1400)"
                alt="Flame-grilled burger stacked with fresh greens, cheese and tomato"
            />
            <div class="hero-image-shade"></div>
            <div class="round-stamp">
                MADE WITH LOVE<span>✳</span>SERVED WITH SOUL
            </div>
            <div class="hero-dish">
                <div>
                    <span>THE HOUSE FAVORITE</span>
                    <h3>The Signature Burger</h3>
                </div>
                <router-link to="/menu" aria-label="See our signature burger"
                    >↗</router-link
                >
            </div>
            <div class="fresh-note">
                <span>✦</span>
                <div>Always fresh.<small>Never a shortcut.</small></div>
            </div>
        </div>
    </section>
    <div class="values-strip">
        <div class="container values-inner">
            <span><i>✳</i> Fresh, quality ingredients</span
            ><span><i>♨</i> Made to order, always</span
            ><span><i>♡</i> Big flavors. Bigger smiles.</span
            ><span><i>↗</i> Dine in or delivered</span>
        </div>
    </div>
    <section class="container home-categories section-space"><div class="section-heading"><div><div class="eyebrow">FOLLOW YOUR CRAVING</div><h2>Something for <span class="serif">everyone.</span></h2></div></div><div class="home-category-links"><router-link v-for="category in categories" :key="category.id" :to="{path:'/menu',query:{category:category.slug}}"><img v-if="category.image" :src="foodImage(category.image,150)" :alt="category.name" loading="lazy"><span v-else>✳</span>{{ category.name }} <small>↗</small></router-link></div></section>
    <section class="container favorites section-space">
        <div class="section-heading">
            <div>
                <div class="eyebrow">THE GOOD STUFF</div>
                <h2>Love at first <span class="serif">bite.</span></h2>
            </div>
            <router-link class="text-button" to="/menu"
                >View the full menu <span>↗</span></router-link
            >
        </div>
        <p class="section-intro">
            The dishes you come back for. And tell your friends about.
        </p>
        <div class="product-grid">
            <ProductCard
                v-for="product in products.filter(product => product.featured).slice(0, 4)"
                :key="product.id"
                :product="product"
            />
        </div>
    </section>
    <section v-if="products.some(product=>product.isDeal)" class="container section-space home-deals"><div class="section-heading"><div><div class="eyebrow">GOOD FOOD TOGETHER</div><h2>A little more <span class="serif">to share.</span></h2></div><router-link to="/deals" class="text-button">Discover all deals ↗</router-link></div><div class="product-grid"><ProductCard v-for="product in products.filter(product=>product.isDeal).slice(0,4)" :key="product.id" :product="product" /></div></section>
    <section class="container story section-space" id="story">
        <div class="story-image">
            <img
                :src="foodImage('photo-1414235077428-338989a2e8c0', 1000)"
                loading="lazy"
                alt="A carefully prepared meal at a welcoming restaurant"
            />
            <div class="story-label">GOOD FOOD.<br />BETTER MOMENTS.</div>
        </div>
        <div class="story-copy">
            <div class="eyebrow">MORE THAN A MEAL</div>
            <h2>
                Your everyday place.<br />With a little
                <span class="serif accent">extra.</span>
            </h2>
            <p>
                We believe the best meals bring people together. That’s why we
                put a little more care into every ingredient, every dish, and
                every welcome.
            </p>
            <p>
                From a quick lunch to a long catch-up, there’s always a seat for
                you at {{ settings.name }}.
            </p>
            <button class="text-button" @click="$emit('book')">
                Make yourself at home <span>↗</span>
            </button>
        </div>
    </section>
    <section class="container booking-banner">
        <div>
            <div class="eyebrow">SAVE A SEAT FOR SOMETHING GOOD</div>
            <h2>Good company. <span class="serif">Great food.</span></h2>
            <p>Bring your favorite people. We’ll bring the flavor.</p>
        </div>
        <button class="button" @click="$emit('book')">
            Let’s book a table <span>↗</span>
        </button>
    </section>
</template>
