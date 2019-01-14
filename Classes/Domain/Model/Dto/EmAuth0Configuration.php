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

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EmAuth0Configuration implements SingletonInterface
{
    /**
     * @var bool
     */
    protected $enableBackendLogin = false;

    /**
     * @var int
     */
    protected $backendConnection = 0;

    /**
     * @var int
     */
    protected $userStoragePage = 0;

    /**
     * @var int
     */
    protected $reactivateDisabledBackendUsers = 0;

    /**
     * @var int
     */
    protected $reactivateDeletedBackendUsers = 0;

    /**
     * @var int
     */
    protected $reactivateDisabledFrontendUsers = 1;

    /**
     * @var int
     */
    protected $reactivateDeletedFrontendUsers = 1;

    /**
     * EmAuth0Configuration constructor.
     *
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
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
}
