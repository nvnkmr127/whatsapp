import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: "class",
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
        "./app/Livewire/**/*Table.php",
        "./vendor/power-components/livewire-powergrid/resources/views/**/*.php",
        "./vendor/power-components/livewire-powergrid/src/Themes/Tailwind.php",
        "./resources/views/**/*.blade.php",
    ],
    presets: [
        require("./vendor/power-components/livewire-powergrid/tailwind.config.js"),
    ],

    theme: {
        extend: {
            animation: {
                'slow-ping': 'ping 2s linear infinite', // Slows down the default ping
            },
            screens: {
                xs: { max: "360px" },
                xss:{min:"400px"}  // Custom media query for 360px
            },
            fontFamily: {
                sans: ["Inter", ...defaultTheme.fontFamily.sans],
                display: ["Lexend", ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [
        require("@tailwindcss/forms"),
        require("@tailwindcss/typography"),
        require("@tailwindcss/aspect-ratio"),
    ],

    plugins: [forms],
};
