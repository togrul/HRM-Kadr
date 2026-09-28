{{-- One module's guide: a short head with the way into the module, then its markdown.
     The markdown's H2s carry ids ({key}-h-N) that the guide's sidebar links to. --}}
<section id="{{ $key }}-module" class="docs-section">
    <div class="docs-module-head">
        <div>
            <p class="docs-header-kicker">{{ $module['label'] }} modulu</p>
        </div>
        @if ($module['route'] && \Illuminate\Support\Facades\Route::has($module['route']))
            <a href="{{ route($module['route']) }}" class="docs-module-link">Modulu aç</a>
        @endif
    </div>

    <div id="{{ $key }}-doc" class="docs-content">
        {!! $html !!}
    </div>
</section>
