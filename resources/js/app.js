import Alpine from 'alpinejs';

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;
export async function api(url, { method = 'GET', body } = {}) {
    const res = await fetch(url, {
        method,
        headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(body ? { 'Content-Type': 'application/json' } : {}) },
        body: body ? JSON.stringify(body) : undefined,
        credentials: 'same-origin',
    });
    let data = {};
    try { data = await res.json(); } catch (e) {}
    if (res.status === 419) { location.reload(); }
    return { ok: res.ok, status: res.status, data };
}
const money = (n) => 'Rs. ' + Math.round(Number(n) || 0).toLocaleString('en-US');
window.tbMoney = money;
const boot = window.__TB || { cart: { count: 0 }, wish: [] };

Alpine.store('ui', {
    drawer: false, catSheet: false, search: false, menu: false, toasts: [],
    toast(message, type = 'ok') {
        const id = Date.now() + Math.random();
        this.toasts.push({ id, message, type });
        setTimeout(() => (this.toasts = this.toasts.filter((t) => t.id !== id)), 3200);
    },
    lock(on) { document.documentElement.style.overflow = on ? 'hidden' : ''; },
});

Alpine.store('cart', {
    count: boot.cart.count || 0, items: [], subtotal: 0, discount: 0, shipping: 0, total: 0, coupon: null, loaded: false, busy: false, freeThreshold: 0, shippingLabel: '',
    apply(c) {
        this.count = c.count; this.items = c.items; this.subtotal = c.subtotal; this.discount = c.discount; this.shipping = c.shipping;
        this.total = c.total; this.coupon = c.coupon; this.freeThreshold = c.free_threshold; this.shippingLabel = c.shipping_label; this.loaded = true;
    },
    async refresh() { const r = await api('/cart/data'); if (r.ok) this.apply(r.data); },
    async call(url, body, okMsg) {
        this.busy = true;
        const r = await api(url, { method: 'POST', body });
        this.busy = false;
        if (r.data.cart) this.apply(r.data.cart);
        if (!r.ok) { Alpine.store('ui').toast(r.data.message || Object.values(r.data.errors || {})[0]?.[0] || 'Something went wrong', 'err'); return false; }
        if (okMsg || r.data.message) Alpine.store('ui').toast(okMsg || r.data.message);
        return true;
    },
    async add(productId, variantId = null, qty = 1, { open = true } = {}) {
        const ok = await this.call('/cart/add', { product_id: productId, variant_id: variantId, quantity: qty });
        if (ok && open) { Alpine.store('ui').drawer = true; Alpine.store('ui').lock(true); }
        return ok;
    },
    setQty(id, qty) { return this.call('/cart/update', { item_id: id, quantity: qty }); },
    remove(id) { return this.call('/cart/remove', { item_id: id }); },
    changeVariant(id, variantId) { return this.call('/cart/variant', { item_id: id, variant_id: variantId }); },
    async applyCoupon(code, city) { return this.call('/cart/coupon', { code, city }); },
    async removeCoupon() { this.busy = true; const r = await api('/cart/coupon', { method: 'DELETE' }); this.busy = false; if (r.data.cart) this.apply(r.data.cart); },
    get remainingForFree() { return this.freeThreshold > 0 ? Math.max(0, this.freeThreshold - (this.subtotal - this.discount)) : 0; },
});

Alpine.store('wish', {
    ids: boot.wish || [],
    has(id) { return this.ids.includes(id); },
    get count() { return this.ids.length; },
    async toggle(id) {
        const r = await api('/wishlist/toggle', { method: 'POST', body: { product_id: id } });
        if (r.ok) { this.ids = r.data.ids; Alpine.store('ui').toast(r.data.added ? 'Added to wishlist' : 'Removed from wishlist'); }
    },
});

