<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Domain\Model\Dto;

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

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EmAuth0Configuration implements SingletonInterface
{
    protected $enableBackendLogin = false;

    protected $backendConnection = 0;

    protected $userStoragePage = 0;

    protected $reactivateDisabledBackendUsers = 0;

    protected $reactivateDeletedBackendUsers = 0;

    protected $reactivateDisabledFrontendUsers = 1;

    protected $reactivateDeletedFrontendUsers = 1;

    protected $softLogout = false;

    /**
     * EmAuth0Configuration constructor.
     *
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function __construct()
    {
        $configuration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('auth0');

        if ($configuration) {
            $this->setPropertiesFromConfiguration($configuration);
        }
    }

    protected function setPropertiesFromConfiguration(array $configuration)
    {
        foreach ($configuration as $key => $value) {
            if (property_exists(__CLASS__, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function getEnableBackendLogin(): bool
    {
        return (bool)$this->enableBackendLogin;
    }

    public function getBackendConnection(): int
    {
        return (int)$this->backendConnection;
    }

    public function getUserStoragePage(): int
    {
        return (int)$this->userStoragePage;
    }

    public function getReactivateDisabledBackendUsers(): bool
    {
        return (bool)$this->reactivateDisabledBackendUsers;
    }

    public function getReactivateDeletedBackendUsers(): bool
    {
        return (bool)$this->reactivateDeletedBackendUsers;
    }

    public function getReactivateDisabledFrontendUsers(): bool
    {
        return (bool)$this->reactivateDisabledFrontendUsers;
    }

    public function getReactivateDeletedFrontendUsers(): bool
    {
        return (bool)$this->reactivateDeletedFrontendUsers;
    }

    public function isSoftLogout(): bool
    {
        return (bool)$this->softLogout;
    }
}
