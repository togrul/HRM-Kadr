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
        maxVisible: 4,
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
        push(rawMessage, variant = 'success', title = '') {
            const message = this.normalizeMessage(rawMessage)
            if (!message) return

            const id = Date.now() + Math.floor(Math.random() * 100000)
            const toast = {
                id,
                title,
                message,
                variant,
                visible: true,
                progress: 100,
            }

            this.toasts = [toast, ...this.toasts].slice(0, this.maxVisible)
            this.$nextTick(() => { toast.progress = 0 })

            this.timers[id] = setTimeout(() => this.close(id), 5000)
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
        }
    }"
    x-init="
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

        document.addEventListener('livewire:init', () => {
            successEvents.forEach(event => Livewire.on(event, (...payload) => push(payload.length <= 1 ? payload[0] : payload, 'success')))
            errorEvents.forEach(event => Livewire.on(event, (...payload) => push(payload.length <= 1 ? payload[0] : payload, 'danger')))
        })

        @if($redirect && filled($messageToDisplay))
            $nextTick(() => push(@js($messageToDisplay), @js($type === 'error' ? 'danger' : 'success')))
        @endif
        @if(filled($initialMessage))
            $nextTick(() => push(@js($initialMessage), @js($initialType === 'error' ? 'danger' : 'success')))
        @endif
    "
    class="fixed top-4 right-4 sm:top-6 sm:right-6 z-[99999] pointer-events-none"
    style="display: block;"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2 scale-[.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-[.98]"
            class="w-[min(24rem,calc(100vw-1.5rem))] mb-3 pointer-events-auto"
        >
            <div
                class="relative p-2 rounded-2xl shadow-[0_12px_34px_rgba(15,23,42,0.12)] bg-white/95 backdrop-blur-md border border-zinc-200/90 overflow-hidden"
                :class="{
                    'border-l-4 border-l-lime-500': toast.variant === 'success',
                    'border-l-4 border-l-amber-500': toast.variant === 'warning',
                    'border-l-4 border-l-rose-500': toast.variant === 'danger'
                }"
            >
                <div class="flex items-start gap-3">
                    <div class="pt-0.5">
                        <svg x-show="toast.variant === 'success'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 text-lime-600">
                            <path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm3.844-8.791a.75.75 0 0 0-1.188-.918l-3.7 4.79-1.649-1.833a.75.75 0 1 0-1.114 1.004l2.25 2.5a.75.75 0 0 0 1.15-.043l4.25-5.5Z" clip-rule="evenodd"></path>
                        </svg>
                        <svg x-show="toast.variant === 'warning'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 text-amber-500">
                            <path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 1 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"></path>
                        </svg>
                        <svg x-show="toast.variant === 'danger'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 text-rose-500">
                            <path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"></path>
                        </svg>
                    </div>

                    <div class="flex-1 min-w-0 py-1">
                        <div x-show="toast.title" class="pb-1 text-sm font-semibold text-zinc-900" x-text="toast.title"></div>
                        <div class="text-sm font-medium leading-5 text-zinc-700 break-words pr-1" x-text="toast.message"></div>
                    </div>

                    <button type="button"
                            @click="close(toast.id)"
                            class="inline-flex items-center justify-center w-8 h-8 text-zinc-400 rounded-lg hover:bg-zinc-100/90 hover:text-zinc-700 transition">
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"></path>
                        </svg>
                    </button>
                </div>

                <div class="absolute bottom-0 left-0 h-[2px] bg-zinc-200/70 w-full"></div>
                <div
                    class="absolute bottom-0 left-0 h-[2px] transition-[width] duration-[5000ms] ease-linear"
                    :style="{ width: toast.progress + '%' }"
                    :class="{
                        'bg-lime-500': toast.variant === 'success',
                        'bg-amber-500': toast.variant === 'warning',
                        'bg-rose-500': toast.variant === 'danger'
                    }"
                ></div>
            </div>
        </div>
    </template>
</div>
