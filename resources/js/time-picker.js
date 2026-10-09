/**
 * One time field for the whole app (<x-ui.time-input>, and the time half of
 * <x-ui.datetime-input>), replacing the browser's <input type="time"> whose look, 12/24-hour
 * clock and keyboard depended on the operating system's language.
 *
 * The field shows and binds HH:MM (24 hours) — exactly what a native time input produced —
 * so no Livewire property or validation rule has to change. Typing is masked ("930" becomes
 * 09:30), ↑/↓ move by the field's step (Shift: an hour), and a list of times opens under it.
 */

const pad = (value) => String(value).padStart(2, '0');

const MINUTES_PER_DAY = 24 * 60;

export const formatMinutes = (total) => {
    const value = ((total % MINUTES_PER_DAY) + MINUTES_PER_DAY) % MINUTES_PER_DAY;

    return `${pad(Math.floor(value / 60))}:${pad(value % 60)}`;
};

/** "9", "930", "0930", "9:30", "09:30", "09:30:00" -> minutes since midnight; anything else null. */
export const parseTime = (value) => {
    const text = String(value ?? '').trim();
    if (text === '') {
        return null;
    }

    let hours;
    let minutes;
    let match = text.match(/^(\d{1,2})[:.](\d{1,2})(?::\d{1,2})?$/);
    if (match) {
        hours = Number(match[1]);
        minutes = Number(match[2]);
    } else if (/^\d{1,4}$/.test(text)) {
        const digits = text.length <= 2 ? text : text.padStart(4, '0');
        hours = Number(digits.length <= 2 ? digits : digits.slice(0, 2));
        minutes = digits.length <= 2 ? 0 : Number(digits.slice(2));
    } else {
        return null;
    }

    if (hours > 23 || minutes > 59) {
        return null;
    }

    return (hours * 60) + minutes;
};

/** Digits only, the colon appears after the hours. Deleting is left alone. */
export const maskTimeInput = (event) => {
    const input = event?.target;
    if (!input || (event.inputType && !event.inputType.startsWith('insert'))) {
        return;
    }

    const digits = input.value.replace(/\D/g, '').slice(0, 4);
    let masked = digits.slice(0, 2);
    if (digits.length > 2) masked += `:${digits.slice(2)}`;
    if (digits.length === 2 && !input.value.includes(':')) masked += ':';

    // A first digit above 2 can only be an hour on its own: "9" -> "09:".
    if (digits.length === 1 && Number(digits) > 2) masked = `0${digits}:`;

    if (masked !== input.value) {
        input.value = masked;
    }
};

window.hrmMaskTimeInput = maskTimeInput;

/**
 * Alpine state for <x-ui.time-input>. `value` is the bound HH:MM string ('' when empty);
 * the component entangles it with wire:model or exposes it through x-modelable.
 */
