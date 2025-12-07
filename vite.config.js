import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/css/arabic.css',
                'resources/css/classic.css',
                'resources/css/meditative.css',
                'resources/css/fitness.css',
                'resources/css/ckeditor-dark.css',
                'resources/js/app.js', 
                'resources/js/dashboard.js',
                'resources/js/classic.js',
                'resources/js/meditative.js',
                'resources/js/fitness.js',
                'resources/js/ckeditor-cdn.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
    },
});