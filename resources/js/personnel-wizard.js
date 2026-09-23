/**
 * State for the personnel create/edit wizard (includes/personnel-action.blade.php).
 *
 * Kept out of the x-data attribute on purpose: the source holds CSS selectors with
 * double quotes, which end a double-quoted attribute early and spill the rest of the
 * code onto the page. The step entanglement stays inline, where it has to be.
 */
window.personnelWizard = () => ({
    pendingAction: null,
    pendingStep: null,
    pendingTimer: null,
    focusAfterCommit: false,
    offCommit: null,

    init() {
        this.$watch('currentStep', () => this.clearPending());

        if (!window.Livewire) return;

        // Clear the spinner and, after Next/Save, move to the first rejected field once
        // this component's response has been applied to the DOM.
        this.offCommit = window.Livewire.hook('commit', ({ component, succeed, fail }) => {
            if (component.id !== this.$wire.$id) return;

            succeed(() => requestAnimationFrame(() => {
                const shouldFocus = this.focusAfterCommit;
                this.clearPending();
                if (shouldFocus) this.focusFirstInvalid();
            }));
            fail(() => queueMicrotask(() => this.clearPending()));
        });
    },

    destroy() {
        if (this.offCommit) this.offCommit();
        this.offCommit = null;
        this.clearPending();
    },

    setPending(action, step = null) {
        if (this.pendingTimer) clearTimeout(this.pendingTimer);
        this.pendingAction = action;
        this.pendingStep = step;
        this.focusAfterCommit = ['next', 'select', 'save'].includes(action);
        // Safety net: never leave the buttons disabled if a response is lost.
        this.pendingTimer = setTimeout(() => this.clearPending(), 8000);
    },

    clearPending() {
        if (this.pendingTimer) clearTimeout(this.pendingTimer);
        this.pendingTimer = null;
        this.pendingAction = null;
        this.pendingStep = null;
        this.focusAfterCommit = false;
    },

    focusFirstInvalid() {
        const field = this.$el.querySelector('[aria-invalid="true"]');
        if (!field) return;

        field.scrollIntoView({ block: 'center', behavior: 'smooth' });
        field.focus({ preventScroll: true });
    },

    stepState(step) {
        if (step < this.currentStep) return 'completed';
        if (step === this.currentStep) return 'active';
        return 'upcoming';
    },

    progressWidth() {
        return `${Math.max(0, ((this.currentStep - 1) / 7) * 100)}%`;
    },
});
