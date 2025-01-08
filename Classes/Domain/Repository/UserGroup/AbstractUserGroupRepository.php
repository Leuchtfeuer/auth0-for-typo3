<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\Auth0\Domain\Repository\UserGroup;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

abstract class AbstractUserGroupRepository
{
    public const USER_GROUP_FIELD = 'auth0_user_group';

    protected string $tableName;

    public function __construct(protected readonly ConnectionPool $connectionPool)
    {
        $this->setTableName();
    }

    abstract protected function setTableName(): void;

    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable($this->tableName);
    }

    /**
     * @return array<array<string, mixed>>
     * @throws Exception
     */
    public function findAll(): array
    {
        return $this->getQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
