<?php
declare(strict_types = 1);

return [
    'frontend' => [
        'bitmotion/auth0' => [
            'target' => \Bitmotion\Auth0\Middleware\Auth0Middleware::class,
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver',
            ],
        ],
        'bitmotion/auth0/callback' => [
            'target' => \Bitmotion\Auth0\Middleware\CallbackMiddleware::class,
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
            'before' => [
                'typo3/cms-redirects/redirecthandler',
            ],
        ]
    ],
];
