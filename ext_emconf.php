<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Auth0 for TYPO3',
    'description' => 'Auth0 for TYPO3',
    'version' => '1.0.0',
    'category' => 'misc',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-8.7.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'state' => 'alpha',
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