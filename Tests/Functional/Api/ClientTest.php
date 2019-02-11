<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

use Bitmotion\Auth0\Api\Management\ClientApi;
use Bitmotion\Auth0\Domain\Model\Auth0\Client;
use Bitmotion\Auth0\Domain\Model\Auth0\Client\EncryptionKey;
use Bitmotion\Auth0\Domain\Model\Auth0\Client\JwtConfiguration;
use Bitmotion\Auth0\Domain\Model\Auth0\Client\Mobile;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Utility\ApiUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ClientTest extends FunctionalTestCase
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
        Scope::CLIENT_READ,
        Scope::CLIENT_UPDATE,
        Scope::CLIENT_CREATE,
        Scope::CLIENT_DELETE,
        Scope::CLIENT_KEYS_UPDATE,
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
     * Tries to instantiate the ClientApi
     *
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getClientApi
     */
    public function instantiateApi(): ClientApi
    {
        $clientApi = $this->apiUtility->getClientApi(...$this->scopes);
        $this->assertInstanceOf(ClientApi::class, $clientApi);

        return $clientApi;
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\ClientApi::list
     */
    public function listClients(ClientApi $clientApi)
    {
        $clients = $clientApi->list();
        $this->assertIsArray($clients);
        $this->assertNotEmpty($clients);

        $client = array_shift($clients);
        $this->assertInstanceOf(Client::class, $client);
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\ClientApi::create
     */
    public function createClient(ClientApi $clientApi): Client
    {
        $mobile = new Mobile();
        $mobile->setAndroid([
            'app_package_name' => 'com.example',
            'sha256_cert_fingerprints' => [
                'D8:A0:83:...',
            ],
        ]);
        $mobile->setIos([
            'team_id' => '9JA89QQLNQ',
            'app_bundle_identifier' => 'com.my.bundle.id',
        ]);

        $jwtConfiguration = new JwtConfiguration();
        $jwtConfiguration->setAlg(JwtConfiguration::ALG_RS256);
        $jwtConfiguration->setLifetimeInSeconds(4200);
        $jwtConfiguration->setScopes(['a' => Scope::CLIENT_KEYS_UPDATE, 'b' => Scope::CLIENT_READ]);
        $jwtConfiguration->setSecretEncoded(true);

        $encryptionKey = new EncryptionKey();
        $encryptionKey->setCert('CERT');
        $encryptionKey->setPub('pub');
        $encryptionKey->setSubject('Subject');

        $client = new Client();
        $client->setName('NEW_CLIENT_' . uniqid());
        $client->setDescription('Temporär bin ich wär');
        $client->setLogoUri('https://www.google.com/favicon.ico');
        $client->setCallbacks(['https://www.bitmotion.de', 'http://typo39.local/']);
        $client->setAllowedOrigins(['https://www.bitmotion.de', 'http://typo39.local/']);
        $client->setWebOrigins(['https://www.bitmotion.de', 'http://typo39.local/']);
        $client->setClientAliases(['https://www.bitmotion.de', 'http://typo39.local/']);
        $client->setMobile($mobile);
        $client->setSsoDisabled(false);
        $client->setSso(true);
        $client->setClientMetadata(['foo' => 'bar']);
        $client->setJwtConfiguration($jwtConfiguration);
        $client->setGrantTypes(['client_credentials']);
        $client->setClientId(uniqid() . time());

        $client = $clientApi->create($client);

        $this->assertInstanceOf(Client::class, $client);

        return $client;
    }

    /**
     * @test
     * @depends instantiateApi
     * @depends createClient
     * @covers \Bitmotion\Auth0\Api\Management\ClientApi::update
     */
    public function updateClient(ClientApi $clientApi, Client $client)
    {
        $this->assertFalse($client->isOidcConformant());
        $client->setOidcConformant(true);
        $updatedClient = $clientApi->update($client);

        $this->assertInstanceOf(Client::class, $updatedClient);
        $this->assertTrue($updatedClient->isOidcConformant());
    }

    /**
     * @test
     * @depends instantiateApi
     * @depends createClient
     * @covers \Bitmotion\Auth0\Api\Management\ClientApi::get
     */
    public function getClient(ClientApi $clientApi, Client $client)
    {
        $newClient = $clientApi->get($client->getClientId());
        $this->assertInstanceOf(Client::class, $newClient);
        $this->assertEquals($client->getClientId(), $newClient->getClientId());
    }

    /**
     * @test
     * @depends instantiateApi
     * @depends createClient
     * @covers \Bitmotion\Auth0\Api\Management\ClientApi::rotateSecret
     */
    public function rotateSecret(ClientApi $clientApi, Client $client)
    {
        $secret = $client->getClientSecret();
        $newClient = $clientApi->rotateSecret($client->getClientId());
        $newSecret = $newClient->getClientSecret();
        $this->assertNotEquals($secret, $newSecret);
    }

    /**
     * @test
     * @depends instantiateApi
     * @depends createClient
     * @covers \Bitmotion\Auth0\Api\Management\ClientApi::delete
     */
    public function delete(ClientApi $clientApi, Client $client)
    {
        $deleted = $clientApi->delete($client->getClientId());
        $this->assertTrue($deleted);
    }
}
