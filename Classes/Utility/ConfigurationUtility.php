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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class ConfigurationUtility implements SingletonInterface
{
    protected static $settings = [];

    /**
     * @throws InvalidConfigurationTypeException
     */
    private static function makeInstance(): void
    {
        $configurationManager = GeneralUtility::makeInstance(ObjectManager::class)->get(ConfigurationManager::class);
        self::$settings = $configurationManager->getConfiguration(
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            'auth0'
        );
    }

    /**
     * @throws InvalidConfigurationTypeException
     * @return array|string
     */
    public static function getSetting(string ...$keys)
    {
        if (empty(self::$settings)) {
            self::makeInstance();
            if (empty(self::$settings)) {
                throw new InvalidConfigurationTypeException('No settings found. TypoScript included?', 1531381794);
            }
        }

        if (!empty($keys)) {
            return self::getSettingRecursive($keys, self::$settings);
        }

        return self::$settings;
    }

    /**
     * @throws InvalidConfigurationTypeException
     */
    protected static function getSettingRecursive(array $keys, array $settings)
    {
        $key = array_shift($keys);

        if (isset($settings[$key])) {
            $setting = $settings[$key];

            if (!empty($keys)) {
                return self::getSettingRecursive($keys, $setting);
            }

            return $setting;
        }

        throw new InvalidConfigurationTypeException(sprintf('No Configuration for %s found.', $key), 1528561132);
    }

    /**
     * @throws InvalidConfigurationTypeException
     */
    public static function isLoaded(): bool
    {
        if (empty(self::$settings)) {
            self::makeInstance();
        }

        return (empty(self::$settings)) ? false : true;
    }
}
