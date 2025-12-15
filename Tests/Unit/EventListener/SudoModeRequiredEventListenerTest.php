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

namespace Leuchtfeuer\Auth0\Tests\Unit\EventListener;

use Leuchtfeuer\Auth0\EventListener\SudoModeRequiredEventListener;
use Leuchtfeuer\Auth0\Service\Auth0SessionValidator;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessClaim;
use TYPO3\CMS\Backend\Security\SudoMode\Event\SudoModeRequiredEvent;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * Test case for SudoModeRequiredEventListener
 */
class SudoModeRequiredEventListenerTest extends TestCase
{
    protected SudoModeRequiredEventListener $subject;
    protected Auth0SessionValidator $sessionValidator;
    protected ExtensionConfiguration $extensionConfiguration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionValidator = $this->createMock(Auth0SessionValidator::class);
        $this->extensionConfiguration = $this->createMock(ExtensionConfiguration::class);

        // Configure extension configuration to not disable sudo mode bypass
        $this->extensionConfiguration->method('get')
            ->with('auth0', 'disableSudoModeBypass')
            ->willReturn(false);

        $this->subject = new SudoModeRequiredEventListener(
            $this->sessionValidator,
            $this->extensionConfiguration
        );
    }

    /**
     * @test
     */
    public function testBypassesWhenValidAuth0Session(): void
    {
        // Create real event with mocked claim
        $claim = $this->createMock(AccessClaim::class);
        $event = new SudoModeRequiredEvent($claim);

        // Configure session validator to allow bypass
        $this->sessionValidator->method('hasValidAuth0Session')->willReturn(true);

        // Invoke event listener
        ($this->subject)($event);

        // Verify sudo mode was bypassed (state verification instead of mock expectation)
        self::assertFalse($event->isVerificationRequired());
    }

    /**
     * @test
     */
    public function testDoesNotBypassWhenNoValidSession(): void
    {
        // Create real event with mocked claim
        $claim = $this->createMock(AccessClaim::class);
        $event = new SudoModeRequiredEvent($claim);

        // Configure session validator to reject
        $this->sessionValidator->method('hasValidAuth0Session')->willReturn(false);

        // Invoke event listener
        ($this->subject)($event);

        // Verify sudo mode was NOT bypassed (still required)
        self::assertTrue($event->isVerificationRequired());
    }

    /**
     * @test
     */
    public function testDoesNothingWhenVerificationAlreadyDenied(): void
    {
        // Create real event with verification already denied
        $claim = $this->createMock(AccessClaim::class);
        $event = new SudoModeRequiredEvent($claim);
        $event->setVerificationRequired(false); // Pre-set to denied

        // Expect session validator NOT to be called (early return in listener)
        $this->sessionValidator->expects(self::never())
            ->method('hasValidAuth0Session');

        // Invoke event listener
        ($this->subject)($event);

        // Verify verification requirement unchanged (still false)
        self::assertFalse($event->isVerificationRequired());
    }
}
