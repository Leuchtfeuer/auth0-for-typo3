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

namespace Leuchtfeuer\Auth0\Tests\Unit\Factory;

use GuzzleHttp\Client;
use Leuchtfeuer\Auth0\Domain\Model\Application;
use Leuchtfeuer\Auth0\Domain\Repository\ApplicationRepository;
use Leuchtfeuer\Auth0\Factory\ApplicationFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The Auth0 SDK resolves the PSR-17 factories and the PSR-18 client through
 * psr-discovery whenever they are not passed in, and that library gates its
 * candidates on hardcoded package constraints rather than on the classes that
 * are actually loadable. These tests pin that the extension passes all four in
 * explicitly, so no installation can end up without them.
 */
class ApplicationFactoryTest extends TestCase
{
    private const APPLICATION_UID = 1;

    private RequestFactory $requestFactory;
    private ResponseFactory $responseFactory;
    private StreamFactory $streamFactory;
    private ClientInterface $httpClient;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = str_repeat('a', 32);
        $GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] = false;
        $GLOBALS['TYPO3_CONF_VARS']['HTTP'] = ['verify' => true];

        $this->requestFactory = new RequestFactory(new GuzzleClientFactory());
        $this->responseFactory = new ResponseFactory();
        $this->streamFactory = new StreamFactory();
        $this->httpClient = new Client();
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();

        parent::tearDown();
    }

    #[Test]
    public function createPassesAllFourHttpImplementationsIntoTheSdkConfiguration(): void
    {
        $configuration = $this->buildSubject()->create(self::APPLICATION_UID, ApplicationFactory::SESSION_PREFIX_BACKEND, $this->createRequest())->configuration();

        self::assertSame($this->requestFactory, $configuration->getHttpRequestFactory());
        self::assertSame($this->responseFactory, $configuration->getHttpResponseFactory());
        self::assertSame($this->streamFactory, $configuration->getHttpStreamFactory());
        self::assertSame($this->httpClient, $configuration->getHttpClient());
    }

    #[Test]
    public function createLeavesNoHttpImplementationToDiscovery(): void
    {
        $configuration = $this->buildSubject()->create(self::APPLICATION_UID, ApplicationFactory::SESSION_PREFIX_BACKEND, $this->createRequest())->configuration();

        self::assertTrue($configuration->hasHttpRequestFactory());
        self::assertTrue($configuration->hasHttpResponseFactory());
        self::assertTrue($configuration->hasHttpStreamFactory());
        self::assertTrue($configuration->hasHttpClient());
    }

    /**
     * `SdkConfiguration` skips keys it does not know without complaining, so a
     * misspelled one silently leaves the default in place. This pins the spelling.
     */
    #[Test]
    #[DataProvider('signatureAlgorithmProvider')]
    public function createPassesTheApplicationsSignatureAlgorithmIntoTheSdkConfiguration(string $algorithm): void
    {
        $configuration = $this->buildSubject($this->createApplicationRepository($algorithm))
            ->create(self::APPLICATION_UID, ApplicationFactory::SESSION_PREFIX_BACKEND, $this->createRequest())
            ->configuration();

        self::assertSame($algorithm, $configuration->getTokenAlgorithm());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function signatureAlgorithmProvider(): array
    {
        return [
            'key pair' => [Application::ALG_RS256],
            'shared secret' => [Application::ALG_HS256],
        ];
    }

    #[Test]
    public function deprecatedStaticBuildDelegatesToCreate(): void
    {
        $subject = $this->buildSubject();
        GeneralUtility::addInstance(ApplicationFactory::class, $subject);

        $configuration = ApplicationFactory::build(self::APPLICATION_UID, ApplicationFactory::SESSION_PREFIX_BACKEND, $this->createRequest())->configuration();

        self::assertSame($this->requestFactory, $configuration->getHttpRequestFactory());
        self::assertSame($this->responseFactory, $configuration->getHttpResponseFactory());
        self::assertSame($this->streamFactory, $configuration->getHttpStreamFactory());
        self::assertSame($this->httpClient, $configuration->getHttpClient());
    }

    #[Test]
    public function createThrowsWhenApplicationIsUnknown(): void
    {
        $repository = self::createStub(ApplicationRepository::class);
        $repository->method('findByUid')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Application not found: ' . self::APPLICATION_UID);

        $this->buildSubject($repository)->create(self::APPLICATION_UID, ApplicationFactory::SESSION_PREFIX_BACKEND, $this->createRequest());
    }

    private function buildSubject(?ApplicationRepository $repository = null): ApplicationFactory
    {
        return new ApplicationFactory(
            $repository ?? $this->createApplicationRepository(),
            $this->requestFactory,
            $this->responseFactory,
            $this->streamFactory,
            $this->httpClient,
        );
    }

    private function createApplicationRepository(string $algorithm = Application::ALG_RS256): ApplicationRepository
    {
        $application = self::createStub(Application::class);
        $application->method('hasApi')->willReturn(false);
        $application->method('getAudience')->willReturn('https://example.eu.auth0.com/api/v2/');
        $application->method('getClientId')->willReturn('someClientId');
        $application->method('getClientSecret')->willReturn('someClientSecret');
        $application->method('getDomain')->willReturn('example.eu.auth0.com');
        $application->method('getSignatureAlgorithm')->willReturn($algorithm);

        $repository = self::createStub(ApplicationRepository::class);
        $repository->method('findByUid')->willReturn($application);

        return $repository;
    }

    private function createRequest(): ServerRequestInterface
    {
        $normalizedParams = self::createStub(NormalizedParams::class);
        $normalizedParams->method('getRequestHost')->willReturn('https://example.org');

        $request = self::createStub(ServerRequestInterface::class);
        $request->method('getAttribute')->willReturn($normalizedParams);

        return $request;
    }
}
