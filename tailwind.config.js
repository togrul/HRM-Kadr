import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    mode: 'jit',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php'
    ],
    safelist: [
        'bg-cyan-100',
        'hover:bg-cyan-200',
        'text-cyan-500',
        'text-orange-500',
        'bg-rose-100',
        'hover:bg-rose-200',
        'text-rose-500',
        'border-rose-400',
        'border-emerald-400',
        'border-cyan-400',
        'border-orange-400',
        'text-yellow-500',
        'text-yellow-400',
      ],
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['CircularSpotify', ...defaultTheme.fontFamily.sans],
                title: ['CircularSpTitle', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
