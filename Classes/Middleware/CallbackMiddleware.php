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

use Bitmotion\Auth0\Api\Management\UserApi;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Bitmotion\Auth0\Domain\Transfer\EmAuth0Configuration;
use Bitmotion\Auth0\ErrorCode;
use Bitmotion\Auth0\Exception\TokenException;
use Bitmotion\Auth0\Exception\UnknownErrorCodeException;
use Bitmotion\Auth0\Factory\SessionFactory;
use Bitmotion\Auth0\LoginProvider\Auth0Provider;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Service\RedirectService;
use Bitmotion\Auth0\Utility\ApiUtility;
use Bitmotion\Auth0\Utility\Database\UpdateUtility;
use Bitmotion\Auth0\Utility\TokenUtility;
use Lcobucci\JWT\Token;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CallbackMiddleware implements MiddlewareInterface
{
    const PATH = '/auth0/callback';

    const TOKEN_PARAMETER = 'token';

    const BACKEND_URI = '%s/typo3/?loginProvider=%d&code=%s&state=%s';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (strpos($request->getUri()->getPath(), self::PATH) === false) {
            // Middleware is not responsible for given request
            return $handler->handle($request);
        }

        $tokenUtility = GeneralUtility::makeInstance(TokenUtility::class);

        if (!$tokenUtility->verifyToken((string)GeneralUtility::_GET(self::TOKEN_PARAMETER))) {
            return new Response('php://temp', 400);
        }

        try {
            $token = $tokenUtility->getToken();
        } catch (TokenException $exception) {
            return new Response('php://temp', 400);
        }

        if ($token->getClaim('environment') === TokenUtility::ENVIRONMENT_BACKEND) {
            return $this->handleBackendCallback($request, $tokenUtility);
        }

        // Perform frontend callback as environment can only be 'BE' or 'FE'
        return $this->handleFrontendCallback($request, $token);
    }

    protected function handleBackendCallback(ServerRequestInterface $request, TokenUtility $tokenUtility): RedirectResponse
    {
        $queryParams = $request->getQueryParams();

        $redirectUri = sprintf(
            self::BACKEND_URI,
            $tokenUtility->getIssuer(),
            Auth0Provider::LOGIN_PROVIDER,
            $queryParams['code'],
            $queryParams['state']
        );

        // Add error parameters to backend uri if exists
        if (!empty(GeneralUtility::_GET('error')) && !empty(GeneralUtility::_GET('error_description'))) {
            $redirectUri .= sprintf(
                '&error=%s&error_description=%',
                GeneralUtility::_GET('error'),
                GeneralUtility::_GET('error_description')
            );
        }

        return new RedirectResponse($redirectUri, 302);
    }

    protected function handleFrontendCallback(ServerRequestInterface $request, Token $token): RedirectResponse
    {
        $errorCode = (string)GeneralUtility::_GET('error');

        if (!empty($errorCode)) {
            return $this->enrichReferrerByErrorCode($errorCode, $token);
        }

        if ($this->isUserLoggedIn($request)) {
            $loginType = GeneralUtility::_GET('logintype');
            $application = $token->getClaim('application');
            $userInfo = (new SessionFactory())->getSessionStoreForApplication($application, SessionFactory::SESSION_PREFIX_FRONTEND)->getUserInfo();

            // Redirect when user just logged in (and update him)
            if ($loginType === 'login' && !empty($userInfo)) {
                $this->updateTypo3User($application, $userInfo);

                if ((bool)$token->getClaim('redirectDisable') === false) {
                    $allowedMethods = ['groupLogin', 'userLogin', 'login', 'getpost', 'referrer'];
                    $this->performRedirectFromPluginConfiguration($token, $allowedMethods);
                } else {
                    return new RedirectResponse($token->getClaim('referrer'));
                }
            } elseif ($loginType === 'logout') {
                // User was logged out prior to this method. That's why there is no valid TYPO3 frontend user anymore.
                $this->performRedirectFromPluginConfiguration($token, ['logout', 'referrer']);
            }
        }

        // Redirect back to logout page if no redirect was executed before
        return new RedirectResponse($token->getClaim('referrer'));
    }

    /**
     * @throws UnknownErrorCodeException
     */
    protected function enrichReferrerByErrorCode(string $errorCode, Token $token): RedirectResponse
    {
        if (in_array($errorCode, (new \ReflectionClass(ErrorCode::class))->getConstants())) {
            $referrer = new Uri($token->getClaim('referrer'));

            $errorQuery = sprintf(
                'error=%s&error_description=%s',
                $errorCode,
                GeneralUtility::_GET('error_description')
            );

            $query = $referrer->getQuery() . (!empty($referrer->getQuery()) ? '&' : '') . $errorQuery;

            return new RedirectResponse($referrer->withQuery($query));
        }

        throw new UnknownErrorCodeException(sprintf('Error %s is unknown.', $errorCode), 1586000737);
    }

    protected function isUserLoggedIn(ServerRequestInterface $request): bool
    {
        try {
            // TODO: Get rid of TSFE when dropping TYPO3 v9 support
            // This is necessary as group data is not fetched to this time
            ($request->getAttribute('frontend.user') ?? $GLOBALS['TSFE']->fe_user)->fetchGroupData();
            $context = GeneralUtility::makeInstance(Context::class);

            return (bool)$context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
        } catch (\Exception $exception) {
            return false;
        }
    }

    protected function updateTypo3User(int $application, array $user): void
    {
        // Get user
        $application = BackendUtility::getRecord(ApplicationRepository::TABLE_NAME, $application, 'api, uid');

        if ((bool)$application['api'] === true) {
            $userApi = GeneralUtility::makeInstance(ApiUtility::class, $application['uid'])->getApi(UserApi::class, Scope::USER_READ);
            $user = $userApi->get($user[GeneralUtility::makeInstance(EmAuth0Configuration::class)->getUserIdentifier()]);
        }

        // Update user
        $updateUtility = GeneralUtility::makeInstance(UpdateUtility::class, 'fe_users', $user);
        $updateUtility->updateUser();
        $updateUtility->updateGroups();
    }

    protected function performRedirectFromPluginConfiguration(Token $token, array $allowedMethods): void
    {
        $redirectService = new RedirectService([
            'redirectDisable' => false,
            'redirectMode' => $token->getClaim('redirectMode'),
            'redirectFirstMethod' => $token->getClaim('redirectFirstMethod'),
            'redirectPageLogin' => $token->getClaim('redirectPageLogin'),
            'redirectPageLoginError' => $token->getClaim('redirectPageLoginError'),
            'redirectPageLogout' => $token->getClaim('redirectPageLogout')
        ]);

        $redirectService->handleRedirect($allowedMethods);
    }
}
