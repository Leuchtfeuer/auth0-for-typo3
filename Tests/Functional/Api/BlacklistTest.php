<?php
declare(strict_types=1);
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
        $this->assertInstanceOf(BlacklistApi::class, $blacklistApi);

        return $blacklistApi;
    }

    // TODO: add test for get
    // TODO: add test for add
}
