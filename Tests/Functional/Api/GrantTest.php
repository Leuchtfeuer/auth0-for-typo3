<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

use Bitmotion\Auth0\Api\Management\GrantApi;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Tests\Functional\Auth0TestCase;

class GrantTest extends Auth0TestCase
{
    protected $scopes = [
        Scope::GRANT_READ,
        Scope::GRANT_DELETE,
    ];

    /**
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getGrantApi
     */
    public function instantiateApi(): GrantApi
    {
        $grantApi = $this->getApiUtility()->getGrantApi(...$this->scopes);
        $this->assertInstanceOf(GrantApi::class, $grantApi);

        return $grantApi;
    }

    // TODO: implement
}
