<?php

declare(strict_types=1);

namespace Leuchtfeuer\Auth0\EventListener;

use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use TYPO3\CMS\Core\Authentication\Event\AfterUserLoggedOutEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AfterUserLoggedOutEventListener
{
    public function __construct(protected Responsibility $responsibility) {}

    public function __invoke(AfterUserLoggedOutEvent $event): void
    {
        if (!$this->responsibility->isResponsible()) {
            return;
        }
        $configuration = new EmAuth0Configuration();
        if ($configuration->isEnableBackendLogin() && !$configuration->isSoftLogout()) {
            $backendRoot = sprintf('%s/typo3/?%s', GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'), 'auth0[action]=logout');
            header('Location: ' . $backendRoot);
            exit;
        }
    }
}