window.hrmTimeField = (config = {}) => ({
    value: config.value ?? '',
    step: Math.max(1, Math.round((Number(config.step) || 60) / 60)),
    listStep: Math.max(Math.round((Number(config.step) || 60) / 60), 30),
    min: parseTime(config.min),
    max: parseTime(config.max),
    isOpen: false,
    dispatching: false,
    panelStyles: {},
    activeIndex: -1,

    init() {
        this.render();
        this.$watch('value', () => {
            if (document.activeElement !== this.$refs.display) {
                this.render();
            }
        });
        this.$watch('isOpen', (open) => {
            if (open) {
                this.$nextTick(() => requestAnimationFrame(() => {
                    this.reposition();
                    this.scrollToCurrent();
                }));
            }
        });
    },

    /** Times offered in the list: every listStep minutes inside min/max. */
    slots() {
        const slots = [];
        for (let minute = 0; minute < MINUTES_PER_DAY; minute += this.listStep) {
            if ((this.min !== null && minute < this.min) || (this.max !== null && minute > this.max)) {
                continue;
            }
            slots.push(formatMinutes(minute));
        }

        return slots;
    },

    current() {
        return parseTime(this.value);
    },

    render() {
        const minutes = parseTime(this.value);
        this.$refs.display.value = minutes === null ? '' : formatMinutes(minutes);
    },

    clamp(minutes) {
        if (this.min !== null && minutes < this.min) return this.min;
        if (this.max !== null && minutes > this.max) return this.max;

        return minutes;
    },

    commit(minutes) {
        const next = minutes === null ? '' : formatMinutes(this.clamp(minutes));
        this.$refs.display.value = next;
        if (next !== (this.value ?? '')) {
            this.value = next;
            // a surrounding side panel counts this as an edit, a field error clears itself
            this.dispatching = true;
            try {
                this.$refs.display.dispatchEvent(new Event('change', { bubbles: true }));
            } finally {
                this.dispatching = false;
            }
        }
    },

    pick(slot) {
        this.commit(parseTime(slot));
        this.isOpen = false;
        this.$refs.display.focus({ preventScroll: true });
    },

    onInput(event) {
        maskTimeInput(event);
    },

    onChange(event) {
        // our own change event (dispatched by commit) must not re-enter
        if (this.dispatching) {
            return;
        }

        const text = this.$refs.display.value.trim();
        if (text === '') {
            this.commit(null);

            return;
        }

        const minutes = parseTime(text);
        if (minutes === null) {
            // a half-typed or impossible time is not kept: the field shows the last real one
            this.render();

            return;
        }

        this.commit(minutes);
    },

    onKeydown(event) {
        if (event.key === 'Escape') {
            if (this.isOpen) {
                event.preventDefault();
                event.stopPropagation();
                this.isOpen = false;
            }

            return;
        }

        if (event.key === 'Enter') {
            if (this.isOpen && this.activeIndex >= 0) {
                event.preventDefault();
                this.pick(this.slots()[this.activeIndex]);

                return;
            }
            this.onChange();
            this.isOpen = false;

            return;
        }

        if (event.key === 'Tab') {
            this.isOpen = false;

            return;
        }

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (event.altKey) {
                this.isOpen = !this.isOpen;

                return;
            }

            const direction = event.key === 'ArrowUp' ? 1 : -1;
            const delta = (event.shiftKey ? 60 : this.step) * direction;
            const typed = parseTime(this.$refs.display.value);
            const base = typed ?? this.current();
            // from an empty field the first press lands on the current time, rounded to the step
            const now = new Date();
            const start = Math.round(((now.getHours() * 60) + now.getMinutes()) / this.step) * this.step;
            this.commit(base === null ? start : base + delta);
            this.highlightCurrent();
        }
    },

    toggle() {
        if (this.$refs.display.disabled) return;
        this.isOpen = !this.isOpen;
    },

    /** Index of the listed time nearest to the field's value (-1 when empty). */
    nearestIndex() {
        const minutes = this.current();
        if (minutes === null) return -1;
        const slots = this.slots().map((slot) => parseTime(slot));
        let best = 0;
        slots.forEach((slot, index) => {
            if (Math.abs(slot - minutes) < Math.abs(slots[best] - minutes)) best = index;
        });

        return best;
    },

    highlightCurrent() {
        this.activeIndex = this.nearestIndex();
        if (this.isOpen) this.scrollToCurrent();
    },

    scrollToCurrent() {
        this.activeIndex = this.nearestIndex();
        const panel = this.$refs.panel;
        const node = panel?.querySelector(`[data-time-slot="${this.activeIndex}"]`);
        if (node) {
            panel.scrollTop = node.offsetTop - (panel.clientHeight / 2) + (node.offsetHeight / 2);
        }
    },

    reposition() {
        const field = this.$refs.display;
        if (!field) return;
        const rect = field.getBoundingClientRect();
        const viewportHeight = window.visualViewport?.height || window.innerHeight;
        const height = 224;
        const below = viewportHeight - rect.bottom - 8;
        const openUp = below < height && rect.top > below;
        const width = Math.max(rect.width, 120);
        this.panelStyles = {
            left: `${Math.round(rect.left)}px`,
            top: `${Math.round(openUp ? Math.max(8, rect.top - 8 - height) : rect.bottom + 6)}px`,
            width: `${Math.round(width)}px`,
            maxHeight: `${height}px`,
        };
    },
});

/**
 * Alpine state for <x-ui.datetime-input>: one bound value in the format a native
 * datetime-local input produced (Y-m-d\TH:i), split into a date field and a time field.
 * The value is only written once both halves are set; clearing the date clears it.
 */
window.hrmDateTimeField = (config = {}) => ({
    value: config.value ?? '',
    datePart: '',
    timePart: '',

    init() {
        this.split();
        this.$watch('value', () => this.split());
        this.$watch('datePart', () => this.join());
        this.$watch('timePart', () => this.join());
    },

    split() {
        const match = String(this.value ?? '').match(/^(\d{4}-\d{2}-\d{2})(?:[T ](\d{1,2}:\d{2}))?/);
        const date = match ? match[1] : '';
        const minutes = match && match[2] ? parseTime(match[2]) : null;
        const time = minutes === null ? '' : formatMinutes(minutes);
        if (date !== this.datePart) this.datePart = date;
        if (time !== this.timePart) this.timePart = time;
    },

    join() {
        let next;
        if (!this.datePart) {
            next = '';
        } else if (!this.timePart) {
            return;
        } else {
            next = `${this.datePart}T${this.timePart}`;
        }

        const current = String(this.value ?? '');
        if (next !== current.slice(0, 16) || (next === '' && current !== '')) {
            this.value = next;
        }
    },
});
