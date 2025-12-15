<?php

declare(strict_types=1);

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Leuchtfeuer Digital Marketing <dev@Leuchtfeuer.com>
 */

namespace Leuchtfeuer\Auth0\Domain\Transfer;

use Leuchtfeuer\Auth0\Utility\ParametersUtility;
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

    protected $privateKeyFile = '';

    protected $publicKeyFile = '';

    protected $userIdentifier = 'sub';

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

    public function isReactivateDisabledBackendUsers(): bool
    {
        return (bool)$this->reactivateDisabledBackendUsers;
    }

    public function isReactivateDeletedBackendUsers(): bool
    {
        return (bool)$this->reactivateDeletedBackendUsers;
    }

    public function isReactivateDisabledFrontendUsers(): bool
    {
        return (bool)$this->reactivateDisabledFrontendUsers;
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

    public function getPrivateKeyFile(): string
    {
        return 'file://' . $this->privateKeyFile;
    }

    public function getPublicKeyFile(): string
    {
        return 'file://' . $this->publicKeyFile;
    }

    public function useKeyFiles(): bool
    {
        return !empty($this->publicKeyFile) && !empty($this->privateKeyFile);
    }

    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }
}
