<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Hooks;

use Bitmotion\Auth0\Domain\Model\Dto\EmAuth0Configuration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

class SingleSignOutHook
{
    protected $configuration;

    public function __construct()
    {
        $this->configuration = GeneralUtility::makeInstance(EmAuth0Configuration::class);
    }

    public function performLogin(): void
    {
        // TODO: Future use
    }

    public function performLogout(): void
    {
        $environmentService = GeneralUtility::makeInstance(EnvironmentService::class);

        if ($environmentService->isEnvironmentInBackendMode()) {
            $this->performBackendLogout();
        } elseif ($environmentService->isEnvironmentInFrontendMode()) {
            $this->performFrontendLogout();
        }
    }

    /**
     * Performs single sign-out if configured
     */
    protected function performBackendLogout(): void
    {
        if ($this->configuration->getEnableBackendLogin() === true && $this->configuration->isSoftLogout() === false) {
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
