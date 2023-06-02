<?php
declare(strict_types = 1);

return [
    'frontend' => [
        'leuchtfeuer/auth0/callback' => [
            'target' => \Leuchtfeuer\Auth0\Middleware\CallbackMiddleware::class,
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver',
            ],
        ]
    ],
];
