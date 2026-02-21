import colors from 'tailwindcss/colors'

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            colors: {
                // Primary color (#006FCD base) - change this to update the entire site's accent color
                primary: {
                    50:  '#f0f7ff',
                    100: '#e0effe',
                    200: '#baddfb',
                    300: '#7dc3f8',
                    400: '#38a3f2',
                    500: '#0e88e3',
                    600: '#006FCD',
                    700: '#0058a6',
                    800: '#004b89',
                    900: '#003f71',
                    950: '#00294b',
                },
            },
        },
    },
    plugins: [],
}
