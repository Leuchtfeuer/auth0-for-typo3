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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Event\BeforeUserLogoutEvent;

class BeforeUserLogoutEventListener
{
    public function __construct(protected Responsibility $responsibility) {}

    public function __invoke(BeforeUserLogoutEvent $event): void
    {
        $user = $event->getUser();
        if ($user instanceof BackendUserAuthentication) {
            $rawUser = $user->user;
            $this->responsibility->setResponsible(
                isset($rawUser['auth0_user_id']) && !empty($rawUser['auth0_user_id'])
            );
        }
    }
}
