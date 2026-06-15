@props(['text' => ''])

{{--
    Branded loading indicator: a leaf gently floating & swaying (see `.leaf-loader`
    in resources/css/app.css). Colored with text-accent so it tracks the theme.

    Outer element is the show/hide toggle target — callers pass id/style through
    attributes (e.g. <x-leaf-loader id="loading-indicator" style="display:none;" />),
    and JS flips its `display`. The inner flex wrapper handles centering so toggling
    display:block on the outer element doesn't break the layout.

    During normal loading only the leaf shows; the [data-loader-text] span stays
    hidden and is reused by JS for error/restore messages.
--}}
<div {{ $attributes }} role="status" aria-live="polite">
    <div class="flex flex-col items-center justify-center gap-2 py-4">
        {{-- Motion track: 144×80 box matching the offset-path coords in app.css.
             overflow-visible so the leaf can ride the top of the loop without clipping. --}}
        <div class="relative h-20 w-36 overflow-visible">
            {{-- Lucide-style leaf; inherits theme color via currentColor.
                 .leaf-loader animates along a CSS Motion Path (see app.css). --}}
            <svg class="leaf-loader absolute left-0 top-0 h-6 w-6 text-accent" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" aria-hidden="true">
                <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/>
                <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>
            </svg>
        </div>
        <span data-loader-text class="text-sm text-muted {{ $text === '' ? 'hidden' : '' }}">{{ $text }}</span>
        <span class="sr-only">Loading</span>
    </div>
</div>
