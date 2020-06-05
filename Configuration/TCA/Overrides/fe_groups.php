<?php
declare(strict_types = 1);
defined('TYPO3_MODE') || die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_groups', [
    'auth0_user_group' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:fe_groups.auth0_user_group',
        'config' => [
            'type' => 'input',
            'size' => 30,
            'eval' => 'trim',
        ],
    ],
]);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'fe_groups',
    '--div--;LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tabs.auth0,auth0_user_group',
    '0,Tx_Extbase_Domain_Model_FrontendUserGroup',
    'after:description'
);
