import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Global
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/landing.css',
                'resources/js/landing.js',

                // Components
                'resources/js/components/buyer-header.js',

                // Shared pages
                'resources/js/pages/help.js',
                'resources/js/pages/messaging.js',

                // Auth
                'resources/css/pages/auth-register.css',
                'resources/js/pages/auth/login.js',
                'resources/js/pages/auth/buyer-register.js',
                'resources/js/pages/auth/seller-register.js',
                'resources/js/pages/auth/logistics-register.js',

                // Buyer
                'resources/js/pages/buyer/buyer-cart.js',
                'resources/js/pages/buyer/message-widget.js',
                'resources/js/pages/buyer/orders.js',
                'resources/js/pages/buyer/product-show.js',

                // Seller
                'resources/css/pages/seller/products.css',
                'resources/js/pages/seller/account.js',
                'resources/js/pages/seller/products.js',
                'resources/js/pages/seller/reports.js',

                // Logistics
                'resources/js/pages/logistics/areas.js',

                // Admin
                'resources/js/pages/admin/buyers.js',
                'resources/js/pages/admin/logistics.js',
                'resources/js/pages/admin/registration-review.js',
                'resources/js/pages/admin/sellers.js',
                'resources/js/pages/admin/transactions.js',
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
