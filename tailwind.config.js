import forms from '@tailwindcss/forms';
import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './resources/**/*.{blade.php,js,vue}',
        './app/Livewire/**/*.php',
        './app/View/Components/**/*.php',
        './app/Modules/**/*.php',
        './app/Modules/**/*.blade.php',
    ],
    safelist: [
        {
            pattern: /(bg|text|border)-(cyan|orange|rose|amber|indigo|lime|blue|emerald|yellow|slate|neutral|gray)-(50|100|200|300|400|500|600|700)(\/(50|60|70))?/,
            variants: ['hover'],
        },
    ],
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['CircularSpotify', ...defaultTheme.fontFamily.sans],
                title: ['CircularSpTitle', ...defaultTheme.fontFamily.sans],
                mono: ['IBMPlexMono', ...defaultTheme.fontFamily.mono],
            },
            borderRadius: {
                '4xl': 'calc(.625rem + 16px)',
            },
        },
    },

    plugins: [forms],
};
