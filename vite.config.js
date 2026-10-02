import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/pages/buyer/product-show.js',
                'resources/js/pages/buyer/buyer-cart.js',
                'resources/js/pages/buyer/orders.js',
                'resources/js/pages/auth/logistics-register.js',
                'resources/css/pages/auth-register.css',
                'resources/js/pages/auth/buyer-register.js',
                'resources/js/pages/auth/seller-register.js',
                'resources/js/pages/messaging.js',
                'resources/js/pages/buyer/message-widget.js',
                'resources/js/pages/seller/reports.js',
                'resources/js/pages/seller/account.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
