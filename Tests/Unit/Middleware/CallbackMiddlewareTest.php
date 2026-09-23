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

use Leuchtfeuer\Auth0\Exception\TokenException;
use Leuchtfeuer\Auth0\Middleware\CallbackMiddleware;
use Leuchtfeuer\Auth0\Utility\Database\UpdateUtilityFactory;
use Leuchtfeuer\Auth0\Utility\TokenUtility;
use Leuchtfeuer\Auth0\Utility\UserUtility;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;

class CallbackMiddlewareTest extends TestCase
{
    private const EXPECTED_CACHE_CONTROL = 'no-cache, no-store, must-revalidate, max-age=0';

    #[Test]
    public function requestOutsideTheCallbackPathIsPassedThroughUntouched(): void
    {
        $handlerResponse = new Response('php://temp', 204);
        $tokenUtility = self::createStub(TokenUtility::class);

        $subject = new CallbackMiddleware(
            self::createStub(UpdateUtilityFactory::class),
            self::createStub(UserUtility::class),
            $tokenUtility
        );

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

        $subject = new CallbackMiddleware(
            self::createStub(UpdateUtilityFactory::class),
            self::createStub(UserUtility::class),
            $tokenUtility
        );

        $response = $subject->process(
            $this->buildRequest(CallbackMiddleware::PATH, ['token' => 'nonsense']),
            $this->buildHandler()
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(self::EXPECTED_CACHE_CONTROL, $response->getHeaderLine('Cache-Control'));
        self::assertSame('no-cache', $response->getHeaderLine('Pragma'));
    }

    #[Test]
    public function tokenExceptionReturnsUnstorable(): void
    {
        $tokenUtility = self::createStub(TokenUtility::class);
        $tokenUtility->method('verifyToken')->willReturn(true);
        $tokenUtility->method('getToken')->willThrowException(new TokenException());

        $subject = new CallbackMiddleware(
            self::createStub(UpdateUtilityFactory::class),
            self::createStub(UserUtility::class),
            $tokenUtility
        );

        $response = $subject->process(
            $this->buildRequest(CallbackMiddleware::PATH, ['token' => 'valid']),
            $this->buildHandler()
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(self::EXPECTED_CACHE_CONTROL, $response->getHeaderLine('Cache-Control'));
        self::assertSame('no-cache', $response->getHeaderLine('Pragma'));
    }

    #[Test]
    public function cacheHeadersAreAddedToRedirectResponses(): void
    {
        $response = new Response('php://temp', 302);

        $subject = new CallbackMiddleware(
            self::createStub(UpdateUtilityFactory::class),
            self::createStub(UserUtility::class),
            self::createStub(TokenUtility::class)
        );

        // Use reflection to test the denyCaching method directly
        $reflection = new \ReflectionClass($subject);
        $method = $reflection->getMethod('denyCaching');
        $method->setAccessible(true);

        $result = $method->invoke($subject, $response);

        self::assertSame(self::EXPECTED_CACHE_CONTROL, $result->getHeaderLine('Cache-Control'));
        self::assertSame('no-cache', $result->getHeaderLine('Pragma'));
    }

    private function buildRequest(string $path = '/', array $queryParams = []): ServerRequestInterface
    {
        $request = new ServerRequest('https://example.org' . $path);
        if ($queryParams !== []) {
            $request = $request->withQueryParams($queryParams);
        }
        return $request;
    }

    private function buildHandler(?ResponseInterface $response = null): RequestHandlerInterface
    {
        $handler = self::createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response ?? new Response('php://temp', 204));
        return $handler;
    }
}
