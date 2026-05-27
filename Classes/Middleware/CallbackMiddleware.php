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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Response;

class CallbackMiddleware implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

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

        if ($code === null || $code === '' || $state === null || $state === '' || $applicationId === 0) {
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
        $preExchangeCookies = $this->captureBufferedSetCookieHeaders();
        try {
            $auth0 = ApplicationFactory::build(
                $applicationId,
                ApplicationFactory::SESSION_PREFIX_BACKEND,
                $request
            );
            $auth0->exchange($issuer . self::PATH, $code, $state);
            $response = $this->migrateBufferedCookiesToResponse($response, $preExchangeCookies);
        } catch (\Throwable $throwable) {
            $this->logger?->warning(
                'Auth0 OAuth code exchange failed in CallbackMiddleware.',
                ['exception' => $throwable]
            );
        }

        return $response;
    }

    /**
     * @return list<string>
     */
    private function captureBufferedSetCookieHeaders(): array
    {
        $prefix = 'set-cookie:';
        $cookies = [];
        foreach (headers_list() as $header) {
            if (stripos($header, $prefix) === 0) {
                $cookies[] = $header;
            }
        }
        return $cookies;
    }

    /**
     * Moves Set-Cookie headers that were queued in PHP's header buffer since
     * $preExchangeCookies was captured into the PSR-7 response. Cookies that
     * were already present before the exchange stay in the header buffer.
     *
     * @param list<string> $preExchangeCookies
     */
    private function migrateBufferedCookiesToResponse(
        ResponseInterface $response,
        array $preExchangeCookies,
    ): ResponseInterface {
        $prefix = 'set-cookie:';
        $remaining = array_count_values($preExchangeCookies);
        foreach (headers_list() as $header) {
            if (stripos($header, $prefix) !== 0) {
                continue;
            }
            if (($remaining[$header] ?? 0) > 0) {
                $remaining[$header]--;
                continue;
            }
            $response = $response->withAddedHeader('Set-Cookie', trim(substr($header, strlen($prefix))));
        }
        header_remove('Set-Cookie');
        // Re-emit pre-existing Set-Cookie headers with replace=false. The original
        // replace semantics are not preserved because the preceding TYPO3 middlewares
        // queue distinct, additive cookies; no same-name overwrite is expected here.
        foreach ($preExchangeCookies as $header) {
            header($header, false);
        }
        return $response;
    }
}
