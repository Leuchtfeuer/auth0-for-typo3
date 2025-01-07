<?php

namespace Leuchtfeuer\Auth0\Utility\Database;

use Leuchtfeuer\Auth0\Configuration\Auth0Configuration;
use Leuchtfeuer\Auth0\Domain\Repository\UserGroup\BackendUserGroupRepository;
use Leuchtfeuer\Auth0\Domain\Repository\UserGroup\FrontendUserGroupRepository;

class UpdateUtilityFactory
{
    public function __construct(
        protected readonly Auth0Configuration $auth0Configuration,
        protected readonly BackendUserGroupRepository $backendUserGroupRepository,
        protected readonly FrontendUserGroupRepository $frontendUserGroupRepository,
    ) {}

    public function create(string $tableName, array $user): UpdateUtility
    {
        return new UpdateUtility(
            $tableName,
            $user,
            $this->auth0Configuration,
            $this->backendUserGroupRepository,
            $this->frontendUserGroupRepository
        );
    }
}