<?php
declare(strict_types=1);

if (!isset($GLOBALS['TCA']['fe_groups']['ctrl']['type'])) {
    // no type field defined, so we define it here. This will only happen the first time the extension is installed!!
    $GLOBALS['TCA']['fe_groups']['ctrl']['type'] = 'tx_extbase_type';

    $tempColumnstx_auth0_fe_groups = [];
    $tempColumnstx_auth0_fe_groups[$GLOBALS['TCA']['fe_groups']['ctrl']['type']] = [
        'exclude' => 1,
        'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0.tx_extbase_type',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                ['FrontendUserGroup', 'Tx_Auth0_FrontendUserGroup'],
            ],
            'default' => 'Tx_Auth0_FrontendUserGroup',
            'size' => 1,
            'maxitems' => 1,
        ],
    ];
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_groups', $tempColumnstx_auth0_fe_groups);
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'fe_groups',
    $GLOBALS['TCA']['fe_groups']['ctrl']['type'],
    '',
    'after:' . $GLOBALS['TCA']['fe_groups']['ctrl']['label']
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_groups', [
    'auth0_user_group' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_frontendusergroup.auth0_user_group',
        'config' => [
            'type' => 'input',
            'size' => 30,
            'eval' => 'trim',
        ],
    ],
]);

/* inherit and extend the show items from the parent class */

if (isset($GLOBALS['TCA']['fe_groups']['types']['0']['showitem'])) {
    $GLOBALS['TCA']['fe_groups']['types']['Tx_Auth0_FrontendUserGroup']['showitem'] = $GLOBALS['TCA']['fe_groups']['types']['0']['showitem'];
} elseif (is_array($GLOBALS['TCA']['fe_groups']['types'])) {
    // use first entry in types array
    $fe_groups_type_definition = reset($GLOBALS['TCA']['fe_groups']['types']);
    $GLOBALS['TCA']['fe_groups']['types']['Tx_Auth0_FrontendUserGroup']['showitem'] = $fe_groups_type_definition['showitem'];
} else {
    $GLOBALS['TCA']['fe_groups']['types']['Tx_Auth0_FrontendUserGroup']['showitem'] = '';
}
$GLOBALS['TCA']['fe_groups']['types']['Tx_Auth0_FrontendUserGroup']['showitem'] .= ',--div--;LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_frontendusergroup,';
$GLOBALS['TCA']['fe_groups']['types']['Tx_Auth0_FrontendUserGroup']['showitem'] .= 'auth0_user_group';

$GLOBALS['TCA']['fe_groups']['columns'][$GLOBALS['TCA']['fe_groups']['ctrl']['type']]['config']['items'][] = [
    'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:fe_groups.tx_extbase_type.Tx_Auth0_FrontendUserGroup',
    'Tx_Auth0_FrontendUserGroup',
];
