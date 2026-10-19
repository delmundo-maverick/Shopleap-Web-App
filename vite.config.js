
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import { globSync } from 'node:fs';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Automatically include all CSS and JavaScript files
                // inside resources/css and resources/js
                ...globSync([
                    'resources/css/**/*.css',
                    'resources/js/**/*.js',
                ]),
            ],

            // Automatically refresh the browser when relevant files change
            refresh: true,

            // Bunny Fonts
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),

        // Tailwind CSS v4
        tailwindcss(),
    ],

    server: {
        watch: {
            // Ignore Laravel's compiled Blade views
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
