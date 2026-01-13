import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
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
                    light: '#dcf8c6', // Light green for bubbles
                    green: '#25D366', // Primary brand green
                    teal: '#128C7E',  // Dark teal for headers
                    dark: '#075E54',  // Darker teal
                    blue: '#34B7F1',  // Accents
                    bg: '#ece5dd',    // Chat background
                }
            }
        },
    },

    plugins: [forms, typography],
};
