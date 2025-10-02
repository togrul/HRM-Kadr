@props(['rows'])
<tr>
    <td colspan="{{ $rows }}">
        <div class="flex flex-col space-y-3 items-center py-6">
            <img src="{{ asset('assets/images/empty.png') }}" class="max-w-full max-h-48 bg-blend-luminosity mix-blend-luminosity" alt="">
            <span class="font-medium text-lg">{{ __('No information added') }}</span>
        </div>
    </td>
</tr>
