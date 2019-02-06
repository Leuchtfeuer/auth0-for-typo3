<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\Ticket;
use Symfony\Component\VarExporter\Exception\ClassNotFoundException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class EmailApi extends GeneralManagementApi
{
    /**
     * This endpoint can be used to retrieve the name of the email provider.
     * Required scope: "read:email_provider"
     *
     * @param string $fields        A comma separated list of fields to include or exclude (depending on include_fields) from
     *                              the result, empty to retrieve: name, enabled, settings fields
     * @param bool   $includeFields true if the fields specified are to be excluded from the result, false otherwise
     *                              (defaults to true)
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Emails/get_provider
     */
    public function getProvider(string $fields = '', bool $includeFields = true)
    {
        $params = [
            'include_fields' => $includeFields,
        ];

        if ($fields !== '') {
            $params['fields'] = $fields;
        }

        $response = $this->apiClient
            ->method('get')
            ->addPath('emails')
            ->addPath('provider')
            ->withParams($params)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Deletes an email provider. USE WITH CAUTION
     * Required scope: "delete:email_provider"
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Emails/delete_provider
     */
    public function deleteProvider()
    {
        $response = $this->apiClient
            ->method('delete')
            ->addPath('emails')
            ->addPath('provider')
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Can be used to change details for an email provider.
     * Depending on the type of provider, the credentials object requires different attributes:
     *  - mandrill requires api_key
     *  - sendgrid requires api_key
     *  - sparkpost requires api_key
     *  - ses requires accessKeyId, secretAccessKey and region
     *  - smtp requires smtp_host, smtp_port, smtp_user and smtp_pass
     * Required scope: "update:email_provider"
     *
     * @param string $name               The name of the email provider ['mandrill' or 'sendgrid' or 'sparkpost' or 'ses' or 'smtp']
     * @param array  $credentials
     * @param bool   $enabled            true if the email provider is enabled, false otherwise
     * @param string $defaultFromAddress The default from address
     * @param array  $settings
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Emails/patch_provider
     */
    public function updateProvider(
        string $name = '',
        array $credentials = [],
        bool $enabled = true,
        string $defaultFromAddress = '',
        array $settings = []
    ) {
        $body = [
            'enabled' => $enabled
        ];

        if ($name !== '') {
            $body['name'] = $name;
        }

        if ($defaultFromAddress !== '') {
            $body['default_from_address'] = $defaultFromAddress;
        }

        if (!empty($credentials)) {
            $body['credentials'] = $credentials;
        }

        if (!empty($settings)) {
            $body['settings'] = $settings;
        }

        $response = $this->apiClient
            ->method('patch')
            ->addPath('emails')
            ->addPath('provider')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * To be used to set a new email provider.
     * Depending on the type of provider, the credentials object requires different attributes:
     *  - mandrill requires api_key
     *  - sendgrid requires api_key
     *  - sparkpost requires api_key
     *  - ses requires accessKeyId, secretAccessKey and region
     *  - smtp requires smtp_host, smtp_port, smtp_user and smtp_pass
     * Required scope: "create:email_provider"
     *
     * @param string $name               The name of the email provider ['mandrill' or 'sendgrid' or 'sparkpost' or 'ses' or 'smtp']
     * @param array  $credentials
     * @param bool   $enabled            true if the email provider is enabled, false otherwise
     * @param string $defaultFromAddress The default from address
     * @param array  $settings
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Emails/post_provider
     */
    public function createProvider(
        string $name,
        array $credentials,
        bool $enabled = true,
        string $defaultFromAddress = '',
        array $settings = []
    ) {
        $body = [
            'enabled' => $enabled,
            'name' => $name,
            'credentials' => $credentials,
        ];

        if ($defaultFromAddress !== '') {
            $body['default_from_address'] = $defaultFromAddress;
        }

        if (!empty($settings)) {
            $body['settings'] = $settings;
        }

        $response = $this->apiClient
            ->method('post')
            ->addPath('emails')
            ->addPath('provider')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
