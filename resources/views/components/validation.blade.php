<p {{ $attributes->merge(['class' => 'mt-2 rounded-2xl bg-rose-500 px-3.5 py-1.5 text-xs font-semibold leading-5 text-white shadow-sm shadow-rose-500/10 break-words transition-all duration-300']) }}>
    {{ trim($slot) }}
</p>
