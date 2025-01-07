<?php

namespace Leuchtfeuer\Auth0\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;

class UserRepositoryFactory
{
    public function __construct(protected readonly ConnectionPool $connectionPool) {}

    public function create(string $tableName): UserRepository
    {
        return new UserRepository($this->connectionPool, $tableName);
    }
}