<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

use Bitmotion\Auth0\Api\Management\ClientGrantApi;
use Bitmotion\Auth0\Domain\Model\Auth0\ClientGrant;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Utility\ApiUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ClientGrantsTest extends FunctionalTestCase
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
        Scope::CLIENT_GRANTS_UPDATE,
        Scope::CLIENT_GRANTS_DELETE,
        Scope::CLIENT_GRANTS_CREATE,
        Scope::CLIENT_GRANTS_READ,
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
     * Tries to instantiate the ClientGrantApi
     *
     * @test
     */
    public function instantiateApi(): ClientGrantApi
    {
        $clientGrantApi = $this->apiUtility->getClientGrantApi(...$this->scopes);
        $this->assertInstanceOf(ClientGrantApi::class, $clientGrantApi);

        return $clientGrantApi;
    }

    /**
     * Find all ClientGrants in Auth0
     *
     * @test
     * @depends instantiateApi
     */
    public function listClientGrants(ClientGrantApi $clientGrantApi): array
    {
        $clientGrants = $clientGrantApi->list();
        $this->assertNotEmpty($clientGrants);

        return $clientGrants;
    }

    /**
     * Checks whether ClientGrant array contains a ClientGrant object
     *
     * @test
     * @depends listClientGrants
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
     */
    public function findClientGrant(ClientGrantApi $clientGrantApi, ClientGrant $clientGrant)
    {
        $newClientGrant = $clientGrantApi->list($clientGrant->getClientId());
        $this->assertSame($newClientGrant->getId(), $clientGrant->getId());
    }
}
