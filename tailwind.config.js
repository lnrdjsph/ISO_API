/** @type {import('tailwindcss').Config} */
const defaultTheme = require('tailwindcss/defaultTheme');

export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
        "./app/View/Components/**/*.php",
    ],
    safelist: [
        // StockBadge — badge background + text
        'border-red-200/60',    'bg-red-100/60',    'text-red-800',    'text-red-300',
        'border-orange-200/60', 'bg-orange-100/60', 'text-orange-800', 'text-orange-300',
        'border-green-300/60',  'bg-green-200/60',  'text-green-900',  'text-green-300',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: [
                    'Roboto', // Primary font: requires local installation or external import
                    'ui-sans-serif',
                    'system-ui',
                    '-apple-system',
                    'BlinkMacSystemFont',
                    '"Segoe UI"',
                    '"Helvetica Neue"',
                    'Arial',
                    '"Noto Sans"',
                    'sans-serif',
                    '"Apple Color Emoji"',
                    '"Segoe UI Emoji"',
                    '"Segoe UI Symbol"',
                    '"Noto Color Emoji"'
                ],
            },
        },
    },
    plugins: [
        require("@tailwindcss/forms"),
        require("@tailwindcss/typography"),
        require("@tailwindcss/aspect-ratio"),
        require("@tailwindcss/container-queries"),
    ],
};