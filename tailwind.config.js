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
            pattern: /(bg|text|border)-(cyan|orange|rose|amber|indigo|lime|blue|emerald|yellow|slate|neutral|gray|zinc)-(50|100|200|300|400|500|600|700)(\/(50|60|70))?/,
            variants: ['hover'],
        },
    ],
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['CircularSpotify', ...defaultTheme.fontFamily.sans],
                title: ['CircularSpTitle', ...defaultTheme.fontFamily.sans],
                mono: ['IBMPlexMono', 'IBM Plex Mono', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                ink: {
                    DEFAULT: '#18181b',
                    hover: '#000000',
                    soft: '#27272a',
                    muted: '#52525b',
                    faint: '#a1a1aa',
                },
                hairline: {
                    DEFAULT: '#e4e4e7',
                    subtle: '#f4f4f5',
                },
            },
            borderRadius: {
                '4xl': 'calc(.625rem + 16px)',
            },
            boxShadow: {
                overlay: '0 24px 60px -14px rgba(0,0,0,0.3)',
                card: '0 1px 2px rgba(16,24,40,0.04)',
            },
            spacing: {
                rail: '60px',
                panel: '300px',
            },
            maxWidth: {
                // full-bleed up to a 24" monitor (1920px), centred beyond that
                shell: '1920px',
            },
        },
    },

    plugins: [forms],
};
