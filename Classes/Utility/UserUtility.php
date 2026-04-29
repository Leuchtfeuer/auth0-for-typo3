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
        $usernameAuth0Property = $this->auth0Configuration->getAuth0PropertyForDatabaseField($tableName, 'username') ?? 'nickname';
        $username = (string)($userInfo[$usernameAuth0Property] ?? '');
        $newAuth0UserId = (string)($userInfo[$this->configuration->getUserIdentifier()] ?? '');

        if ($email === '' || $username === '' || $newAuth0UserId === '') {
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
     * @throws InvalidPasswordHashException
     */
    public function insertUser(string $tableName, array $user): void
    {
        match ($tableName) {
            'be_users' => $this->insertBeUser($tableName, $user),
            /** @extensionScannerIgnoreLine */
            default => $this->logger?->error(sprintf('"%s" is not a valid table name.', $tableName)),
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
     * @throws InvalidPasswordHashException
     */
    public function insertBeUser(string $tableName, array $user): void
    {
        $values = $this->getTcaDefaults($tableName);
        $userIdentifier = $this->configuration->getUserIdentifier();

        ArrayUtility::mergeRecursiveWithOverrule($values, [
            'pid' => 0,
            'tstamp' => time(),
            'username' => $user['email'] ?? $user[$userIdentifier],
            'password' => $this->getPassword('BE'),
            'email' => $user['email'] ?? '',
            'crdate' => time(),
            'auth0_user_id' => $user[$userIdentifier],
        ]);

        $this->userRepositoryFactory->create($tableName)->insertUser($values);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getTcaDefaults(string $tableName): array
    {
        $defaults = [];
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
