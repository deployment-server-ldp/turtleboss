import Alpine from 'alpinejs';

// Rich text editor (contenteditable -> hidden textarea). Content is sanitised again on the server.
Alpine.data('richText', () => ({
    init() { this.$nextTick(() => { this.$refs.area.innerHTML = this.$refs.input.value; }); },
    cmd(c, v = null) { this.$refs.area.focus(); document.execCommand(c, false, v); this.sync(); },
    link() { const u = prompt('Link URL (use /shop or a full https:// URL)'); if (u) this.cmd('createLink', u); },
    image() { const u = prompt('Image URL'); if (u) this.cmd('insertImage', u); },
    sync() { this.$refs.input.value = this.$refs.area.innerHTML; },
}));

// Variant generator for product form
Alpine.data('variantEditor', (attrs, rows, hasOld) => ({
    attrs, rows, chosen: {}, 
    init() {
        // pre-tick attributes already used by existing variants
        this.rows.forEach((r) => r.values.forEach((vid) => { const a = this.attrs.find((x) => x.values.some((v) => v.id === vid)); if (a) (this.chosen[a.id] ??= []).includes(vid) || this.chosen[a.id].push(vid); }));
    },
    toggle(aid, vid) { const l = (this.chosen[aid] ??= []); const i = l.indexOf(vid); i >= 0 ? l.splice(i, 1) : l.push(vid); },
    has(aid, vid) { return (this.chosen[aid] || []).includes(vid); },
    label(r) { return r.values.map((id) => this.attrs.flatMap((a) => a.values).find((v) => v.id === id)?.value).join(' / '); },
    generate() {
        const lists = Object.values(this.chosen).filter((l) => l.length);
        if (!lists.length) return alert('Tick at least one value first.');
        const combos = lists.reduce((acc, l) => acc.flatMap((a) => l.map((v) => [...a, v])), [[]]);
        const key = (vals) => [...vals].sort((a, b) => a - b).join('-');
        const existing = new Set(this.rows.map((r) => key(r.values)));
        combos.forEach((vals) => { if (!existing.has(key(vals))) this.rows.push({ id: null, sku: '', price: '', sale_price: '', stock_qty: 0, is_active: true, values: vals, image: null }); });
    },
    remove(i) { if (confirm('Remove this variant?')) this.rows.splice(i, 1); },
}));

// Tiny SVG bar/line chart renderer
Alpine.data('chart', (series, key, fmt) => ({
    series, key, hover: null,
    get max() { return Math.max(1, ...this.series.map((s) => s[this.key])); },
    h(s) { return Math.max(1, (s[this.key] / this.max) * 100); },
}));

window.Alpine = Alpine;
Alpine.start();
