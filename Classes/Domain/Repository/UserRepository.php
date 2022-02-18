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

use Bitmotion\Auth0\Domain\Transfer\EmAuth0Configuration;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UserRepository implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var ExpressionBuilder
     */
    protected $expressionBuilder;

    /**
     * @var string
     */
    protected string $tableName;

    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
        $this->queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $this->expressionBuilder = $this->queryBuilder->expr();
    }

    /**
     * Gets a user by given auth0 user ID.
     */
    public function getUserByAuth0Id(string $auth0UserId): ?array
    {
        $this->queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $this->expressionBuilder->eq(
                    'auth0_user_id',
                    $this->queryBuilder->createNamedParameter($auth0UserId)
                )
            );
        $this->logger->debug(sprintf('[%s] Executed SELECT query: %s', $this->tableName, $this->queryBuilder->getSQL()));
        $user = $this->queryBuilder->execute()->fetchAssociative();

        return ($user !== false) ? $user : null;
    }

    /**
     * Removes DeletedRestriction and / or HiddenRestriction from QueryBuilder.
     * Depends on extension configuration.
     */
    public function removeRestrictions(): void
    {
        $emConfiguration = GeneralUtility::makeInstance(EmAuth0Configuration::class);

        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()) {
            $this->removeFrontendRestrictions($emConfiguration);
        } else {
            $this->removeBackendRestrictions($emConfiguration);
        }
    }

    protected function removeFrontendRestrictions(EmAuth0Configuration $emConfiguration): void
    {
        if ($emConfiguration->isReactivateDeletedFrontendUsers()) {
            $this->removeDeletedRestriction();
        }
        if ($emConfiguration->isReactivateDisabledFrontendUsers()) {
            $this->removeHiddenRestriction();
        }
    }

    protected function removeBackendRestrictions(EmAuth0Configuration $emConfiguration): void
    {
        if ($emConfiguration->isReactivateDeletedBackendUsers()) {
            $this->removeDeletedRestriction();
        }
        if ($emConfiguration->isReactivateDisabledBackendUsers()) {
            $this->removeHiddenRestriction();
        }
    }

    protected function removeHiddenRestriction(): void
    {
        $this->queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $this->logger->debug('Removed HiddenRestriction.');
    }

    protected function removeDeletedRestriction(): void
    {
        $this->queryBuilder->getRestrictions()->removeByType(DeletedRestriction::class);
        $this->logger->debug('Removed DeletedRestriction.');
    }

    /**
     * Get not deleted users.
     * The restriction is not available in some cases.
     */
    public function addDeletedRestriction(): void
    {
        $this->queryBuilder->andWhere(
            $this->expressionBuilder->eq(
                'deleted',
                $this->queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
            )
        );
    }

    /**
     * Get only active users.
     * The restriction is not available in some cases.
     */
    public function addDisabledRestriction(): void
    {
        $this->queryBuilder->andWhere(
            $this->expressionBuilder->eq(
                'disable',
                $this->queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
            )
        );
    }

    /**
     * Adds ordering to a select query.
     */
    public function setOrdering(string $fieldName, string $order = 'ASC'): void
    {
        $this->queryBuilder->orderBy($fieldName, $order);
    }

    /**
     * Adds max results to a select query.
     */
    public function setMaxResults(int $maxResults): void
    {
        $this->queryBuilder->setMaxResults($maxResults);
    }

    /**
     * Updates a backend or frontend user by given uid.
     */
    public function updateUserByUid(array $sets, int $uid): void
    {
        $this->resolveSets($sets);
        $this->queryBuilder->where(
            $this->expressionBuilder->eq('uid', $this->queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
        );
        $this->updateUser();
    }

    /**
     * Updates a backend or frontend user by given auth0_user_id.
     */
    public function updateUserByAuth0Id(array $sets, string $auth0Id): void
    {
        $this->resolveSets($sets);
        $this->queryBuilder->where(
            $this->expressionBuilder->eq('auth0_user_id', $this->queryBuilder->createNamedParameter($auth0Id))
        );
        $this->updateUser();
    }

    /**
     * Resolves the set array.
     */
    protected function resolveSets(array $sets): void
    {
        foreach ($sets as $key => $value) {
            $this->logger->debug(sprintf('Set property "%s" to: "%s"', $key, $value));
            $this->queryBuilder->set($key, $value);
        }
    }

    /**
     * Executes the update query.
     */
    protected function updateUser(): void
    {
        $this->queryBuilder->update($this->tableName);
        $this->logger->debug(sprintf('[%s] Executed UPDATE query: %s', $this->tableName, $this->queryBuilder->getSQL()));
        $this->queryBuilder->execute();
    }

    /**
     * Inserts a backend or frontend user by given value array.
     */
    public function insertUser(array $values): void
    {
        $this->queryBuilder->insert($this->tableName)->values($values);
        $this->logger->debug(sprintf('[%s] Executed INSERT query: %s', $this->tableName, $this->queryBuilder->getSQL()));
        $this->queryBuilder->execute();
    }
}
