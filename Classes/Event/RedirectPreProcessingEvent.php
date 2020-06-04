<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Event;

use Bitmotion\Auth0\Service\RedirectService;

/***
 *
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2020 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

final class RedirectPreProcessingEvent
{
    /**
     * @var string
     */
    private $redirectUri;

    /**
     * @var array
     */
    private $redirectService;

    public function __construct(string $redirectUri, RedirectService $redirectService)
    {
        $this->redirectUri = $redirectUri;
        $this->redirectService = $redirectService;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function setRedirectUri(string $redirectUri): void
    {
        $this->redirectUri = $redirectUri;
    }

    public function getRedirectService(): array
    {
        return $this->redirectService;
    }
}
