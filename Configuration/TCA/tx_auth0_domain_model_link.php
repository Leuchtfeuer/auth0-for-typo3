<?php
declare(strict_types = 1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_link',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => false,
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'title,client_id,domain',
        'iconfile' => 'EXT:auth0/Resources/Public/Icons/tx_auth0_domain_model_link.png',
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden, title, client_id, domain, redirect_uri',
    ],
    'types' => [
        '1' => ['showitem' => '
            --div--;LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tabs.basic,
                title,
                --palette--;;application,
                redirect_uri,
                additional_authorize_parameters,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                --palette--;;hidden,',
        ],
    ],
    'palettes' => [
        'application' => [
            'showitem' => 'application, --linebreak--, domain, client_id',
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
        'title' => [
            'exclude' => false,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_link.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ],
        ],
        'application' => [
            'exclude' => false,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_link.application',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_auth0_domain_model_application',
                'items' => [
                    ['LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_link.application.choose', 0],
                ],
                'default' => 0,
            ],
        ],
        'domain' => [
            'displayCond' => 'FIELD:application:REQ:false',
            'exclude' => false,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_link.domain',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ],
        ],
        'client_id' => [
            'displayCond' => 'FIELD:application:REQ:false',
            'exclude' => false,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_link.client_id',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ],
        ],
        'redirect_uri' => [
            'exclude' => false,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_link.redirect_uri',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim,required',
            ],
        ],
        'additional_authorize_parameters' => [
            'exclude' => false,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_link.additional_authorize_parameters',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ],
        ],
    ],
];
