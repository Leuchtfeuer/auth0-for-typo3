<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Client;
use Bitmotion\Auth0\Extractor\PropertyTypeExtractor\ClientExtractor;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use TYPO3\CMS\Extbase\Object\Exception;

class ClientApi extends GeneralManagementApi
{
    const EXCLUDED_CREATE_PROPERTIES = [
        'clientId',
    ];

    const EXCLUDED_UPDATE_PROPERTIES = [
        'clientId',
        'signingKeys',
        'firstParty',
    ];

    public function __construct(\Bitmotion\Auth0\Domain\Model\Auth0\Api\Client $client)
    {
        $this->extractor = new ClientExtractor();
        $this->defaultContext[ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT] = true;

        parent::__construct($client);
    }

    /**
     * Retrieves a list of all client applications. Accepts a list of fields to include or exclude.
     * Important: The client_secret and encryption_key attributes can only be retrieved with the read:client_keys scope.
     * Required scopes: "read:clients read:client_keys"
     *
     * @param string    $type          A comma separated list of application types used to filter the returned clients
     *                                 (native, spa, regular_web, non_interactive, rms, box, cloudbees, concur, dropbox, mscrm,
     *                                 echosign, egnyte, newrelic, office365, salesforce, sentry, sharepoint, slack, springcm,
     *                                 zendesk, zoom)
     * @param string    $fields        A comma separated list of fields to include or exclude (depending on include_fields) from
     *                                 the result, empty to retrieve all fields
     * @param bool      $includeFields true if the fields specified are to be included in the result, false otherwise
     *                                 (defaults to true)
     * @param int       $page          The page number. Zero based.
     * @param int       $perPage       The amount of entries per page. Default: 50. Max value: 100. If not present, pagination
     *                                 will be disabled
     * @param bool      $includeTotals true if a query summary must be included in the result
     * @param bool|null $global        Optionally filter on the global client parameter
     * @param bool|null $firstParty    Filter on whether or not a client is a first party client
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return Client|Client[]
     * @see https://auth0.com/docs/api/management/v2#!/Clients/get_clients_by_id
     */
    public function list(
        string $type = '',
        string $fields = '',
        bool $includeFields = true,
        int $page = 0,
        int $perPage = 50,
        bool $includeTotals = false,
        bool $global = false,
        bool $firstParty = true
    ) {
        $params = [
            'page' => $page,
            'per_page' => $perPage,
            'include_fields' => $includeFields,
            'include_totals' => $includeTotals,
        ];

        $this->addStringProperty($params, 'fields', $fields);
        $this->addStringProperty($params, 'app_type', $type);
        $this->addBooleanProperty($params, 'is_global', $global);
        $this->addBooleanProperty($params, 'is_first_party', $firstParty);

        $response = $this->client
            ->request('get')
            ->addPath('clients')
            ->withDictParams($params)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Creates a new client application. The samples on the right show most attributes that can be used.
     * We recommend to let us to generate a safe secret for you, but you can also provide your own with the
     * client_secret parameter
     * Required scope: "create:clients"
     *
     * @param Client $client The client you want to create
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return Client
     * @see https://auth0.com/docs/api/management/v2#!/Clients/post_clients
     */
    public function create(Client $client)
    {
        $data = $this->normalize($client, 'array', self::EXCLUDED_CREATE_PROPERTIES, true);

        $response = $this->client
            ->request('post')
            ->addPath('clients')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($data))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Retrieves a client by its id.
     * Important: The client_secret encryption_key and signing_keys attributes can only be retrieved with the read:client_keys
     * scope.
     * Required scopes: "read:clients read:client_keys"
     *
     * @param string $id            The id of the client to retrieve
     * @param string $fields        A comma separated list of fields to include or exclude (depending on include_fields) from the
     *                              result, empty to retrieve all fields
     * @param bool   $includeFields true if the fields specified are to be included in the result, false otherwise
     *                              (defaults to true)
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return Client
     * @see https://auth0.com/docs/api/management/v2#!/Clients/get_clients_by_id
     */
    public function get(string $id, string $fields = '', bool $includeFields = true)
    {
        $params = [
            'include_fields' => $includeFields,
        ];

        $this->addStringProperty($params, 'fields', $fields);

        $response = $this->client
            ->request('get')
            ->addPath('clients')
            ->addPath($id)
            ->withDictParams($params)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Deletes a client and all its related assets (like rules, connections, etc) given its id.
     * Required scope: "delete:clients"
     *
     * @param string $id The id of the client to delete
     *
     * @throws CoreException
     * @return bool true if request was successful, false if not.
     * @see https://auth0.com/docs/api/management/v2#!/Clients/delete_clients_by_id
     */
    public function delete(string $id): bool
    {
        $response = $this->client
            ->request('delete')
            ->addPath('clients')
            ->addPath($id)
            ->setReturnType('object')
            ->call();

        return $response->getStatusCode() === 204;
    }

    /**
     * Update a client
     * Important: The client_secret and encryption_key attributes can only be updated with the update:client_keys scope.
     * Required scopes: "update:clients update:client_keys"
     *
     * @param Client $client The client you want to update.
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return Client
     * @see https://auth0.com/docs/api/management/v2#!/Clients/patch_clients_by_id
     */
    public function update(Client $client)
    {
        $data = $this->normalize($client, 'array', self::EXCLUDED_UPDATE_PROPERTIES, true);
        $this->cleanProperties($data);

        $response = $this->client
            ->request('patch')
            ->addPath('clients')
            ->addPath($client->getClientId())
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($data))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Rotate a client secret. The generated secret is NOT base64 encoded.
     * Required scope: "update:client_keys"
     *
     * @param string $id  The id of the client to rotate secrets
     *
     * @throws ApiException
     * @throws CoreException
     * @throws Exception
     * @return Client
     * @see https://auth0.com/docs/api/management/v2#!/Clients/post_rotate_secret
     */
    public function rotateSecret(string $id)
    {
        $response = $this->client
            ->request('post')
            ->addPath('clients')
            ->addPath($id)
            ->addPath('rotate-secret')
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    protected function cleanProperties(array &$data)
    {
        // TODO: Find a solution for that:
        if (isset($data['jwt_configuration']['secret_encoded'])) {
            unset($data['jwt_configuration']['secret_encoded']);
        }

        // TODO: There is a problem while updating a fresh created client
        if (isset($data['signing_keys'])) {
            unset($data['signing_keys']);
        }
        if (isset($data['first_party'])) {
            unset($data['first_party']);
        }
    }
}
