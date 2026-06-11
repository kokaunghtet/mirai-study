<div class="flex items-center gap-2.5 flex-1 min-w-0">
    {{--
        Logo silhouette painted with the active theme's accent gradient.
        logo-mask.png (a lightweight silhouette traced from logo.svg) is used purely
        as a CSS *mask* (its alpha = the shape); the visible color comes from the
        gradient background, so it recolors automatically whenever the theme swaps
        --accent-from / --accent-to. To regenerate after changing logo.svg:
        rsvg-convert -w 512 public/images/logo.svg -o public/images/logo-mask.png
    --}}
    <div class="w-14 h-14 shrink-0 bg-gradient-to-tr from-accent-from to-accent-to"
         role="img" aria-label="MiraiStudy Logo"
         style="
            -webkit-mask: url('{{ asset('images/logo-mask.png') }}') center / contain no-repeat;
                    mask: url('{{ asset('images/logo-mask.png') }}') center / contain no-repeat;
         "></div>
    <span class="font-semibold text-lg bg-gradient-to-tr from-accent-from to-accent-to bg-clip-text text-transparent whitespace-nowrap">MiraiStudy</span>
</div>
