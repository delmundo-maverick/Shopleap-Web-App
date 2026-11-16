<?php

return [
    'logistics_seller_settlements' => env('LOGISTICS_SELLER_SETTLEMENTS', true),
    'seller_settlement_due_days' => 3,
    'timezone' => 'Asia/Manila',
    'return_shipping_payer' => [
        'wrong_item' => 'seller',
        'defective' => 'seller',
        'damaged' => 'seller',
        'changed_mind' => 'buyer',
    ],
    'default_return_shipping_payer' => 'seller',
];
