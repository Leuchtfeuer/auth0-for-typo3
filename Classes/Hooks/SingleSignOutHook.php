<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Hooks;

use Bitmotion\Auth0\Domain\Model\Dto\EmAuth0Configuration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

class SingleSignOutHook
{
    public function perform()
    {
        $backendMode = GeneralUtility::makeInstance(EnvironmentService::class)->isEnvironmentInBackendMode();

        if ($backendMode === true) {
            $configuration = GeneralUtility::makeInstance(EmAuth0Configuration::class);
            if ($configuration->getEnableBackendLogin() === true && $configuration->isSoftLogout() === false) {
                $backendRoot = sprintf('%s/typo3/?%s', GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'), 'auth0[action]=logout');
                header('Location: ' . $backendRoot);
                exit;
            }
        }
    }
}
