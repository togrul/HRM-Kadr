<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex h-10 items-center justify-center rounded-[10px] border border-rose-200 bg-rose-50 px-4 text-[14px] font-semibold tracking-[-0.01em] text-rose-700 transition-colors duration-150 hover:bg-rose-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-300 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
