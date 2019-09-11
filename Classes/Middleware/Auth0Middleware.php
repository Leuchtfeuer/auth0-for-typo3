<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Middleware;

use Auth0\SDK\Store\SessionStore;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class Auth0Middleware implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Creates an Auth0 session when there is an logged in frontend user.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $feUserAuthentication = $GLOBALS['TSFE']->fe_user;
        $sessionStore = new SessionStore();
        $userInfo = $sessionStore->get('user');

        if ($userInfo === null && is_array($feUserAuthentication->user) && $feUserAuthentication->user['auth0_user_id'] !== '') {
            $this->logger->notice('Logoff user.');
            $feUserAuthentication->logoff();
        }

        return $handler->handle($request);
    }
}
