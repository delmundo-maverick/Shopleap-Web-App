<?php

return [
    'timezone' => 'Asia/Manila',
    'return_shipping_payer' => [
        'wrong_item' => 'seller',
        'defective' => 'seller',
        'damaged' => 'seller',
        'changed_mind' => 'buyer',
    ],
    'default_return_shipping_payer' => 'seller',
];
