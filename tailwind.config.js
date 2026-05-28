import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                wa: {
                    DEFAULT: '#25D366',
                    soft: '#f0fdfa',
                    brand: '#25D366',
                    light: '#dcf8c6',
                    'bubble-out': '#dcf8c6',
                    'bubble-in': '#ffffff',
                    green: '#25D366',
                    teal: '#128C7E',
                    dark: '#075E54',
                    blue: '#34B7F1',
                    bg: '#ece5dd',
                    'dark-bg': '#0b141a',
                    'dark-surface': '#202c33',
                    'dark-elevated': '#111b21',
                    'chat-bg': '#e5ddd5',
                    'surface': {
                        50: '#f8fafc',
                        100: '#f1f5f9',
                        800: '#1e293b',
                        900: '#0f172a',
                    }
                },
                brand: {
                    DEFAULT: '#f97316',
                    50:  '#fff7ed',
                    100: '#ffedd5',
                    200: '#fed7aa',
                    300: '#fdba74',
                    400: '#fb923c',
                    500: '#f97316',
                    600: '#ea580c',
                    700: '#c2410c',
                    800: '#9a3412',
                    900: '#7c2d12',
                    950: '#431407',
                },
            },
            fontSize: {
                'tiny': ['0.625rem', '0.75rem'],  // 10px
                'micro': ['0.5rem', '0.625rem'],  // 8px
                'nano': ['0.5625rem', '0.6875rem'], // 9px
                'wa-caption': ['13px', '18px'],
            },
            borderRadius: {
                'wa-mockup': '3rem',
                'premium': '1.5rem',
                'xl-premium': '2rem',
            }
        },
    },

    plugins: [forms, typography],
};
