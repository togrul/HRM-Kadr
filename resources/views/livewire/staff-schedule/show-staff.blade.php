<div class="flex flex-col space-y-2">
    <div class="sidemenu-title">
        <h2 class="text-2xl font-title font-semibold text-gray-500" id="slide-over-title">
          {{ $title ?? ''}}
        </h2>
    </div>

    <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
        <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
            <x-table.tbl :headers="[__('#'),__('Tabel'),__('Fullname'),__('Gender')]">
                @foreach ($staffs as $staff)
                    <tr>
                        <x-table.td>
                            <span class="text-sm font-medium">
                                {{ $loop->iteration }}
                           </span>
                        </x-table.td>

                        <x-table.td>
                            <span class="text-sm font-medium text-blue-500">
                                {{ $staff->tabel_no }}
                           </span>
                        </x-table.td>

                        <x-table.td>
                           <div class="flex items-center space-x-2">
                                @if(!empty($staff->photo))
                                <img src="{{ asset('/storage/'.$staff->photo) }}" alt="" class="flex-none rounded-xl object-cover w-14 h-14 border-4 border-gray-200">
                                @else
                                <img src="{{ asset('assets/images/no-image.png') }}" alt="" class="flex-none rounded-xl object-cover w-14 h-14 border-4 border-gray-200">
                                @endif
                               <div class="flex flex-col space-y-1">
                                <span class="text-sm font-medium text-gray-600">
                                    {{ $staff->fullname }}
                               </span>
                               </div>
                            </div>
                        </x-table.td>
    
                       <x-table.td>
                        <span class="text-sm font-medium text-gray-500 rounded-xl px-3 py-1 shadow-sm bg-gray-100">
                            {{ $staff->gender ? __('Man') : __('Woman') }}
                       </span>
                        </x-table.td>
                    
                    </tr>   
                @endforeach
            </x-table.tbl>

        </div>
        </div>
        <div>
            {{ $staffs->links() }}
        </div>
    </div>
</div>
