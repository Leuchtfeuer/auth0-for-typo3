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

namespace Leuchtfeuer\Auth0\Tests\Functional\Middleware;

use Auth0\SDK\Auth0;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha256 as HmacSha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256 as RsaSha256;
use Lcobucci\JWT\Token\Builder;
use Leuchtfeuer\Auth0\Domain\Repository\ApplicationRepository;
use Leuchtfeuer\Auth0\Factory\ApplicationFactory;
use Leuchtfeuer\Auth0\Middleware\CallbackMiddleware;
use Leuchtfeuer\Auth0\Tests\Functional\Fixtures\QueuedHttpClient;
use Leuchtfeuer\Auth0\Tests\Functional\Fixtures\RecordingLogger;
use Leuchtfeuer\Auth0\Tests\Functional\Fixtures\RsaKeyPair;
use Leuchtfeuer\Auth0\Utility\Database\UpdateUtilityFactory;
use Leuchtfeuer\Auth0\Utility\TokenUtility;
use Leuchtfeuer\Auth0\Utility\UserUtility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Drives the callback against a real database, the real Auth0 SDK and the real
 * cookie encryption. Only the outbound HTTP is stubbed.
 *
 * What this test cannot observe: the SDK persists its session through
 * `setrawcookie()`, and `CallbackMiddleware` migrates those buffered headers
 * into the PSR-7 response. Under the CLI SAPI `setrawcookie()` is a no-op and
 * `headers_list()` stays empty, so the migrated `Set-Cookie` headers never
 * appear on the response inside a test process. Asserted instead is the effect
 * that migration exists to produce: the session state is written, and a
 * following request resolves it.
 */
class CallbackMiddlewareTest extends FunctionalTestCase
{
    private const HOST = 'https://www.example.com';

    private const CLIENT_ID = 'test-client-id';

    private const TENANT = 'tenant.example.com';

    // HMAC signing requires at least 256 bits of key material.
    private const CLIENT_SECRET = 'test-client-secret-with-at-least-256-bits';

    /** Fixture record signing with a key pair. */
    private const APPLICATION_RS256 = 1;

    /** Fixture record signing with the client secret. */
    private const APPLICATION_HS256 = 2;

    protected array $testExtensionsToLoad = [
        'leuchtfeuer/auth0',
    ];

    private QueuedHttpClient $httpClient;

    private RsaKeyPair $keyPair;

    private RecordingLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/application.csv');

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['auth0']['backendConnection'] = '1';
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['auth0']['privateKeyFile'] = '';
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['auth0']['publicKeyFile'] = '';

        $this->keyPair = new RsaKeyPair(self::TENANT);
        $this->httpClient = new QueuedHttpClient();
        $this->httpClient->respondTo('/.well-known/jwks.json', $this->keyPair->toJwks());
        $this->logger = new RecordingLogger();

