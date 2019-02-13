<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

use Bitmotion\Auth0\Api\Management\ResourceServerApi;
use Bitmotion\Auth0\Domain\Model\Auth0\Client\JwtConfiguration;
use Bitmotion\Auth0\Domain\Model\Auth0\ResourceServer;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Tests\Functional\Auth0TestCase;

class ResourceServerApiTest extends Auth0TestCase
{
    /**
     * @var array
     */
    protected $scopes = [
        Scope::RESOURCE_SERVERS_READ,
        Scope::RESOURCE_SERVERS_CREATE,
        Scope::RESOURCE_SERVERS_DELETE,
        Scope::RESOURCE_SERVERS_UPDATE,
    ];

    /**
     * Tries to instantiate the ResourceServerApi
     *
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getResourceServerApi
     */
    public function instantiateApi(): ResourceServerApi
    {
        $resourceServer = $this->getApiUtility()->getResourceServerApi(...$this->scopes);
        $this->assertInstanceOf(ResourceServerApi::class, $resourceServer);

        return $resourceServer;
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\ResourceServerApi::create
     */
    public function create(ResourceServerApi $resourceServerApi)
    {
        $resourceServer = $this->getNewResourceServer();
        $newServer = $resourceServerApi->create($resourceServer);
        $this->assertInstanceOf(ResourceServer::class, $newServer);

        return $newServer;
    }

    /**
     * @test
     * @depends instantiateApi
     * @depends create
     * @covers \Bitmotion\Auth0\Api\Management\ResourceServerApi::get
     */
    public function get(ResourceServerApi $resourceServerApi, ResourceServer $resourceServer)
    {
        $foundResourceServer = $resourceServerApi->get($resourceServer->getId());
        $this->assertInstanceOf(ResourceServer::class, $foundResourceServer);
        $this->assertEquals($resourceServer->getName(), $foundResourceServer->getName());
    }

    /**
     * @test
     * @depends instantiateApi
     * @depends create
     * @covers \Bitmotion\Auth0\Api\Management\ResourceServerApi::update
     */
    public function update(ResourceServerApi $resourceServerApi, ResourceServer $resourceServer)
    {
        $originTokenLifetimeForWeb = $resourceServer->getTokenLifetimeForWeb();
        $resourceServer->setTokenLifetimeForWeb($resourceServer->getTokenLifetime());
        $updatedResourceServer = $resourceServerApi->update($resourceServer);
        $this->assertInstanceOf(ResourceServer::class, $updatedResourceServer);
        $this->assertNotEquals($originTokenLifetimeForWeb, $updatedResourceServer->getTokenLifetimeForWeb());
    }

    /**
     * @test
     * @depends instantiateApi
     * @depends create
     * @covers \Bitmotion\Auth0\Api\Management\ResourceServerApi::delete
     */
    public function delete(ResourceServerApi $resourceServerApi, ResourceServer $resourceServer)
    {
        $response = $resourceServerApi->delete($resourceServer->getId());
        $this->assertTrue($response);
    }

    protected function getNewResourceServer(): ResourceServer
    {
        $allowedScopes = [
            Scope::RESOURCE_SERVERS_UPDATE,
            Scope::RESOURCE_SERVERS_READ,
            Scope::RESOURCE_SERVERS_DELETE,
        ];

        $scopes = [];
        foreach ($allowedScopes as $allowedScope) {
            $scope = new ResourceServer\Scope();
            $scope->setValue($allowedScope);
            $scopes[] = $scope;
        }

        $newServer = new ResourceServer();
        $newServer->setTokenLifetime(7200);
        $newServer->setName('Mein Name');
        $newServer->setScopes($scopes);
        $newServer->setAllowOfflineAccess(false);
        $newServer->setId(uniqid() . time());
        $newServer->setIdentifier(uniqid());
        $newServer->setIsSystem(false);
        $newServer->setSigningAlg(JwtConfiguration::ALG_HS256);
        $newServer->setSigningSecret('jkldasöfjklasdjfklasjkldfjaöl');
        $newServer->setSkipConsentForVerifiableFirstPartyClients(false);
        $newServer->setTokenLifetimeForWeb(3600);

        return $newServer;
    }
}
