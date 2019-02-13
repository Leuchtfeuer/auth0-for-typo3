<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Tests\Functional;

use Auth0\SDK\API\Authentication;
use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\API\Helpers\ApiClient;
use Auth0\SDK\API\Management;
use Bitmotion\Auth0\Api\Management\UserApi;
use Bitmotion\Auth0\Domain\Model\Auth0\User;
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
     * @var UserApi
     */
    private static $userApi;

    /**
     * @var Authentication
     */
    private static $authentication;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $credentials = self::connect();
        $management = new Management($credentials['access_token'], self::$domain, ['http_errors' => false]);
        $apiClient = $management->users->getApiClient();
        self::$userApi = new UserApi($apiClient);

        self::insertUser($apiClient);
    }

    private static function connect(): array
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

        $result = self::$authentication->client_credentials([
            'client_secret' => $application['secret'],
            'client_id' => $application['id'],
            'audience' => 'https://' . $application['domain'] . '/' . $application['audience'],
        ]);

        return $result ?: [];
    }

    private static function getSerializer(): Serializer
    {
        $encoders = [new YamlEncoder(), new JsonEncoder()];
        $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());

        return new Serializer([$normalizer], $encoders);
    }

    private static function insertUser(ApiClient $apiClient)
    {
        $serializer = self::getSerializer();
        $file = __DIR__ . '/Fixtures/auth0_user.yml';
        $user = $serializer->deserialize(file_get_contents($file), User::class, 'yml');

        $data = $serializer->normalize($user, 'array', [
            ObjectNormalizer::ATTRIBUTES => UserApi::ALLOWED_ATTRIBUTES_CREATE,
            ObjectNormalizer::SKIP_NULL_VALUES => true,
        ]);
        $data['connection'] = self::CONNECTION_NAME;
        $data['verify_email'] = true;

        $response = self::createUser($data, $apiClient);
        $jsonResponse = $response->getBody()->getContents();

        self::$user = $serializer->deserialize($jsonResponse, User::class, 'json');
    }

    private static function createUser(array $data, ApiClient $apiClient)
    {
        return $apiClient
            ->method('post')
            ->addPath('users')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($data))
            ->setReturnType('object')
            ->call();
    }

    public static function tearDownAfterClass()
    {
        self::$userApi->delete(self::$user->getUserId());
        parent::tearDownAfterClass();
    }

    public function setUp()
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/Fixtures/tx_auth0_domain_model_application.xml');
        $this->apiUtility = GeneralUtility::makeInstance(ApiUtility::class);
        $this->apiUtility->setApplication($this->application);
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
