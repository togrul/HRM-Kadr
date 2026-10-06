<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex h-9 items-center justify-center gap-1.5 rounded-full border border-hairline bg-[#f4f4f5] px-4 text-[13px] font-semibold tracking-[-0.01em] text-ink-soft transition-colors duration-150 hover:bg-[#e4e4e7] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
