
@push('js')
    <script>
        function initializeSwalEvents() {
            Livewire.on('swal',(event) => {
                const data = event
                swal.fire({
                    icon:data[0]['icon'],
                    title:data[0]['title'],
                    text:data[0]['text'],
                    timer: data[0]['timer'],
                    timerProgressBar: data[0]['timerProgressBar'],
                    showConfirmButton: false,
                })
            })

            Livewire.on('delete-prompt',(event)=>{
                const data = event
                swal.fire({
                    icon:data[0]['icon'],
                    title:data[0]['title'],
                    text:data[0]['text'],
                    showCancelButton:true,
                    confirmButtonColor:'#3085d6',
                    cancelButtonColor:'#ff0000',
                    confirmButtonText: "{{ __('Yes, Delete it!') }}",
                    cancelButtonText: "{{ __('Cancel') }}"
                }).then((result)=>{
                    if(result.isConfirmed){
                        Livewire.dispatch('goOn-Delete')

                        Livewire.on('deleted',(event)=>{
                            swal.fire({
                                title: "{{ __('Deleted') }}",
                                text: "{{ __('Your record has been deleted') }}",
                                icon:'success',
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true,
                            })
                        })
                    }
                })
            })
        }

        window.addEventListener('livewire:navigated', () => {
            initializeSwalEvents();
        });
    </script>
@endpush
