<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\Auth0\Utility;

use Leuchtfeuer\Auth0\Configuration\Auth0Configuration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TcaUtility
{
    private const EXCLUDE_LIST = [
        'password' => 1,
        'usergroup' => 1,
        'felogin_forgotHash' => 1,
        'auth0_user_id' => 1,
        'auth0_metadata' => 1,
        'auth0_last_application' => 1,
    ];

    /**
     * @return array<mixed>
     */
    public function getColumnsFromTable(string $tableName): array
    {
        $columns = [];

        foreach ($GLOBALS['TCA'][$tableName]['columns'] ?? [] as $name => $column) {
            if (!isset(self::EXCLUDE_LIST[$name])) {
                $type = $column['config']['type'];

                if ($type === 'passthrough') {
                    continue;
                }

                $columns[$name] = [
                    'label' => $GLOBALS['LANG']->sl($column['label']),
                    'type' => $type,
                ];

                if ($type === 'select') {
                    $columns[$name]['items'] = [];
                    foreach ($column['config']['items'] ?? [] as $item) {
                        $columns[$name]['items'][$item[1] ?? $item['value']] = $GLOBALS['LANG']->sl($item[0] ?? $item['label']);
                    }
                }
            }
        }

        return $columns;
    }

    /**
     * @return array<mixed>
     */
    public function getUnusedColumnsFromTable(string $tableName, ?string $exclude = null): array
    {
        $properties = $this->getColumnsFromTable($tableName);
        $configurationProperties = $this->getColumnsFromConfiguration($tableName);

        foreach ($configurationProperties as $configurationProperty) {
            if (isset($properties[$configurationProperty]) && $configurationProperty !== $exclude) {
                unset($properties[$configurationProperty]);
            }
        }

        return $properties;
    }

    /**
     * @return array<mixed>
     */
    protected function getColumnsFromConfiguration(string $tableName): array
    {
        $configuration = GeneralUtility::makeInstance(Auth0Configuration::class)->load();
        $propertyGroups = $configuration['properties'][$tableName];
        $properties = [];

        foreach ($propertyGroups as $group) {
            foreach ($group as $property) {
                $properties[] = $property['databaseField'];
            }
        }

        return $properties;
    }
}
