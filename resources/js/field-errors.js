/**
 * Clears a field's validation error the moment the user corrects it.
 *
 * Shared inputs mark a rejected field with aria-invalid="true" and list the classes that
 * paint it red in data-error-classes; error messages (<x-validation>, <x-input-error>)
 * carry data-field-error. On the first input/change in such a field the red state and
 * its message go away client-side. The server side matches it: the ClearFieldErrorOnUpdate
 * Livewire hook drops the same key from the error bag when the value arrives, so the next
 * render does not bring the stale message back.
 *
 * One delegated listener on document; registered once per page load (document survives
 * wire:navigate).
 */

const MAX_DEPTH = 4;

const findInvalid = (target) => {
    if (!(target instanceof Element)) {
        return null;
    }

    if (target.getAttribute('aria-invalid') === 'true') {
        return target;
    }

    // <x-ui.select-dropdown> reports its choice from the root; the invalid part is its button.
    return target.querySelector?.('[aria-invalid="true"]') ?? null;
};

const hideMessageNear = (field) => {
    const key = field.getAttribute('data-error-key');
    if (key) {
        const keyed = document.querySelectorAll(`[data-field-error-for="${CSS.escape(key)}"]`);
        if (keyed.length > 0) {
            keyed.forEach((message) => message.setAttribute('hidden', ''));

            return;
        }
    }

    let container = field.parentElement;
    for (let depth = 0; container && depth < MAX_DEPTH; depth += 1, container = container.parentElement) {
        const messages = container.querySelectorAll('[data-field-error]:not([hidden])');
        if (messages.length === 0) {
            continue;
        }

        // Stop when the nearest message could belong to another rejected field next door.
        if (container.querySelector('[aria-invalid="true"]')) {
            return;
        }

        messages.forEach((message) => message.setAttribute('hidden', ''));

        return;
    }
};

const clearFieldError = (event) => {
    const field = findInvalid(event.target);
    if (!field) {
        return;
    }

    field.removeAttribute('aria-invalid');
    (field.getAttribute('data-error-classes') || '')
        .split(/\s+/)
        .filter(Boolean)
        .forEach((name) => field.classList.remove(name));

    hideMessageNear(field);
};

if (!window.__hrmFieldErrors) {
    window.__hrmFieldErrors = true;
    document.addEventListener('input', clearFieldError, true);
    document.addEventListener('change', clearFieldError, true);
    document.addEventListener('ui-select-change', clearFieldError, true);
}
