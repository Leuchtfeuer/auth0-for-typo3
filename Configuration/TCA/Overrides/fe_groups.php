<?php
declare(strict_types = 1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Leuchtfeuer\Auth0\Domain\Repository\UserGroup\FrontendUserGroupRepository;
use Leuchtfeuer\Auth0\Domain\Repository\UserGroup\AbstractUserGroupRepository;
defined('TYPO3') or die();

ExtensionManagementUtility::addTCAcolumns(
    FrontendUserGroupRepository::TABLE_NAME,
    [
        AbstractUserGroupRepository::USER_GROUP_FIELD => [
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

ExtensionManagementUtility::addToAllTCAtypes(
    FrontendUserGroupRepository::TABLE_NAME,
    sprintf(
        '--div--;LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tabs.auth0,%s',
        AbstractUserGroupRepository::USER_GROUP_FIELD
    ),
    '0,Tx_Extbase_Domain_Model_FrontendUserGroup',
    'after:description'
);
