/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        'app/views/admin/**/*.php',
        'public/assets/js/admin/**/*.js'
    ],
    theme: {
        extend: {
            colors: {
                sidebar: {
                    DEFAULT: '#0f172a',
                    hover: '#1e293b',
                    active: '#334155',
                    border: '#1e293b'
                }
            }
        }
    },
    plugins: []
};
