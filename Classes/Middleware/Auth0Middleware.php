<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Middleware;

use Auth0\SDK\Store\SessionStore;
use Bitmotion\Auth0\Service\SessionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
            $applicationUid = (int)$feUserAuthentication->user['auth0_last_application'];
            $sessionService = GeneralUtility::makeInstance(SessionService::class);

            try {
                $sessionService->createAuth0Session($feUserAuthentication, $sessionStore, $applicationUid);
            } catch (\Exception $exception) {
                $this->logger->warning(
                    sprintf(
                        'Could not update Auth0 Session. - %s: %s',
                        $exception->getCode(),
                        $exception->getMessage()
                    )
                );
                $this->logger->notice('Logoff user.');
                $feUserAuthentication->logoff();
            }
        }

        return $handler->handle($request);
    }
}
