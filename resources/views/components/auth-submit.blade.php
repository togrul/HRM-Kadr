<button type="submit" {{ $attributes->merge(['class' => 'mt-2 h-11 w-full rounded-xl bg-ink text-[13.5px] font-semibold text-white transition hover:bg-ink-hover active:scale-[0.99]']) }}>
    {{ $slot }}
</button>
