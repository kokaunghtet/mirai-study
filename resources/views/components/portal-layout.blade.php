@props(['mode' => 'login', 'portal' => true])
@php($activeMode = in_array(old('form_intent'), ['login', 'register']) ? old('form_intent') : $mode)
<!DOCTYPE html>
{{-- Auth portal is a self-contained twilight scene: forced dark so the form's
     theme tokens land on the dusk palette, with the brand "venom" accent.
     (Swap data-theme / drop the `dark` class to restyle.) --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      class="dark" data-theme="venom" data-fill="gradient">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/auth-scene.js'])

        {{-- Auth portal switch choreography: forms cross-slide + blur, the card
             tilts + shines, the pill indicator slides, and fields stagger in.
             (Driven by the 'portal' Alpine component in app.js.) --}}
        <style>
            :root { --portal-ease: cubic-bezier(0.22, 1, 0.36, 1); }

            /* ── Card: frosted glass + a brief 3D tilt and light sweep on switch ── */
            .auth-portal-card {
                position: relative;
                -webkit-backdrop-filter: blur(22px) saturate(160%);
                backdrop-filter: blur(22px) saturate(160%);
                transition:
                    transform .7s var(--portal-ease),
                    -webkit-backdrop-filter .7s var(--portal-ease),
                    backdrop-filter .7s var(--portal-ease);
            }
            .auth-portal-card.is-switching {
                transform: perspective(1200px) rotateY(8deg) scale(.96) translateX(-22px);
                -webkit-backdrop-filter: blur(34px) saturate(170%);
                backdrop-filter: blur(34px) saturate(170%);
            }
            .auth-portal-card.is-switching.to-register {
                transform: perspective(1200px) rotateY(-8deg) scale(.96) translateX(22px);
            }

            /* Diagonal shine that sweeps across while switching */
            .auth-portal-card::after {
                content: "";
                position: absolute;
                top: -100%;
                left: -50%;
                width: 80%;
                height: 300%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .14), transparent);
                transform: rotate(25deg);
                pointer-events: none;
                opacity: 0;
                z-index: 30;
            }
            .auth-portal-card.is-switching::after {
                opacity: 1;
                animation: portal-shine .8s ease;
            }
            @keyframes portal-shine {
                from { left: -80%; }
                to   { left: 150%; }
            }

            /* ── Form stage: both forms stacked; height animates to the active one ── */
            .portal-form-container { position: relative; }
            .portal-form-container.h-anim { transition: height .5s var(--portal-ease); }

            .portal-form {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                transition:
                    opacity .45s ease,
                    transform .65s var(--portal-ease),
                    filter .45s ease;
            }
            .portal-form.enter {
                opacity: 0;
                transform: translateX(60px);
                filter: blur(8px);
                pointer-events: none;
            }
            .portal-form.exit {
                opacity: 0;
                transform: translateX(-60px);
                filter: blur(8px);
                pointer-events: none;
            }
            .portal-form.active {
                opacity: 1;
                transform: translateX(0);
                filter: blur(0);
            }

            /* Stagger the active form's fields in (capped so long forms stay snappy) */
            .portal-form.active > * {
                animation: portal-stagger .5s var(--portal-ease) backwards;
            }
            .portal-form.active > *:nth-child(1) { animation-delay: .04s; }
            .portal-form.active > *:nth-child(2) { animation-delay: .08s; }
            .portal-form.active > *:nth-child(3) { animation-delay: .12s; }
            .portal-form.active > *:nth-child(4) { animation-delay: .16s; }
            .portal-form.active > *:nth-child(5) { animation-delay: .20s; }
            .portal-form.active > *:nth-child(6) { animation-delay: .24s; }
            .portal-form.active > *:nth-child(7) { animation-delay: .28s; }
            .portal-form.active > *:nth-child(8) { animation-delay: .32s; }
            .portal-form.active > *:nth-child(n+9) { animation-delay: .34s; }
            @keyframes portal-stagger {
                from { opacity: 0; transform: translateY(10px); }
                to   { opacity: 1; transform: translateY(0); }
            }

            /* ── Pill toggle: a single highlight that slides between the tabs ── */
            .portal-switcher { position: relative; }
            .portal-switcher-indicator {
                position: absolute;
                top: 4px;
                left: 4px;
                width: calc(50% - 4px);
                height: calc(100% - 8px);
                transition: transform .45s var(--portal-ease);
            }
            .portal-switcher.is-register .portal-switcher-indicator {
                transform: translateX(100%);
            }

            @media (prefers-reduced-motion: reduce) {
                .auth-portal-card,
                .auth-portal-card.is-switching,
                .portal-form,
                .portal-form.active > *,
                .portal-form-container.h-anim,
                .portal-switcher-indicator {
                    transition: none !important;
                    animation: none !important;
                }
                .auth-portal-card.is-switching { transform: none; }
                .auth-portal-card.is-switching::after { opacity: 0; }
                .portal-form { filter: none; }
                .portal-form.enter,
                .portal-form.exit { opacity: 0; transform: none; }
                .portal-form.active { opacity: 1; transform: none; }
            }
        </style>
    </head>
    {{-- Fixed twilight sky (independent of theme) so the fireflies always read as dusk. --}}
    <body class="relative min-h-screen overflow-x-hidden font-sans text-content antialiased"
          style="background:
                    radial-gradient(120% 80% at 50% 100%, rgba(201,123,107,0.35) 0%, rgba(201,123,107,0) 45%),
                    linear-gradient(180deg, #140f2a 0%, #241a47 35%, #3a2a58 65%, #5a3a5e 100%);
                 background-attachment: fixed;">

        {{-- Animated scene: procedural grass + interactive fireflies (resources/js/auth-scene.js). --}}
        <canvas id="auth-scene" class="pointer-events-none fixed inset-0" style="z-index:0"></canvas>

        <div class="relative z-10 flex min-h-screen items-center justify-center p-4 sm:p-6">
            {{-- Frosted-glass card (single column). x-data drives the login/register toggle,
                 username suggestions, and live validation (the 'portal' component in app.js). --}}
            <div @if($portal) x-data="portal({ mode: '{{ $activeMode }}', username: @js(old('username')), displayName: @js(old('display_name')), email: @js(old('email')), suggestUrl: '{{ route('username.suggestions') }}', checkUrl: '{{ route('username.available') }}' })"
                 :class="{ 'is-switching': switching, 'to-register': mode === 'register' }" @endif
                 class="auth-portal-card w-full max-w-[420px] overflow-hidden rounded-3xl border border-white/[0.06] bg-surface/55 p-7 shadow-[0_28px_70px_-20px_rgba(0,0,0,0.6)] sm:p-9">

                {{-- Logo --}}
                <a href="/" class="mb-7 flex items-center justify-center gap-2.5">
                    <span class="h-9 w-9 bg-gradient-to-br from-mirai-lime to-mirai-dark" role="img" aria-label="MiraiStudy"
                          style="-webkit-mask: url('{{ asset('images/logo-mask.png') }}') center / contain no-repeat;
                                         mask: url('{{ asset('images/logo-mask.png') }}') center / contain no-repeat;"></span>
                    <span class="bg-gradient-to-r from-mirai-lime to-mirai-dark bg-clip-text text-lg font-semibold text-transparent">MiraiStudy</span>
                </a>

                {{ $slot }}
            </div>
        </div>
    </body>
</html>
