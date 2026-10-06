/**
 * Behaviour for <x-command-palette>: one keyboard-driven list over three sections —
 * people (fetched as you type), actions and modules (rendered server-side, filtered here).
 *
 * Rows are addressed through the DOM ([data-palette-item] that is currently visible), so
 * the arrow keys walk people, actions and modules as one list without the three having
 * to share a data model.
 */
window.hrmCommandPalette = (config) => ({
    query: '',
    people: [],
    loading: false,
    active: 0,
    labels: config.labels,
    searchUrl: config.searchUrl,
    timer: null,
    controller: null,
    cache: new Map(),

    open() {
        this.query = '';
        this.people = [];
        this.active = 0;
        this.$nextTick(() => {
            this.$refs.paletteInput.focus();
            this.paint();
        });
    },

    matches(label) {
        return this.query === '' || label.includes(this.query.trim().toLowerCase());
    },

    get nothingFound() {
        const q = this.query.trim().toLowerCase();

        return q !== '' && !this.loading && this.people.length === 0 && !this.labels.some((label) => label.includes(q));
    },

    onInput() {
        this.active = 0;
        this.$nextTick(() => this.paint());

        if (!this.searchUrl) {
            return;
        }

        clearTimeout(this.timer);
        const q = this.query.trim();

        if (q.length < 2) {
            this.controller?.abort();
            this.people = [];
            this.loading = false;

            return;
        }

        if (this.cache.has(q)) {
            this.show(this.cache.get(q));

            return;
        }

        this.loading = true;
        this.timer = setTimeout(() => this.fetchPeople(q), 180);
    },

    async fetchPeople(q) {
        this.controller?.abort();
        this.controller = new AbortController();

        try {
            const response = await fetch(`${this.searchUrl}?q=${encodeURIComponent(q)}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: this.controller.signal,
            });
            const results = response.ok ? (await response.json()).results ?? [] : [];

            this.cache.set(q, results);

            if (this.query.trim() === q) {
                this.show(results);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                this.show([]);
            }
        }
    },

    show(results) {
        this.people = results;
        this.loading = false;
        this.active = 0;
        this.$nextTick(() => this.paint());
    },

    items() {
        return [...this.$root.querySelectorAll('[data-palette-item]')].filter((item) => item.offsetParent !== null);
    },

    move(step) {
        const items = this.items();

        if (items.length === 0) {
            return;
        }

        this.active = (this.active + step + items.length) % items.length;
        this.paint();
        items[this.active].scrollIntoView({ block: 'nearest' });
    },

    paint() {
        this.items().forEach((item, index) => item.setAttribute('aria-selected', index === this.active ? 'true' : 'false'));
    },

    choose() {
        this.items()[this.active]?.click();
    },

    hover(event) {
        const index = this.items().indexOf(event.target.closest('[data-palette-item]'));

        if (index !== -1 && index !== this.active) {
            this.active = index;
            this.paint();
        }
    },
});
