<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Domain\Repository;

use Bitmotion\Auth0\Exception\InvalidApplicationException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

class ApplicationRepository
{
    /**
     * @throws InvalidApplicationException
     */
    public function findByUid(int $uid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_auth0_domain_model_application');

        $application = $queryBuilder
            ->select('*')
            ->from('tx_auth0_domain_model_application')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
            ->execute()
            ->fetch();

        if (!empty($application)) {
            return $application;
        }

        throw new InvalidApplicationException(sprintf('No Application found for given id %s', $uid), 1526046354);
    }
}
