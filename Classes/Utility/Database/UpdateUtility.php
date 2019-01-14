<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Utility\Database;

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
use Bitmotion\Auth0\Utility\ConfigurationUtility;
use Bitmotion\Auth0\Utility\ParseFuncUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UpdateUtility implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const TYPO_SCRIPT_NODE_VALUE = '_typoScriptNodeValue';

    /**
     * @var string
     */
    protected $tableName = '';

    /**
     * @var array
     */
    protected $user = [];

    /**
     * @var ParseFuncUtility
     */
    protected $parseFuncUtility;

    public function __construct(string $tableName, array $user)
    {
        $this->tableName = $tableName;
        $this->user = $user;
    }

    public function updateGroups()
    {
        try {
            $groupMapping = ConfigurationUtility::getSetting('roles', $this->tableName);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getCode() . ': ' . $exception->getMessage());

            return;
        }

        if (empty($groupMapping)) {
            $this->logger->error(sprintf('Cannot update user groups: No role mapping for %s found', $this->tableName));

            return;
        }

        $shouldUpdate = false;
        $isBeAdmin = false;
        $groupsToAssign = [];

        // Map Auth0 roles on TYPO3 user groups
        $this->mapRoles($groupMapping, $groupsToAssign, $isBeAdmin, $shouldUpdate);

        // Update user only if necessary
        if ($shouldUpdate === true) {
            $this->logger->notice('Update user groups.');
            $this->performGroupUpdate($groupsToAssign, $isBeAdmin);
        }
    }

    public function updateUser()
    {
        try {
            // Get mapping configuration
            $mappingConfiguration = ConfigurationUtility::getSetting('propertyMapping', $this->tableName);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getCode() . ': ' . $exception->getMessage());

            return;
        }

        if (empty($mappingConfiguration)) {
            $this->logger->error(sprintf('Cannot update user: No mapping configuration for %s found', $this->tableName));

            return;
        }

        $this->performUserUpdate($mappingConfiguration);
    }

    protected function mapRoles(array $groupMapping, array &$groupsToAssign, bool &$isBeAdmin, bool &$shouldUpdate)
    {
        $userRoles = $this->user['app_metadata']['roles'];

        if (empty($userRoles)) {
            $this->logger->notice('No Auth0 roles defined.');

            return;
        }

        foreach ($userRoles as $role) {
            if (isset($groupMapping[$role])) {
                if ($this->tableName === 'be_users' && $groupMapping[$role] === 'admin') {
                    $isBeAdmin = true;
                } else {
                    $this->logger->notice(sprintf('Assign group "%s" to user.', $groupMapping[$role]));
                    $groupsToAssign[] = $groupMapping[$role];
                }
                $shouldUpdate = true;
            } else {
                $this->logger->warning(sprintf('No mapping for Auth0 role "%s" found.', $role));
            }
        }
    }

    protected function performGroupUpdate(array $groupsToAssign, bool $isBeAdmin)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $queryBuilder->update($this->tableName);

        // Update usergroup in database
        if (!empty($groupsToAssign)) {
            $queryBuilder->set('usergroup', implode(',', $groupsToAssign));
        }

        // Set admin flag for backend users
        if ($this->tableName === 'be_users') {
            $queryBuilder->set('admin', (int)$isBeAdmin);
        }

        $queryBuilder
            ->andWhere(
                $queryBuilder->expr()->eq(
                    'auth0_user_id',
                    $queryBuilder->createNamedParameter($this->user['user_id'])
                )
            )->execute();
    }

    protected function performUserUpdate(array $mappingConfiguration)
    {
        $this->logger->debug(sprintf('%s: Prepare update for Auth0 user "%s"', $this->tableName, $this->user['user_id']));
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $queryBuilder->update($this->tableName);

        $this->mapUserData($queryBuilder, $mappingConfiguration);

        // Fixed values
        $queryBuilder->set('disable', 0);
        $queryBuilder->set('deleted', 0);

        $queryBuilder->where(
            $queryBuilder->expr()->eq(
                'auth0_user_id',
                $queryBuilder->createNamedParameter($this->user['user_id'])
            )
        );

        $this->checkForRestrictions($queryBuilder);
        $this->logger->debug(sprintf('%s: Executing query: %s', $this->tableName, $queryBuilder->getSQL()));
        $queryBuilder->execute();
    }

    protected function checkForRestrictions(QueryBuilder &$queryBuilder)
    {
        $emConfiguration = GeneralUtility::makeInstance(EmAuth0Configuration::class);
        $reactivateDeleted = false;
        $reactivateDisabled = false;

        if ($this->tableName === 'fe_users') {
            $reactivateDeleted = $emConfiguration->getReactivateDeletedFrontendUsers();
            $reactivateDisabled = $emConfiguration->getReactivateDisabledFrontendUsers();
        } elseif ($this->tableName === 'be_users') {
            $reactivateDeleted = $emConfiguration->getReactivateDeletedBackendUsers();
            $reactivateDisabled = $emConfiguration->getReactivateDisabledBackendUsers();
        } else {
            $this->logger->notice('Undefined environment');
        }

        $this->addRestrictions($queryBuilder, $reactivateDisabled, $reactivateDeleted);
    }

    protected function addRestrictions(QueryBuilder &$queryBuilder, bool $reactivateDisabled, bool $reactivateDeleted)
    {
        $expressionBuilder = $queryBuilder->expr();

        if ($reactivateDeleted === false) {
            $queryBuilder->andWhere(
                $expressionBuilder->eq(
                    'deleted',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            );
        }

        if ($reactivateDisabled === false) {
            $queryBuilder->andWhere(
                $expressionBuilder->eq(
                    'disable',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            );
        }
    }

    protected function mapUserData(QueryBuilder &$queryBuilder, array $mappingConfiguration)
    {
        $this->parseFuncUtility = $parseFuncUtility = GeneralUtility::makeInstance(ParseFuncUtility::class);

        foreach ($mappingConfiguration as $typo3FieldName => $auth0FieldName) {
            if (!is_array($auth0FieldName)) {
                // Update without parsing function
                $this->parseFuncUtility->updateWithoutParseFunc($queryBuilder, $typo3FieldName, $auth0FieldName, $this->user);
            } elseif (is_array($auth0FieldName) && isset($auth0FieldName[self::TYPO_SCRIPT_NODE_VALUE])) {
                // Update with parsing function
                $this->parseFuncUtility->updateWithParseFunc($queryBuilder, $typo3FieldName, $auth0FieldName, $this->user);
            }
        }
    }
}
