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
use GuzzleHttp\Exception\GuzzleException;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\UnencryptedToken;
use Leuchtfeuer\Auth0\Exception\TokenException;
use Leuchtfeuer\Auth0\Exception\UnknownErrorCodeException;
use Leuchtfeuer\Auth0\LoginProvider\Auth0Provider;
use Leuchtfeuer\Auth0\Utility\Database\UpdateUtilityFactory;
use Leuchtfeuer\Auth0\Utility\TokenUtility;
use Leuchtfeuer\Auth0\Utility\UserUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Response;

class CallbackMiddleware implements MiddlewareInterface
{
    public const PATH = '/auth0/callback';

    public const TOKEN_PARAMETER = 'token';

    private const BACKEND_URI = '%s/typo3/?loginProvider=%d&code=%s&state=%s';

    public function __construct(
        protected readonly UpdateUtilityFactory $updateUtilityFactory,
        protected readonly UserUtility $userUtility,
        protected readonly TokenUtility $tokenUtility,
    ) {}

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
            $token = $this->tokenUtility->getToken();
            if (!$token instanceof UnencryptedToken) {
                throw new TokenException();
            }
            $dataSet = $token->claims();
        } catch (TokenException) {
            return new Response('php://temp', 400);
        }

        return $this->handleBackendCallback($request, $dataSet);
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
                '&error=%s&error_description=%s',
                $request->getQueryParams()['error'],
                $request->getQueryParams()['error_description']
            );
        }

        return new RedirectResponse($redirectUri, 302);
    }
}
