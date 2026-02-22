<?php

return [
    'currency' => 'EUR',

    'tax' => [
        'default_rate' => 20,
    ],

    'orders' => [
        'auto_confirm' => false,
    ],

    'mail' => [
        'order_notifications' => true,
    ],

    'users' => [
        'allow_secondary_users' => false,
        'default_role' => 'customer',
        'admin_role' => 'admin',
    ],

    'feature_flags' => [
        'payment.stripe' => false,
        'payment.paypal' => false,
    ],
];
