<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

/***
 *
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

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
        self::assertInstanceOf(DeviceCredentialApi::class, $deviceCredentialApi);

        return $deviceCredentialApi;
    }

    // TODO: implement
}
