<?php
declare(strict_types=1);
defined('TYPO3_MODE') || die();

if (!isset($GLOBALS['TCA']['fe_users']['ctrl']['type'])) {
    // no type field defined, so we define it here. This will only happen the first time the extension is installed!!
    $GLOBALS['TCA']['fe_users']['ctrl']['type'] = 'tx_extbase_type';

    $tempColumnstx_auth0_fe_users = [];
    $tempColumnstx_auth0_fe_users[$GLOBALS['TCA']['fe_users']['ctrl']['type']] = [
        'exclude' => 1,
        'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0.tx_extbase_type',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                ['FrontendUser', 'Tx_Auth0_FrontendUser'],
            ],
            'default' => 'Tx_Auth0_FrontendUser',
            'size' => 1,
            'maxitems' => 1,
        ],
    ];
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $tempColumnstx_auth0_fe_users);
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'fe_users',
    $GLOBALS['TCA']['fe_users']['ctrl']['type'],
    '',
    'after:' . $GLOBALS['TCA']['fe_users']['ctrl']['label']
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'fe_users',
    [
        'auth0_user_id' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_frontenduser.auth0_user_id',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ],
        ],
        'auth0_metadata' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_frontenduser.auth0_metadata',
            'config' => [
                'readOnly' => 1,
                'type' => 'text',
                'cols' => 60,
                'rows' => 5,
            ],
        ],
    ]
);

/* inherit and extend the show items from the parent class */

if (isset($GLOBALS['TCA']['fe_users']['types']['0']['showitem'])) {
    $GLOBALS['TCA']['fe_users']['types']['Tx_Auth0_FrontendUser']['showitem'] = $GLOBALS['TCA']['fe_users']['types']['0']['showitem'];
} elseif (is_array($GLOBALS['TCA']['fe_users']['types'])) {
    // use first entry in types array
    $fe_users_type_definition = reset($GLOBALS['TCA']['fe_users']['types']);
    $GLOBALS['TCA']['fe_users']['types']['Tx_Auth0_FrontendUser']['showitem'] = $fe_users_type_definition['showitem'];
} else {
    $GLOBALS['TCA']['fe_users']['types']['Tx_Auth0_FrontendUser']['showitem'] = '';
}
$GLOBALS['TCA']['fe_users']['types']['Tx_Auth0_FrontendUser']['showitem'] .= ',--div--;LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_frontenduser,';
$GLOBALS['TCA']['fe_users']['types']['Tx_Auth0_FrontendUser']['showitem'] .= 'auth0_user_id,auth0_metadata';

$GLOBALS['TCA']['fe_users']['columns'][$GLOBALS['TCA']['fe_users']['ctrl']['type']]['config']['items'][] = [
    'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:fe_users.tx_extbase_type.Tx_Auth0_FrontendUser',
    'Tx_Auth0_FrontendUser',
];
