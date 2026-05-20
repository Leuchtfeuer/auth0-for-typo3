<?php

declare(strict_types=1);

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Leuchtfeuer Digital Marketing <dev@Leuchtfeuer.com>
 */

namespace Leuchtfeuer\Auth0\Utility;

use Doctrine\DBAL\Exception as DBALException;
use Leuchtfeuer\Auth0\Configuration\Auth0Configuration;
use Leuchtfeuer\Auth0\Domain\Repository\UserRepositoryFactory;
use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;

class UserUtility implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected EmAuth0Configuration $configuration;

    public function __construct(
        protected readonly PasswordHashFactory $passwordHashFactory,
        protected readonly Random $random,
        protected readonly UserRepositoryFactory $userRepositoryFactory,
        protected readonly Auth0Configuration $auth0Configuration,
        protected readonly ParseFuncUtility $parseFuncUtility,
    ) {
        $this->configuration = new EmAuth0Configuration();
    }

    /**
     * @param array<string, mixed> $userInfo
     * @return array<string, mixed>
     * @throws DBALException
     */
    public function checkIfUserExists(string $tableName, array $userInfo): array
    {
        $auth0UserId = (string)($userInfo[$this->configuration->getUserIdentifier()] ?? '');

        $userRepository = $this->userRepositoryFactory->create($tableName);
        $user = $userRepository->getUserByAuth0Id($auth0UserId);

        if ($user === null && $this->configuration->isMergeUsersByEmailAndUsername()) {
            $user = $this->findExistingUserByEmailAndUsername($tableName, $userInfo);
        }

        return $user ?? $this->findUserWithoutRestrictions($tableName, $auth0UserId);
    }

    /**
     * Migration helper: locate an existing TYPO3 user by email + username when
     * no auth0_user_id match exists. If found, update its auth0_user_id to the
     * new value so subsequent logins match via the standard path.
     *
     * @param array<string, mixed> $userInfo
     * @return array<string, mixed>|null
     */
    protected function findExistingUserByEmailAndUsername(string $tableName, array $userInfo): ?array
    {
        $email = (string)($userInfo['email'] ?? '');
        $username = $this->resolveUserInfoValue(
            $tableName,
            'username',
            $userInfo,
            ['configurationType' => Auth0Configuration::CONFIG_TYPE_ROOT, 'auth0Property' => 'nickname']
        );
        $newAuth0UserId = (string)($userInfo[$this->configuration->getUserIdentifier()] ?? '');

        if ($email === '' || $username === '' || $newAuth0UserId === '') {
            $this->logger?->notice(sprintf(
                'Skip merge by email+username: missing values (email="%s", username="%s", auth0_user_id="%s").',
                $email,
                $username,
                $newAuth0UserId
            ));
            return null;
        }

        $userRepository = $this->userRepositoryFactory->create($tableName);
        $userRepository->removeRestrictions();
        $userRepository->setOrdering('uid', 'ASC');
        $userRepository->setMaxResults(1);
        $user = $userRepository->getUserByEmailAndUsername($email, $username);

        if ($user === null) {
            return null;
        }

        $sets = ['auth0_user_id' => $newAuth0UserId];
        if ($this->configuration->isReactivateDisabledBackendUsers() && (int)($user['disable'] ?? 0) === 1) {
            $sets['disable'] = 0;
        }
        if ($this->configuration->isReactivateDeletedBackendUsers() && (int)($user['deleted'] ?? 0) === 1) {
            $sets['deleted'] = 0;
        }

        $updateRepository = $this->userRepositoryFactory->create($tableName);
        $updateRepository->updateUserByUid($sets, (int)$user['uid']);

        $this->logger?->notice(sprintf(
            'Merged existing user (uid=%d) with new auth0_user_id "%s" via email+username match.',
            (int)$user['uid'],
            $newAuth0UserId
        ));

        return array_merge($user, $sets);
    }

    /**
     * Resolves the value that UpdateUtility would have written into the given
     * TYPO3 column for this userInfo payload. Walks every YAML mapping that
     * targets the field, in declaration order, and returns the last
     * non-empty resolution — mirroring UpdateUtility::mapUserData, where each
     * subsequent mapping overwrites the previous. A first-match lookup would
     * silently disagree with the persisted value whenever two mappings point
     * at the same column (e.g. root.nickname *and* user_metadata.login_name
     * → username), reintroducing the duplicate-user bug this method exists
     * to prevent.
     *
     * The fallback mapping is only consulted when the YAML declares nothing
     * at all for the field.
     *
     * @param array<string, mixed> $userInfo
     * @param array{configurationType: string, auth0Property: string, processing?: string}|null $fallback
     */
    protected function resolveUserInfoValue(string $tableName, string $databaseField, array $userInfo, ?array $fallback = null): string
    {
        $mappings = $this->auth0Configuration->getAuth0MappingsForDatabaseField($tableName, $databaseField);
        if ($mappings === [] && $fallback !== null) {
            $mappings = [[
                'configurationType' => (string)$fallback['configurationType'],
                'auth0Property' => (string)$fallback['auth0Property'],
                'processing' => (string)($fallback['processing'] ?? ''),
            ]];
        }
        if ($mappings === []) {
            return '';
        }

        $resolvedValue = null;
        foreach ($mappings as $mapping) {
            $value = $this->parseFuncUtility->updateWithoutParseFunc(
                $mapping['configurationType'],
                $mapping['auth0Property'],
                $userInfo
            );
            if ($value === ParseFuncUtility::NO_AUTH0_VALUE) {
                continue;
            }
            $processing = $mapping['processing'];
            if ($processing !== '' && $processing !== 'null') {
                $value = $this->parseFuncUtility->transformValue($processing, $value);
            }
            $resolvedValue = $value;
        }

        return $resolvedValue === null ? '' : (string)$resolvedValue;
    }

    /**
     * @return array<string, mixed>
     * @throws DBALException
     */
    protected function findUserWithoutRestrictions(string $tableName, string $auth0UserId): array
    {
        $this->logger?->notice('Try to find user without restrictions.');
        $userRepository = $this->userRepositoryFactory->create($tableName);
        $userRepository->removeRestrictions();
        $userRepository->setOrdering('uid', 'DESC');
        $userRepository->setMaxResults(1);
        $user = $userRepository->getUserByAuth0Id($auth0UserId);

        if ($user !== null && $user !== []) {
            $userRepository = $this->userRepositoryFactory->create($tableName);
            $userRepository->updateUserByUid(['disable' => 0, 'deleted' => 0], (int)$user['uid']);

            $this->logger?->notice(sprintf('Reactivated user with ID %s.', $user['uid']));
        }

        return $user ?? [];
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed> The complete inserted user record
     * @throws InvalidPasswordHashException
     */
    public function insertUser(string $tableName, array $user): array
    {
        return match ($tableName) {
            'be_users' => $this->insertBeUser($tableName, $user),
            /** @extensionScannerIgnoreLine */
            default => (function () use ($tableName) {
                $this->logger?->error(sprintf('"%s" is not a valid table name.', $tableName));
                return [];
            })(),
        };
    }

    /**
     * @param array<string, mixed> $managementUser
     * @return array<string, mixed>
     */
    public function enrichManagementUser(array $managementUser): array
    {
        $managementUser[$this->configuration->getUserIdentifier()] = $managementUser['user_id'];
        return $managementUser;
    }

    /**
     * Inserts a new backend user
     *
     * @param array<string, mixed> $user
     * @return array<string, mixed> The complete inserted user record
     * @throws InvalidPasswordHashException
     */
    public function insertBeUser(string $tableName, array $user): array
    {
        $values = $this->getTcaDefaults($tableName);
        $userIdentifier = $this->configuration->getUserIdentifier();

        ArrayUtility::mergeRecursiveWithOverrule($values, [
            'pid' => 0,
            'tstamp' => time(),
            'username' => $user['email'] ?? $user[$userIdentifier],
            'password' => $this->getPassword('BE'),
            'email' => $user['email'] ?? '',
            'disable' => 0,
            'admin' => 0,
            'crdate' => time(),
            'auth0_user_id' => $user[$userIdentifier],
        ]);

        $values['uid'] = $this->userRepositoryFactory->create($tableName)->insertUser($values);

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getTcaDefaults(string $tableName): array
    {
        $defaults = [
            'workspace_id' => 0,
            'usergroup' => '',
            'admin' => 0,
            'disable' => 0,
            'deleted' => 0,
            'starttime' => 0,
            'endtime' => 0,
            'lang' => '',
            'db_mountpoints' => '',
            'file_mountpoints' => '',
        ];
        $columns = $GLOBALS['TCA'][$tableName]['columns'] ?? [];

        foreach ($columns as $fieldName => $field) {
            if (isset($field['config']['default'])) {
                $defaults[$fieldName] = $field['config']['default'];
            }
        }

        return $defaults;
    }

    /**
     * @throws InvalidPasswordHashException
     */
    protected function getPassword(string $mode): ?string
    {
        $saltFactory = $this->passwordHashFactory->getDefaultHashInstance($mode);
        $password = $this->random->generateRandomHexString(50);

        return $saltFactory->getHashedPassword($password);
    }
}