        $_COOKIE = [];
    }

    protected function tearDown(): void
    {
        $_COOKIE = [];

        parent::tearDown();
    }

    /**
     * The algorithm named on the record decides how the identity token is
     * verified. RS256 is checked against the tenant's published keys, HS256
     * against the client secret and without consulting them at all.
     */
    #[Test]
    #[DataProvider('applicationProvider')]
    public function aSuccessfulExchangeEstablishesTheSessionAndForbidsCaching(
        int $applicationUid,
        bool $expectsJwksLookup,
    ): void {
        $nonce = $this->startLoginAndReturnNonce($applicationUid);

        $this->httpClient->respondTo('/oauth/token', [
            'access_token' => 'an-access-token',
            'id_token' => $this->buildIdToken($nonce, $applicationUid),
            'token_type' => 'Bearer',
            'expires_in' => 86400,
            'scope' => 'openid profile read:current_user',
        ]);

        $response = $this->runCallback([
            'code' => 'an-authorization-code',
            'state' => $this->stateFromTransientStorage($applicationUid),
        ], $applicationUid);

        $this->assertNothingWasSwallowed();
        self::assertSame(302, $response->getStatusCode());
        self::assertTrue($this->httpClient->hasRequestedPathSuffix('/oauth/token'));
        self::assertStringNotContainsString('error=', $response->getHeaderLine('Location'));
        $this->assertResponseIsUnstorable($response);

        self::assertSame(
            $expectsJwksLookup,
            $this->httpClient->hasRequestedPathSuffix('/.well-known/jwks.json'),
            'Whether the tenant keys are consulted must follow the configured algorithm.'
        );

        $user = $this->buildAuth0($applicationUid)->configuration()->getSessionStorage()?->get('user');
        self::assertIsArray($user);
        self::assertSame(
            'auth0|functional-test-user',
            $user['sub'] ?? null,
            'The Auth0 session was not persisted, so a following request would not be authenticated.'
        );
    }

    /**
     * @return array<string, array{0: int, 1: bool}>
     */
    public static function applicationProvider(): array
    {
        return [
            'tenant signing with a key pair' => [self::APPLICATION_RS256, true],
            'tenant signing with a shared secret' => [self::APPLICATION_HS256, false],
        ];
    }

    #[Test]
    public function anIdentityTokenSignedWithTheWrongAlgorithmIsRejected(): void
    {
        // The record expects a key pair, the tenant answers with a shared secret.
        $nonce = $this->startLoginAndReturnNonce(self::APPLICATION_RS256);

        $this->httpClient->respondTo('/oauth/token', [
            'access_token' => 'an-access-token',
            'id_token' => $this->buildIdToken($nonce, self::APPLICATION_HS256),
            'token_type' => 'Bearer',
            'expires_in' => 86400,
            'scope' => 'openid profile read:current_user',
        ]);

        $response = $this->runCallback([
            'code' => 'an-authorization-code',
            'state' => $this->stateFromTransientStorage(self::APPLICATION_RS256),
        ], self::APPLICATION_RS256);

        self::assertStringContainsString(
            'error=' . CallbackMiddleware::ERROR_EXCHANGE_FAILED,
            $response->getHeaderLine('Location')
        );
        self::assertSame(
            [],
            $this->buildAuth0(self::APPLICATION_RS256)->configuration()->getSessionStorage()?->get('user') ?? [],
            'A token signed with the wrong algorithm must not establish a session.'
        );
    }

    #[Test]
    public function aFailingExchangeReportsTheErrorAndForbidsCaching(): void
    {
        $this->startLoginAndReturnNonce();

        $this->httpClient->respondTo('/oauth/token', ['error' => 'invalid_grant'], 403);

        $response = $this->runCallback([
            'code' => 'an-authorization-code',
            'state' => $this->stateFromTransientStorage(),
        ]);

        self::assertSame(302, $response->getStatusCode());
        self::assertStringContainsString(
            'error=' . CallbackMiddleware::ERROR_EXCHANGE_FAILED,
            $response->getHeaderLine('Location')
        );
        self::assertNotNull(
            $this->logger->getLoggedException(),
            'The failure must reach the log, not only the login screen.'
        );
        self::assertSame('error', $this->logger->getLastLevel());
        $this->assertResponseIsUnstorable($response);

        self::assertSame(
            [],
            $this->buildAuth0()->configuration()->getSessionStorage()?->get('user') ?? [],
            'A failed exchange must not leave a session behind.'
        );
    }

    #[Test]
    public function aStateThatWasNeverIssuedIsRejectedBeforeTheCodeIsExchanged(): void
    {
        $this->startLoginAndReturnNonce();

        $response = $this->runCallback([
            'code' => 'an-authorization-code',
            'state' => 'a-state-that-was-never-issued',
        ]);

        self::assertStringContainsString(
            'error=' . CallbackMiddleware::ERROR_EXCHANGE_FAILED,
            $response->getHeaderLine('Location')
        );
        self::assertFalse(
            $this->httpClient->hasRequestedPathSuffix('/oauth/token'),
            'A mismatched state must be rejected before the code is exchanged.'
        );
        $this->assertResponseIsUnstorable($response);
    }

    #[Test]
    public function aCallbackWithoutAnAuthorizationCodeIsAlsoUnstorable(): void
    {
        $response = $this->runCallback([]);

        self::assertSame(302, $response->getStatusCode());
        self::assertFalse($this->httpClient->hasRequestedPathSuffix('/oauth/token'));
        $this->assertResponseIsUnstorable($response);
    }

    #[Test]
    public function anUnverifiableTokenIsRejectedAndTheRejectionIsUnstorable(): void
    {
        $response = $this->buildSubject()->process(
            $this->buildRequest(['token' => 'not-a-valid-token']),
            $this->buildHandler()
        );

        self::assertSame(400, $response->getStatusCode());
        $this->assertResponseIsUnstorable($response);
    }

    private function assertResponseIsUnstorable(ResponseInterface $response): void
    {
        self::assertSame(
            'no-cache, no-store, must-revalidate, max-age=0',
            $response->getHeaderLine('Cache-Control')
        );
        self::assertSame('no-cache', $response->getHeaderLine('Pragma'));
    }

    /**
     * The middleware reports failures to the log and returns a redirect either
     * way, so a broken expectation would otherwise surface as a confusing
     * assertion about a URL. This turns it into the actual cause.
     */
    private function assertNothingWasSwallowed(): void
    {
        $exception = $this->logger->getLoggedException();

        if ($exception instanceof \Throwable) {
            self::fail(sprintf(
                'The exchange failed with %s: %s',
                $exception::class,
                $exception->getMessage()
            ));
        }
    }

    /**
     * Lets the SDK establish state, nonce and PKCE verifier the way the login
     * provider does, rather than hand-crafting an encrypted transient cookie.
     */
    private function startLoginAndReturnNonce(int $applicationUid = self::APPLICATION_RS256): string
    {
        $authorizeUrl = $this->buildAuth0($applicationUid)->login(self::HOST . CallbackMiddleware::PATH);
        parse_str((string)parse_url($authorizeUrl, PHP_URL_QUERY), $params);

        self::assertArrayHasKey('nonce', $params);

        return (string)$params['nonce'];
    }

    private function stateFromTransientStorage(int $applicationUid = self::APPLICATION_RS256): string
    {
        return (string)$this->buildAuth0($applicationUid)->configuration()->getTransientStorage()?->get('state');
    }

    private function buildIdToken(string $nonce, int $applicationUid = self::APPLICATION_RS256): string
    {
        $now = new \DateTimeImmutable();
        $usesSharedSecret = $applicationUid === self::APPLICATION_HS256;

        $builder = (new Builder(new JoseEncoder(), ChainedFormatter::default()))
            ->issuedBy('https://' . self::TENANT . '/')
            ->permittedFor(self::CLIENT_ID)
            ->relatedTo('auth0|functional-test-user')
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 hour'))
            ->withClaim('nonce', $nonce)
            ->withClaim('nickname', 'Functional Test User')
            ->withClaim('email', 'functional@example.com');

        if ($usesSharedSecret) {
            return $builder
                ->getToken(new HmacSha256(), InMemory::plainText(self::CLIENT_SECRET))
                ->toString();
        }

        return $builder
            ->withHeader('kid', RsaKeyPair::KEY_ID)
            ->getToken(new RsaSha256(), InMemory::plainText($this->keyPair->getPrivateKeyPem()))
            ->toString();
    }

    /**
     * @param array<string, string> $queryParams
     */
    private function runCallback(array $queryParams, int $applicationUid = self::APPLICATION_RS256): ResponseInterface
    {
        $tokenUtility = $this->get(TokenUtility::class);
        $tokenUtility->withPayload('environment', TokenUtility::ENVIRONMENT_BACKEND);
        $tokenUtility->withPayload('application', $applicationUid);
        $token = $tokenUtility->buildToken(self::HOST)->toString();

        return $this->buildSubject()->process(
            $this->buildRequest($queryParams + ['token' => $token]),
            $this->buildHandler()
        );
    }

    private function buildSubject(): CallbackMiddleware
    {
        $subject = new CallbackMiddleware(
            $this->get(UpdateUtilityFactory::class),
            $this->get(UserUtility::class),
            $this->get(TokenUtility::class),
            $this->buildApplicationFactory(),
        );
        $subject->setLogger($this->logger);

        return $subject;
    }

    private function buildApplicationFactory(): ApplicationFactory
    {
        return new ApplicationFactory(
            $this->get(ApplicationRepository::class),
            $this->get(RequestFactory::class),
            $this->get(ResponseFactory::class),
            $this->get(StreamFactory::class),
            $this->httpClient,
        );
    }

    private function buildAuth0(int $applicationUid = self::APPLICATION_RS256): Auth0
    {
        return $this->buildApplicationFactory()->create(
            $applicationUid,
            ApplicationFactory::SESSION_PREFIX_BACKEND,
            $this->buildRequest([])
        );
    }

    /**
     * @param array<string, string> $queryParams
     */
    private function buildRequest(array $queryParams): ServerRequestInterface
    {
        $serverParams = [
            'HTTP_HOST' => 'www.example.com',
            'HTTPS' => 'on',
            'SCRIPT_NAME' => '/index.php',
        ];

        return (new ServerRequest(self::HOST . CallbackMiddleware::PATH, 'GET', 'php://input', [], $serverParams))
            ->withQueryParams($queryParams)
            ->withAttribute('normalizedParams', new NormalizedParams($serverParams, [], '', ''));
    }

    private function buildHandler(): RequestHandlerInterface
    {
        $handler = self::createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response('php://temp', 200));

        return $handler;
    }
}
