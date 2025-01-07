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

namespace Leuchtfeuer\Auth0\Middleware;

use Auth0\SDK\Exception\ArgumentException;
use Auth0\SDK\Exception\ConfigurationException;
use Auth0\SDK\Exception\NetworkException;
use Auth0\SDK\Exception\StateException;
use Auth0\SDK\Utility\HttpResponse;
use GuzzleHttp\Exception\GuzzleException;
use Lcobucci\JWT\Token\DataSet;
use Leuchtfeuer\Auth0\Domain\Repository\ApplicationRepository;
use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use Leuchtfeuer\Auth0\ErrorCode;
use Leuchtfeuer\Auth0\Exception\TokenException;
use Leuchtfeuer\Auth0\Exception\UnknownErrorCodeException;
use Leuchtfeuer\Auth0\Factory\ApplicationFactory;
use Leuchtfeuer\Auth0\LoginProvider\Auth0Provider;
use Leuchtfeuer\Auth0\Service\RedirectService;
use Leuchtfeuer\Auth0\Utility\Database\UpdateUtility;
use Leuchtfeuer\Auth0\Utility\TokenUtility;
use Leuchtfeuer\Auth0\Utility\UserUtility;
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

    public function __construct(
        protected readonly UpdateUtility $updateUtility,
        protected readonly UserUtility $userUtility,
        protected readonly TokenUtility $tokenUtility,
    ) {}

    /**
     * @throws NetworkException
     * @throws UnknownErrorCodeException
     * @throws ArgumentException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!str_contains($request->getUri()->getPath(), self::PATH)) {
            // Middleware is not responsible for given request
            return $handler->handle($request);
        }

        if (!$this->tokenUtility->verifyToken((string)($request->getQueryParams()[self::TOKEN_PARAMETER] ?? null))) {
            return new Response('php://temp', 400);
        }

        try {
            $dataSet = $this->tokenUtility->getToken()->claims();
        } catch (TokenException) {
            return new Response('php://temp', 400);
        }

        if ($dataSet->get('environment') === TokenUtility::ENVIRONMENT_BACKEND) {
            return $this->handleBackendCallback($request, $dataSet);
        }
        // Perform frontend callback as environment can only be 'BE' or 'FE'
        return $this->handleFrontendCallback($request, $dataSet);
    }

    protected function handleBackendCallback(
        ServerRequestInterface $request,
        DataSet $dataSet
    ): RedirectResponse {
        if ($dataSet->get('redirectUri') !== null) {
            return new RedirectResponse($dataSet->get('redirectUri'), 302);
        }

        $queryParams = $request->getQueryParams();
        $redirectUri = sprintf(
            self::BACKEND_URI,
            $this->tokenUtility->getIssuer(),
            Auth0Provider::LOGIN_PROVIDER,
            $queryParams['code'],
            $queryParams['state']
        );

        // Add error parameters to backend uri if exists
        if (!empty($request->getQueryParams()['error'] ?? null) && !empty($request->getQueryParams()['error_description'] ?? null)) {
            $redirectUri .= sprintf(
                '&error=%s&error_description=%',
                $request->getQueryParams()['error'],
                $request->getQueryParams()['error_description']
            );
        }

        return new RedirectResponse($redirectUri, 302);
    }

    /**
     * @param ServerRequestInterface $request
     * @param DataSet $tokenDataSet
     * @return RedirectResponse
     * @throws ArgumentException
     * @throws ConfigurationException
     * @throws GuzzleException
     * @throws NetworkException
     * @throws UnknownErrorCodeException
     * @throws StateException
     */
    protected function handleFrontendCallback(ServerRequestInterface $request, DataSet $tokenDataSet): RedirectResponse
    {
        $errorCode = (string)($request->getQueryParams()['error'] ?? null);

        if ($errorCode !== '' && $errorCode !== '0') {
            return $this->enrichReferrerByErrorCode($request, $errorCode, $tokenDataSet);
        }

        if ($this->isUserLoggedIn($request)) {
            $loginType = $request->getQueryParams()['logintype'] ?? '';
            $application = $tokenDataSet->get('application');
            $auth0 = ApplicationFactory::build($application, ApplicationFactory::SESSION_PREFIX_FRONTEND);
            $auth0->exchange(null, $request->getQueryParams()['code'] ?? null, $request->getQueryParams()['state'] ?? null);
            $userInfo = $auth0->getUser();

            // Redirect when user just logged in (and update him)
            if ($loginType === 'login' && ($userInfo !== null && $userInfo !== [])) {
                $this->updateTypo3User($application, $userInfo);

                if ((bool)$tokenDataSet->get('redirectDisable') === false) {
                    $allowedMethods = ['groupLogin', 'userLogin', 'login', 'getpost', 'referrer'];
                    $this->performRedirectFromPluginConfiguration($tokenDataSet, $allowedMethods);
                } else {
                    return new RedirectResponse($tokenDataSet->get('referrer'));
                }
            } elseif ($loginType === 'logout') {
                // User was logged out prior to this method. That's why there is no valid TYPO3 frontend user anymore.
                $this->performRedirectFromPluginConfiguration($tokenDataSet, ['logout', 'referrer']);
            }
        }

        // Redirect back to logout page if no redirect was executed before
        return new RedirectResponse($tokenDataSet->get('referrer'));
    }

    /**
     * @throws UnknownErrorCodeException
     */
    protected function enrichReferrerByErrorCode(ServerRequestInterface $request, string $errorCode, DataSet $tokenDataSet): RedirectResponse
    {
        if (in_array($errorCode, (new \ReflectionClass(ErrorCode::class))->getConstants())) {
            $referrer = new Uri($tokenDataSet->get('referrer'));

            $errorQuery = sprintf(
                'error=%s&error_description=%s',
                $errorCode,
                $request->getQueryParams()['error_description'] ?? ''
            );

            $query = $referrer->getQuery() . (in_array($referrer->getQuery(), ['', '0'], true) ? '' : '&') . $errorQuery;

            return new RedirectResponse($referrer->withQuery($query));
        }

        throw new UnknownErrorCodeException(sprintf('Error %s is unknown.', $errorCode), 1586000737);
    }

    protected function isUserLoggedIn(ServerRequestInterface $request): bool
    {
        try {
            // This is necessary as group data is not fetched to this time
            $request->getAttribute('frontend.user')->fetchGroupData();
            $context = GeneralUtility::makeInstance(Context::class);

            return (bool)$context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * @throws ArgumentException
     * @throws NetworkException
     * @throws ConfigurationException
     * @throws GuzzleException
     */
    protected function updateTypo3User(int $applicationId, array $user): void
    {
        // Get application record
        $application = BackendUtility::getRecord(ApplicationRepository::TABLE_NAME, $applicationId, 'api, uid');

        if ($application['api']) {
            $auth0 = ApplicationFactory::build($applicationId);
            $response = $auth0->management()->users()->get($user[GeneralUtility::makeInstance(EmAuth0Configuration::class)->getUserIdentifier()]);
            if (HttpResponse::wasSuccessful($response)) {
                $this->userUtility->enrichManagementUser(HttpResponse::decodeContent($response));
            }
        }

        // Update user
        $this->updateUtility->updateUser();
        $this->updateUtility->updateGroups();
    }

    protected function performRedirectFromPluginConfiguration(DataSet $tokenDataSet, array $allowedMethods): void
    {
        $redirectService = new RedirectService([
            'redirectDisable' => false,
            'redirectMode' => $tokenDataSet->get('redirectMode'),
            'redirectFirstMethod' => $tokenDataSet->get('redirectFirstMethod'),
            'redirectPageLogin' => $tokenDataSet->get('redirectPageLogin'),
            'redirectPageLoginError' => $tokenDataSet->get('redirectPageLoginError'),
            'redirectPageLogout' => $tokenDataSet->get('redirectPageLogout'),
        ]);

        $redirectService->handleRedirect($allowedMethods);
    }
}
