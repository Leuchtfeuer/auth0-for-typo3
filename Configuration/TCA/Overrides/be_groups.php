<?php
declare(strict_types = 1);
defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    \Bitmotion\Auth0\Domain\Repository\UserGroup\BackendUserGroupRepository::TABLE_NAME,
    [
        \Bitmotion\Auth0\Domain\Repository\UserGroup\AbstractUserGroupRepository::USER_GROUP_FIELD => [
            'exclude' => 1,
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/Database.xlf:fe_groups.auth0_user_group',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    \Bitmotion\Auth0\Domain\Repository\UserGroup\BackendUserGroupRepository::TABLE_NAME,
    sprintf(
        '--div--;LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tabs.auth0,%s',
        \Bitmotion\Auth0\Domain\Repository\UserGroup\AbstractUserGroupRepository::USER_GROUP_FIELD
    ),
    '0',
    'after:description'
);
