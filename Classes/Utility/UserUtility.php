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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

class UserUtility implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function checkIfUserExists(string $tableName, string $auth0UserId): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $user = $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where($queryBuilder->expr()->eq('auth0_user_id', $queryBuilder->createNamedParameter($auth0UserId)))
            ->execute()
            ->fetch();

        return (empty($user)) ? $this->findUserWithoutRestrictions($tableName, $auth0UserId) : $user;
    }

    protected function findUserWithoutRestrictions(string $tableName, string $auth0UserId)
    {
        $this->logger->notice('Try to find user without restrictions.');
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);

        try {
            $this->removeRestrictions($queryBuilder);
        } catch (\Exception $exception) {
            $this->logger->warning($exception->getCode() . ': ' . $exception->getMessage());
        }

        $user = $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq('auth0_user_id', $queryBuilder->createNamedParameter($auth0UserId))
            )->orderBy('uid', 'DESC')
            ->setMaxResults(1)
            ->execute()
            ->fetch();

        if (!empty($user)) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
            $queryBuilder
                ->update($tableName)
                ->set('deleted', 0)
                ->set('disable', 0)
                ->where($queryBuilder->expr()->eq('uid', $user['uid']))
                ->execute();
            $this->logger->notice(sprintf('Reactivated user with ID %s.', $user['uid']));
        }

        return $user;
    }

    protected function removeRestrictions(QueryBuilder &$queryBuilder)
    {
        $environmentService = GeneralUtility::makeInstance(EnvironmentService::class);
        $emConfiguration = GeneralUtility::makeInstance(EmAuth0Configuration::class);

        if ($environmentService->isEnvironmentInFrontendMode()) {
            if ($emConfiguration->getReactivateDeletedFrontendUsers()) {
                $queryBuilder->getRestrictions()->removeByType(DeletedRestriction::class);
            }
            if ($emConfiguration->getReactivateDisabledFrontendUsers()) {
                $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
            }
        } elseif ($environmentService->isEnvironmentInBackendMode()) {
            if ($emConfiguration->getReactivateDeletedBackendUsers()) {
                $queryBuilder->getRestrictions()->removeByType(DeletedRestriction::class);
            }
            if ($emConfiguration->getReactivateDisabledBackendUsers()) {
                $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
            }
        } else {
            $this->logger->notice('Undefined environment');
        }
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $queryBuilder
            ->insert($tableName)
            ->values(
                [
                    'tx_extbase_type' => 'Tx_Auth0_FrontendUser',
                    'pid' => $emConfiguration->getUserStoragePage(),
                    'tstamp' => time(),
                    'username' => $auth0User['email'],
                    'password' => $this->getPassword(),
                    'email' => $auth0User['email'],
                    'crdate' => time(),
                    'auth0_user_id' => $auth0User['user_id'],
                    'auth0_metadata' => \GuzzleHttp\json_encode($auth0User['user_metadata']),
                ]
            )->execute();
    }

    /**
     * Inserts a new backend user
     *
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     */
    public function insertBeUser(string $tableName, array $auth0User)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $queryBuilder
            ->insert($tableName)
            ->values(
                [
                    'pid' => 0,
                    'tstamp' => time(),
                    'username' => $auth0User['email'],
                    'password' => $this->getPassword(),
                    'email' => $auth0User['email'],
                    'crdate' => time(),
                    'auth0_user_id' => $auth0User['user_id'],
                ]
            )->execute();
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
