<?php
declare(strict_types=1);

use Leuchtfeuer\Auth0\Domain\Model\Application;

return [
    'ctrl' => [
        'title' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_application',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => false,
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
        'searchFields' => 'title,id,domain,audience',
        'iconfile' => 'EXT:auth0/Resources/Public/Icons/auth0.svg',
    ],
    'types' => [
        '1' => ['showitem' => '
            --div--;LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tabs.basic,
                title,domain,
                --palette--;;client,
            --div--;LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tabs.features,
                --palette--;;api,
                single_log_out,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                --palette--;;hidden,',
        ],
    ],
    'palettes' => [
        'client' => [
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:palettes.client',
            'showitem' => 'id,secret,--linebreak--,signature_algorithm',
        ],
        'api' => [
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:palettes.api',
            'showitem' => 'api,audience'
        ],
        'hidden' => [
            'showitem' => 'hidden',
        ],
    ],
    'columns' => [
        'single_log_out' => [
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_application.single_log_out',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => ''
                    ],
                ],
                'default' => 1,
            ],
        ],
        'api' => [
            'exclude' => true,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_application.api',
            'onChange' => 'reload',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                    ],
                ],
            ],
        ],
        'title' => [
            'exclude' => false,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_application.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'id' => [
            'exclude' => false,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_application.id',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'secret' => [
            'exclude' => false,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_application.secret',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'domain' => [
            'exclude' => false,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_application.domain',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'audience' => [
            'exclude' => false,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_application.audience',
            'displayCond' => 'FIELD:api:REQ:true',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'required' => true,
                'default' => 'api/v2/',
            ],
        ],
        'signature_algorithm' => [
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_application.signature_algorithm',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => Application::ALG_RS256,
                        'value' => Application::ALG_RS256,
                    ],
                    [
                        'label' => Application::ALG_HS256,
                        'value' => Application::ALG_HS256,
                    ],
                ],
                'default' => Application::ALG_RS256,
            ],
        ]
    ],
];
