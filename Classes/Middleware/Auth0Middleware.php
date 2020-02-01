<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Middleware;

/***
 *
 * This file is part of the "Auth0 for TYPO3" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Florian Wessels <f.wessels@bitmotion.de>, Bitmotion GmbH
 *
 ***/

use Auth0\SDK\Store\SessionStore;
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
        // TODO: Remove the $GLOBALS array when dropping TYPO3 9 LTS support
        $feUserAuthentication = $request->getAttribute('frontend.user') ?? $GLOBALS['TSFE']->fe_user;
        $sessionStore = new SessionStore();
        $userInfo = $sessionStore->get('user');

        if ($userInfo === null && $this->loggedInUserIsAuth0User($feUserAuthentication)) {
            // Log off user from TYPO3 as there is no valid Auth0 session
            $this->logger->notice('Logoff user.');
            $feUserAuthentication->logoff();
        } elseif ($userInfo && !is_array($feUserAuthentication->user) && !isset($feUserAuthentication->user['auth0_user_id'])) {
            // Destroy Auth0 session as there is no valid TYPO3 frontend user
            $sessionStore->delete('user');
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
