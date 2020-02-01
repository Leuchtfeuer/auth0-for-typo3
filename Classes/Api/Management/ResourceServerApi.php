<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

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

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\Api\Client;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\ResourceServer;
use Bitmotion\Auth0\Extractor\PropertyTypeExtractor\ResourceServerExtractor;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use TYPO3\CMS\Extbase\Object\Exception;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class ResourceServerApi extends GeneralManagementApi
{
    const EXCLUDED_UPDATE_PROPERTIES = [
        'system', 'id', 'identifier',
    ];

    const EXCLUDED_CREATE_PROPERTIES = [
        'system', 'id',
    ];

    public function __construct(Client $client)
    {
        $this->extractor = new ResourceServerExtractor();
        $this->defaultContext[ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT] = true;

        parent::__construct($client);
    }

    /**
     * Manage your Resource Servers. These host protected resources that you can access by interfacing with its APIs.
     * Required scope: "read:resource_servers"
     *
     * @param int  $page          The page number. Zero based.
     * @param int  $perPage       The amount of entries per page. Default: 50. Max value: 100. If not present, pagination will be
     *                            disabled
     * @param bool $includeTotals true if a query summary must be included in the result, false otherwise. Default false.
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return ResourceServer|ResourceServer[]
     * @see https://auth0.com/docs/api/management/v2#!/Resource_Servers/get_resource_servers
     */
    public function list(int $page = 0, int $perPage = 50, bool $includeTotals = false)
    {
        $params = [
            'page' => $page,
            'per_page' => $perPage,
            'include_totals' => $includeTotals,
        ];

        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('resource-servers')
            ->withDictParams($params)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Create a resource server
     * Required scope: "create:resource_servers"
     *
     * @param ResourceServer $resourceServer The ResourceServer you want to create.
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Resource_Servers/post_resource_servers
     */
    public function create(ResourceServer $resourceServer)
    {
        $data = $this->normalize($resourceServer, 'array', self::EXCLUDED_CREATE_PROPERTIES, true);

        $response = $this->client
            ->request(Client::METHOD_POST)
            ->addPath('resource-servers')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($data))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Get a resource server by its id or audience
     * Required scope: "read:resource_servers"
     *
     * @param string $id The id or audience of the resource server to retrieve
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return ResourceServer
     * @see https://auth0.com/docs/api/management/v2#!/Resource_Servers/get_resource_servers_by_id
     */
    public function get(string $id)
    {
        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('resource-servers')
            ->addPath($id)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Delete a resource server
     * Required scope: "delete:resource_servers"
     *
     * @param string $id The id or the audience of the resource server to delete
     *
     * @throws CoreException
     * @return bool
     * @see https://auth0.com/docs/api/management/v2#!/Resource_Servers/delete_resource_servers_by_id
     */
    public function delete(string $id)
    {
        $response = $this->client
            ->request(Client::METHOD_DELETE)
            ->addPath('resource-servers')
            ->addPath($id)
            ->setReturnType('object')
            ->call();

        return $response->getStatusCode() === 204;
    }

    /**
     * Update a resource server
     * Required scope: "update:resource_servers"
     *
     * @param ResourceServer $resourceServer The ResourceServer you want to update.
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return ResourceServer
     * @see https://auth0.com/docs/api/management/v2#!/Resource_Servers/patch_resource_servers_by_id
     */
    public function update(ResourceServer $resourceServer)
    {
        $originServer = $this->get($resourceServer->getId());
        $originData = $this->normalize($originServer, 'array', self::EXCLUDED_UPDATE_PROPERTIES, true);

        $newData = $this->normalize($resourceServer, 'array', self::EXCLUDED_UPDATE_PROPERTIES, true);

        // Auth0 excepts diff only - wtf
        $data = array_diff_assoc($newData, $originData);

        $response = $this->client
            ->request(Client::METHOD_PATCH)
            ->addPath('resource-servers')
            ->addPath($resourceServer->getId())
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($data))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
