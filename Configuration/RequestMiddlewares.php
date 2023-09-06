<?php
declare(strict_types = 1);

use Bitmotion\Auth0\Middleware\CallbackMiddleware;

return [
    'frontend' => [
        'bitmotion/auth0/callback' => [
            'target' => CallbackMiddleware::class,
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver',
            ],
        ]
    ],
];
