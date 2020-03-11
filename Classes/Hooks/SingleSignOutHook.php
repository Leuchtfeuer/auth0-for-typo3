<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Hooks;

/***
 *
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use Bitmotion\Auth0\Domain\Transfer\EmAuth0Configuration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

class SingleSignOutHook implements SingletonInterface
{
    protected $configuration;

    protected $environmentService;

    protected $responsible = false;

    public function __construct()
    {
        $this->configuration = GeneralUtility::makeInstance(EmAuth0Configuration::class);
        $this->environmentService = GeneralUtility::makeInstance(EnvironmentService::class);
    }

    public function isResponsible(): void
    {
        if ($this->environmentService->isEnvironmentInBackendMode()) {
            $beUser = $GLOBALS['BE_USER'];
            $this->responsible = isset($beUser->user['auth0_user_id']) && !empty($beUser->user['auth0_user_id']);
        }
    }

    public function performLogout(): void
    {
        if ($this->responsible === true) {
            if ($this->environmentService->isEnvironmentInBackendMode()) {
                $this->performBackendLogout();
            } elseif ($this->environmentService->isEnvironmentInFrontendMode()) {
                $this->performFrontendLogout();
            }
        }
    }

    /**
     * Performs single sign-out if configured
     */
    protected function performBackendLogout(): void
    {
        if ($this->configuration->isEnableBackendLogin() && !$this->configuration->isSoftLogout()) {
            $backendRoot = sprintf('%s/typo3/?%s', GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'), 'auth0[action]=logout');
            header('Location: ' . $backendRoot);
            exit;
        }
    }

    protected function performFrontendLogout(): void
    {
        // TODO: Future use
    }
}
