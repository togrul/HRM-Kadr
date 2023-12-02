@props(['inputName','auto' => true])

@push('css')
     <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/pikaday.min.css') }}">
@endpush
@push('js')
     <script src="{{ asset('assets/js/moment.min.js') }}"></script>
     <script src="{{ asset('assets/js/pikaday.min.js') }}"></script>
     @if($auto)
     <script>
          new Pikaday ({
               field: document.getElementById('date'),
               onSelect: function(){
                    @this.set('{{$inputName}}',this.getMoment().format('Y-MM-DD'));
               }
          })
     </script>
     @endif
@endpush