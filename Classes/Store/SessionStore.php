<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Bitmotion\Auth0\Store;

use Auth0\SDK\Configuration\SdkConfiguration;

class SessionStore
{
    const SESSION_PREFIX = 'auth0';
    /**
     * @var \Auth0\SDK\Store\SessionStore
     */
    private \Auth0\SDK\Store\SessionStore $sessionStore;

    public function __construct(SdkConfiguration $configuration, string $sessionPrefix = self::SESSION_PREFIX)
    {
        $this->sessionStore = new \Auth0\SDK\Store\SessionStore($configuration, $sessionPrefix);
    }

    public function getUserInfo(): array
    {
        return $this->sessionStore->get('user') ?? [];
    }

    public function deleteUserInfo(): void
    {
        $this->sessionStore->delete('user');
    }
}
