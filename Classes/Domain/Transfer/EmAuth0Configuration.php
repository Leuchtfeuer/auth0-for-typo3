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
    protected bool $enableBackendLogin = false;

    protected int $backendConnection = 0;

    protected int $userStoragePage = 0;

    protected bool $reactivateDisabledBackendUsers = false;

    protected bool $reactivateDeletedBackendUsers = false;

    protected bool $softLogout = false;

    protected string $additionalAuthorizeParameters = '';

    protected string $privateKeyFile = '';

    protected string $publicKeyFile = '';

    protected string $userIdentifier = 'sub';

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

    /**
     * @param array<string, mixed> $configuration
     */
    protected function setPropertiesFromConfiguration(array $configuration): void
    {
        foreach ($configuration as $key => $value) {
            if (property_exists(self::class, $key)) {
                $value = match (gettype($this->$key)) {
                    'string' => (string)$value,
                    'integer' => (int)$value,
                    'boolean' => (bool)$value,
                    'array' => (array)$value,
                    default => $value,
                };
                $this->$key = $value;
            }
        }
    }

    public function isEnableBackendLogin(): bool
    {
        return $this->enableBackendLogin;
    }

    public function getBackendConnection(): int
    {
        return $this->backendConnection;
    }

    public function getUserStoragePage(): int
    {
        return $this->userStoragePage;
    }

    public function isReactivateDisabledBackendUsers(): bool
    {
        return $this->reactivateDisabledBackendUsers;
    }

    public function isReactivateDeletedBackendUsers(): bool
    {
        return $this->reactivateDeletedBackendUsers;
    }

    public function isSoftLogout(): bool
    {
        return $this->softLogout;
    }

    /**
     * @return array<string>
     */
    public function getAdditionalAuthorizeParameters(): array
    {
        return ParametersUtility::transformUrlParameters($this->additionalAuthorizeParameters);
    }

    /**
     * @return non-empty-string
     */
    public function getPrivateKeyFile(): string
    {
        return 'file://' . $this->privateKeyFile;
    }

    /**
     * @return non-empty-string
     */
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
