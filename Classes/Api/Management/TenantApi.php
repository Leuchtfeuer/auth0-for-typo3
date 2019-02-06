<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\Ticket;
use Symfony\Component\VarExporter\Exception\ClassNotFoundException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class TenantApi extends GeneralManagementApi
{
    /**
     * Use this endpoint to retrieve various settings for a tenant.
     * Required scope: "read:tenant_settings"
     *
     * @param  string $fields        A comma separated list of fields to include or exclude (depending on include_fields) from
     *                               the result, empty to retrieve all fields
     * @param  bool   $includeFields true if the fields specified are to be included in the result, false otherwise (defaults
     *                               to true)
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Tenants/get_settings
     */
    public function get(string $fields = '', bool $includeFields = true)
    {
        $params = [
            'fields' => $fields,
        ];

        if ($includeFields === false) {
            $params['include_fields'] = $includeFields;
        }


        $response = $this->apiClient
            ->method('get')
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
     * @param array  $changePassword
     * @param array  $guardianMfaPage
     * @param string $defaultAudience     Default audience for API Authorization
     * @param string $defaultDirectory    Name of the connection that will be used for password grants at the token endpoint.
     *                                    Only the following connection types are supported: LDAP, AD, Database Connections,
     *                                    Passwordless, Windows Azure Active Directory, ADFS
     * @param array  $errorPage
     * @param array  $flags
     * @param string $friendlyName        The friendly name of the tenant
     * @param string $pictureUrl          The URL of the tenant logo (recommended size: 150x150)
     * @param string $supportEmail        User support email
     * @param string $supportUrl          User support url
     * @param array  $allowedLogoutUrls   A set of URLs that are valid to redirect to after logout from Auth0
     * @param int    $sessionLivetime     Login session lifetime, how long the session will stay valid (unit: hours)
     * @param int    $idleSessionLifetime Force a user to login after they have been inactive for the specified number (unit: hours)
     * @param string $sandboxVersion      The selected sandbox version to be used for the extensibility environment
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Tenants/patch_settings
     */
    public function update(
        array $changePassword = [],
        array $guardianMfaPage = [],
        string $defaultAudience = '',
        string $defaultDirectory = '',
        array $errorPage = [],
        array $flags = [],
        string $friendlyName = '',
        string $pictureUrl = '',
        string $supportEmail = '',
        string $supportUrl = '',
        array $allowedLogoutUrls = [],
        int $sessionLivetime = 0,
        int $idleSessionLifetime = 0,
        string $sandboxVersion = ''
    ) {
        $body = [];

        if (!empty($changePassword)) {
            $body['change_password'] = $changePassword;
        }

        if (!empty($guardianMfaPage)) {
            $body['guardian_mfa_page'] = $guardianMfaPage;
        }

        if ($defaultAudience !== '') {
            $body['default_audience'] = $defaultAudience;
        }

        if ($defaultDirectory !== '') {
            $body['default_directory'] = $defaultDirectory;
        }

        if (!empty($errorPage)) {
            $body['error_page'] = $errorPage;
        }

        if (!empty($flags)) {
            $body['flags'] = $flags;
        }

        if ($friendlyName !== '') {
            $body['friendly_name'] = $friendlyName;
        }

        if ($pictureUrl !== '') {
            $body['picture_url'] = $pictureUrl;
        }

        if ($supportEmail !== '') {
            $body['support_email'] = $supportEmail;
        }

        if ($supportUrl !== '') {
            $body['support_url'] = $supportUrl;
        }

        if (!empty($allowedLogoutUrls)) {
            $body['allowed_logout_urls'] = $allowedLogoutUrls;
        }

        if ($sessionLivetime !== 0) {
            $body['session_lifetime'] = $sessionLivetime;
        }

        if ($idleSessionLifetime !== 0) {
            $body['idle_session_lifetime'] = $idleSessionLifetime;
        }

        if ($sandboxVersion !== '') {
            $body['sandbox_version'] = $sandboxVersion;
        }

        $response = $this->apiClient
            ->method('patch')
            ->addPath('tenants')
            ->addPath('settings')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
