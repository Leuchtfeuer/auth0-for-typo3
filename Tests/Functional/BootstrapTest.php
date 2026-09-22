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

namespace Leuchtfeuer\Auth0\Tests\Functional;

use Leuchtfeuer\Auth0\Domain\Repository\ApplicationRepository;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Guards the harness itself. A failure here means the test environment is
 * broken, not the extension.
 */
class BootstrapTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'leuchtfeuer/auth0',
    ];

    #[Test]
    public function theExtensionIsLoadedAndItsServicesResolve(): void
    {
        self::assertTrue(
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('auth0')
        );
        self::assertInstanceOf(
            ApplicationRepository::class,
            $this->get(ApplicationRepository::class)
        );
    }
}
