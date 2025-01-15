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

namespace Leuchtfeuer\Auth0\Domain\Repository;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Http\ApplicationType;

class UserRepository implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected readonly QueryBuilder $queryBuilder;

    /**
     * @var ExpressionBuilder
     */
    protected readonly ExpressionBuilder $expressionBuilder;

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly string $tableName
    ) {
        $this->queryBuilder = $this->connectionPool->getQueryBuilderForTable($this->tableName);
        $this->expressionBuilder = $this->queryBuilder->expr();
    }

    /**
     * Gets a user by given auth0 user ID.
     *
     * @return array<string, mixed>|null
     * @throws Exception
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
        $this->logger?->debug(
            sprintf(
                '[%s] Executed SELECT query: %s',
                $this->tableName,
                $this->queryBuilder->getSQL()
            )
        );
        $user = $this->queryBuilder
            ->executeQuery()
            ->fetchAssociative();

        return ($user !== false) ? $user : null;
    }

    /**
     * Removes DeletedRestriction and / or HiddenRestriction from QueryBuilder.
     * Depends on extension configuration.
     */
    public function removeRestrictions(): void
    {
        $configuration = new EmAuth0Configuration();

        $this->removeBackendRestrictions($configuration);
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
        $this->logger?->debug('Removed HiddenRestriction.');
    }

    protected function removeDeletedRestriction(): void
    {
        $this->queryBuilder->getRestrictions()->removeByType(DeletedRestriction::class);
        $this->logger?->debug('Removed DeletedRestriction.');
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
                $this->queryBuilder->createNamedParameter(0, ParameterType::INTEGER)
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
                $this->queryBuilder->createNamedParameter(0, ParameterType::INTEGER)
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
     *
     * @param array<string, int|string> $sets
     */
    public function updateUserByUid(array $sets, int $uid): void
    {
        $this->resolveSets($sets);
        $this->queryBuilder->where(
            $this->expressionBuilder->eq(
                'uid',
                $this->queryBuilder->createNamedParameter($uid, ParameterType::INTEGER)
            )
        );
        $this->updateUser();
    }

    /**
     * Updates a backend or frontend user by given auth0_user_id.
     *
     * @param array<string, int|string> $sets
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
     *
     * @param array<string, string|int> $sets
     */
    protected function resolveSets(array $sets): void
    {
        foreach ($sets as $key => $value) {
            $this->logger?->debug(
                sprintf(
                    'Set property "%s" to: "%s"',
                    $key,
                    $value
                )
            );
            $this->queryBuilder->set($key, $value);
        }
    }

    /**
     * Executes the update query.
     */
    protected function updateUser(): void
    {
        $this->queryBuilder->update($this->tableName);
        $this->logger?->debug(
            sprintf(
                '[%s] Executed UPDATE query: %s',
                $this->tableName,
                $this->queryBuilder->getSQL()
            )
        );
        $this->queryBuilder->executeStatement();
    }

    /**
     * Inserts a backend or frontend user by given value array.
     *
     * @param array<string, mixed> $values
     */
    public function insertUser(array $values): void
    {
        $this->queryBuilder->insert($this->tableName)->values($values);
        $this->logger?->debug(
            sprintf(
                '[%s] Executed INSERT query: %s',
                $this->tableName,
                $this->queryBuilder->getSQL()
            )
        );
        $this->queryBuilder->executeStatement();
    }
}
