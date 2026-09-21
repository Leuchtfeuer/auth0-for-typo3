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

namespace Leuchtfeuer\Auth0\Tests\Functional\Fixtures;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;

/**
 * Answers the Auth0 endpoints the SDK talks to, so a functional test never
 * reaches the network. Requests are recorded so a test can assert what the SDK
 * actually sent.
 */
final class QueuedHttpClient implements ClientInterface
{
    /** @var list<RequestInterface> */
    private array $recordedRequests = [];

    /** @var array<string, array{0: int, 1: array<string, mixed>}> */
    private array $responsesByPathSuffix = [];

    /**
     * @param array<string, mixed> $payload
     */
    public function respondTo(string $pathSuffix, array $payload, int $status = 200): self
    {
        $this->responsesByPathSuffix[$pathSuffix] = [$status, $payload];

        return $this;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->recordedRequests[] = $request;
        $path = $request->getUri()->getPath();

        foreach ($this->responsesByPathSuffix as $suffix => [$status, $payload]) {
            if (str_ends_with($path, $suffix)) {
                return $this->jsonResponse($status, $payload);
            }
        }

        return $this->jsonResponse(404, ['error' => 'no stubbed response for ' . $path]);
    }

    /**
     * @return list<RequestInterface>
     */
    public function getRecordedRequests(): array
    {
        return $this->recordedRequests;
    }

    public function hasRequestedPathSuffix(string $pathSuffix): bool
    {
        foreach ($this->recordedRequests as $request) {
            if (str_ends_with($request->getUri()->getPath(), $pathSuffix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonResponse(int $status, array $payload): ResponseInterface
    {
        $body = new Stream('php://temp', 'rw');
        $body->write((string)json_encode($payload));
        $body->rewind();

        return (new Response($body, $status))->withHeader('Content-Type', 'application/json');
    }
}
