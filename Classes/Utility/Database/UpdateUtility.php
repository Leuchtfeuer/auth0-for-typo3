<?php

declare(strict_types=1);

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\Auth0\Utility\Database;

use Leuchtfeuer\Auth0\Configuration\Auth0Configuration;
use Leuchtfeuer\Auth0\Domain\Repository\UserGroup\AbstractUserGroupRepository;
use Leuchtfeuer\Auth0\Domain\Repository\UserGroup\BackendUserGroupRepository;
use Leuchtfeuer\Auth0\Domain\Repository\UserGroup\FrontendUserGroupRepository;
use Leuchtfeuer\Auth0\Domain\Repository\UserRepository;
use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use Leuchtfeuer\Auth0\Utility\ParseFuncUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UpdateUtility implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected EmAuth0Configuration $configuration;

    protected $yamlConfiguration = [];

    public function __construct(
        protected string $tableName,
        protected array $user,
        protected readonly Auth0Configuration $auth0Configuration,
        protected readonly BackendUserGroupRepository $backendUserGroupRepository,
        protected readonly FrontendUserGroupRepository $frontendUserGroupRepository,
    ) {
        $this->configuration = new EmAuth0Configuration();
        $this->yamlConfiguration = $this->auth0Configuration->load();
    }

    public function updateGroups(): void
    {
        $groupMapping = $this->getGroupMappingFromDatabase();
        $this->addDefaultGroup($groupMapping);

        if (empty($groupMapping)) {
            /** @extensionScannerIgnoreLine */
            $this->logger->error(sprintf('Cannot update user groups: No role mapping for %s found', $this->tableName));

            return;
        }

        $shouldUpdate = false;
        $isBackendAdmin = false;
        $groupsToAssign = [];

        // Map Auth0 roles on TYPO3 user groups
        $this->mapRoles($groupMapping, $groupsToAssign, $isBackendAdmin, $shouldUpdate);

        // Update user only if necessary
        if ($shouldUpdate === true) {
            $this->logger->notice('Update user groups.');
            $this->performGroupUpdate($groupsToAssign, $isBackendAdmin);
        }
    }

    public function updateUser(bool $reactivateUser = false): void
    {
        $mappingConfiguration = $this->yamlConfiguration['properties'][$this->tableName] ?? null;

        if ($mappingConfiguration === null) {
            /** @extensionScannerIgnoreLine */
            $this->logger->error(sprintf('Cannot update user: No mapping configuration for %s found', $this->tableName));

            return;
        }

        $this->performUserUpdate($mappingConfiguration, $reactivateUser);
    }

    protected function getGroupMappingFromDatabase(): array
    {
        $groupMapping = [];
        $userGroupRepository = $this->getUserGroupRepository();

        if ($userGroupRepository instanceof AbstractUserGroupRepository) {
            foreach ($userGroupRepository->findAll() as $userGroup) {
                if (!empty($userGroup['auth0_user_group'])) {
                    $groupMapping[$userGroup[AbstractUserGroupRepository::USER_GROUP_FIELD]] ??= [];
                    $groupMapping[$userGroup[AbstractUserGroupRepository::USER_GROUP_FIELD]][] = $userGroup['uid'];
                }
            }
        }

        return $groupMapping;
    }

    protected function getUserGroupRepository(): ?AbstractUserGroupRepository
    {
        return match ($this->tableName) {
            'fe_users' => $this->frontendUserGroupRepository,
            'be_users' => $this->backendUserGroupRepository,
            default => null,
        };
    }

    protected function addDefaultGroup(array &$groupMapping): void
    {
        $key = 'frontend';
        $userGroupTableName = 'fe_groups';

        if ($this->tableName === 'be_users') {
            $key = 'backend';
            $userGroupTableName = 'be_groups';
        }

        $defaultGroup = (int)($this->yamlConfiguration['roles']['default'][$key] ?? 0);
        $userGroup = BackendUtility::getRecord($userGroupTableName, $defaultGroup);

        // Use default user group only when group id is not null and record exists (and is neither deleted nor hidden)
        if ($defaultGroup !== 0 && $userGroup !== null) {
            $groupMapping['__default'] = [$defaultGroup];
        }
    }

    protected function mapRoles(array $groupMapping, array &$groupsToAssign, bool &$isBeAdmin, bool &$shouldUpdate): void
    {
        $rolesKey = $this->yamlConfiguration['roles']['key'] ?? 'roles';
        $roles = (array)($this->user['app_metadata'][$rolesKey] ?? []);

        foreach ($roles as $role) {
            if (isset($groupMapping[$role])) {
                $this->logger->notice(sprintf('Assign group "%s" to user.', $groupMapping[$role]));
                $groupsToAssign = array_merge($groupsToAssign, $groupMapping[$role]);
                $shouldUpdate = true;
            } elseif (!empty($this->yamlConfiguration['roles']['beAdmin']) && $role === $this->yamlConfiguration['roles']['beAdmin']) {
                $isBeAdmin = true;
                $shouldUpdate = true;
            } else {
                $this->logger->warning(sprintf('No mapping for Auth0 role "%s" found.', $role));
            }
        }

        // Assign default group to user if no group matches
        if ($shouldUpdate === false && isset($groupMapping['__default']) && !$isBeAdmin) {
            $groupsToAssign = array_merge($groupsToAssign, $groupMapping['__default']);
            $shouldUpdate = true;
        }
    }

    protected function performGroupUpdate(array $groupsToAssign, bool $isBeAdmin): void
    {
        $updates = [];
        $groupsToAssign = array_unique($groupsToAssign);

        // Update usergroup in database
        if ($groupsToAssign !== []) {
            $updates['usergroup'] = implode(',', $groupsToAssign);
        }

        // Set admin flag for backend users
        if ($this->tableName === 'be_users') {
            $updates['admin'] = (int)$isBeAdmin;
        }

        if (!empty($updates)) {
            $userRepository = GeneralUtility::makeInstance(UserRepository::class, $this->tableName);
            $userRepository->updateUserByAuth0Id($updates, $this->user[$this->configuration->getUserIdentifier()]);
        }
    }

    protected function performUserUpdate(array $mappingConfiguration, bool $reactivateUser): void
    {
        $this->logger->debug(
            sprintf(
                '%s: Prepare update for Auth0 user "%s"',
                $this->tableName,
                $this->user[$this->configuration->getUserIdentifier()]
            )
        );

        $updates = [];
        $userRepository = GeneralUtility::makeInstance(UserRepository::class, $this->tableName);

        $this->mapUserData($updates, $mappingConfiguration);

        // Fixed values
        // TODO: Check - seems no to be used anymore
        if ($reactivateUser) {
            $updates['disable'] = 0;
            $updates['deleted'] = 0;
        }

        $this->addRestrictions($userRepository);
        $userRepository->updateUserByAuth0Id($updates, $this->user[$this->configuration->getUserIdentifier()]);
    }

    protected function addRestrictions(UserRepository &$userRepository): void
    {
        $reactivateDeleted = false;
        $reactivateDisabled = false;

        if ($this->tableName === 'fe_users') {
            $reactivateDeleted = $this->configuration->isReactivateDeletedFrontendUsers();
            $reactivateDisabled = $this->configuration->isReactivateDisabledFrontendUsers();
        } elseif ($this->tableName === 'be_users') {
            $reactivateDeleted = $this->configuration->isReactivateDeletedBackendUsers();
            $reactivateDisabled = $this->configuration->isReactivateDisabledBackendUsers();
        } else {
            $this->logger->notice('Undefined environment');
        }

        if ($reactivateDeleted === false) {
            $userRepository->addDeletedRestriction();
        }

        if ($reactivateDisabled === false) {
            $userRepository->addDisabledRestriction();
        }
    }

    protected function mapUserData(array &$updates, array $mappingConfiguration): void
    {
        $parseFuncUtility = GeneralUtility::makeInstance(ParseFuncUtility::class);

        foreach ($mappingConfiguration as $configurationType => $properties) {
            foreach ($properties as $property) {
                $value = $parseFuncUtility->updateWithoutParseFunc($configurationType, $property['auth0Property'], $this->user);

                if (($property['processing'] ?? 'null') !== 'null') {
                    $value = $parseFuncUtility->transformValue($property['processing'], $value);
                }

                if ($value !== ParseFuncUtility::NO_AUTH0_VALUE) {
                    $updates[$property['databaseField']] = $value;
                }
            }
        }
    }
}
