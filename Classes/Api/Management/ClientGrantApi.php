<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\ClientGrant;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class ClientGrantApi extends GeneralManagementApi
{
    /**
     * Manage your Client Grants (also called Client Credentials Grants). Using Client Grants, your Client can request an Auth0
     * access token using its credentials (a Client ID and a Client Secret). The access token then represents your Client during
     * API calls.
     * Required scope: "read:client_grants"
     *
     * @param string $id            id of a client to filter
     * @param string $audience      URL Encoded audience of a client grant to filter
     * @param int    $page          The page number. Zero based
     * @param int    $perPage       The amount of entries per page.
     * @param bool   $includeTotals true if a query summary must be included in the result, false otherwise. Default false.
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return ClientGrant|ClientGrant[]
     * @see https://auth0.com/docs/api/management/v2#!/Client_Grants/get_client_grants
     */
    public function list(string $id = '', string $audience = '', int $page = 0, int $perPage = 50, bool $includeTotals = false)
    {
        $params = [
            'page' => $page,
            'per_page' => $perPage,
            'include_totals' => $includeTotals,
        ];

        $this->addStringProperty($params, 'client_id', $id);
        $this->addStringProperty($params, 'audience', $audience);

        $response = $this->client
            ->request('get')
            ->addPath('client-grants')
            ->withDictParams($params)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Create a client grant
     * Required scope: "create:client_grants"
     *
     * @param string   $id       The identifier of the client.
     * @param string   $audience The audience.
     * @param string[] $scope    Scopes.
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Client_Grants/post_client_grants
     */
    public function create(string $id, string $audience, array $scope)
    {
        $body = [
            'client_id' => $id,
            'audience' => $audience,
            'scope' => $scope,
        ];

        $response = $this->client
            ->request('post')
            ->addPath('client-grants')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Delete a client grant
     * Required scope: "delete:client_grants"
     *
     * @param string $id The id of the client grant to delete
     *
     * @throws ApiException
     * @throws CoreException
     * @throws Exception
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Client_Grants/delete_client_grants_by_id
     */
    public function delete(string $id)
    {
        $response = $this->client
            ->request('delete')
            ->addPath('client-grants')
            ->addPath($id)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Update a client grant
     * Required scope: "update:client_grants"
     *
     * @param string   $id    The id of the client grant to modify
     * @param string[] $scope Scopes.
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Client_Grants/patch_client_grants_by_id
     */
    public function update(string $id, array $scope)
    {
        $response = $this->client
            ->request('patch')
            ->addPath('client-grants')
            ->addPath($id)
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode(['scope' => $scope]))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