// Header live search
Alpine.data('liveSearch', () => ({
    q: '', results: [], loading: false, timer: null, done: false,
    input() {
        clearTimeout(this.timer);
        if (this.q.trim().length < 2) { this.results = []; this.done = false; return; }
        this.timer = setTimeout(async () => {
            this.loading = true;
            const r = await api('/search/suggest?q=' + encodeURIComponent(this.q.trim()));
            this.results = r.ok ? r.data : []; this.loading = false; this.done = true;
        }, 220);
    },
    go() { if (this.q.trim()) location.href = '/shop?q=' + encodeURIComponent(this.q.trim()); },
}));

// Shop: "load more" pagination
Alpine.data('loadMore', (next) => ({
    next, loading: false,
    async more(target) {
        if (!this.next || this.loading) return;
        this.loading = true;
        const url = new URL(this.next, location.origin); url.searchParams.set('more', '1');
        const r = await api(url.toString());
        if (r.ok) { document.getElementById(target).insertAdjacentHTML('beforeend', r.data.html); this.next = r.data.next; }
        this.loading = false;
    },
}));

// Quick view modal
Alpine.data('quickView', () => ({
    open: false, loading: false, html: '',
    async show(slug) {
        this.open = true; this.loading = true; this.html = ''; Alpine.store('ui').lock(true);
        const r = await api('/product/' + slug + '/quick');
        this.loading = false;
        if (r.ok) { this.html = r.data.html; this.$nextTick(() => { const el = this.$refs.body; Alpine.initTree(el); }); }
        else this.html = '<p class="p-8 text-center">Could not load product.</p>';
    },
    close() { this.open = false; Alpine.store('ui').lock(false); },
}));

// Product page / quick view: variants, gallery, quantity, cart
Alpine.data('productPage', (cfg) => ({
    cfg, sel: {}, qty: 1, adding: false, main: 0, zoom: false, zx: 50, zy: 50, lightbox: false, variantImages: null, thumbStart: 0,
    init() {
        if (!cfg.hasVariants) return;
        // pre-select when only one option exists for an attribute
        cfg.attributes.forEach((a) => { if (a.values.length === 1) this.sel[a.id] = a.values[0].id; });
        const first = cfg.variants.find((v) => v.active && v.stock > 0);
        if (first && cfg.attributes.length && Object.keys(this.sel).length === 0) this.sel = { ...first.map };
    },
    get gallery() { return this.variantImages?.length ? this.variantImages.map((u) => ({ url: u, alt: '' })).concat(cfg.images) : cfg.images; },
    get variant() {
        if (!cfg.hasVariants) return null;
        const ids = Object.keys(this.sel);
        if (ids.length < cfg.attributes.length) return null;
        return cfg.variants.find((v) => v.active && cfg.attributes.every((a) => v.map[a.id] == this.sel[a.id])) || false;
    },
    get complete() { return !cfg.hasVariants || this.variant !== null; },
    get price() { return this.variant ? this.variant.price : (cfg.hasVariants ? this.minPrice : cfg.base.price); },
    get regular() { return this.variant ? this.variant.regular : cfg.base.regular; },
    get minPrice() { const p = cfg.variants.filter((v) => v.active).map((v) => v.price); return p.length ? Math.min(...p) : cfg.base.price; },
    get onSale() { return this.variant ? this.variant.price < this.variant.regular : cfg.base.price < cfg.base.regular; },
    get discount() { return this.onSale ? Math.round((1 - this.price / this.regular) * 100) : 0; },
    get stock() { return this.variant ? this.variant.stock : (cfg.hasVariants ? null : cfg.base.stock); },
    get sku() { return this.variant ? this.variant.sku : cfg.base.sku; },
    get maxQty() { return Math.max(1, Math.min(this.stock ?? cfg.maxQty, cfg.maxQty * 5)); },
    get stockText() {
        if (this.variant === false) return 'This combination is unavailable';
        if (this.stock === null) return 'Select options to see availability';
        if (this.stock <= 0) return 'Out of stock';
        if (this.stock < 10) return 'Only ' + this.stock + ' left';
        return 'In stock';
    },
    get canBuy() { return this.complete && this.variant !== false && (this.stock === null || this.stock > 0); },
    optionAvailable(attrId, valueId) {
        // value is disabled when no active in-stock variant has it given the other selections
        return cfg.variants.some((v) => v.active && v.stock > 0 && v.map[attrId] == valueId && cfg.attributes.every((a) => a.id == attrId || !this.sel[a.id] || v.map[a.id] == this.sel[a.id]));
    },
    pick(attrId, valueId) {
        this.sel[attrId] = valueId; this.qty = 1;
        const v = this.variant;
        this.variantImages = v && v.images?.length ? v.images : null; this.main = 0;
        if (this.qty > this.maxQty) this.qty = this.maxQty;
    },
    inc() { if (this.qty < this.maxQty) this.qty++; else Alpine.store('ui').toast('Maximum available quantity reached', 'err'); },
    dec() { if (this.qty > 1) this.qty--; },
    clamp() { this.qty = Math.min(this.maxQty, Math.max(1, parseInt(this.qty) || 1)); },
    check() {
        if (!this.complete) { Alpine.store('ui').toast('Please select ' + cfg.attributes.filter((a) => !this.sel[a.id]).map((a) => a.name.toLowerCase()).join(' and '), 'err'); return false; }
        if (!this.canBuy) { Alpine.store('ui').toast('This option is out of stock', 'err'); return false; }
        return true;
    },
    async add() { if (!this.check()) return; this.adding = true; await Alpine.store('cart').add(cfg.productId, this.variant?.id ?? null, this.qty); this.adding = false; },
    async buyNow() { if (!this.check()) return; this.adding = true; const ok = await Alpine.store('cart').add(cfg.productId, this.variant?.id ?? null, this.qty, { open: false }); if (ok) location.href = '/checkout'; else this.adding = false; },
    move(e) { const r = e.currentTarget.getBoundingClientRect(); this.zx = ((e.clientX - r.left) / r.width) * 100; this.zy = ((e.clientY - r.top) / r.height) * 100; },
    next() { this.main = (this.main + 1) % this.gallery.length; }, prev() { this.main = (this.main - 1 + this.gallery.length) % this.gallery.length; },
    money,
}));

