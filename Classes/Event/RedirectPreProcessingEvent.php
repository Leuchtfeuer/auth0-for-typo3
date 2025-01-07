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

namespace Leuchtfeuer\Auth0\Event;

use Leuchtfeuer\Auth0\Service\RedirectService;

final class RedirectPreProcessingEvent
{
    public function __construct(
        private string $redirectUri,
        private readonly RedirectService $redirectService
    ) {}

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function setRedirectUri(string $redirectUri): void
    {
        $this->redirectUri = $redirectUri;
    }

    public function getRedirectService(): RedirectService
    {
        return $this->redirectService;
    }
}
