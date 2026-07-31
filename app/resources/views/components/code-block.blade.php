@props(['lang' => null])

<div class="code-block">
    @if ($lang)
        <div class="code-block__lang">{{ $lang }}</div>
    @endif
    <pre><code>{{ $slot }}</code></pre>
</div>
