<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Tests\Functional;

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

use Auth0\SDK\API\Authentication;
use Auth0\SDK\API\Header\Authorization\AuthorizationBearer;
use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\API\Header\Header;
use Auth0\SDK\API\Helpers\InformationHeaders;
use Bitmotion\Auth0\Api\Management\UserApi;
use Bitmotion\Auth0\Domain\Model\Auth0\Api\Client;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\User;
use Bitmotion\Auth0\Utility\ApiUtility;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class Auth0TestCase extends FunctionalTestCase
{
    const CONNECTION_NAME = 'Username-Password-Authentication';

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/auth0',
    ];

    /**
     * @var int
     */
    private $application = 1;

    /**
     * @var ApiUtility
     */
    private $apiUtility;

    /**
     * @var string
     */
    private static $domain;

    /**
     * @var User
     */
    private static $user;

    /**
     * @var Authentication
     */
    private static $authentication;

    /**
     * @var Client
     */
    private static $client;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::connect();
        self::insertUser();
    }

    private static function connect(): void
    {
        $xml = new XmlEncoder();
        $file = __DIR__ . '/Fixtures/tx_auth0_domain_model_application.xml';
        $dataset = $xml->decode(file_get_contents($file), 'array');
        $application = $dataset['tx_auth0_domain_model_application'];
        self::$domain = $application['domain'];

        self::$authentication = new Authentication(
            $application['domain'],
            $application['id'],
            $application['secret'],
            "https://{$application['domain']}/{$application['audience']}"
        );

        $credentials = self::$authentication->client_credentials([
            'client_secret' => $application['secret'],
            'client_id' => $application['id'],
            'audience' => 'https://' . $application['domain'] . '/' . $application['audience'],
        ]);

        self::setClient($credentials, $application);
    }

    private static function setClient(array $credentials, array $application): void
    {
        $informationHeader = new InformationHeaders();
        $informationHeader->setPackage('auth0-typo3-test', '3.0.0');
        $informationHeader->setEnvProperty('php', phpversion());

        $audience = sprintf('/%s/', trim($application['audience'], '/'));
        $domain = sprintf('https://%s', rtrim($application['domain'], '/'));

        self::$client = new Client(false);
        self::$client->addHeader(new Header('Auth0-Client', $informationHeader->build()));
        self::$client->setDomain($domain);
        self::$client->setBasePath($audience);
        self::$client->setGuzzleOptions(['http_errors' => false]);
        self::$client->addHeader(new AuthorizationBearer($credentials['access_token']));
    }

    private static function getSerializer(): Serializer
    {
        $encoders = [new YamlEncoder(), new JsonEncoder()];
        $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());

        return new Serializer([$normalizer], $encoders);
    }

    private static function insertUser(): void
    {
        $serializer = self::getSerializer();
        $file = __DIR__ . '/Fixtures/auth0_user.yml';

        $user = $serializer->deserialize(file_get_contents($file), User::class, 'yml');
        $user->setEmail(sprintf($user->getEmail(), uniqid()));
        $user->setNickname(sprintf($user->getNickname(), uniqid()));

        $data = $serializer->normalize($user, 'array', [
            ObjectNormalizer::ATTRIBUTES => UserApi::ALLOWED_ATTRIBUTES_CREATE,
            ObjectNormalizer::SKIP_NULL_VALUES => true,
        ]);
        $data['connection'] = self::CONNECTION_NAME;
        $data['verify_email'] = true;

        $response = self::createUser($data);
        $jsonResponse = $response->getBody()->getContents();

        self::$user = $serializer->deserialize($jsonResponse, User::class, 'json');
    }

    private static function createUser(array $data)
    {
        return self::$client
            ->request(Client::METHOD_POST)
            ->addPath('users')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($data))
            ->setReturnType('object')
            ->call();
    }

    public static function tearDownAfterClass(): void
    {
        self::$client
            ->request(Client::METHOD_DELETE)
            ->addPath('users')
            ->addPath(self::$user->getUserId())
            ->setReturnType('object')
            ->call();

        parent::tearDownAfterClass();
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/Fixtures/tx_auth0_domain_model_application.xml');
        $this->apiUtility = GeneralUtility::makeInstance(ApiUtility::class, $this->application);
    }

    abstract public function instantiateApi();

    protected function getApiUtility(): ApiUtility
    {
        return $this->apiUtility;
    }

    protected function getUser(): User
    {
        return self::$user;
    }

    protected function getAuthentication()
    {
        return self::$authentication;
    }
}
