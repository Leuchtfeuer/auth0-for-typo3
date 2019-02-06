<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\Ticket;
use Symfony\Component\VarExporter\Exception\ClassNotFoundException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class ResourceServerApi extends GeneralManagementApi
{
    /**
     * Manage your Resource Servers. These host protected resources that you can access by interfacing with its APIs.
     * Required scope: "read:resource_servers"
     *
     * @param int  $page          The page number. Zero based.
     * @param int  $perPage       The amount of entries per page. Default: 50. Max value: 100. If not present, pagination will be
     *                            disabled
     * @param bool $includeTotals true if a query summary must be included in the result, false otherwise. Default false.
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Resource_Servers/get_resource_servers
     */
    public function list(int $page = 0, int $perPage = 50, bool $includeTotals = false)
    {
        $params = [
            'page' => $page,
            'per_page' => $perPage,
            'include_totals' => $includeTotals,
        ];

        $response = $this->apiClient
            ->method('get')
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
     * @param string $id                   The id or audience of the resource server to update
     * @param string $name                 The name of the resource server. Must contain at least one character. Does not
     *                                     allow '<' or '>'
     * @param array  $scopes
     * @param string $algorithm            The algorithm used to sign tokens ['HS256' or 'RS256']
     * @param string $secret               The secret used to sign tokens when using symmetric algorithms
     * @param bool   $skip                 Flag this entity as capable of skipping consent
     * @param bool   $offlineAccess        Allows issuance of refresh tokens for this entity
     * @param int    $lifetime             The amount of time (in seconds) that the token will be valid after being issued
     * @param string $verificationLocation A uri from which to retrieve JWKs for this resource server used for verifying the JWT
     *                                     sent to Auth0 for token introspection.
     * @param array  $options              Used to store additional metadata
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Resource_Servers/post_resource_servers
     */
    public function create(
        string $id,
        string $name = '',
        array $scopes = [],
        string $algorithm = 'RS256',
        string $secret = '',
        bool $skip = false,
        bool $offlineAccess = false,
        int $lifetime = 0,
        string $verificationLocation = '',
        array $options = []
    ) {
        $body = [
            'skip_consent_for_verifiable_first_party_clients' => $skip,
            'allow_offline_access' => $offlineAccess,
            'signing_alg' => $algorithm,
        ];

        if ($name !== '') {
            $body['name'] = $name;
        }

        if (!empty($scopes)) {
            $body['scopes'] = $scopes;
        }

        if ($secret !== '') {
            $body['signing_secret'] = $secret;
        }

        if ($lifetime !== 0) {
            $body['token_lifetime'] = $lifetime;
        }

        if ($verificationLocation !== '') {
            $body['verificationLocation'] = $verificationLocation;
        }

        if (!empty($options)) {
            $body['options'] = $options;
        }


        $response = $this->apiClient
            ->method('patch')
            ->addPath('resource-servers')
            ->addPath($id)
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
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
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Resource_Servers/get_resource_servers_by_id
     */
    public function get(string $id)
    {
        $response = $this->apiClient
            ->method('get')
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
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Resource_Servers/delete_resource_servers_by_id
     */
    public function delete(string $id)
    {
        $response = $this->apiClient
            ->method('delete')
            ->addPath('resource-servers')
            ->addPath($id)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Update a resource server
     * Required scope: "update:resource_servers"
     *
     * @param string $id                   The id or audience of the resource server to update
     * @param string $name                 The name of the resource server. Must contain at least one character. Does not
     *                                     allow '<' or '>'
     * @param array  $scopes
     * @param string $algorithm            The algorithm used to sign tokens ['HS256' or 'RS256']
     * @param string $secret               The secret used to sign tokens when using symmetric algorithms
     * @param bool   $skip                 Flag this entity as capable of skipping consent
     * @param bool   $offlineAccess        Allows issuance of refresh tokens for this entity
     * @param int    $lifetime             The amount of time (in seconds) that the token will be valid after being issued
     * @param string $verificationLocation A uri from which to retrieve JWKs for this resource server used for verifying the JWT
     *                                     sent to Auth0 for token introspection.
     * @param array  $options              Used to store additional metadata
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Resource_Servers/patch_resource_servers_by_id
     */
    public function update(
        string $id,
        string $name = '',
        array $scopes = [],
        string $algorithm = 'RS256',
        string $secret = '',
        bool $skip = false,
        bool $offlineAccess = false,
        int $lifetime = 0,
        string $verificationLocation = '',
        array $options = []
    ) {
        $body = [
            'skip_consent_for_verifiable_first_party_clients' => $skip,
            'allow_offline_access' => $offlineAccess,
            'signing_alg' => $algorithm,
        ];

        if ($name !== '') {
            $body['name'] = $name;
        }

        if (!empty($scopes)) {
            $body['scopes'] = $scopes;
        }

        if ($secret !== '') {
            $body['signing_secret'] = $secret;
        }

        if ($lifetime !== 0) {
            $body['token_lifetime'] = $lifetime;
        }

        if ($verificationLocation !== '') {
            $body['verificationLocation'] = $verificationLocation;
        }

        if (!empty($options)) {
            $body['options'] = $options;
        }


        $response = $this->apiClient
            ->method('patch')
            ->addPath('resource-servers')
            ->addPath($id)
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
