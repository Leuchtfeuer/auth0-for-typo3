<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Utility;

/***
 *
 * This file is part of the "Auth0 for TYPO3" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Florian Wessels <f.wessels@bitmotion.de>, Bitmotion GmbH
 *
 ***/

use Auth0\SDK\Store\SessionStore;
use Bitmotion\Auth0\Api\AuthenticationApi;
use Bitmotion\Auth0\Domain\Model\Auth0\User;
use Bitmotion\Auth0\Domain\Model\Dto\EmAuth0Configuration;
use Bitmotion\Auth0\Domain\Repository\UserRepository;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Utility\Database\UpdateUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UserUtility implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function checkIfUserExists(string $tableName, string $auth0UserId): array
    {
        $userRepository = GeneralUtility::makeInstance(UserRepository::class, $tableName);
        $user = $userRepository->getUserByAuth0Id($auth0UserId);

        return (empty($user)) ? $this->findUserWithoutRestrictions($tableName, $auth0UserId) : $user;
    }

    protected function findUserWithoutRestrictions(string $tableName, string $auth0UserId)
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

        return $user;
    }

    /**
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     */
    public function insertUser(string $tableName, User $user)
    {
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

    /**
     * Inserts a new frontend user
     *
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     */
    public function insertFeUser(string $tableName, User $user)
    {
        $emConfiguration = new EmAuth0Configuration();
        $userRepository = GeneralUtility::makeInstance(UserRepository::class, $tableName);
        $userRepository->insertUser([
            'tx_extbase_type' => 'Tx_Auth0_FrontendUser',
            'pid' => $emConfiguration->getUserStoragePage(),
            'tstamp' => time(),
            'username' => $user->getEmail(),
            'password' => $this->getPassword(),
            'email' => $user->getEmail(),
            'crdate' => time(),
            'auth0_user_id' => $user->getUserId(),
            'auth0_metadata' => \GuzzleHttp\json_encode($user->getUserMetadata()),
        ]);
    }

    /**
     * Inserts a new backend user
     *
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     */
    public function insertBeUser(string $tableName, User $user)
    {
        $userRepository = GeneralUtility::makeInstance(UserRepository::class, $tableName);
        $userRepository->insertUser([
            'pid' => 0,
            'tstamp' => time(),
            'username' => $user->getEmail(),
            'password' => $this->getPassword(),
            'email' => $user->getEmail(),
            'crdate' => time(),
            'auth0_user_id' => $user->getUserId(),
        ]);
    }

    /**
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     */
    protected function getPassword(): string
    {
        $saltFactory = GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance(TYPO3_MODE);
        $password = GeneralUtility::makeInstance(Random::class)->generateRandomHexString(50);

        return $saltFactory->getHashedPassword($password);
    }

    public function convertAuth0UserToUserInfo(array $auth0User): array
    {
        return [
            'sub' => $auth0User['user_id'],
            'given_name' => $auth0User['given_name'],
            'family_name' => $auth0User['family_name'],
            'nickname' => $auth0User['nickname'],
            'name' => $auth0User['name'],
            'picture' => $auth0User['picture'],
            'locale' => $auth0User['locale'],
            'updated_at' => $auth0User['updated_at'],
        ];
    }

    public function loginUser(AuthenticationApi $authenticationApi)
    {
        try {
            $userInfo = $authenticationApi->getUser();

            if (!$userInfo) {
                // Try to login user to Auth0
                $this->logger->notice('Try to login user to Auth0.');
                $authenticationApi->login();
            }
        } catch (\Exception $exception) {
            if (isset($authenticationApi) && $authenticationApi instanceof AuthenticationApi) {
                $authenticationApi->deleteAllPersistentData();
            }
        }
    }

    public function logoutUser(AuthenticationApi $authenticationApi)
    {
        try {
            $this->logger->notice('Log out user');
            $authenticationApi->logout();
        } catch (\Exception $exception) {
            // Delete user from SessionStore
            $store = new SessionStore();
            if ($store->get('user')) {
                $store->delete('user');
            }
        }
    }

    public function updateUser(AuthenticationApi $authenticationApi, int $applicationUid)
    {
        try {
            $this->logger->notice('Try to update user.');

            $tokenInfo = $authenticationApi->getUser();
            $apiUtility = GeneralUtility::makeInstance(ApiUtility::class);
            $apiUtility->setApplication($applicationUid);
            $userApi = $apiUtility->getUserApi(Scope::USER_READ);
            $user = $userApi->get($tokenInfo['sub']);

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

    public function updateLastApplication(string $auth0UserId, int $application)
    {
        $userRepository = GeneralUtility::makeInstance(UserRepository::class, 'fe_users');
        $userRepository->updateUserByAuth0Id(['auth0_last_application' => $application], $auth0UserId);
    }
}
