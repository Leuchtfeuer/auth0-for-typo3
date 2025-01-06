<?php
declare(strict_types = 1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
defined('TYPO3') || die();

ExtensionManagementUtility::addTCAcolumns(
    'fe_users',
    [
        'auth0_user_id' => [
            'exclude' => true,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_frontenduser.auth0_user_id',
            'config' => [
                'readOnly' => true,
                'type' => 'input',
                'size' => 30,
            ],
        ],
        'auth0_metadata' => [
            'exclude' => true,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_frontenduser.auth0_metadata',
            'config' => [
                'readOnly' => true,
                'type' => 'text',
                'cols' => 60,
                'rows' => 5,
            ],
        ],
        'auth0_last_application' => [
            'exclude' => true,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_frontenduser.auth0_last_application',
            'config' => [
                'readOnly' => true,
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_auth0_domain_model_application',
            ],
        ],
    ]
);

$auth0Showitem = <<<TCA
    --div--;LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_frontenduser,
        auth0_user_id,
        auth0_metadata,
        auth0_last_application
TCA;

foreach ($GLOBALS['TCA']['fe_users']['types'] ?? [] as $type => $_) {
    $showitem = trim((string) $GLOBALS['TCA']['fe_users']['types'][$type]['showitem']);
    $showitem = sprintf('%s,%s', rtrim($showitem, ','), $auth0Showitem);
    $GLOBALS['TCA']['fe_users']['types'][$type]['showitem'] = $showitem;
}
