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
                'mirai-sangria': '#C96B5D', // primary warm
                'mirai-sunset': '#FFC46B', // golden sunset
                'mirai-obsidian': '#2B2730', // neutral dark
                'mirai-midnight': '#4A3AFF', // vivid indigo
                'mirai-apricot': '#FFA94D',  // soft orange
                'mirai-slate': '#23252B',  // cool dark gray
            },
        },
    },

    plugins: [forms],
};
