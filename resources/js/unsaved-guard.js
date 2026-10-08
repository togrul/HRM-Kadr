/**
 * Unsaved-changes guard shared by the side panels (<x-side-modal>, <x-ui.side-panel>).
 *
 * A panel becomes "dirty" once the user types or picks something inside it after it
 * opened. Closing a dirty panel (Esc, backdrop, ×) asks first through the app's own
 * confirmation dialog — never the browser's native confirm(). A clean panel closes at once.
 * A save that closes the panel goes through the server close path and is never asked
 * about; a save that keeps the panel open can dispatch `hrm-form-saved` to reset it.
 *
 * Opt out per panel with :guard-unsaved="false"; ignore single controls (filters, search
 * boxes inside a panel) with data-dirty-ignore.
 */

const FOCUSABLE_FIELD = [
    '[autofocus]:not([disabled])',
    'input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=button]):not([type=submit]):not([type=file]):not([disabled]):not([readonly]):not([data-date-input])',
    'textarea:not([disabled]):not([readonly])',
    'select:not([disabled])',
].join(', ');

const isVisible = (element) => element.offsetParent !== null || element.getClientRects().length > 0;

window.hrmUnsavedGuard = (config = {}) => ({
    guardUnsaved: config.enabled !== false,
    dirty: false,
    guardTexts: config.texts || {},

    trackDirty(event) {
        if (!this.guardUnsaved) {
            return;
        }

        const target = event?.target;
        if (target instanceof Element && (target.closest('[data-dirty-ignore]') || target.matches('input[type=search]'))) {
            return;
        }

        this.dirty = true;
    },

    resetDirty() {
        this.dirty = false;
    },

    /** Runs closeFn now when nothing would be lost, otherwise once the user confirms. */
    guardedClose(closeFn) {
        if (!this.guardUnsaved || !this.dirty) {
            closeFn();

            return;
        }

        window.dispatchEvent(new CustomEvent('confirm-action', {
            detail: {
                title: this.guardTexts.title,
                message: this.guardTexts.message,
                confirmText: this.guardTexts.confirm,
                cancelText: this.guardTexts.cancel,
                tone: 'amber',
                focus: 'cancel',
                run: () => {
                    this.dirty = false;
                    closeFn();
                },
            },
        }));
    },

    /** A reopened panel starts at its top, not where the previous form was left. */
    resetPanelScroll(root) {
        root?.querySelectorAll('[data-panel-scroll]').forEach((element) => {
            element.scrollTop = 0;
        });
    },

    /**
     * Focus the first real field of the form. Date fields are skipped (focus would pop
     * the calendar open); falls back to the given element (usually the × button).
     */
    focusFirstField(root, fallback = null, attempt = 0) {
        const field = [...(root?.querySelectorAll(FOCUSABLE_FIELD) ?? [])].find(isVisible);
        if (field) {
            field.focus({ preventScroll: true });

            return;
        }

        // The form body may still be arriving from the server; try again briefly.
        if (attempt < 3) {
            window.setTimeout(() => this.focusFirstField(root, fallback, attempt + 1), 120);

            return;
        }

        fallback?.focus?.({ preventScroll: true });
    },
});
