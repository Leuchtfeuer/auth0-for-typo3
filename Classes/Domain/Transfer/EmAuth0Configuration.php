<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Domain\Transfer;

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

use Bitmotion\Auth0\Utility\ParametersUtility;
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

    protected $reactivateDisabledBackendUsers = false;

    protected $reactivateDeletedBackendUsers = false;

    protected $reactivateDisabledFrontendUsers = true;

    protected $reactivateDeletedFrontendUsers = true;

    protected $softLogout = false;

    protected $additionalAuthorizeParameters = '';

    protected $enableFrontendLogin = true;

    /**
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

    protected function setPropertiesFromConfiguration(array $configuration): void
    {
        foreach ($configuration as $key => $value) {
            if (property_exists(__CLASS__, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * @deprecated Will be removed in version 4. Use $this->isEnableBackendLogin() instead.
     */
    public function getEnableBackendLogin(): bool
    {
        return $this->isEnableBackendLogin();
    }

    public function isEnableBackendLogin(): bool
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

    /**
     * @deprecated Will be removed in version 4. Use $this->isReactivateDisabledBackendUsers() instead.
     */
    public function getReactivateDisabledBackendUsers(): bool
    {
        return $this->isReactivateDisabledBackendUsers();
    }

    public function isReactivateDisabledBackendUsers(): bool
    {
        return (bool)$this->reactivateDisabledBackendUsers;
    }

    /**
     * @deprecated Will be removed in version 4. Use $this->isReactivateDeletedBackendUsers() instead.
     */
    public function getReactivateDeletedBackendUsers(): bool
    {
        return $this->isReactivateDeletedBackendUsers();
    }

    public function isReactivateDeletedBackendUsers(): bool
    {
        return (bool)$this->reactivateDeletedBackendUsers;
    }

    /**
     * @deprecated Will be removed in version 4. Use $this->isReactivateDisabledFrontendUsers() instead.
     */
    public function getReactivateDisabledFrontendUsers(): bool
    {
        return $this->isReactivateDisabledFrontendUsers();
    }

    public function isReactivateDisabledFrontendUsers(): bool
    {
        return (bool)$this->reactivateDisabledFrontendUsers;
    }

    /**
     * @deprecated Will be removed in version 4. Use $this->isReactivateDeletedFrontendUsers() instead.
     */
    public function getReactivateDeletedFrontendUsers(): bool
    {
        return $this->isReactivateDeletedFrontendUsers();
    }

    public function isReactivateDeletedFrontendUsers(): bool
    {
        return (bool)$this->reactivateDeletedFrontendUsers;
    }

    public function isSoftLogout(): bool
    {
        return (bool)$this->softLogout;
    }

    public function getAdditionalAuthorizeParameters(): array
    {
        return ParametersUtility::transformUrlParameters($this->additionalAuthorizeParameters);
    }

    public function isEnableFrontendLogin(): bool
    {
        return (bool)$this->enableFrontendLogin;
    }
}
