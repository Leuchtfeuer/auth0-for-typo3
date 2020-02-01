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

use Bitmotion\Auth0\Api\Management\TenantApi;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Tenant;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Tests\Functional\Auth0TestCase;

class TenantTest extends Auth0TestCase
{
    protected $scopes = [
        Scope::TENANT_SETTINGS_UPDATE,
        Scope::TENANT_SETTINGS_READ,
    ];

    /**
     * Tries to instantiate the TenantApi
     *
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getTenantApi
     */
    public function instantiateApi(): TenantApi
    {
        $tenantApi = $this->getApiUtility()->getTenantApi(...$this->scopes);
        $this->assertInstanceOf(TenantApi::class, $tenantApi);

        return $tenantApi;
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\TenantApi::get
     */
    public function get(TenantApi $tenantApi)
    {
        $tenant = $tenantApi->get();
        $this->assertInstanceOf(Tenant::class, $tenant);
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\TenantApi::update
     */
    public function update(TenantApi $tenantApi)
    {
        $newAddress = 'support+' . time() . '@bitmotion.de';
        $tenant = $tenantApi->get();
        $tenant->setSupportEmail($newAddress);

        $updatedTenant = $tenantApi->update($tenant);
        $this->assertInstanceOf(Tenant::class, $updatedTenant);
        $this->assertEquals($newAddress, $tenant->getSupportEmail());
    }
}
