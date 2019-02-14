<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

use Bitmotion\Auth0\Api\Management\ClientGrantApi;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\ClientGrant;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Tests\Functional\Auth0TestCase;

class ClientGrantsTest extends Auth0TestCase
{
    protected $scopes = [
        Scope::CLIENT_GRANTS_UPDATE,
        Scope::CLIENT_GRANTS_DELETE,
        Scope::CLIENT_GRANTS_CREATE,
        Scope::CLIENT_GRANTS_READ,
    ];

    /**
     * Tries to instantiate the ClientGrantApi
     *
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getClientGrantApi
     */
    public function instantiateApi(): ClientGrantApi
    {
        $clientGrantApi = $this->getApiUtility()->getClientGrantApi(...$this->scopes);
        $this->assertInstanceOf(ClientGrantApi::class, $clientGrantApi);

        return $clientGrantApi;
    }

    /**
     * Find all ClientGrants in Auth0
     *
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\ClientGrantApi::list
     */
    public function listClientGrants(ClientGrantApi $clientGrantApi): array
    {
        $clientGrants = $clientGrantApi->list();
        $this->assertIsArray($clientGrants);
        $this->assertNotEmpty($clientGrants);

        return $clientGrants;
    }

    /**
     * Checks whether ClientGrant array contains a ClientGrant object
     *
     * @test
     * @depends listClientGrants
     * @covers \Bitmotion\Auth0\Api\Management\ClientGrantApi::list
     */
    public function loadSingleClientGrant(array $clientGrants): ClientGrant
    {
        $clientGrant = array_shift($clientGrants);
        $this->assertInstanceOf(ClientGrant::class, $clientGrant);

        return $clientGrant;
    }

    /**
     * Compares ClientGrant found in loadSingleClientGrant() and ClientGrant retrieved from API call
     *
     * @test
     * @depends instantiateApi
     * @depends loadSingleClientGrant
     * @covers \Bitmotion\Auth0\Api\Management\ClientGrantApi::list
     */
    public function findClientGrant(ClientGrantApi $clientGrantApi, ClientGrant $clientGrant)
    {
        $newClientGrant = $clientGrantApi->list($clientGrant->getClientId());
        $this->assertSame($newClientGrant->getId(), $clientGrant->getId());
    }
}
