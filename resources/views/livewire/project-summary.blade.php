<div class="fi-in-entry">
    <div class="fi-in-entry-label">AI summary</div>
    <div class="fi-in-text-item fi-prose">
        {!! Illuminate\Support\Str::markdown($summary, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
    </div>
</div>
