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

namespace Leuchtfeuer\Auth0\Tests\Unit\Middleware;

use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\UnencryptedToken;
use Leuchtfeuer\Auth0\Factory\ApplicationFactory;
use Leuchtfeuer\Auth0\LoginProvider\Auth0Provider;
use Leuchtfeuer\Auth0\Middleware\CallbackMiddleware;
use Leuchtfeuer\Auth0\Utility\Database\UpdateUtilityFactory;
use Leuchtfeuer\Auth0\Utility\TokenUtility;
use Leuchtfeuer\Auth0\Utility\UserUtility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;

class CallbackMiddlewareTest extends TestCase
{
    private const ISSUER = 'https://www.example.com';

    private const EXPECTED_CACHE_CONTROL = 'no-cache, no-store, must-revalidate, max-age=0';

    #[Test]
    public function requestOutsideTheCallbackPathIsPassedThroughUntouched(): void
    {
        $handlerResponse = new Response('php://temp', 204);
        $subject = $this->buildSubject();

        $response = $subject->process(
            $this->buildRequest('/some/other/path'),
            $this->buildHandler($handlerResponse)
        );

        self::assertSame($handlerResponse, $response);
        self::assertFalse($response->hasHeader('Cache-Control'));
    }

    #[Test]
    public function anUnverifiableTokenIsRejectedAndTheRejectionIsNotCacheable(): void
    {
        $tokenUtility = self::createStub(TokenUtility::class);
        $tokenUtility->method('verifyToken')->willReturn(false);

        $response = $this->buildSubject($tokenUtility)->process(
            $this->buildRequest(CallbackMiddleware::PATH, ['token' => 'nonsense']),
            $this->buildHandler()
        );

        self::assertSame(400, $response->getStatusCode());
        $this->assertResponseIsUnstorable($response);
    }

    /**
     * @param array<string, string> $queryParams
     * @param array<string, mixed> $claims
     */
    #[Test]
    #[DataProvider('redirectingOutcomesProvider')]
    public function everyRedirectingOutcomeIsUnstorable(array $queryParams, array $claims): void
    {
        $response = $this->buildSubject(
            $this->buildTokenUtility($claims)
        )->process(
            $this->buildRequest(CallbackMiddleware::PATH, $queryParams + ['token' => 'valid']),
            $this->buildHandler()
        );

        self::assertSame(302, $response->getStatusCode());
        $this->assertResponseIsUnstorable($response);
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: array<string, mixed>}>
     */
    public static function redirectingOutcomesProvider(): array
    {
        return [
            'redirect uri claim' => [
                [],
                ['redirectUri' => self::ISSUER . '/typo3/logout'],
            ],
            'error reported by Auth0' => [
                ['error' => 'access_denied', 'error_description' => 'Denied'],
                ['application' => 1],
            ],
            'authorization code missing' => [
                ['state' => 'somestate'],
                ['application' => 1],
            ],
            'state missing' => [
                ['code' => 'somecode'],
                ['application' => 1],
            ],
            'application claim missing' => [
                ['code' => 'somecode', 'state' => 'somestate'],
                [],
            ],
        ];
    }

    #[Test]
    public function anErrorReportedByAuth0IsCarriedForwardUnchanged(): void
    {
        $response = $this->buildSubject(
            $this->buildTokenUtility(['application' => 1])
        )->process(
            $this->buildRequest(CallbackMiddleware::PATH, [
                'token' => 'valid',
                'error' => 'access_denied',
                'error_description' => 'User denied access',
            ]),
            $this->buildHandler()
        );

        $location = $response->getHeaderLine('Location');
        self::assertStringContainsString('error=access_denied', $location);
        self::assertStringContainsString('error_description=User%20denied%20access', $location);
        self::assertStringNotContainsString(CallbackMiddleware::ERROR_EXCHANGE_FAILED, $location);
    }

    #[Test]
    public function aFailingExchangeTellsTheLoginScreenAndIsLoggedAsAnError(): void
    {
        $applicationFactory = self::createStub(ApplicationFactory::class);
        $applicationFactory->method('create')->willThrowException(new \RuntimeException('exchange broke'));

        $logger = self::createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                self::stringContains('code exchange failed'),
                self::callback(static fn(array $context): bool => $context['exception'] instanceof \Throwable)
            );

        $subject = $this->buildSubject($this->buildTokenUtility(['application' => 1]), $applicationFactory);
        $subject->setLogger($logger);

