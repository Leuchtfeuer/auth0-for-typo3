<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\Ticket;
use Symfony\Component\VarExporter\Exception\ClassNotFoundException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class DeviceCredentialApi extends GeneralManagementApi
{

    /**
     * List device credentials
     * Required scope: "read:device_credentials"
     *
     * @param string $user          The user_id of the devices to retrieve
     * @param string $client        The client_id of the devices to retrieve
     * @param string $type          The type of credentials
     * @param string $fields        A comma separated list of fields to include or exclude (depending on include_fields) from the
     *                              result, empty to retrieve all fields
     * @param bool   $includeFields true if the fields specified are to be excluded from the result, false otherwise
     *                              (defaults to true)
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Device_Credentials/post_device_credentials
     */
    public function list (string $user = '', string $client = '', string $type = '', string $fields = '', bool $includeFields = true)
    {
        $params = [
            'include_fields' => $includeFields
        ];

        if ($user !== '') {
            $params['user_id'] = $user;
        }

        if ($client !== '') {
            $params['client_id'] = $client;
        }

        if ($type !== '') {
            $params['type'] = $type;
        }

        if ($fields !== '') {
            $params['fields'] = $fields;
        }

        $response = $this->apiClient
            ->method('get')
            ->addPath('device-credentials')
            ->withDictParams($params)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);

    }

    /**
     * Create a device public key
     * Required scope: "create:device_credentials"
     *
     * @param string $deviceName The device's name, a value that must be easily recognized by the device's owner
     * @param string $value      A base64 encoded string with the value of the credential
     * @param string $deviceId   A unique identifier for the device. Recommendation: use this for Android and this for iOS
     * @param string $type       The type of the credential
     * @param string $client     The client_id of the client for which the credential will be created
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     */
    public function create(string $deviceName, string $value, string $deviceId, string $type = 'public_key', string $client = '')
    {
        $body = [
            'device_name' => $deviceName,
            'type' => $type,
            'value' => $value,
            'device_id' => $deviceId,
        ];

        if ($client !== '') {
            $body['client_id'] = $client;
        }

        $response = $this->apiClient
            ->method('post')
            ->addPath('device-credentials')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Delete a device credential
     * Required scope: "delete:device_credentials"
     *
     * @param string $id The id of the credential to delete
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Device_Credentials/delete_device_credentials_by_id
     */
    public function delete(string $id)
    {
        $response = $this->apiClient
            ->method('delete')
            ->addPath('device-credentials')
            ->addPath($id)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
