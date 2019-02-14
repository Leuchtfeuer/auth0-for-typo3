<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\Api\Client;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Tenant;
use Bitmotion\Auth0\Extractor\PropertyTypeExtractor\TenantExtractor;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use TYPO3\CMS\Extbase\Object\Exception;

class TenantApi extends GeneralManagementApi
{
    const EXCLUDED_UPDATE_PROPERTIES = [
        'sandboxVersionsAvailable',
        'flags',
    ];

    public function __construct(Client $client)
    {
        $this->extractor = new TenantExtractor();
        $this->defaultContext[ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT] = true;

        parent::__construct($client);
    }

    /**
     * Use this endpoint to retrieve various settings for a tenant.
     * Required scope: "read:tenant_settings"
     *
     * @param  string $fields        A comma separated list of fields to include or exclude (depending on include_fields) from
     *                               the result, empty to retrieve all fields
     * @param  bool   $includeFields true if the fields specified are to be included in the result, false otherwise (defaults
     *                               to true)
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return Tenant|Tenant[]
     * @see https://auth0.com/docs/api/management/v2#!/Tenants/get_settings
     */
    public function get(string $fields = '', bool $includeFields = true)
    {
        $params = [
            'include_fields' => $includeFields,
        ];

        $this->addStringProperty($params, 'fields', $fields);

        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('tenants')
            ->addPath('settings')
            ->withDictParams($params)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Use this endpoint to update various fields for a tenant. Enter the new settings in a JSON string in the body parameter.
     * Required scope: "update:tenant_settings"
     *
     * @param Tenant $tenant The tenant you want to update.
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return Tenant
     * @see https://auth0.com/docs/api/management/v2#!/Tenants/patch_settings
     */
    public function update(Tenant $tenant)
    {
        $body = $this->normalize($tenant, 'array', self::EXCLUDED_UPDATE_PROPERTIES, true);

        $response = $this->client
            ->request(Client::METHOD_PATCH)
            ->addPath('tenants')
            ->addPath('settings')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
