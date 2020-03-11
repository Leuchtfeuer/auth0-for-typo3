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

use Bitmotion\Auth0\Api\Management\ResourceServerApi;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Client\JwtConfiguration;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\ResourceServer;
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
        self::assertInstanceOf(ResourceServerApi::class, $resourceServer);

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
        self::assertInstanceOf(ResourceServer::class, $newServer);

        return $newServer;
    }

    /**
     * @test
     * @depends instantiateApi
     * @depends create
     * @covers \Bitmotion\Auth0\Api\Management\ResourceServerApi::get
     */
    public function get(ResourceServerApi $resourceServerApi, ResourceServer $resourceServer): void
    {
        $foundResourceServer = $resourceServerApi->get($resourceServer->getId());
        self::assertInstanceOf(ResourceServer::class, $foundResourceServer);
        self::assertEquals($resourceServer->getName(), $foundResourceServer->getName());
    }

    /**
     * @test
     * @depends instantiateApi
     * @depends create
     * @covers \Bitmotion\Auth0\Api\Management\ResourceServerApi::update
     */
    public function update(ResourceServerApi $resourceServerApi, ResourceServer $resourceServer): void
    {
        $originTokenLifetimeForWeb = $resourceServer->getTokenLifetimeForWeb();
        $resourceServer->setTokenLifetimeForWeb($resourceServer->getTokenLifetime());
        $updatedResourceServer = $resourceServerApi->update($resourceServer);
        self::assertInstanceOf(ResourceServer::class, $updatedResourceServer);
        self::assertNotEquals($originTokenLifetimeForWeb, $updatedResourceServer->getTokenLifetimeForWeb());
    }

    /**
     * @test
     * @depends instantiateApi
     * @depends create
     * @covers \Bitmotion\Auth0\Api\Management\ResourceServerApi::delete
     */
    public function delete(ResourceServerApi $resourceServerApi, ResourceServer $resourceServer): void
    {
        $response = $resourceServerApi->delete($resourceServer->getId());
        self::assertTrue($response);
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
