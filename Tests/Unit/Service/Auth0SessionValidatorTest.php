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

namespace Leuchtfeuer\Auth0\Tests\Unit\Service;

use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use Leuchtfeuer\Auth0\Service\Auth0SessionValidator;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Test case for Auth0SessionValidator
 *
 * Note: Some test methods require functional testing with real TYPO3 environment
 * due to ApplicationFactory static calls and $GLOBALS['BE_USER'] dependency.
 */
class Auth0SessionValidatorTest extends TestCase
{
    protected Auth0SessionValidator $subject;
    protected EmAuth0Configuration $configuration;
    protected ?BackendUserAuthentication $originalBackendUser = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configuration = $this->createMock(EmAuth0Configuration::class);

        $this->subject = new Auth0SessionValidator($this->configuration);

        // Store original BE_USER if exists
        $this->originalBackendUser = $GLOBALS['BE_USER'] ?? null;
    }

    protected function tearDown(): void
    {
        // Restore original BE_USER
        if ($this->originalBackendUser !== null) {
            $GLOBALS['BE_USER'] = $this->originalBackendUser;
        } else {
            unset($GLOBALS['BE_USER']);
        }

        parent::tearDown();
    }

    /**
     * @test
     */
    public function testReturnsFalseWhenNoBackendUser(): void
    {
        unset($GLOBALS['BE_USER']);

        $result = $this->subject->hasValidAuth0Session();

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function testReturnsFalseWhenUserNotAuth0User(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = [
            'uid' => 1,
            'username' => 'admin',
            'auth0_user_id' => '', // Empty Auth0 ID
        ];

        $GLOBALS['BE_USER'] = $backendUser;

        $result = $this->subject->hasValidAuth0Session();

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function testReturnsFalseWhenAuth0UserIdIsNull(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = [
            'uid' => 1,
            'username' => 'regular_user',
            // auth0_user_id key doesn't exist
        ];

        $GLOBALS['BE_USER'] = $backendUser;

        $result = $this->subject->hasValidAuth0Session();

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function testReturnsTrueWhenAuth0SessionIsValid(): void
    {
        // Setup backend user with Auth0 authentication
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = [
            'uid' => 2,
            'username' => 'auth0_user',
            'auth0_user_id' => 'auth0|123456789',
        ];
        $GLOBALS['BE_USER'] = $backendUser;

        // Create partial mock to stub the hasAuth0Session method
        // This avoids calling ApplicationFactory::build() which requires functional environment
        $validatorMock = $this->getMockBuilder(Auth0SessionValidator::class)
            ->setConstructorArgs([$this->configuration])
            ->onlyMethods(['hasAuth0Session'])
            ->getMock();

        // Stub hasAuth0Session to return true (simulating valid Auth0 session)
        $validatorMock->expects(self::once())
            ->method('hasAuth0Session')
            ->willReturn(true);

        // Call the method
        $result = $validatorMock->hasValidAuth0Session();

        // Verify returns true when Auth0 session is valid
        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function testReturnsFalseWhenAuth0SessionCheckFails(): void
    {
        // Setup backend user with Auth0 authentication
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = [
            'uid' => 3,
            'username' => 'auth0_session_fail_user',
            'auth0_user_id' => 'auth0|987654321',
        ];
        $GLOBALS['BE_USER'] = $backendUser;

        // Create partial mock to stub the hasAuth0Session method
        $validatorMock = $this->getMockBuilder(Auth0SessionValidator::class)
            ->setConstructorArgs([$this->configuration])
            ->onlyMethods(['hasAuth0Session'])
            ->getMock();

        // Stub hasAuth0Session to return false (simulating ApplicationFactory failure or invalid session)
        // In the real implementation, exceptions are caught and false is returned
        $validatorMock->expects(self::once())
            ->method('hasAuth0Session')
            ->willReturn(false);

        // Call the method
        $result = $validatorMock->hasValidAuth0Session();

        // Verify returns false when Auth0 session check fails
        self::assertFalse($result);
    }
}
