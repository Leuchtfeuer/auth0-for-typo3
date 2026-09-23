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

namespace Leuchtfeuer\Auth0\Tests\Unit\Domain\Model;

use Leuchtfeuer\Auth0\Domain\Model\Application;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    /**
     * The SDK accepts RS256 and HS256 and throws for anything else, so a record
     * carrying neither must not produce a connection that cannot be built.
     */
    #[Test]
    #[DataProvider('signatureAlgorithmProvider')]
    public function theSignatureAlgorithmIsNormalisedToSomethingTheSdkAccepts(
        string $configured,
        string $expected,
    ): void {
        $application = (new Application())->setSignatureAlgorithm($configured);

        self::assertSame($expected, $application->getSignatureAlgorithm());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function signatureAlgorithmProvider(): array
    {
        return [
            'key pair is kept' => [Application::ALG_RS256, Application::ALG_RS256],
            'shared secret is kept' => [Application::ALG_HS256, Application::ALG_HS256],
            'empty falls back' => ['', Application::ALG_RS256],
            'unknown algorithm falls back' => ['ES256', Application::ALG_RS256],
            'wrong case falls back' => ['hs256', Application::ALG_RS256],
        ];
    }

    #[Test]
    public function anApplicationWithoutAnyAlgorithmDefaultsToTheKeyPair(): void
    {
        self::assertSame(Application::ALG_RS256, (new Application())->getSignatureAlgorithm());
    }

    /** `fromArray()` passes the raw column through, including an empty one. */
    #[Test]
    public function aRecordWithAnEmptyAlgorithmColumnStillYieldsAUsableApplication(): void
    {
        $application = Application::fromArray([
            'title' => 'Test',
            'id' => 'client-id',
            'secret' => 'client-secret',
            'domain' => 'tenant.example.com',
            'audience' => 'api/v2/',
            'single_log_out' => 0,
            'signature_algorithm' => '',
            'api' => 0,
        ]);

        self::assertSame(Application::ALG_RS256, $application->getSignatureAlgorithm());
    }
}
