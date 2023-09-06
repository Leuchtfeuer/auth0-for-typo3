<?php

$EM_CONF['auth0'] = [
    'title' => 'Auth0 for TYPO3',
    'description' => 'This extension allows you to log into a TYPO3 backend or frontend via Auth0. Auth0 is the solution you need for web, mobile, IoT, and internal applications. Loved by developers and trusted by enterprises.',
    'version' => '5.0.0',
    'category' => 'misc',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.99-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'author' => 'Max Rösch',
    'author_email' => 'm.roesch@Leuchtfeuer.com',
    'author_company' => 'Leuchtfeuer Digital Marketing',
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
