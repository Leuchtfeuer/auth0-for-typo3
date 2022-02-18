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

namespace Bitmotion\Auth0\Domain\Repository;

use Bitmotion\Auth0\Domain\Model\Application;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class ApplicationRepository
{
    const TABLE_NAME = 'tx_auth0_domain_model_application';

    public function findByUid(int $uid): Application
    {
        return GeneralUtility::makeInstance(PersistenceManager::class)->getObjectByIdentifier($uid, Application::class);
    }

    public function findAll(): array
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_NAME)
            ->select('*')
            ->from(self::TABLE_NAME)
            ->execute()
            ->fetchAllAssociative();
    }

    public function remove(Application $application): void
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);

        $qb->delete(self::TABLE_NAME)->where(
            $qb->expr()->eq('uid', $qb->createNamedParameter($application->getUid(), \PDO::PARAM_INT))
        )->execute();
    }
}
