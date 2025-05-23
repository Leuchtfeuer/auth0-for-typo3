<?php
declare(strict_types = 1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
defined('TYPO3') || die();

ExtensionManagementUtility::addTCAcolumns(
    'be_users',
    [
        'auth0_user_id' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_frontenduser.auth0_user_id',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'readOnly' => true,
            ],
        ],
    ]
);

$auth0Showitem = <<<TCA
    --div--;LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_backenduser,
        auth0_user_id,
TCA;

foreach ($GLOBALS['TCA']['be_users']['types'] ?? [] as $type => $_) {
    $showitem = trim((string) $GLOBALS['TCA']['be_users']['types'][$type]['showitem']);
    $showitem = sprintf('%s,%s', rtrim($showitem, ','), $auth0Showitem);
    $GLOBALS['TCA']['be_users']['types'][$type]['showitem'] = $showitem;
}
