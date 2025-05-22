<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\Auth0\Configuration;

use Leuchtfeuer\Auth0\Factory\ConfigurationFactory;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Auth0Configuration implements SingletonInterface
{
    protected const CONFIG_FILE_NAME = 'config.yaml';

    protected const CONFIG_FOLDER_NAME = 'auth0';

    public const CONFIG_TYPE_ROOT = 'root';

    public const CONFIG_TYPE_USER = 'user_metadata';

    public const CONFIG_TYPE_APP = 'app_metadata';

    protected string $configPath;

    protected string $filePath;

    public function __construct(string $configPath = null)
    {
        $this->configPath = $configPath ?? sprintf('%s/%s', Environment::getConfigPath(), self::CONFIG_FOLDER_NAME);
        $this->filePath = sprintf('%s/%s', $this->configPath, self::CONFIG_FILE_NAME);
    }

    public function load(): array
    {
        $loader = $this->getYamlFileLoader();

        try {
            return $loader->load(GeneralUtility::fixWindowsFilePath($this->filePath), YamlFileLoader::PROCESS_IMPORTS);
        } catch (\Exception) {
            return $this->buildDefaultConfiguration();
        }
    }

    public function write(array $configuration): void
    {
        if (!file_exists($this->configPath)) {
            GeneralUtility::mkdir_deep($this->configPath);
        }

        $newConfiguration = $configuration;

        if (file_exists($this->filePath)) {
            $loader = $this->getYamlFileLoader();
            $windowsFixedFilePath = GeneralUtility::fixWindowsFilePath($this->filePath);

            // load without any processing to have the unprocessed base to modify
            $newConfiguration = $loader->load($windowsFixedFilePath, 0);

            // load the processed configuration to diff changed values
            $processed = $loader->load($windowsFixedFilePath);

            // find properties that were modified via GUI
            $newModified = array_replace_recursive(
                $this->findRemoved($processed, $configuration),
                $this->findModified($processed, $configuration)
            );

            // change _only_ the modified keys, leave the original non-changed areas alone
            ArrayUtility::mergeRecursiveWithOverrule($newConfiguration, $newModified);
        }

        ksort($newConfiguration);

        $yamlFileContents = Yaml::dump($newConfiguration, 99, 2);
        GeneralUtility::writeFile($this->filePath, $yamlFileContents);
    }

    protected function buildDefaultConfiguration(): array
    {
        $configuration = [
            'properties' => [
                'be_users' => [
                    self::CONFIG_TYPE_ROOT => [
                        [
                            'auth0Property' => 'created_at',
                            'databaseField' => 'crdate',
                            'readOnly' => true,
                            'processing' => 'strtotime',
                        ], [
                            'auth0Property' => 'updated_at',
                            'databaseField' => 'tstamp',
                            'readOnly' => true,
                            'processing' => 'strtotime',
                        ], [
                            'auth0Property' => 'email_verified',
                            'databaseField' => 'disable',
                            'readOnly' => true,
                            'processing' => 'negate-bool',
                        ], [
                            'auth0Property' => 'nickname',
                            'databaseField' => 'username',
                        ],
                    ],
                    self::CONFIG_TYPE_USER => [],
                    self::CONFIG_TYPE_APP => [],
                ]
            ],
            'roles' => (new ConfigurationFactory())->buildRoles('roles', '', 0),
        ];

        $this->write($configuration);

        return $configuration;
    }

    protected function getYamlFileLoader(): YamlFileLoader
    {
        return GeneralUtility::makeInstance(YamlFileLoader::class);
    }

    protected function findModified(array $currentConfiguration, array $newConfiguration): array
    {
        $differences = [];

        foreach ($newConfiguration as $key => $value) {
            if (!isset($currentConfiguration[$key]) || $currentConfiguration[$key] !== $newConfiguration[$key]) {
                if (!isset($newConfiguration[$key]) && isset($currentConfiguration[$key])) {
                    $differences[$key] = '__UNSET';
                } elseif (isset($currentConfiguration[$key])
                    && is_array($newConfiguration[$key])
                    && is_array($currentConfiguration[$key])
                ) {
                    $differences[$key] = $this->findModified($currentConfiguration[$key], $newConfiguration[$key]);
                } else {
                    $differences[$key] = $value;
                }
            }
        }

        return $differences;
    }

    protected function findRemoved(array $currentConfiguration, array $newConfiguration): array
    {
        $removed = [];

        foreach (array_keys($currentConfiguration) as $key) {
            if (!isset($newConfiguration[$key])) {
                $removed[$key] = '__UNSET';
            } elseif (isset($currentConfiguration[$key]) && is_array($currentConfiguration[$key]) && is_array($newConfiguration[$key])) {
                $removedInRecursion = $this->findRemoved($currentConfiguration[$key], $newConfiguration[$key]);
                if ($removedInRecursion !== []) {
                    $removed[$key] = $removedInRecursion;
                }
            }
        }

        return $removed;
    }
}
