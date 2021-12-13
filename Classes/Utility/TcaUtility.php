<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Bitmotion\Auth0\Utility;

use Bitmotion\Auth0\Configuration\Auth0Configuration;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TcaUtility
{
    private const EXCLUDE_PROPERTY_LIST = [
        'password' => 1,
        'usergroup' => 1,
        'felogin_forgotHash' => 1,
        'auth0_user_id' => 1,
        'auth0_metadata' => 1,
        'auth0_last_application' => 1,
    ];

    private const EXCLUDE_TABLES_LIST = [];

    /** @var array */
    private $tca;

    /** @var array */
    private $configuration;

    /** @var LanguageService */
    private $languageService;

    public function __construct(array $tca = null, $configuration = null, ?LanguageService $languageService = null)
    {
        $this->tca = $tca ?? $GLOBALS['TCA'];
        $this->configuration = $configuration ?? GeneralUtility::makeInstance(Auth0Configuration::class)->load();
        $this->languageService = $languageService ?? $GLOBALS['LANG'];
    }

    public function getColumnsFromTable(string $tableName): array
    {
        $columns = [];

        foreach ($this->tca[$tableName]['columns'] ?? [] as $name => $column) {
            if (!isset(self::EXCLUDE_PROPERTY_LIST[$name])) {
                $type = $column['config']['type'];

                if ($type === 'passthrough') {
                    continue;
                }

                $columns[$name] = [
                    'label' => $this->languageService->sl($column['label']),
                    'type' => $type,
                ];

                if ($type === 'select') {
                    $columns[$name]['items'] = [];
                    foreach ($column['config']['items'] ?? [] as $item) {
                        $columns[$name]['items'][$item[1]] = $GLOBALS['LANG']->sl($item[0]);
                    }
                }
            }
        }

        return $columns;
    }

    public function getUnusedColumnsFromTable(string $tableName, ?string $exclude = null, ?string $foreignTable = null): array
    {
        $properties = $this->getColumnsFromTable($foreignTable ?? $tableName);
        $configurationProperties = $this->getColumnsFromConfiguration($tableName, $foreignTable);

        foreach ($configurationProperties as $configurationProperty) {
            if (isset($properties[$configurationProperty]) && $configurationProperty !== $exclude) {
                unset($properties[$configurationProperty]);
            }
        }

        return $properties;
    }

    public function getTables(string $excludedTable = null): array
    {
        if (empty($this->tca)) {
            return [];
        }

        $tables = [];
        foreach ($this->tca as $table => $columns) {
            if (in_array($table, self::EXCLUDE_TABLES_LIST)) {
                continue;
            }

            if ($table === $excludedTable) {
                continue;
            }

            $tables[] = $table;
        }

        return $tables;
    }

    protected function getColumnsFromConfiguration(string $tableName, ?string $foreignTable = null): array
    {
        $propertyGroups = $this->configuration['properties'][$tableName];
        $properties = [];

        foreach ($propertyGroups as $group) {
            foreach ($group as $property) {
                if (!$foreignTable && empty($property['foreignTable'])) {
                    $properties[] = $property['databaseField'];
                    continue;
                }

                if (isset($property['foreignTable']) && $property['foreignTable'] === $foreignTable) {
                    $properties[] = $property['databaseField'];
                }
            }
        }

        return $properties;
    }
}
