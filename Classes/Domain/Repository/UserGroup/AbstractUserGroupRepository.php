<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Leuchtfeuer Digital Marketing <dev@Leuchtfeuer.com>
 */

namespace Leuchtfeuer\Auth0\Domain\Repository\UserGroup;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractUserGroupRepository
{
    public const USER_GROUP_FIELD = 'auth0_user_group';

    protected $tableName;

    public function __construct()
    {
        $this->setTableName();
    }

    abstract protected function setTableName(): void;

    protected function getQueryBuilder(): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
    }

    public function findAll(): array
    {
        return $this->getQueryBuilder()->select('*')->from($this->tableName)->execute()->fetchAll();
    }
}
