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

namespace Bitmotion\Auth0\Utility;

use Bitmotion\Auth0\Api\Auth0;
use Bitmotion\Auth0\Api\Management\UserApi;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\User;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Bitmotion\Auth0\Domain\Repository\UserRepository;
use Bitmotion\Auth0\Domain\Transfer\EmAuth0Configuration;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Utility\Database\UpdateUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UserUtility implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected $extensionConfiguration;

    public function __construct()
    {
        $this->extensionConfiguration = GeneralUtility::makeInstance(EmAuth0Configuration::class);
    }

    public function checkIfUserExists(string $tableName, string $auth0UserId): array
    {
        $userRepository = GeneralUtility::makeInstance(UserRepository::class, $tableName);
        $user = $userRepository->getUserByAuth0Id($auth0UserId);

        return $user ?? $this->findUserWithoutRestrictions($tableName, $auth0UserId);
    }

    protected function findUserWithoutRestrictions(string $tableName, string $auth0UserId): array
    {
        $this->logger->notice('Try to find user without restrictions.');
        $userRepository = GeneralUtility::makeInstance(UserRepository::class, $tableName);
        $userRepository->removeRestrictions();
        $userRepository->setOrdering('uid', 'DESC');
        $userRepository->setMaxResults(1);
        $user = $userRepository->getUserByAuth0Id($auth0UserId);

        if (!empty($user)) {
            $userRepository = GeneralUtility::makeInstance(UserRepository::class, $tableName);
            $userRepository->updateUserByUid(['disable' => 0, 'deleted' => 0], (int)$user['uid']);

            $this->logger->notice(sprintf('Reactivated user with ID %s.', $user['uid']));
        }

        return $user ?? [];
    }

    /**
     * @throws InvalidPasswordHashException
     */
    public function insertUser(string $tableName, $user): void
    {
        $user = $user instanceof User ? $this->transformAuth0User($user) : $user;

        switch ($tableName) {
            case 'fe_users':
                $this->insertFeUser($tableName, $user);
                break;
            case 'be_users':
                $this->insertBeUser($tableName, $user);
                break;
            default:
                $this->logger->error(sprintf('"%s" is not a valid table name.', $tableName));
        }
    }

    protected function transformAuth0User(User $user): array
    {
        return [
            'email' => $user->getEmail(),
            'user_metadata' => $user->getUserMetadata(),
            $this->extensionConfiguration->getUserIdentifier() => $user->getUserId(),
        ];
    }
    /**
     * Inserts a new frontend user
     *
     * @throws InvalidPasswordHashException
     */
    public function insertFeUser(string $tableName, array $user): void
    {
        $values = $this->getTcaDefaults($tableName);
        $userIdentifier = $this->extensionConfiguration->getUserIdentifier();

        ArrayUtility::mergeRecursiveWithOverrule($values, [
            'pid' => $this->extensionConfiguration->getUserStoragePage(),
            'tstamp' => time(),
            'username' => $user['email'] ?? $user[$userIdentifier],
            'password' => $this->getPassword(),
            'email' => $user['email'] ?? '',
            'crdate' => time(),
            'auth0_user_id' => $user[$userIdentifier],
            'auth0_metadata' => \GuzzleHttp\json_encode($user['user_metadata'] ?? ''),
        ]);

        GeneralUtility::makeInstance(UserRepository::class, $tableName)->insertUser($values);
    }

    /**
     * Inserts a new backend user
     *
     * @throws InvalidPasswordHashException
     */
    public function insertBeUser(string $tableName, array $user): void
    {
        $values = $this->getTcaDefaults($tableName);
        $userIdentifier = $this->extensionConfiguration->getUserIdentifier();

        ArrayUtility::mergeRecursiveWithOverrule($values, [
            'pid' => 0,
            'tstamp' => time(),
            'username' => $user['email'] ?? $user[$userIdentifier],
            'password' => $this->getPassword(),
            'email' => $user['email'] ?? '',
            'crdate' => time(),
            'auth0_user_id' => $user[$userIdentifier],
        ]);

        GeneralUtility::makeInstance(UserRepository::class, $tableName)->insertUser($values);
    }

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
    protected function getPassword(): string
    {
        $saltFactory = GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance(TYPO3_MODE);
        $password = GeneralUtility::makeInstance(Random::class)->generateRandomHexString(50);

        return $saltFactory->getHashedPassword($password);
    }

    /**
     * @deprecated Will be removed in next major release.
     */
    public function convertAuth0UserToUserInfo(User $auth0User): array
    {
        return [
            'sub' => $auth0User->getUserId(),
            'given_name' => $auth0User->getGivenName(),
            'family_name' => $auth0User->getFamilyName(),
            'nickname' => $auth0User->getNickname(),
            'name' => $auth0User->getName(),
            'picture' => $auth0User->getPicture(),
            'updated_at' => $auth0User->getUpdatedAt(),
        ];
    }

    /**
     * @deprecated Use $auth0->login() instead.
     */
    public function loginUser(Auth0 $auth0): void
    {
        try {
            $userInfo = $auth0->getUser();

            if (!$userInfo) {
                // Try to login user to Auth0
                $this->logger->notice('Try to login user to Auth0.');
                $auth0->login();
            }
        } catch (\Exception $exception) {
            if (isset($auth0) && $auth0 instanceof Auth0) {
                $auth0->deleteAllPersistentData();
            }
        }
    }

    /**
     * @deprecated Use $auth0->logout() instead.
     */
    public function logoutUser(Auth0 $auth0): void
    {
        $this->logger->notice('Log out user');
        $auth0->logout();
    }

    public function updateUser(Auth0 $auth0, int $application): void
    {
        try {
            $this->logger->notice('Try to update user.');
            $user = $auth0->getUser();
            $application = BackendUtility::getRecord(ApplicationRepository::TABLE_NAME, $application, 'api, uid');

            if ((bool)$application['api'] === true) {
                $apiUtility = GeneralUtility::makeInstance(ApiUtility::class, $application['uid']);
                $userApi = $apiUtility->getApi(UserApi::class, Scope::USER_READ);
                $user = $userApi->get($user[$this->extensionConfiguration->getUserIdentifier()]);
            }

            // Update existing user on every login
            $updateUtility = GeneralUtility::makeInstance(UpdateUtility::class, 'fe_users', $user);
            $updateUtility->updateUser();
            $updateUtility->updateGroups();
        } catch (\Exception $exception) {
            $this->logger->warning(
                sprintf(
                    'Updating user failed with following message: %s (%s)',
                    $exception->getMessage(),
                    $exception->getCode()
                )
            );
        }
    }

    public function setLastUsedApplication(string $auth0UserId, int $application): void
    {
        $userRepository = GeneralUtility::makeInstance(UserRepository::class, 'fe_users');
        $userRepository->updateUserByAuth0Id(['auth0_last_application' => $application], $auth0UserId);
    }
}
