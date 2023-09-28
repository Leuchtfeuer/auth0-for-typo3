<?php
declare(strict_types = 1);

use Leuchtfeuer\Auth0\Middleware\CallbackMiddleware;

return [
    'frontend' => [
        'leuchtfeuer/auth0/callback' => [
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
