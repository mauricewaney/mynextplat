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
                // Primary color - change this to update the entire site's accent color
                primary: colors.teal,
            },
        },
    },
    plugins: [],
}
