<?php

namespace Leuchtfeuer\Auth0\Utility\Database;

use Leuchtfeuer\Auth0\Configuration\Auth0Configuration;
use Leuchtfeuer\Auth0\Domain\Repository\UserGroup\BackendUserGroupRepository;
use Leuchtfeuer\Auth0\Domain\Repository\UserGroup\FrontendUserGroupRepository;
use Leuchtfeuer\Auth0\Domain\Repository\UserRepositoryFactory;
use Leuchtfeuer\Auth0\Utility\UserUtility;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository;

class UpdateUtilityFactory
{
    public function __construct(
        protected readonly Auth0Configuration $auth0Configuration,
        protected readonly BackendUserGroupRepository $backendUserGroupRepository,
        protected readonly FrontendUserGroupRepository $frontendUserGroupRepository,
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
            $this->frontendUserGroupRepository,
            $this->userRepositoryFactory,
            $tableName,
            $user,
        );
    }
}