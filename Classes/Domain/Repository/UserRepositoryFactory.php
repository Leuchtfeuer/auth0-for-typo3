<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Leuchtfeuer Digital Marketing <dev@Leuchtfeuer.com>
 */

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
