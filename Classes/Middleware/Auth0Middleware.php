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

namespace Bitmotion\Auth0\Middleware;

use Bitmotion\Auth0\Factory\SessionFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class Auth0Middleware implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Log off user in TYPO3 or Auth0 if there is no corresponding session in Auth0 or TYPO3.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $feUserAuthentication = $request->getAttribute('frontend.user');

        // TODO: Add application ID
        // TODO: Application and context is set to avoid initialization of EnvironmentService since GLOBALS['TYPO3_REQUEST'] is not
        // TODO: set in TYPO3 v11 at this early point.
        //$session = (new SessionFactory())->getSessionStoreForApplication(0, SessionFactory::SESSION_PREFIX_FRONTEND);
        //$userInfo = $session->getUserInfo();

        if (empty($userInfo) && $this->loggedInUserIsAuth0User($feUserAuthentication)) {
            // Log off user from TYPO3 as there is no valid Auth0 session
            $this->logger->notice('Logoff user.');
            $feUserAuthentication->logoff();
        } elseif (!empty($userInfo) && !is_array($feUserAuthentication->user) && !isset($feUserAuthentication->user['auth0_user_id'])) {
            // Destroy Auth0 session as there is no valid TYPO3 frontend user
            $session->deleteUserInfo();
        }

        return $handler->handle($request);
    }

    protected function loggedInUserIsAuth0User(FrontendUserAuthentication $feUserAuthentication): bool
    {
        return is_array($feUserAuthentication->user)
            && isset($feUserAuthentication->user['auth0_user_id'])
            && !empty($feUserAuthentication->user['auth0_user_id']);
    }
}
