<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\Api\Client;
use Symfony\Component\VarExporter\Exception\ClassNotFoundException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class CustomDomainApi extends GeneralManagementApi
{
    /**
     * Retrieves the status of every custom domain.
     * Required scope: "read:custom_domains"
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Custom_Domains/get_custom_domains
     */
    public function list()
    {
        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('custom-domains')
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Creates a new custom domain and returns it.
     * The custom domain will need to be verified before it starts accepting requests.
     * Required scope: "create:custom_domains"
     *
     * @param string $domain Your custom domain.
     * @param string $type   The custom domain provisioning type. ['auth0_managed_certs' or 'self_managed_certs']
     * @param string $method The custom domain verification method. ['txt']
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Custom_Domains/post_custom_domains
     */
    public function create(string $domain, string $type = 'self_managed_certs', string $method = 'txt')
    {
        $body = [
            'domain' => $domain,
            'type' => $type,
            'verification_method' => $method,
        ];

        $response = $this->client
            ->request(Client::METHOD_POST)
            ->addPath('custom-domains')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Retrieves a custom domain status by its ID.
     * Required scope: "read:custom_domains"
     *
     * @param string $id The id of the custom domain to retrieve
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Custom_Domains/get_custom_domains_by_id
     */
    public function get(string $id)
    {
        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('custom-domains')
            ->addPath($id)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Deletes a custom domain by its ID. We will stop serving requests for this domain.
     * Required scope: "delete:custom_domains"
     *
     * @param string $id The id of the custom domain to delete
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Custom_Domains/delete_custom_domains_by_id
     */
    public function delete(string $id)
    {
        $response = $this->client
            ->request(Client::METHOD_DELETE)
            ->addPath('custom-domains')
            ->addPath($id)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Run the verification process for the custom domain. Check the status field to see its verification status.
     * Once the verification is complete, it might take up to 10 minutes before the custom domain can start accepting requests.
     * For self_managed_certs, when the custom domain is verified for the first time, the response will also include the
     * cname_api_key which you will need to configure your proxy. This key must be kept secret, and is used to validate the proxy
     * requests.
     * Required scope: "create:custom_domains"
     *
     * @param string $id The id of the custom domain to verify
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Custom_Domains/post_verify
     */
    public function verify(string $id)
    {
        $response = $this->client
            ->request(Client::METHOD_DELETE)
            ->addPath('custom-domains')
            ->addPath($id)
            ->addPath('verify')
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
