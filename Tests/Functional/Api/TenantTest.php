<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

use Bitmotion\Auth0\Api\Management\TenantApi;
use Bitmotion\Auth0\Domain\Model\Auth0\Tenant;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Utility\ApiUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class TenantTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/auth0',
    ];

    /**
     * @var array
     */
    protected $scopes = [
        Scope::TENANT_SETTINGS_UPDATE,
        Scope::TENANT_SETTINGS_READ,
    ];

    /**
     * @var int
     */
    protected $application = 1;

    /**
     * @var ApiUtility
     */
    protected $apiUtility;

    public function setUp()
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_auth0_domain_model_application.xml');
        $this->apiUtility = GeneralUtility::makeInstance(ApiUtility::class);
        $this->apiUtility->setApplication($this->application);
    }

    /**
     * Tries to instantiate the TenantApi
     *
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getTenantApi
     */
    public function instantiateApi(): TenantApi
    {
        $tenantApi = $this->apiUtility->getTenantApi(...$this->scopes);
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
