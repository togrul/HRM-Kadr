<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex h-10 items-center justify-center rounded-[10px] border border-transparent bg-ink px-4 text-[14px] font-semibold tracking-[-0.01em] text-white transition-colors duration-150 hover:bg-ink-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