        $response = $subject->process(
            $this->buildRequest(CallbackMiddleware::PATH, [
                'token' => 'valid',
                'code' => 'somecode',
                'state' => 'somestate',
            ]),
            $this->buildHandler()
        );

        $location = $response->getHeaderLine('Location');
        self::assertSame(302, $response->getStatusCode());
        self::assertStringContainsString(
            'loginProvider=' . Auth0Provider::LOGIN_PROVIDER,
            $location
        );
        self::assertStringContainsString('error=' . CallbackMiddleware::ERROR_EXCHANGE_FAILED, $location);
        $this->assertResponseIsUnstorable($response);
    }

    #[Test]
    public function aFailingExchangeDoesNotLeakTheExceptionMessageIntoTheRedirect(): void
    {
        $applicationFactory = self::createStub(ApplicationFactory::class);
        $applicationFactory->method('create')
            ->willThrowException(new \RuntimeException('client secret rejected by tenant'));

        $subject = $this->buildSubject($this->buildTokenUtility(['application' => 1]), $applicationFactory);

        $response = $subject->process(
            $this->buildRequest(CallbackMiddleware::PATH, [
                'token' => 'valid',
                'code' => 'somecode',
                'state' => 'somestate',
            ]),
            $this->buildHandler()
        );

        self::assertStringNotContainsString('client secret', $response->getHeaderLine('Location'));
    }

    /**
     * The successful exchange needs the SDK's final Auth0 class and is covered
     * functionally. What matters here is that the cache headers leave the
     * migrated Set-Cookie headers alone.
     */
    #[Test]
    public function addingTheCacheHeadersLeavesSessionCookiesInPlace(): void
    {
        $subject = new class (
            self::createStub(UpdateUtilityFactory::class),
            self::createStub(UserUtility::class),
            self::createStub(TokenUtility::class),
            self::createStub(ApplicationFactory::class),
        ) extends CallbackMiddleware {
            public function denyCachingOf(ResponseInterface $response): ResponseInterface
            {
                return $this->denyCaching($response);
            }
        };

        $response = $subject->denyCachingOf(
            (new Response('php://temp', 302))
                ->withAddedHeader('Set-Cookie', 'auth0_session_BE_0=first; path=/; HttpOnly')
                ->withAddedHeader('Set-Cookie', 'auth0_session_BE_1=second; path=/; HttpOnly')
        );

        self::assertSame(
            [
                'auth0_session_BE_0=first; path=/; HttpOnly',
                'auth0_session_BE_1=second; path=/; HttpOnly',
            ],
            $response->getHeader('Set-Cookie')
        );
        $this->assertResponseIsUnstorable($response);
    }

    private function assertResponseIsUnstorable(ResponseInterface $response): void
    {
        self::assertSame(self::EXPECTED_CACHE_CONTROL, $response->getHeaderLine('Cache-Control'));
        self::assertSame('no-cache', $response->getHeaderLine('Pragma'));
    }

    private function buildSubject(
        ?TokenUtility $tokenUtility = null,
        ?ApplicationFactory $applicationFactory = null,
    ): CallbackMiddleware {
        return new CallbackMiddleware(
            self::createStub(UpdateUtilityFactory::class),
            self::createStub(UserUtility::class),
            $tokenUtility ?? self::createStub(TokenUtility::class),
            $applicationFactory ?? self::createStub(ApplicationFactory::class),
        );
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function buildTokenUtility(array $claims): TokenUtility
    {
        $token = self::createStub(UnencryptedToken::class);
        $token->method('claims')->willReturn(new DataSet($claims, ''));

        $tokenUtility = self::createStub(TokenUtility::class);
        $tokenUtility->method('verifyToken')->willReturn(true);
        $tokenUtility->method('getToken')->willReturn($token);

        return $tokenUtility;
    }

    /**
     * @param array<string, string> $queryParams
     */
    private function buildRequest(string $path, array $queryParams = []): ServerRequestInterface
    {
        $serverParams = [
            'HTTP_HOST' => 'www.example.com',
            'HTTPS' => 'on',
            'SCRIPT_NAME' => '/index.php',
        ];

        return (new ServerRequest(self::ISSUER . $path, 'GET', 'php://input', [], $serverParams))
            ->withQueryParams($queryParams)
            ->withAttribute('normalizedParams', new NormalizedParams($serverParams, [], '', ''));
    }

    private function buildHandler(?ResponseInterface $response = null): RequestHandlerInterface
    {
        $handler = self::createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response ?? new Response('php://temp', 200));

        return $handler;
    }
}
