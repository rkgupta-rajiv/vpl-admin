/** Tailwind build for the Blade admin panel → public/css/admin.css
 *  Rebuild after changing views:  npm install && npm run build:admin-css
 */
module.exports = {
    content: ['./resources/views/**/*.blade.php', './app/**/*.php', './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/tailwind.blade.php'],
    theme: {
        extend: {
            colors: {
                pitch: { 50: '#eefbf3', 100: '#d6f5e1', 600: '#13704a', 700: '#0f5a3c', 800: '#0c4630', 900: '#0a3a28', 950: '#062419' },
            },
            fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'Segoe UI', 'Roboto', 'sans-serif'] },
        },
    },
    plugins: [],
};
