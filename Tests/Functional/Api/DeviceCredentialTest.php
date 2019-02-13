<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

use Bitmotion\Auth0\Api\Management\DeviceCredentialApi;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Tests\Functional\Auth0TestCase;

class DeviceCredentialTest extends Auth0TestCase
{
    protected $scopes = [
        Scope::DEVICE_CREDENTIALS_CREATE,
        Scope::DEVICE_CREDENTIALS_DELETE,
        Scope::DEVICE_CREDENTIALS_READ,
        Scope::DEVICE_CREDENTIALS_UPDATE,
    ];

    /**
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getDeviceCredentialApi
     */
    public function instantiateApi(): DeviceCredentialApi
    {
        $deviceCredentialApi = $this->getApiUtility()->getDeviceCredentialApi(...$this->scopes);
        $this->assertInstanceOf(DeviceCredentialApi::class, $deviceCredentialApi);

        return $deviceCredentialApi;
    }

    // TODO: implemnt
}
