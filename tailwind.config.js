import forms from '@tailwindcss/forms';
import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    mode: 'jit',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
        "./app/Livewire/**/*.php",
        "./app/View/Components/**/*.php",
    ],
    safelist: [
        'bg-cyan-100',
        'hover:bg-cyan-200',
        'text-cyan-500',
        'text-orange-500',
        'bg-rose-100',
        'bg-amber-100',
        'bg-indigo-100',
        'text-amber-500',
        'text-blue-700',
        'border-blue-400',
        'hover:bg-rose-200',
        'text-rose-500',
        'text-amber-400',
        'border-rose-400',
        'border-emerald-400',
        'border-cyan-400',
        'border-orange-400',
        'text-yellow-500',
        'text-yellow-400',
        'bg-slate-500',
        'bg-cyan-500',
        'bg-orange-100',
        'bg-orange-500',
        'bg-neutral-200/60',
        'border-neutral-200/70',
        'border-amber-200'
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
