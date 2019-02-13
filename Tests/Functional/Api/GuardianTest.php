<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

use Bitmotion\Auth0\Api\Management\GuardianApi;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Tests\Functional\Auth0TestCase;

class GuardianTest extends Auth0TestCase
{
    protected $scopes = [
        Scope::GUARDIAN_FACTOR_READ,
        Scope::GUARDIAN_FACTOR_UPDATE,
    ];

    /**
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getGuardianApi
     */
    public function instantiateApi(): GuardianApi
    {
        $guardianApi = $this->getApiUtility()->getGuardianApi(...$this->scopes);
        $this->assertInstanceOf(GuardianApi::class, $guardianApi);

        return $guardianApi;
    }

    // TODO: implement
}
