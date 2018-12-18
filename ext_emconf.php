<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Auth0 for TYPO3',
    'description' => 'Auth0 for TYPO3',
    'version' => '2.0.0-dev',
    'category' => 'misc',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-9.5.99',
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
];