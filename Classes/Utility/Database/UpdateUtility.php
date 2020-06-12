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

namespace Bitmotion\Auth0\Utility\Database;

use Bitmotion\Auth0\Configuration\Auth0Configuration;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\User;
use Bitmotion\Auth0\Domain\Repository\UserGroup\AbstractUserGroupRepository;
use Bitmotion\Auth0\Domain\Repository\UserGroup\BackendUserGroupRepository;
use Bitmotion\Auth0\Domain\Repository\UserGroup\FrontendUserGroupRepository;
use Bitmotion\Auth0\Domain\Repository\UserRepository;
use Bitmotion\Auth0\Domain\Transfer\EmAuth0Configuration;
use Bitmotion\Auth0\Utility\ConfigurationUtility;
use Bitmotion\Auth0\Utility\ParseFuncUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;

class UpdateUtility implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const TYPO_SCRIPT_NODE_VALUE = '_typoScriptNodeValue';

    /**
     * @var string
     */
    protected $tableName = '';

    /**
     * @var User
     */
    protected $user;

    /**
     * @var ParseFuncUtility
     */
    protected $parseFuncUtility;

    /**
     * @var array
     */
    protected $yamlConfiguration = [];

    public function __construct(string $tableName, User $user)
    {
        $this->tableName = $tableName;
        $this->user = $user;
        $this->yamlConfiguration = (new Auth0Configuration())->load();
    }

    public function updateGroups(): void
    {
        $groupMapping = array_merge(
            $this->getGroupMappingFromDatabase(),
            $this->getGroupMappingFromTypoScript()
        );

        $this->addDefaultGroup($groupMapping);

        if (empty($groupMapping)) {
            $this->logger->error(sprintf('Cannot update user groups: No role mapping for %s found', $this->tableName));

            return;
        }

        $shouldUpdate = false;
        $isBeAdmin = false;
        $groupsToAssign = [];

        // Map Auth0 roles on TYPO3 user groups
        $this->mapRoles($groupMapping, $groupsToAssign, $isBeAdmin, $shouldUpdate);

        // Update user only if necessary
        if ($shouldUpdate === true) {
            $this->logger->notice('Update user groups.');
            $this->performGroupUpdate($groupsToAssign, $isBeAdmin);
        }
    }

    public function updateUser(bool $reactivateUser = false): void
    {
        $mappingConfiguration = $this->translateConfiguration($this->yamlConfiguration['properties'][$this->tableName]);

        try {
            // Get mapping configuration
            $mappingConfiguration = array_merge(
                $mappingConfiguration,
                ConfigurationUtility::getSetting('propertyMapping', $this->tableName)
            );
        } catch (InvalidConfigurationTypeException $exception) {
            $this->logger->error($exception->getCode() . ': ' . $exception->getMessage());
        }

        if (empty($mappingConfiguration)) {
            $this->logger->error(sprintf('Cannot update user: No mapping configuration for %s found', $this->tableName));

            return;
        }

        $this->performUserUpdate($mappingConfiguration, $reactivateUser);
    }

    /**
     * @deprecated Will be removed with next major release.
     */
    protected function translateConfiguration(array $configuration): array
    {
        $translations = [];
        $root = $configuration[Auth0Configuration::CONFIG_TYPE_ROOT] ?? [];

        foreach ($configuration[Auth0Configuration::CONFIG_TYPE_USER] ?? [] as $userMetadata) {
            $userMetadata['auth0Property'] = 'user_metadata.' . $userMetadata['auth0Property'];
            $root[] = $userMetadata;
        }

        foreach ($configuration[Auth0Configuration::CONFIG_TYPE_APP] ?? [] as $appMetadata) {
            $appMetadata['auth0Property'] = 'app_metadata.' . $appMetadata['auth0Property'];
            $root[] = $appMetadata;
        }

        foreach ($root as $item) {
            $translations[$item['databaseField']] = $item['auth0Property'];

            if (isset($item['processing']) && !empty($item['processing'])) {
                $translations[$item['databaseField']] = [
                    self::TYPO_SCRIPT_NODE_VALUE => $item['auth0Property'],
                    'parseFunc' => str_replace('-', '|', $item['processing'])
                ];
            }
        }

        return $translations;
    }

    protected function getGroupMappingFromDatabase(): array
    {
        $groupMapping = [];
        $userGroupRepository = $this->getRepository();

        if ($userGroupRepository instanceof AbstractUserGroupRepository) {
            foreach ($userGroupRepository->findAll() as $userGroup) {
                $groupMapping[$userGroup[AbstractUserGroupRepository::USER_GROUP_FIELD]] = $userGroup['uid'];
            }
        }

        return $groupMapping;
    }

    protected function getRepository(): ?AbstractUserGroupRepository
    {
        switch ($this->tableName) {
            case 'fe_users':
                return new FrontendUserGroupRepository();

            case 'be_users':
                return new BackendUserGroupRepository();
        }

        return null;
    }

    protected function getGroupMappingFromTypoScript(): array
    {
        try {
            return ConfigurationUtility::getSetting('roles', $this->tableName);
        } catch (InvalidConfigurationTypeException $exception) {
            $this->logger->error($exception->getCode() . ': ' . $exception->getMessage());
        }

        return [];
    }

    protected function addDefaultGroup(array &$groupMapping): void
    {
        if ($this->tableName === 'fe_users') {
            $defaultGroup = $this->yamlConfiguration['roles']['default']['frontend'];
        } else {
            $defaultGroup = $this->yamlConfiguration['roles']['default']['backend'];
        }

        if ($defaultGroup !== 0) {
            $groupMapping['__default'] = $defaultGroup;
        }
    }

    protected function mapRoles(array $groupMapping, array &$groupsToAssign, bool &$isBeAdmin, bool &$shouldUpdate): void
    {
        try {
            // TODO: Support dot syntax for roles; e.g. roles.application
            $rolesKey = ConfigurationUtility::getSetting('roles', 'key') ?? $this->yamlConfiguration['roles']['key'] ?? 'roles';
        } catch (InvalidConfigurationTypeException $exception) {
            $rolesKey = 'roles';
        }

        foreach ($this->user->getAppMetadata()[$rolesKey] ?? [] as $role) {
            if (isset($groupMapping[$role])) {
                // TODO: Remove first and condition ($groupMapping[$role] === 'admin') with next major release)
                if ($this->tableName === 'be_users' && $groupMapping[$role] === 'admin') {
                    $isBeAdmin = true;
                } else {
                    $this->logger->notice(sprintf('Assign group "%s" to user.', $groupMapping[$role]));
                    $groupsToAssign[] = $groupMapping[$role];
                }
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
            $groupsToAssign[] = $groupMapping['__default'];
            $shouldUpdate = true;
        }
    }

    protected function performGroupUpdate(array $groupsToAssign, bool $isBeAdmin): void
    {
        $updates = [];

        // Update usergroup in database
        if (!empty($groupsToAssign)) {
            $updates['usergroup'] = implode(',', $groupsToAssign);
        }

        // Set admin flag for backend users
        if ($this->tableName === 'be_users') {
            $updates['admin'] = (int)$isBeAdmin;
        }

        if (!empty($updates)) {
            $userRepository = GeneralUtility::makeInstance(UserRepository::class, $this->tableName);
            $userRepository->updateUserByAuth0Id($updates, $this->user->getUserId());
        }
    }

    protected function performUserUpdate(array $mappingConfiguration, bool $reactivateUser): void
    {
        $this->logger->debug(sprintf('%s: Prepare update for Auth0 user "%s"', $this->tableName, $this->user->getUserId()));

        $updates = [];
        $userRepository = GeneralUtility::makeInstance(UserRepository::class, $this->tableName);

        $this->mapUserData($updates, $mappingConfiguration);

        // Fixed values
        if ($reactivateUser) {
            $updates['disable'] = 0;
            $updates['deleted'] = 0;
        }

        $this->addRestrictions($userRepository);
        $userRepository->updateUserByAuth0Id($updates, $this->user->getUserId());
    }

    protected function addRestrictions(UserRepository &$userRepository): void
    {
        $emConfiguration = GeneralUtility::makeInstance(EmAuth0Configuration::class);
        $reactivateDeleted = false;
        $reactivateDisabled = false;

        if ($this->tableName === 'fe_users') {
            $reactivateDeleted = $emConfiguration->isReactivateDeletedFrontendUsers();
            $reactivateDisabled = $emConfiguration->isReactivateDisabledFrontendUsers();
        } elseif ($this->tableName === 'be_users') {
            $reactivateDeleted = $emConfiguration->isReactivateDeletedBackendUsers();
            $reactivateDisabled = $emConfiguration->isReactivateDisabledBackendUsers();
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
        $this->parseFuncUtility = $parseFuncUtility = GeneralUtility::makeInstance(ParseFuncUtility::class);
        $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());
        $serializer = new Serializer([$normalizer]);
        $user = $serializer->normalize($this->user, 'array');
        $value = false;

        foreach ($mappingConfiguration as $typo3FieldName => $auth0FieldName) {
            if (!is_array($auth0FieldName)) {
                // Update without parsing function
                $value = $this->parseFuncUtility->updateWithoutParseFunc($auth0FieldName, $user);
            } elseif (is_array($auth0FieldName) && isset($auth0FieldName[self::TYPO_SCRIPT_NODE_VALUE])) {
                // Update with parsing function
                $value = $this->parseFuncUtility->updateWithParseFunc($typo3FieldName, $auth0FieldName, $user);
            }

            if ($value !== false) {
                $updates[$typo3FieldName] = $value;
            }
        }
    }
}
