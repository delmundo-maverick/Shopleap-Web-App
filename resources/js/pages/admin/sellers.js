import { registerReviewPage } from './registration-review.js';

registerReviewPage('sellersPage', {
    baseUrl: '/admin/registrations/sellers',
    noun: 'seller',
    businessFields: [
        { label: 'Business name', key: 'business_name' },
        { label: 'Category', key: 'line_of_business' },
    ],
});
