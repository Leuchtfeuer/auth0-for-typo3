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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class UpdateUtility
 */
class UpdateUtility implements SingletonInterface
{
    /**
     * @var string
     */
    protected $tableName = '';

    /**
     * @var array
     */
    protected $user = [];

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(string $tableName, array $user)
    {
        $this->tableName = $tableName;
        $this->user = $user;
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    public function updateGroups()
    {
        if (!empty($this->user['app_metadata']['roles'])) {
            $userRoles = $this->user['app_metadata']['roles'];

            try {
                $groupMapping = ConfigurationUtility::getSetting('roles', $this->tableName);
                if (!empty($groupMapping)) {
                    $groupsToAssign = [];
                    $isBeAdmin = false;
                    $shouldUpdate = false;

                    // Map Auth0 roles on TYPO3 usergroups
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

                    // Update user only if necessary
                    if ($shouldUpdate === true) {
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
                            ->where($queryBuilder->expr()->eq('auth0_user_id', $queryBuilder->createNamedParameter($this->user['user_id'])))
                            ->execute();
                    }
                }
            } catch (\Exception $exception) {
                $this->logger->error($exception->getCode() . ': ' . $exception->getMessage());
            }
        }
    }

    public function updateUser()
    {
        try {
            // Get mapping configuration
            $mappingConfiguration = ConfigurationUtility::getSetting('propertyMapping', $this->tableName);

            if (!empty($mappingConfiguration)) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
                $queryBuilder->update($this->tableName);

                foreach ($mappingConfiguration as $typo3FieldName => $auth0FieldName) {
                    if (!is_array($auth0FieldName)) {
                        // Update without procFuncs
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
                    } elseif (is_array($auth0FieldName) && isset($auth0FieldName['_typoScriptNodeValue'])) {
                        // Update with procFuncs
                        $fieldName = $auth0FieldName['_typoScriptNodeValue'];
                        if (isset($this->user[$fieldName])) {
                            if (isset($auth0FieldName['parseFunc'])) {
                                $queryBuilder->set(
                                    $typo3FieldName,
                                    $this->handleParseFunc($auth0FieldName['parseFunc'], $this->user[$fieldName])
                                );
                            }
                        } elseif (strpos($auth0FieldName['_typoScriptNodeValue'], 'user_metadata') !== false) {
                            $queryBuilder->set(
                                $typo3FieldName,
                                $this->handleParseFunc(
                                    $auth0FieldName['parseFunc'],
                                    $this->getAuth0ValueRecursive(
                                        $this->user,
                                        explode('.', $auth0FieldName['_typoScriptNodeValue'])
                                    )
                                )
                            );
                        }
                    }
                }

                // Fixed values
                $queryBuilder->set('disable', 0);
                $queryBuilder->set('deleted', 0);

                $queryBuilder->where($queryBuilder->expr()->eq('auth0_user_id', $queryBuilder->createNamedParameter($this->user['user_id'])));
                $queryBuilder->execute();
            }
        } catch (\Exception $exception) {
            $this->logger->error($e->getCode() . ': ' . $e->getMessage());
        }
    }

    protected function getAuth0ValueRecursive(array $user, array $properties): string
    {
        $property = array_shift($properties);
        if (isset($user[$property])) {
            $value = $user[$property];
            if (is_array($properties) && ($value instanceof \stdClass || (is_array($value) && !empty($value)))) {
                return $this->getAuth0ValueRecursive($value, $properties);
            }

            return (string)$value;
        }

        return '';
    }

    /**
     * @param $function
     * @param $value
     *
     * @return bool|false|int
     */
    protected function handleParseFunc($function, $value)
    {
        $parseFunctions = explode('|', $function);

        foreach ($parseFunctions as $function) {
            $value = $this->transformValue($function, $value);
        }

        return $value;
    }

    /**
     * @param $function
     * @param $value
     *
     * @return bool|false|int
     */
    protected function transformValue($function, $value)
    {
        switch ($function) {
            case 'strtotime':
                return strtotime($value);

            case 'bool':
                return (bool)$value;

            case 'negate':
                return (bool)$value ? 0 : 1;

            default:
                $this->logger->notice(sprintf('"%s" is not a valid parseFunc', $function));
        }

        return $value;
    }
}
