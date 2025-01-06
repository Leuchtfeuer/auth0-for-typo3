<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'auth0' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:auth0/Resources/Public/Icons/auth0.svg',
    ],
    'moduleAuth0' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:auth0/Resources/Public/Icons/Module.svg',
    ],
    'auth0LoginProvider' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:auth0/Resources/Public/Icons/sign-in.svg',
    ],
];
