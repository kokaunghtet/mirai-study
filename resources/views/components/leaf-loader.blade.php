@props(['size' => 'md', 'text' => ''])

@php
$px = match($size) { 'sm' => 32, 'lg' => 64, default => 48 };
@endphp

{{--
    Leaf fan loader: 8 leaves arranged radially, staggered opacity sweep.
    Outer element is the show/hide toggle target — callers can pass id/style/class
    through attributes (e.g. <x-leaf-loader style="display:none;" />).
    data-loader-text span is reused by JS for error/restore messages.
    size prop: sm=32px  md=48px (default)  lg=64px
--}}
<div {{ $attributes }} role="status" aria-live="polite">
    <div class="flex flex-col items-center justify-center gap-2 py-4">
        <svg class="text-accent" width="{{ $px }}" height="{{ $px }}" viewBox="0 0 48 48" aria-hidden="true">
            @for ($i = 0; $i < 8; $i++)
                <path d="M24 4 C29 8, 30 13, 24 17 C18 13, 19 8, 24 4Z"
                      fill="currentColor"
                      transform="rotate({{ $i * 45 }}, 24, 24)"
                      class="leaf-fan-petal"
                      style="animation-delay: {{ $i * 0.2 }}s"/>
            @endfor
            <circle cx="24" cy="24" r="2.5" fill="currentColor" opacity="0.45"/>
        </svg>
        <span data-loader-text class="text-sm text-muted {{ $text === '' ? 'hidden' : '' }}">{{ $text }}</span>
        <span class="sr-only">Loading</span>
    </div>
</div>
