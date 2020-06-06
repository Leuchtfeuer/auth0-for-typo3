<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Bitmotion\Auth0\Domain\Repository\UserGroup;

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
        return $this->getQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->execute()
            ->fetchAll();
    }

    public function findByIdentifier(int $id): array
    {
        $qb = $this->getQueryBuilder();

        return $qb
            ->select('*')
            ->from($this->tableName)
            ->where($qb->expr()->eq('uid', $qb->createNamedParameter($id, \PDO::PARAM_INT)))
            ->execute()
            ->fetch();
    }

    public function findAllWithAuth0Role(): array
    {
        return $this->getQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->where(sprintf('%s <> ""', self::USER_GROUP_FIELD))
            ->execute()
            ->fetchAll();
    }

    public function translate(array $mapping): void
    {
        if (!empty($mapping)) {
            foreach ($mapping as $auth0Role => $userGroupId) {
                $userGroup = $this->findByIdentifier((int)$userGroupId);
                $userGroup[self::USER_GROUP_FIELD] = $auth0Role;
                $this->update($userGroup);
            }
        }
    }

    public function update(array $userGroup): void
    {
        $qb = $this->getQueryBuilder();

        foreach ($userGroup as $key => $value) {
            $qb->set($key, $value);
        }

        $qb->update($this->tableName)
            ->where($qb->expr()->eq('uid', $qb->createNamedParameter($userGroup['uid'], \PDO::PARAM_INT)))
            ->execute();
    }
}
