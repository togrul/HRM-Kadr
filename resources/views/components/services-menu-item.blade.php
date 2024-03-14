@props(['key','selectedService','title'])

<button wire:click.prevent="selectService('{{ $key }}')"
    @class([
        'appearance-none space-x-2 rounded-xl transition-all duration-300 font-medium px-6 py-3 flex justify-start items-center',
        'text-slate-800 bg-gray-50 hover:bg-emerald-100' => $selectedService != $key,
        'text-white bg-emerald-500' => $selectedService == $key
    ])>
    <div class="flex justify-center items-center p-2 rounded-xl bg-emerald-100 text-emerald-500">
        {{ $slot }}
    </div>

    <span class="text-sm">{{ $title ?? '' }}</span>
</button>
