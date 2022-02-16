<?php
declare(strict_types = 1);

use Bitmotion\Auth0\Domain\Model\Auth0\Management\Client\JwtConfiguration;

return [
    'ctrl' => [
        'title' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_application',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => false,
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
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
            'showitem' => 'id,secret,--linebreak--,signature_algorithm,secret_base64_encoded',
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
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                        1 => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'single_log_out' => [
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_application.single_log_out',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                        1 => '',
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
                        0 => '',
                        1 => '',
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
                'eval' => 'trim,required',
            ],
        ],
        'id' => [
            'exclude' => false,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_application.id',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ],
        ],
        'secret' => [
            'exclude' => false,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_application.secret',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ],
        ],
        'domain' => [
            'exclude' => false,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_application.domain',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ],
        ],
        'audience' => [
            'exclude' => false,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_application.audience',
            'displayCond' => 'FIELD:api:REQ:true',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
                'default' => 'api/v2/',
            ],
        ],
        'secret_base64_encoded' => [
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_application.secret_base64_encoded',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                        1 => '',
                    ],
                ],
                'default' => 0,
            ],
        ],
        'signature_algorithm' => [
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_application.signature_algorithm',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [ JwtConfiguration::ALG_RS256, JwtConfiguration::ALG_RS256 ],
                    [ JwtConfiguration::ALG_HS256, JwtConfiguration::ALG_HS256 ],
                ],
                'default' => JwtConfiguration::ALG_RS256,
            ],
        ]
    ],
];
