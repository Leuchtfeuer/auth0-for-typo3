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

namespace Leuchtfeuer\Auth0\Tests\Unit\Configuration;

use Leuchtfeuer\Auth0\Configuration\Auth0Configuration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class Auth0ConfigurationTest extends TestCase
{
    #[Test]
    public function getAuth0PropertyForDatabaseFieldReturnsMappedAuth0Property(): void
    {
        $subject = $this->createSubjectWithMapping([
            'properties' => [
                'be_users' => [
                    'root' => [
                        ['auth0Property' => 'nickname', 'databaseField' => 'username'],
                        ['auth0Property' => 'email_verified', 'databaseField' => 'disable'],
                    ],
                    'user_metadata' => [],
                    'app_metadata' => [],
                ],
            ],
        ]);

        self::assertSame('nickname', $subject->getAuth0PropertyForDatabaseField('be_users', 'username'));
        self::assertSame('email_verified', $subject->getAuth0PropertyForDatabaseField('be_users', 'disable'));
    }

    #[Test]
    public function getAuth0PropertyForDatabaseFieldHonoursOverriddenMapping(): void
    {
        $subject = $this->createSubjectWithMapping([
            'properties' => [
                'be_users' => [
                    'root' => [
                        ['auth0Property' => 'preferred_username', 'databaseField' => 'username'],
                    ],
                ],
            ],
        ]);

        self::assertSame('preferred_username', $subject->getAuth0PropertyForDatabaseField('be_users', 'username'));
    }

    #[Test]
    public function getAuth0PropertyForDatabaseFieldReturnsNullWhenNoMappingExists(): void
    {
        $subject = $this->createSubjectWithMapping([
            'properties' => [
                'be_users' => [
                    'root' => [
                        ['auth0Property' => 'nickname', 'databaseField' => 'username'],
                    ],
                ],
            ],
        ]);

        self::assertNull($subject->getAuth0PropertyForDatabaseField('be_users', 'email'));
        self::assertNull($subject->getAuth0PropertyForDatabaseField('unknown_table', 'username'));
    }

    /**
     * @param array<string, mixed> $mapping
     */
    private function createSubjectWithMapping(array $mapping): Auth0Configuration
    {
        return new class ($mapping) extends Auth0Configuration {
            /** @param array<string, mixed> $mapping */
            public function __construct(private readonly array $mapping)
            {
                // Skip parent constructor: file system paths are irrelevant for unit tests.
            }

            public function load(): array
            {
                return $this->mapping;
            }
        };
    }
}
