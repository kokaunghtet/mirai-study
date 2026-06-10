import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'mirai-lime': '#16A34A',
                'mirai-dark': '#166534',
                'mirai-aurora': '#00D4FF',
                'mirai-violet': '#5B0FBE',
                'mirai-sangria': '#BE123C', // wine red (Sangria gradient start / accent)
                'mirai-sunset': '#F472B6', // dusk pink (Twilight gradient start)
                'mirai-obsidian': '#4C0519', // deep blackberry (Sangria gradient end)
                'mirai-midnight': '#7C3AED', // dusk violet (Twilight gradient end / accent)
                'mirai-apricot': '#EA580C',  // blaze orange (Inferno gradient start / accent)
                'mirai-slate': '#7F1D1D',  // blood-red ember (Inferno gradient end)

                // ── Themeable tokens (driven by CSS vars in app.css) ──
                // Accent follows the user's saved accent_color ([data-theme]).
                'accent':        'rgb(var(--accent) / <alpha-value>)',
                'accent-strong': 'rgb(var(--accent-strong) / <alpha-value>)',
                'accent-from':   'rgb(var(--accent-from) / <alpha-value>)',
                'accent-to':     'rgb(var(--accent-to) / <alpha-value>)',
                // Surfaces follow light/dark mode (.dark on <html>).
                'canvas':        'rgb(var(--canvas) / <alpha-value>)',
                'surface':       'rgb(var(--surface) / <alpha-value>)',
                'surface-muted': 'rgb(var(--surface-muted) / <alpha-value>)',
                'content':       'rgb(var(--content) / <alpha-value>)',
                'muted':         'rgb(var(--muted) / <alpha-value>)',
                'line':          'rgb(var(--line) / <alpha-value>)',
            },
        },
    },

    plugins: [forms],
};
