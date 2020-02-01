<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

/***
 *
 * This file is part of the "Auth0 for TYPO3" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Florian Wessels <f.wessels@bitmotion.de>, Bitmotion GmbH
 *
 ***/

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
        $this->assertInstanceOf(BlacklistApi::class, $blacklistApi);

        return $blacklistApi;
    }

    // TODO: add test for get
    // TODO: add test for add
}
