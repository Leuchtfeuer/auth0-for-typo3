<?php

declare(strict_types=1);

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Bitmotion\Auth0\Tests\Functional\Api;

use Bitmotion\Auth0\Api\Management\BlacklistApi;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Tests\Functional\Auth0TestCase;

class BlacklistTest extends Auth0TestCase
{
    protected $scopes = [
        Scope::BLACKLIST_TOKENS,
    ];

    /**
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getBlacklistApi
     */
    public function instantiateApi(): BlacklistApi
    {
        $blacklistApi = $this->getApiUtility()->getBlacklistApi(...$this->scopes);
        self::assertInstanceOf(BlacklistApi::class, $blacklistApi);

        return $blacklistApi;
    }

    // TODO: add test for get
    // TODO: add test for add
}
