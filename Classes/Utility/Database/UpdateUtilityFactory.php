<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Leuchtfeuer Digital Marketing <dev@Leuchtfeuer.com>
 */

namespace Leuchtfeuer\Auth0\Utility\Database;

use Leuchtfeuer\Auth0\Configuration\Auth0Configuration;
use Leuchtfeuer\Auth0\Domain\Repository\UserGroup\BackendUserGroupRepository;
use Leuchtfeuer\Auth0\Domain\Repository\UserRepositoryFactory;

class UpdateUtilityFactory
{
    public function __construct(
        protected readonly Auth0Configuration $auth0Configuration,
        protected readonly BackendUserGroupRepository $backendUserGroupRepository,
        protected readonly UserRepositoryFactory $userRepositoryFactory,
    ) {}

    /**
     * @param array<string, mixed> $user
     */
    public function create(string $tableName, array $user): UpdateUtility
    {
        return new UpdateUtility(
            $this->auth0Configuration,
            $this->backendUserGroupRepository,
            $this->userRepositoryFactory,
            $tableName,
            $user,
        );
    }
}
