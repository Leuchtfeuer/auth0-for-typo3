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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UpdateUtility implements SingletonInterface, LoggerAwareInterface
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
            $this->logger->notice(sprintf('Cannot update user groups: No role mapping for %s found', $this->tableName));

            return;
        }

        // Map Auth0 roles on TYPO3 user groups
        $this->mapRoles(
            $groupMapping,
            $groupsToAssign = [],
            $isBeAdmin = false,
            $shouldUpdate = false
        );

        // Update user only if necessary
        if ($shouldUpdate === true) {
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
            $this->logger->notice(sprintf('Cannot update user: No mapping configuration for %s found', $this->tableName));

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
                    $groupsToAssign[] = $groupMapping[$role];
                }
                $shouldUpdate = true;
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
            ->where(
                $queryBuilder->expr()->eq(
                    'auth0_user_id',
                    $queryBuilder->createNamedParameter($this->user['user_id'])
                )
            )->execute();
    }

    protected function performUserUpdate(array $mappingConfiguration)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $queryBuilder->update($this->tableName);

        $this->mapUserData($mappingConfiguration);

        // Fixed values
        $queryBuilder->set('disable', 0);
        $queryBuilder->set('deleted', 0);

        $queryBuilder->where(
            $queryBuilder->expr()->eq(
                'auth0_user_id',
                $queryBuilder->createNamedParameter($this->user['user_id'])
            )
        );

        $queryBuilder->execute();
    }

    protected function mapUserData(array $mappingConfiguration)
    {
        foreach ($mappingConfiguration as $typo3FieldName => $auth0FieldName) {
            if (!is_array($auth0FieldName)) {
                // Update without parsing function
                $this->updateWithoutParseFunc($queryBuilder, $typo3FieldName, $auth0FieldName);
            } elseif (is_array($auth0FieldName) && isset($auth0FieldName[self::TYPO_SCRIPT_NODE_VALUE])) {
                // Update with parsing function
                $this->updateWithParseFunc($queryBuilder, $typo3FieldName, $auth0FieldName);
            }
        }
    }

    protected function getAuth0ValueRecursive(array $user, array $properties): string
    {
        $value = '';
        $property = array_shift($properties);

        if (isset($user[$property])) {
            $value = $user[$property];

            if (is_array($properties) && ($value instanceof \stdClass || (is_array($value) && !empty($value)))) {
                return $this->getAuth0ValueRecursive($value, $properties);
            }
        }

        return (string)$value;
    }

    protected function updateWithoutParseFunc(QueryBuilder &$queryBuilder, string $typo3FieldName, string $auth0FieldName)
    {
        if (isset($this->user[$auth0FieldName])) {
            $queryBuilder->set(
                $typo3FieldName,
                $this->user[$auth0FieldName]
            );
        } elseif (strpos($auth0FieldName, 'user_metadata') !== false) {
            $queryBuilder->set(
                $typo3FieldName,
                $this->getAuth0ValueRecursive($this->user, explode('.', $auth0FieldName))
            );
        }
    }

    protected function updateWithParseFunc(QueryBuilder &$queryBuilder, string $typo3FieldName, array $auth0FieldName)
    {
        $fieldName = $auth0FieldName[self::TYPO_SCRIPT_NODE_VALUE];
        if (isset($this->user[$fieldName])) {
            if (isset($auth0FieldName['parseFunc'])) {
                $queryBuilder->set(
                    $typo3FieldName,
                    $this->handleParseFunc($auth0FieldName['parseFunc'], $this->user[$fieldName])
                );
            }
        } elseif (strpos($auth0FieldName[self::TYPO_SCRIPT_NODE_VALUE], 'user_metadata') !== false) {
            $queryBuilder->set(
                $typo3FieldName,
                $this->handleParseFunc(
                    $auth0FieldName['parseFunc'],
                    $this->getAuth0ValueRecursive(
                        $this->user,
                        explode('.', $auth0FieldName[self::TYPO_SCRIPT_NODE_VALUE])
                    )
                )
            );
        }
    }

    protected function handleParseFunc(string $function, $value)
    {
        $parseFunctions = explode('|', $function);

        foreach ($parseFunctions as $function) {
            $value = $this->transformValue($function, $value);
        }

        return $value;
    }

    protected function transformValue(string $function, $value)
    {
        switch ($function) {
            case 'strtotime':
                $value = strtotime($value);
                break;

            case 'bool':
                $value = (bool)$value;
                break;

            case 'negate':
                $value = (bool)$value ? 0 : 1;
                break;

            default:
                $this->logger->notice(sprintf('"%s" is not a valid parseFunc', $function));
        }

        return $value;
    }
}
