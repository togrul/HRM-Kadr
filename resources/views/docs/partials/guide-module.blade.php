{{-- One module's guide body: its markdown, whose H2/H3s carry the ids ({key}-h-N / {key}-s-N)
     the guide's navigation, "Bu səhifədə" list and search link to. --}}
<section id="{{ $key }}-module" class="scroll-mt-24">
    <div id="{{ $key }}-doc" class="guide-prose">
        {!! $html !!}
    </div>
</section>
