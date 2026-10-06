import { computed, reactive, ref, watch } from 'vue';
import { api, updateCsrf, errorMessage } from './api';

export const products = reactive([]);
export const categories = ref([]);
export const deliveryAreas = ref([]);
export const settings = reactive({ name: 'Ember & Oak', currency: 'PKR', minimum_order: 700, delivery_charge: 150, tax_percentage: 0, opening_hours: [], timezone: 'Asia/Karachi' });
export const isOpen = ref(false);
export const user = ref(null);
export const ready = ref(false);
export const connectionError = ref('');
export const notification = ref('');
export const cart = ref([]);
let notificationTimer;
let syncTimer;
let syncingEnabled = false;
let syncQueue = Promise.resolve();
const storageKey = () => user.value ? `ember-cart-user-${user.value.id}` : 'ember-cart';

export const foodImage = (value, width = 800) => {
    if (!value) return '/images/food-placeholder.svg';
    if (/^photo-[a-zA-Z0-9-]+$/.test(value)) return `https://images.unsplash.com/${value}?auto=format&fit=crop&w=${width}&q=85`;
    if (/^\/storage\/[a-zA-Z0-9/_.-]+$/.test(value) || /^https:\/\//.test(value)) return value;
    return '/images/food-placeholder.svg';
};
export const money = (value) => {
    const amount = Number(value || 0);
    return settings.currency === 'PKR' ? `Rs. ${amount.toLocaleString('en-PK', { maximumFractionDigits: 2 })}` : new Intl.NumberFormat('en', { style: 'currency', currency: settings.currency }).format(amount);
};
export const statusLabel = value => (value || '').replaceAll('_', ' ').replace(/\b\w/g, letter => letter.toUpperCase());
export function notify(message) {
    notification.value = message;
    clearTimeout(notificationTimer);
    notificationTimer = setTimeout(() => { notification.value = ''; }, 3500);
}
function normalizeItem(item) {
    if (!item || !Number.isInteger(item.id) || !Number.isInteger(item.quantity) || item.quantity < 1) return null;
    const variation = typeof item.variation === 'string' ? item.variation : '';
    const addons = [...new Set(Array.isArray(item.addons) ? item.addons.filter(name => typeof name === 'string') : [])].sort();
    return { id: item.id, key: JSON.stringify([item.id, variation, addons]), variation, addons, quantity: Math.min(item.quantity, 20) };
}
function normalizedCart(items) {
    const merged = new Map();
    for (const entry of Array.isArray(items) ? items : []) {
        const item = normalizeItem(entry);
        if (!item) continue;
        const existing = merged.get(item.key);
        if (existing) existing.quantity = Math.min(existing.quantity + item.quantity, 20);
        else merged.set(item.key, item);
    }
    return [...merged.values()].slice(0,50);
}
function readCart() {
    try { return normalizedCart(JSON.parse(localStorage.getItem(storageKey()) || '[]')); }
    catch { return []; }
}
export const cartItems = computed(() => cart.value.map(item => {
    const product = products.find(product => product.id === item.id);
    if (!product) return { ...item, name: 'Removed dish', price: 0, available: false, customization: [item.variation, ...item.addons].filter(Boolean).join(' · ') };
    const variation = product.variations.find(variation => variation.name === item.variation);
    const addons = product.addons.filter(addon => item.addons.includes(addon.name));
    const valid = (product.variations.length ? !!variation : !item.variation) && addons.length === item.addons.length;
    const price = Math.round(((variation?.price ?? product.discountPrice ?? product.price) + addons.reduce((total, addon) => total + addon.price, 0)) * 100) / 100;
    return { ...product, ...item, price, available: product.available && valid, customization: [item.variation, ...item.addons].filter(Boolean).join(' · ') };
}));
export const cartCount = computed(() => cart.value.reduce((total, item) => total + item.quantity, 0));
export const subtotal = computed(() => Math.round(cartItems.value.reduce((total, item) => total + item.price * item.quantity, 0) * 100) / 100);

export function addToCart(product, options = {}) {
    if (product.available === false) { notify('This dish is currently unavailable.'); return; }
    const configured = normalizeItem({ id: product.id, variation: options.variation || product.variations[0]?.name || '', addons: options.addons || [], quantity: options.quantity ?? 1 });
    if (!configured) return;
    const item = cart.value.find(item => item.key === configured.key);
    if ((item?.quantity || 0) + configured.quantity > 20 || (!item && cart.value.length >= 50)) { notify('Your bag has reached the limit for this order.'); return; }
    if (item) item.quantity += configured.quantity;
    else cart.value.push(configured);
    notify(`${product.name} added to your bag`);
}
export function changeQuantity(key, quantity) {
    if (!Number.isInteger(quantity)) return;
    if (quantity <= 0) cart.value = cart.value.filter(item => item.key !== key);
    else { const item = cart.value.find(item => item.key === key); if (item) item.quantity = Math.min(quantity,20); }
}
function queueSync() {
    const owner = user.value?.id;
    const items = cartPayload();
    syncQueue = syncQueue.catch(() => {}).then(async () => {
        if (owner && user.value?.id === owner) await api.put('/cart', { items });
    });
    return syncQueue;
}
export async function flushCart() { clearTimeout(syncTimer); if (user.value) await queueSync(); }
export function cartPayload() { return cart.value.map(({ id, quantity, variation, addons }) => ({ id, quantity, variation, addons })); }
watch(cart, value => {
    if (!syncingEnabled) return;
    try { localStorage.setItem(storageKey(), JSON.stringify(value)); } catch {}
    if (user.value) {
        clearTimeout(syncTimer);
        syncTimer = setTimeout(() => { queueSync().catch(() => notify('Your bag could not be saved online. It is still saved on this device.')); }, 350);
    }
}, { deep:true });

export function cacheProducts(list) {
    for (const item of list) {
        const index = products.findIndex(product => product.id === item.id);
        if (index === -1) products.push(item);
        else products[index] = item;
    }
}
export async function loadConfiguration() {
    const { data } = await api.get('/configuration');
    Object.assign(settings, data.settings);
    document.title = `${settings.name} — Fresh food, made for you.`;
    categories.value = data.categories;
    deliveryAreas.value = data.delivery_areas;
    isOpen.value = data.is_open;
}
export async function loadProduct(id) {
    const { data } = await api.get(`/products/${id}`);
    cacheProducts([data.data]);
    return data.data;
}
async function loadCartProducts() {
    await Promise.all(cart.value.filter(item => !products.some(product => product.id === item.id)).map(item => loadProduct(item.id).catch(() => {})));
}
let bootstrapRequest;
export function bootstrap() {
    if (!bootstrapRequest) bootstrapRequest = initializeSession().finally(() => { bootstrapRequest = null; });
    return bootstrapRequest;
}
async function initializeSession() {
    ready.value = false;
    connectionError.value = '';
    syncingEnabled = false;
    try {
        const [session, catalog] = await Promise.all([api.get('/session'), api.get('/products'), loadConfiguration()]);
        user.value = session.data.user;
        updateCsrf(session.data);
        cacheProducts(catalog.data.data);
        if (user.value) {
            const { data } = await api.get('/cart');
            cart.value = normalizedCart(data.items);
        } else cart.value = readCart();
        await loadCartProducts();
        ready.value = true;
    } catch(error) { connectionError.value = errorMessage(error); }
    syncingEnabled = true;
}
export async function authenticate(endpoint, details) {
    const guestItems = user.value ? [] : [...cart.value];
    const { data } = await api.post(endpoint, details);
    updateCsrf(data);
    syncingEnabled = false;
    user.value = data.user;
    const saved = await api.get('/cart');
    cart.value = normalizedCart([...saved.data.items, ...guestItems]);
    await loadCartProducts();
    syncingEnabled = true;
    await flushCart();
    try { localStorage.removeItem('ember-cart'); } catch {}
    return data.user;
}
export async function logout() {
    await flushCart();
    const { data } = await api.post('/logout');
    updateCsrf(data);
    syncingEnabled = false;
    user.value = null;
    cart.value = [];
    syncingEnabled = true;
}
export async function reorder(order) {
    let skipped = 0;
    for (const item of order.items) {
        try {
            const product = await loadProduct(item.product_id);
            const addonNames = item.addons.map(addon => addon.name);
            if (!product.available || (item.variation_name && !product.variations.some(size => size.name === item.variation_name)) || addonNames.some(name => !product.addons.some(addon => addon.name === name))) { skipped++; continue; }
            addToCart(product, { quantity: Math.min(item.quantity,20), variation: item.variation_name || '', addons: addonNames });
        } catch { skipped++; }
    }
    notify(skipped ? `${skipped} unavailable or changed items were skipped. Review your bag for current prices.` : 'Your favorites are back in your bag at current prices.');
}
