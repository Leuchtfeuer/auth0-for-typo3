<?php

$EM_CONF['auth0'] = [
    'title' => 'Auth0 for TYPO3',
    'description' => 'This extension allows you to log into a TYPO3 backend via Auth0. Auth0 is the solution you need for web, mobile, IoT, and internal applications. Loved by developers and trusted by enterprises.',
    'version' => '14.0.0',
    'category' => 'misc',
    'constraints' => [
        'depends' => [
            'typo3' => '14.3.0-14.3.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'state' => 'stable',
    'author' => 'Dev Leuchtfeuer',
    'author_email' => 'dev@Leuchtfeuer.com',
    'author_company' => 'Leuchtfeuer Digital Marketing',
    'autoload' => [
        'psr-4' => [
            'Leuchtfeuer\\Auth0\\' => 'Classes',
        ],
    ],
    'autoload-dev' => [
        'psr-4' => [
            'Leuchtfeuer\\Auth0\\Tests\\' => 'Classes/Tests',
        ],
    ],
];
