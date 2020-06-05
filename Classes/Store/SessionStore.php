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

use Auth0\SDK\Store\SessionStore as Auth0SessionStore;

class SessionStore extends Auth0SessionStore
{
    public function __construct($base_name = \Auth0\SDK\Store\SessionStore::BASE_NAME)
    {
        \Auth0\SDK\Store\SessionStore::__construct($base_name);
    }

    public function getUserInfo(): array
    {
        return $this->get('user') ?? [];
    }

    public function deleteUserInfo(): void
    {
        $this->delete('user');
    }
}
