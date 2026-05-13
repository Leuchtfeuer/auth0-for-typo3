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
    public function getAuth0MappingForDatabaseFieldReturnsMappingWithRootConfigurationType(): void
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

        self::assertSame(
            ['configurationType' => 'root', 'auth0Property' => 'nickname'],
            $subject->getAuth0MappingForDatabaseField('be_users', 'username')
        );
        self::assertSame(
            ['configurationType' => 'root', 'auth0Property' => 'email_verified'],
            $subject->getAuth0MappingForDatabaseField('be_users', 'disable')
        );
    }

    #[Test]
    public function getAuth0MappingForDatabaseFieldExposesUserMetadataBucket(): void
    {
        $subject = $this->createSubjectWithMapping([
            'properties' => [
                'be_users' => [
                    'root' => [],
                    'user_metadata' => [
                        ['auth0Property' => 'login_name', 'databaseField' => 'username'],
                    ],
                ],
            ],
        ]);

        self::assertSame(
            ['configurationType' => 'user_metadata', 'auth0Property' => 'login_name'],
            $subject->getAuth0MappingForDatabaseField('be_users', 'username')
        );
    }

    #[Test]
    public function getAuth0MappingForDatabaseFieldHonoursOverriddenMapping(): void
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

        self::assertSame(
            ['configurationType' => 'root', 'auth0Property' => 'preferred_username'],
            $subject->getAuth0MappingForDatabaseField('be_users', 'username')
        );
    }

    #[Test]
    public function getAuth0MappingForDatabaseFieldReturnsNullWhenNoMappingExists(): void
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

        self::assertNull($subject->getAuth0MappingForDatabaseField('be_users', 'email'));
        self::assertNull($subject->getAuth0MappingForDatabaseField('unknown_table', 'username'));
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
