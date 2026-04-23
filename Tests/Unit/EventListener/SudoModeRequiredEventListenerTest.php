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
use PHPUnit\Framework\Attributes\Test;
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
    protected ExtensionConfiguration $extensionConfiguration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $this->extensionConfiguration->method('get')->willReturn(false);
    }

    #[Test]
    public function testBypassesWhenValidAuth0Session(): void
    {
        $sessionValidator = self::createStub(Auth0SessionValidator::class);
        $sessionValidator->method('hasValidAuth0Session')->willReturn(true);

        $this->subject = new SudoModeRequiredEventListener($sessionValidator, $this->extensionConfiguration);

        $event = new SudoModeRequiredEvent(self::createStub(AccessClaim::class));
        ($this->subject)($event);

        self::assertFalse($event->isVerificationRequired());
    }

    #[Test]
    public function testDoesNotBypassWhenNoValidSession(): void
    {
        $sessionValidator = self::createStub(Auth0SessionValidator::class);
        $sessionValidator->method('hasValidAuth0Session')->willReturn(false);

        $this->subject = new SudoModeRequiredEventListener($sessionValidator, $this->extensionConfiguration);

        $event = new SudoModeRequiredEvent(self::createStub(AccessClaim::class));
        ($this->subject)($event);

        self::assertTrue($event->isVerificationRequired());
    }

    #[Test]
    public function testDoesNothingWhenVerificationAlreadyDenied(): void
    {
        $sessionValidator = $this->createMock(Auth0SessionValidator::class);
        $sessionValidator->expects(self::never())->method('hasValidAuth0Session');

        $this->subject = new SudoModeRequiredEventListener($sessionValidator, $this->extensionConfiguration);

        $event = new SudoModeRequiredEvent(self::createStub(AccessClaim::class));
        $event->setVerificationRequired(false);
        ($this->subject)($event);

        self::assertFalse($event->isVerificationRequired());
    }
}
