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
    ) {
        $this->configuration = new EmAuth0Configuration();
    }

    /**
     * @return array<string, mixed>
     * @throws DBALException
     */
    public function checkIfUserExists(string $tableName, string $auth0UserId): array
    {
        $userRepository = $this->userRepositoryFactory->create($tableName);
        $user = $userRepository->getUserByAuth0Id($auth0UserId);

        return $user ?? $this->findUserWithoutRestrictions($tableName, $auth0UserId);
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

        if (!empty($user)) {
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
