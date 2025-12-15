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

namespace Leuchtfeuer\Auth0\EventListener;

use Leuchtfeuer\Auth0\Service\Auth0SessionValidator;
use TYPO3\CMS\Backend\Security\SudoMode\Event\SudoModeRequiredEvent;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * Event listener for sudo mode required events.
 *
 * Allows Auth0-authenticated users to bypass sudo mode for all authorized operations.
 * Authorization checks are handled by TYPO3's core authorization system.
 */
class SudoModeRequiredEventListener
{
    protected Auth0SessionValidator $auth0SessionValidator;
    protected ExtensionConfiguration $extensionConfiguration;

    public function __construct(
        Auth0SessionValidator $auth0SessionValidator,
        ExtensionConfiguration $extensionConfiguration
    ) {
        $this->auth0SessionValidator = $auth0SessionValidator;
        $this->extensionConfiguration = $extensionConfiguration;
    }

    public function __invoke(SudoModeRequiredEvent $event): void
    {
        if ($event->isVerificationRequired() === false) {
            // Already denied, no action needed
            return;
        }

        if ($this->extensionConfiguration->get('auth0', 'disableSudoModeBypass')) {
            // Sudo mode bypass disabled
            return;
        }

        // Check if user is authenticated with Auth0 and has valid session
        if ($this->auth0SessionValidator->hasValidAuth0Session()) {
            $event->setVerificationRequired(false);
        }
    }
}
