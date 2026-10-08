/**
 * One date picker for the whole app.
 *
 * Every Pikaday instance — <x-pikaday-input>, <x-datepicker> and <x-ui.date-input> — goes
 * through the wrapper installed here, so they all share one calendar: the app locale's
 * month and day names, weeks starting on Monday, and the same keyboard behaviour.
 *
 * Locale: the layout publishes window.hrmDateLocale (see layouts/app). Locales without a
 * catalogue of their own get their names from Intl, so the calendar still follows the app
 * locale instead of falling back to English.
 */

const pad = (value) => String(value).padStart(2, '0');

const capitalize = (value, locale) => value.charAt(0).toLocaleUpperCase(locale) + value.slice(1);

/** Names for a locale the server had no catalogue for, read from the browser's Intl data. */
const intlI18n = (locale) => {
    try {
        const months = Array.from({ length: 12 }, (_, month) =>
            capitalize(new Intl.DateTimeFormat(locale, { month: 'long' }).format(new Date(2024, month, 1)), locale));
        // 7 Jan 2024 is a Sunday: Pikaday expects Sunday first.
        const day = (index, style) => new Intl.DateTimeFormat(locale, { weekday: style }).format(new Date(2024, 0, 7 + index));

        return {
            months,
            weekdays: Array.from({ length: 7 }, (_, index) => capitalize(day(index, 'long'), locale)),
            weekdaysShort: Array.from({ length: 7 }, (_, index) => capitalize(day(index, 'short'), locale)),
        };
    } catch (error) {
        return {};
    }
};

const resolveI18n = () => {
    const config = window.hrmDateLocale || {};
    const locale = config.locale || document.documentElement.lang || 'az';
    const names = config.names || intlI18n(locale);

    return {
        previousMonth: config.previousMonth || '‹',
        nextMonth: config.nextMonth || '›',
        months: names.months,
        weekdays: names.weekdays,
        weekdaysShort: names.weekdaysShort,
    };
};

/** DD.MM.YYYY <-> Date, independent of moment. */
export const displayFromDate = (date) => `${pad(date.getDate())}.${pad(date.getMonth() + 1)}.${date.getFullYear()}`;

export const isoFromDate = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

const buildDate = (year, month, day) => {
    const date = new Date(year, month - 1, day);

    return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day ? date : null;
};

/** A complete, real calendar date typed as DD.MM.YYYY (or ISO); anything else is null. */
export const parseDisplay = (value) => {
    const text = String(value ?? '').trim();
    let match = text.match(/^(\d{1,2})[./-](\d{1,2})[./-](\d{4})$/);
    if (match) {
        return buildDate(Number(match[3]), Number(match[2]), Number(match[1]));
    }

    match = text.match(/^(\d{4})-(\d{2})-(\d{2})/);

    return match ? buildDate(Number(match[1]), Number(match[2]), Number(match[3])) : null;
};

/**
 * Typing mask for DD.MM.YYYY: digits only, the dots appear as you type. Deleting is left
 * alone, so Backspace never fights the user.
 */
export const maskDateInput = (event) => {
    const input = event?.target;
    if (!input || (event.inputType && !event.inputType.startsWith('insert'))) {
        return;
    }

    const digits = input.value.replace(/\D/g, '').slice(0, 8);
    let masked = digits.slice(0, 2);
    if (digits.length > 2) masked += `.${digits.slice(2, 4)}`;
    if (digits.length > 4) masked += `.${digits.slice(4)}`;
    if (digits.length === 2 || digits.length === 4) masked += '.';

    if (masked !== input.value) {
        input.value = masked;
    }
};

/**
 * Keyboard on the field itself. Pikaday's own handler listens on the whole document and
 * wipes the date on Backspace — fatal while typing — so it is switched off and replaced:
 * ↑/↓ a day, Shift+↑/↓ a week, Enter confirms, Esc closes the calendar only (it must not
 * also close the side panel the field sits in).
 */
const bindKeyboard = (picker, field) => {
    const onKeydown = (event) => {
        if (event.key === 'Escape') {
            if (picker.isVisible()) {
                event.preventDefault();
                event.stopPropagation();
                picker.hide();
            }

            return;
        }

        if (event.key === 'Enter') {
            if (picker.isVisible()) picker.hide();

            return;
        }

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (!picker.isVisible()) {
                picker.show();

                return;
            }
            picker.adjustDate(event.key === 'ArrowDown' ? 'add' : 'subtract', event.shiftKey ? 7 : 1);
        }
    };

    field.addEventListener('keydown', onKeydown);

    return () => field.removeEventListener('keydown', onKeydown);
};

const install = () => {
    const Original = window.Pikaday;
    if (typeof Original !== 'function' || Original.__hrmLocalized) {
        return;
    }

    const i18n = resolveI18n();

    function LocalizedPikaday(options = {}) {
        const merged = { firstDay: 1, yearRange: 100, showDaysInNextAndPreviousMonths: true, ...options, keyboardInput: false };
        if (!options.i18n && i18n.months) {
            merged.i18n = i18n;
        }

        const picker = new Original(merged);

        if (merged.field) {
            merged.field.setAttribute('data-date-input', '');
            merged.field.setAttribute('autocomplete', 'off');
            const unbind = bindKeyboard(picker, merged.field);
            const destroy = picker.destroy.bind(picker);
            picker.destroy = () => {
                unbind();
                destroy();
            };
        }

        return picker;
    }

    LocalizedPikaday.prototype = Original.prototype;
    LocalizedPikaday.__hrmLocalized = true;
    window.Pikaday = LocalizedPikaday;
};

install();

window.hrmMaskDateInput = maskDateInput;

/**
 * Alpine state for <x-ui.date-input>: shows DD.MM.YYYY, keeps the bound value ISO
 * (Y-m-d) exactly like a native <input type="date">, so no server code has to change.
 * Only a complete real date (or an emptied field) is committed; half-typed text never
 * reaches the server.
 */
window.hrmDateField = (config = {}) => ({
    iso: config.value ?? '',
    picker: null,

    init() {
        const field = this.$refs.display;
        this.render();

        if (typeof window.Pikaday !== 'function') {
            return;
        }

        this.picker = new window.Pikaday({
            field,
            format: 'DD.MM.YYYY',
            toString: (date) => displayFromDate(date),
            parse: (value) => parseDisplay(value),
            minDate: config.min ? parseDisplay(config.min) : null,
            maxDate: config.max ? parseDisplay(config.max) : null,
            defaultDate: parseDisplay(this.iso) || undefined,
            setDefaultDate: Boolean(parseDisplay(this.iso)),
            onSelect: (date) => this.commit(date),
        });

        this.$watch('iso', () => {
            if (document.activeElement !== field) {
                this.render();
            }
        });
    },

    destroy() {
        this.picker?.destroy();
        this.picker = null;
    },

    render() {
        const date = parseDisplay(this.iso);
        this.$refs.display.value = date ? displayFromDate(date) : '';
    },

    commit(date) {
        const next = date ? isoFromDate(date) : '';
        if (next !== (this.iso ?? '')) {
            this.iso = next;
        }
    },

    onInput(event) {
        maskDateInput(event);
    },

    onChange() {
        const text = this.$refs.display.value.trim();
        if (text === '') {
            this.commit(null);

            return;
        }

        const date = parseDisplay(text);
        if (!date) {
            // An impossible or half-typed date is not kept: the field shows the last real one.
            this.render();

            return;
        }

        this.commit(date);
        this.picker?.setDate(date, true);
        this.$refs.display.value = displayFromDate(date);
    },
});
