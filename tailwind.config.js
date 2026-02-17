import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['Fredoka One', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                lucky: {
                    50: '#fef7ec',
                    100: '#fdecc8',
                    200: '#fbd78c',
                    300: '#f9bd50',
                    400: '#f7a528',
                    500: '#f08c10',
                    600: '#d4680a',
                    700: '#b04a0c',
                    800: '#8f3a10',
                    900: '#763110',
                },
                ticket: {
                    50: '#fdf2f8',
                    100: '#fce7f3',
                    200: '#fbcfe8',
                    300: '#f9a8d4',
                    400: '#f472b6',
                    500: '#ec4899',
                    600: '#db2777',
                    700: '#be185d',
                    800: '#9d174d',
                    900: '#831843',
                },
                prize: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    300: '#93c5fd',
                    400: '#60a5fa',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    800: '#1e40af',
                    900: '#1e3a8a',
                },
            },
            animation: {
                'spin-slow': 'spin 3s linear infinite',
                'bounce-slow': 'bounce 2s infinite',
                'shimmer': 'shimmer 2s infinite',
                'confetti': 'confetti 1s ease-out forwards',
                'float': 'float 3s ease-in-out infinite',
                'pulse-glow': 'pulse-glow 2s ease-in-out infinite',
            },
            keyframes: {
                shimmer: {
                    '0%': { backgroundPosition: '-200% 0' },
                    '100%': { backgroundPosition: '200% 0' },
                },
                confetti: {
                    '0%': { transform: 'translateY(0) rotate(0deg)', opacity: '1' },
                    '100%': { transform: 'translateY(-100px) rotate(720deg)', opacity: '0' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0px)' },
                    '50%': { transform: 'translateY(-10px)' },
                },
                'pulse-glow': {
                    '0%, 100%': { boxShadow: '0 0 5px rgba(240, 140, 16, 0.5)' },
                    '50%': { boxShadow: '0 0 25px rgba(240, 140, 16, 0.8), 0 0 50px rgba(240, 140, 16, 0.3)' },
                },
            },
            backgroundImage: {
                'confetti-pattern': "url(\"data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23f7a528' fill-opacity='0.08'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E\")",
                'ticket-border': "url(\"data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='10' cy='10' r='5' fill='%23f0f0f0'/%3E%3C/svg%3E\")",
            },
        },
    },

    plugins: [forms],
};
