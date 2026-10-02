<?php

return [
    'buyer' => ['super_admin', 'seller'],
    'seller' => ['super_admin', 'buyer', 'logistics', 'courier'],
    'super_admin' => ['buyer', 'seller', 'logistics', 'courier'],
    'logistics' => ['super_admin', 'seller', 'courier'],
    'courier' => ['super_admin', 'logistics'],
];
