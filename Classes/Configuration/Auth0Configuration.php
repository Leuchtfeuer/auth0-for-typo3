<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Bitmotion\Auth0\Configuration;

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Auth0Configuration
{
    const CONFIG_FILE_NAME = 'config.yaml';

    const CONFIG_FOLDER_NAME = 'auth0';

    const CONFIG_TYPE_ROOT = 'root';

    const CONFIG_TYPE_METADATA = 'metadata';

    protected $configPath;

    protected $filePath;

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
        } catch (\Exception $exception) {
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
                'user' => [
                    self::CONFIG_TYPE_ROOT => [],
                    self::CONFIG_TYPE_METADATA => [],
                ],
                'app' => [
                    self::CONFIG_TYPE_METADATA => [],
                ]
            ],
            'roles' => [
                'default' => [
                    'frontend' => 0,
                    'backend' => 0,
                ],
                'key' => 'roles',
                'beAdmin' => '',
            ],
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

        foreach ($currentConfiguration as $key => $value) {
            if (!isset($newConfiguration[$key])) {
                $removed[$key] = '__UNSET';
            } elseif (isset($currentConfiguration[$key]) && is_array($currentConfiguration[$key]) && is_array($newConfiguration[$key])) {
                $removedInRecursion = $this->findRemoved($currentConfiguration[$key], $newConfiguration[$key]);
                if (!empty($removedInRecursion)) {
                    $removed[$key] = $removedInRecursion;
                }
            }
        }

        return $removed;
    }
}
