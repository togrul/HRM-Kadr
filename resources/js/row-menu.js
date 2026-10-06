/**
 * Behaviour for the personnel row context menu (x-personnel.row-actions.context-menu).
 *
 * It used to live in each row's x-data — ~1.6KB of source repeated per row — and every
 * row kept three window listeners (keydown, resize, scroll) for its whole life. Now the
 * source ships once and a row only listens while its menu is open.
 */
window.rowMenu = (forceUp = false) => ({
    open: false,
    openUp: forceUp,
    forceUp,
    panelStyle: '',
    onWindow: null,

    toggle() {
        this.open ? this.close() : this.show();
    },

    show() {
        this.open = true;
        this.openUp = this.forceUp;

        this.onWindow = (event) => {
            if (event.type === 'keydown') {
                if (event.key === 'Escape') this.close();
                return;
            }
            this.reposition();
        };
        window.addEventListener('keydown', this.onWindow);
        window.addEventListener('resize', this.onWindow);
        window.addEventListener('scroll', this.onWindow, { passive: true, capture: true });

        this.$nextTick(() => this.reposition());
    },

    close() {
        this.open = false;
        if (!this.onWindow) return;

        window.removeEventListener('keydown', this.onWindow);
        window.removeEventListener('resize', this.onWindow);
        window.removeEventListener('scroll', this.onWindow, { capture: true });
        this.onWindow = null;
    },

    destroy() {
        this.close();
    },

    reposition() {
        const panel = this.$refs.menuPanel;
        const button = this.$refs.menuButton;
        if (!panel || !button) return;

        const buttonRect = button.getBoundingClientRect();
        const panelHeight = panel.offsetHeight || 220;
        const panelWidth = panel.offsetWidth || 260;
        const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

        let left = buttonRect.right - panelWidth;
        left = Math.max(8, Math.min(left, viewportWidth - panelWidth - 8));

        let top;
        if (this.openUp) {
            top = Math.max(8, buttonRect.top - panelHeight - 8);
        } else {
            top = buttonRect.bottom + 8;
            if (top + panelHeight > viewportHeight - 8) {
                top = Math.max(8, viewportHeight - panelHeight - 8);
            }
        }

        this.panelStyle = `left:${left}px; top:${top}px;`;
    },
});
