<?php

declare(strict_types=1);

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\Auth0\Hooks;

use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SingleSignOutHook implements SingletonInterface
{
    protected $configuration;

    protected bool $responsible = false;

    public function __construct()
    {
        $this->configuration = new EmAuth0Configuration();
    }

    public function isResponsible(): void
    {
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()) {
            $beUser = $GLOBALS['BE_USER'];
            $this->responsible = isset($beUser->user['auth0_user_id']) && !empty($beUser->user['auth0_user_id']);
        }
    }

    public function performLogout(): void
    {
        if ($this->responsible === true) {
            if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
                && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()) {
                $this->performFrontendLogout();
            } else {
                $this->performBackendLogout();
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
