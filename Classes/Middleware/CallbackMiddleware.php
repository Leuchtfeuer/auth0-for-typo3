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

namespace Leuchtfeuer\Auth0\Middleware;

use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\UnencryptedToken;
use Leuchtfeuer\Auth0\Exception\TokenException;
use Leuchtfeuer\Auth0\Factory\ApplicationFactory;
use Leuchtfeuer\Auth0\LoginProvider\Auth0Provider;
use Leuchtfeuer\Auth0\Utility\Database\UpdateUtilityFactory;
use Leuchtfeuer\Auth0\Utility\TokenUtility;
use Leuchtfeuer\Auth0\Utility\UserUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Response;

class CallbackMiddleware implements MiddlewareInterface
{
    public const PATH = '/auth0/callback';

    public const TOKEN_PARAMETER = 'token';

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

        $issuer = ($request->getAttribute('normalizedParams') ?? NormalizedParams::createFromServerParams($_SERVER))->getRequestHost();

        if (!$this->tokenUtility->verifyToken((string)($request->getQueryParams()[self::TOKEN_PARAMETER] ?? null), $issuer)) {
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

        return $this->handleBackendCallback($request, $dataSet, $issuer);
    }

    protected function handleBackendCallback(
        ServerRequestInterface $request,
        DataSet $dataSet,
        string $issuer,
    ): ResponseInterface {
        if ($dataSet->get('redirectUri') !== null) {
            return new RedirectResponse($dataSet->get('redirectUri'), 302);
        }

        $queryParams = $request->getQueryParams();
        $redirectUri = sprintf('%s/typo3/?loginProvider=%d', $issuer, Auth0Provider::LOGIN_PROVIDER);

        // Carry forward error information if Auth0 redirected back with an error
        if (!empty($queryParams['error'] ?? null) && !empty($queryParams['error_description'] ?? null)) {
            $redirectUri .= sprintf(
                '&error=%s&error_description=%s',
                rawurlencode($queryParams['error']),
                rawurlencode($queryParams['error_description'])
            );
            return new RedirectResponse($redirectUri, 302);
        }

        $code = $queryParams['code'] ?? null;
        $state = $queryParams['state'] ?? null;
        $applicationId = (int)($dataSet->get('application') ?? 0);

        if ($code === null || $code === '' || $state === null || $applicationId === 0) {
            return new RedirectResponse($redirectUri, 302);
        }

        $response = new RedirectResponse($redirectUri, 302);

        // The Auth0 SDK persists session state via setrawcookie(), which queues
        // Set-Cookie headers in PHP's global header buffer. TYPO3's response
        // emitter then calls header('Set-Cookie: ...', replace=true) for the
        // first PSR-7 Set-Cookie header it encounters (e.g. __Secure-typo3nonce
        // added by RequestTokenMiddleware) and wipes the buffer. Doing the OAuth
        // code exchange here and migrating the buffered cookies into the PSR-7
        // response keeps the emitter from discarding them.
        try {
            $auth0 = ApplicationFactory::build(
                $applicationId,
                ApplicationFactory::SESSION_PREFIX_BACKEND,
                $request
            );
            $auth0->exchange($issuer . self::PATH, $code, $state);
            $response = $this->migrateBufferedCookiesToResponse($response);
        } catch (\Throwable) {
            // Exchange failed; let Auth0Provider report a missing session as login failure.
        }

        return $response;
    }

    /**
     * Pulls Set-Cookie headers that were placed into PHP's header buffer (via
     * setrawcookie() from inside the Auth0 SDK) into the PSR-7 response and
     * removes them from the buffer, so the response emitter can manage them
     * alongside Set-Cookie headers added by other middlewares.
     */
    private function migrateBufferedCookiesToResponse(ResponseInterface $response): ResponseInterface
    {
        $prefix = 'set-cookie:';
        foreach (headers_list() as $header) {
            if (stripos($header, $prefix) !== 0) {
                continue;
            }
            $response = $response->withAddedHeader('Set-Cookie', trim(substr($header, strlen($prefix))));
        }
        header_remove('Set-Cookie');
        return $response;
    }
}
