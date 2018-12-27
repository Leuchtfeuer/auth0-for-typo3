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
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Saltedpasswords\Salt\SaltFactory;

class UserUtility implements SingletonInterface
{
    /**
     * @var Logger
     */
    protected $logger;

    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    public function checkIfUserExists(string $tableName, string $auth0UserId): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);

        try {
            // Find also disabled users. The database update is handled by UpdateUtility.
            if (ConfigurationUtility::getSetting('reactivateUsers', $tableName, 'disabled') == 1) {
                $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
            }

            // Find also deleted users. The database update is handled by UpdateUtility.
            if (ConfigurationUtility::getSetting('reactivateUsers', $tableName, 'deleted') == 1) {
                $queryBuilder->getRestrictions()->removeByType(DeletedRestriction::class);
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getCode() . ': ' . $exception->getMessage());
        }

        $user = $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq('auth0_user_id', $queryBuilder->createNamedParameter($auth0UserId))
            )->execute()
            ->fetch();

        return $user ? $user : [];
    }

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

    protected function getPassword(): string
    {
        $saltFactory = SaltFactory::getSaltingInstance(null);
        $password = GeneralUtility::makeInstance(Random::class)->generateRandomHexString(50);

        return $saltFactory->getHashedPassword($password);
    }
}