// Hero slider
Alpine.data('heroSlider', (count, interval) => ({
    i: 0, timer: null, count, startX: 0,
    init() { if (count > 1) this.play(); },
    play() { this.stop(); this.timer = setInterval(() => this.next(), interval); },
    stop() { clearInterval(this.timer); },
    next() { this.i = (this.i + 1) % this.count; }, prev() { this.i = (this.i - 1 + this.count) % this.count; },
    touchStart(e) { this.startX = e.changedTouches[0].clientX; },
    touchEnd(e) { const d = e.changedTouches[0].clientX - this.startX; if (Math.abs(d) > 40) { d < 0 ? this.next() : this.prev(); this.play(); } },
}));

// Announcement bar rotator
Alpine.data('rotator', (n) => ({ i: 0, init() { if (n > 1) setInterval(() => (this.i = (this.i + 1) % n), 4000); } }));

// Cart page: coupon + city quote on checkout
Alpine.data('checkoutPage', (initialCity) => ({
    city: initialCity || '', coupon: '', quote: null,
    async requote() { if (!this.city) return; const r = await api('/cart/quote?city=' + encodeURIComponent(this.city)); if (r.ok) this.quote = r.data; },
    async apply() { if (!this.coupon.trim()) return; const ok = await Alpine.store('cart').applyCoupon(this.coupon, this.city); if (ok) { this.coupon = ''; this.requote(); } },
    async removeCoupon() { await Alpine.store('cart').removeCoupon(); this.requote(); },
    get shipping() { return this.quote ? this.quote.shipping : Alpine.store('cart').shipping; },
    get total() { return this.quote ? this.quote.total : Alpine.store('cart').total; },
    get shippingLabel() { return this.quote?.shipping_label || Alpine.store('cart').shippingLabel; },
    init() { this.requote(); Alpine.store('cart').refresh(); },
    money,
}));

window.Alpine = Alpine;
Alpine.start();
