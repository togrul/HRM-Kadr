@props([
    'type' => 'success',
    'redirect' => false,
    'messageToDisplay' => '',
    'initialType' => null,
    'initialMessage' => null,
])

<div
    x-data="{
        toasts: [],
        timers: {},
        maxQueue: 12,
        normalizeMessage(payload) {
            if (payload === null || payload === undefined) return ''
            if (typeof payload === 'string' || typeof payload === 'number') return String(payload)
            if (Array.isArray(payload)) return this.normalizeMessage(payload[0] ?? '')
            if (typeof payload === 'object') {
                if (payload[0] !== undefined) return this.normalizeMessage(payload[0])
                if (typeof payload.message === 'string') return payload.message
                if (Array.isArray(payload.detail)) return this.normalizeMessage(payload.detail[0] ?? '')
                if (typeof payload.detail === 'string') return payload.detail
            }
            return ''
        },
        topToast() {
            return this.toasts.length ? this.toasts[0] : null
        },
        stackCount() {
            return Math.max(0, this.toasts.length - 1)
        },
        push(rawMessage, variant = 'success') {
            const message = this.normalizeMessage(rawMessage)
            if (!message) return

            const id = Date.now() + Math.floor(Math.random() * 100000)
            const toast = {
                id,
                message,
                variant,
                visible: true,
            }

            this.toasts = [toast, ...this.toasts].slice(0, this.maxQueue)
            this.timers[id] = setTimeout(() => this.close(id), 4200)
        },
        close(id) {
            const toast = this.toasts.find(t => t.id === id)
            if (!toast) return

            toast.visible = false

            if (this.timers[id]) {
                clearTimeout(this.timers[id])
                delete this.timers[id]
            }

            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id)
            }, 180)
        },
        bindEvents() {
            const successEvents = [
                'personnelAdded', 'personnelWasDeleted',
                'staffAdded', 'staffWasDeleted',
                'roleUpdated', 'roleWasDeleted',
                'permissionUpdated', 'permissionSet', 'permissionWasDeleted',
                'userAdded', 'userWasDeleted',
                'menuAdded', 'menuWasDeleted',
                'fileAdded',
                'settingsUpdated', 'settingsWasDeleted',
                'candidateAdded', 'candidateWasDeleted',
                'templateAdded', 'templateWasDeleted',
                'componentWasDeleted',
                'orderAdded', 'orderWasDeleted',
                'typesUpdated', 'vacancyUpdated',
                'rankAdded', 'rankWasDeleted',
                'contractAdded', 'leaveApproved', 'leaveRejected', 'leaveWasDeleted',
                'leaveAdded'
            ]
            const errorEvents = ['staffScheduleError', 'addError']

            if (Array.isArray(window.__hrmToastListeners)) {
                window.__hrmToastListeners.forEach(([event, fn]) => window.removeEventListener(event, fn))
            }

            const listeners = []
            successEvents.forEach(event => {
                const fn = (e) => this.push(e?.detail ?? '', 'success')
                window.addEventListener(event, fn)
                listeners.push([event, fn])
            })
            errorEvents.forEach(event => {
                const fn = (e) => this.push(e?.detail ?? '', 'danger')
                window.addEventListener(event, fn)
                listeners.push([event, fn])
            })
            window.__hrmToastListeners = listeners
        }
    }"
    x-init="
        bindEvents()

        @if($redirect && filled($messageToDisplay))
            $nextTick(() => push(@js($messageToDisplay), @js($type === 'error' ? 'danger' : 'success')))
        @endif
        @if(filled($initialMessage))
            $nextTick(() => push(@js($initialMessage), @js($initialType === 'error' ? 'danger' : 'success')))
        @endif
    "
    class="fixed top-4 right-4 sm:top-5 sm:right-6 z-[99999] pointer-events-none"
    style="display:block;"
>
    <div class="relative w-[min(22rem,calc(100vw-1.25rem))]">
        <div
            x-show="stackCount() > 0"
            x-cloak
            class="absolute inset-x-1 top-2 h-[58px] rounded-xl border border-zinc-200 bg-white shadow-[0_8px_16px_rgba(15,23,42,0.08)]"
            style="opacity:.85"
        ></div>
        <div
            x-show="stackCount() > 1"
            x-cloak
            class="absolute inset-x-2 top-3 h-[58px] rounded-xl border border-zinc-200 bg-white shadow-[0_8px_14px_rgba(15,23,42,0.06)]"
            style="opacity:.65"
        ></div>

        <div
            x-show="topToast() && topToast().visible"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1 scale-[.99]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 -translate-y-1 scale-[.99]"
            class="relative pointer-events-auto"
        >
            <div class="rounded-xl border border-zinc-200 bg-white shadow-[0_10px_20px_rgba(15,23,42,0.10)] px-4 py-3">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center justify-center shrink-0">
                        <svg x-show="topToast()?.variant === 'success'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-emerald-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <svg x-show="topToast()?.variant === 'warning'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-amber-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374L10.052 3.378c.866-1.5 3.03-1.5 3.896 0l7.355 12.748ZM12 16.5h.008v.008H12V16.5Z" />
                        </svg>
                        <svg x-show="topToast()?.variant === 'danger'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-rose-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                    <div class="text-[15px] leading-6 font-medium text-zinc-900 truncate" x-text="topToast()?.message"></div>
                </div>
            </div>
        </div>
    </div>
</div>
