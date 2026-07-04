@props(['size' => 'md', 'text' => ''])

@php
$fontSize = match($size) { 'sm' => '1rem', 'lg' => '2.5rem', default => '1.75rem' };
@endphp

{{--
    MiraiStudy text wave loader: radial gradient fill sweeps across the text stroke.
    Accent color tracks the theme automatically via the .mirai-loader CSS class.
    Outer element is the show/hide toggle target — callers can pass id/style/class
    through attributes (e.g. <x-leaf-loader style="display:none;" />).
    data-loader-text span is reused by JS for error/restore messages.
    size prop: sm=1rem  md=1.75rem (default)  lg=2.5rem
--}}
<div {{ $attributes }} role="status" aria-live="polite">
    <div class="flex flex-col items-center justify-center gap-2 py-4">
        <span class="mirai-loader" style="font-size: {{ $fontSize }}" aria-hidden="true"></span>
        <span data-loader-text class="text-sm text-muted {{ $text === '' ? 'hidden' : '' }}">{{ $text }}</span>
        <span class="sr-only">Loading</span>
    </div>
</div>
