@props(['notification'])

@php
      $data = $notification->data;
      $type = $data['type'];
      $action = $data['action'] ?? '';
      $isRead = !empty($notification->read_at);
      switch ($action) {
             case 'create':
                 $color = 'teal';
                 $message = __('has created new personnel');
                 $category = __('New '. strtolower($type));
                 $addedBy = $data['added_by'];
                 break;
             case 'delete':
                 $color = 'rose';
                 $message = __('has deleted personnel');
                 $category = __($type . ' deleted');
                 $addedBy = $data['added_by'];
                 break;
             case 'birthday':
                 $color = 'blue';
                 $message = '';
                 $category = $type;
                 $addedBy = null;
                 break;
             default:
                  $color = 'gray';
                  $message = __('has a notification');
         };
@endphp
<li class="w-full">
    <button
        @click.prevent="isOpen = false"
        wire:click.prevent="markAsRead('{{ $notification->id }}')"
        @class([
            'flex w-full px-5 py-3 transition duration-150 ease-in hover:bg-gray-100',
            'bg-slate-50' => !empty($notification->read_at)
        ])
    >
        <div class="flex flex-col items-start w-full space-y-1">
            <div class="flex items-start justify-between w-full">
                <p class="flex items-start space-x-2">
                     <span class="bg-{{ $color }}-100 text-xs rounded uppercase text-{{ $color }}-500 font-medium px-2 py-1">
                         {{ __($category) }}
                     </span>
                </p>
            </div>

            <div class="flex items-start justify-between w-full">
                <p class="flex items-center space-x-1 text-sm font-medium text-gray-500 text-left">
                    @if($action == 'birthday')
                        <x-icons.cake-icon color="text-yellow-800"></x-icons.cake-icon>
                        <span class="text-black text-base flex items-center"><span class="text-sm text-gray-500">{{ __('Age') }}:</span>{{ \Carbon\Carbon::parse($data['added_by'])->age }}</span>
                    @else
                        <span class="font-semibold text-slate-500">{{ $addedBy }}</span>
                    @endif
                    <span>{{ $message ?? '' }} -</span>
                    <span class="font-semibold text-black">{{ $data['name'] }}</span>
                </p>
                @if(! $isRead) <span class="w-2 h-2 rounded-full bg-blue-500 mt-1"></span> @endif
            </div>
            <div class="flex items-center justify-between w-full">
                <span class="font-light text-sm text-gray-500">{{ $notification->created_at->format('d.m.Y H:i') }}</span>
                <span class="font-light text-sm text-gray-500">{{ $notification->created_at->diffForHumans() }}</span>
            </div>
        </div>
    </button>
</li>
