<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Symfony\Component\VarExporter\Exception\ClassNotFoundException;

class RuleConfigApi extends GeneralManagementApi
{
    /**
     * Returns only rules config variable keys. For security, config variable values cannot be retrieved outside rule execution.
     * Required scope: "read:rules_configs"
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|\TYPO3\CMS\Extbase\Persistence\ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Rules_Configs/get_rules_configs
     */
    public function list()
    {
        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('rules-configs')
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Removes the rules config for a given key
     * Config keys must be of the format ^[A-Za-z0-9_\-@*+:]*$
     * Required scope: "delete:rules_configs"
     *
     * @param string $key The key of the rules config to remove (Max length: 127)
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|\TYPO3\CMS\Extbase\Persistence\ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Rules_Configs/delete_rules_configs_by_key
     */
    public function delete(string $key)
    {
        $response = $this->client
            ->request(Client::METHOD_DELETE)
            ->addPath('rules-configs')
            ->addPath($key)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Sets the rules config for a given key
     * Rules config keys must be of the format ^[A-Za-z0-9_\-@*+:]*$.
     * Required scope: "update:rules_configs"
     *
     * @param string $key   The key of the rules config to set (Max length: 127)
     * @param string $value The value for the rules config being set.
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|\TYPO3\CMS\Extbase\Persistence\ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Rules_Configs/put_rules_configs_by_key
     */
    public function create(string $key, string $value)
    {
        $response = $this->client
            ->request(Client::METHOD_PUT)
            ->addPath('rules-configs')
            ->addPath($key)
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode(['value' => $value]))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
