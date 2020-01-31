<?php

$EM_CONF['auth0'] = [
    'title' => 'Auth0 for TYPO3',
    'description' => 'This extension allows you to log into a TYPO3 backend or frontend via Auth0. Auth0 is the solution you need for web, mobile, IoT, and internal applications. Loved by developers and trusted by enterprises.',
    'version' => '3.1.0-dev',
    'category' => 'misc',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-10.1.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'state' => 'stable',
    'uploadfolder' => false,
    'createDirs' => '',
    'clearCacheOnLoad' => true,
    'author' => 'Florian Wessels',
    'author_email' => 'f.wessels@bitmotion.de',
    'author_company' => 'Bitmotion GmbH',
    'autoload' => [
        'psr-4' => [
            'Bitmotion\\Auth0\\' => 'Classes',
        ],
    ],
    'autoload-dev' => [
        'psr-4' => [
            'Bitmotion\\Auth0\\Tests\\' => 'Classes/Tests',
        ],
    ],
];
