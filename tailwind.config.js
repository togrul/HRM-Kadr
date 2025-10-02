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
        'bg-slate-500',
        'bg-cyan-500',
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

// 10 => 'bg-neutral-200/60 text-neutral-600 border-neutral-200/70',
//     20 => 'bg-amber-50 border-amber-200 text-amber-600',
//     30 => 'bg-sky-50 border-sky-200 text-sky-600',
//     40 => 'bg-indigo-50 border-indigo-200 text-indigo-600',
//     70 => 'bg-emerald-50 border-emerald-200 text-emerald-600',
//     90 => 'bg-rose-50 border-rose-200 text-rose-600',
