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
use Bitmotion\Auth0\Domain\Model\Dto\EmAuth0Configuration;
use Bitmotion\Auth0\Domain\Repository\UserRepository;
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
    public function insertUser(string $tableName, array $auth0User)
    {
        switch ($tableName) {
            case 'fe_users':
                $this->insertFeUser($tableName, $auth0User);
                break;
            case 'be_users':
                $this->insertBeUser($tableName, $auth0User);
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
    public function insertFeUser(string $tableName, array $auth0User)
    {
        $emConfiguration = new EmAuth0Configuration();
        $userRepository = GeneralUtility::makeInstance(UserRepository::class, $tableName);
        $userRepository->insertUser([
            'tx_extbase_type' => 'Tx_Auth0_FrontendUser',
            'pid' => $emConfiguration->getUserStoragePage(),
            'tstamp' => time(),
            'username' => $auth0User['email'],
            'password' => $this->getPassword(),
            'email' => $auth0User['email'],
            'crdate' => time(),
            'auth0_user_id' => $auth0User['user_id'],
            'auth0_metadata' => \GuzzleHttp\json_encode($auth0User['user_metadata']),
        ]);
    }

    /**
     * Inserts a new backend user
     *
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     */
    public function insertBeUser(string $tableName, array $auth0User)
    {
        $userRepository = GeneralUtility::makeInstance(UserRepository::class, $tableName);
        $userRepository->insertUser([
            'pid' => 0,
            'tstamp' => time(),
            'username' => $auth0User['email'],
            'password' => $this->getPassword(),
            'email' => $auth0User['email'],
            'crdate' => time(),
            'auth0_user_id' => $auth0User['user_id'],
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
}
