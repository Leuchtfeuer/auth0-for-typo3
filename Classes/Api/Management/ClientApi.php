<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Symfony\Component\VarExporter\Exception\ClassNotFoundException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class ClientApi extends GeneralManagementApi
{
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
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Clients/get_clients_by_id
     */
    public function list(
        string $type = '',
        string $fields = '',
        bool $includeFields = true,
        int $page = 0,
        int $perPage = 50,
        bool $includeTotals = false,
        bool $global = null,
        bool $firstParty = null
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

        $response = $this->apiClient
            ->method('get')
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
     * @param string    $name                   The name of the client. Must contain at least one character. Does not
     *                                          allow '<' or '>'
     * @param string    $description            Free text description of the purpose of the Client. (Max character length: 140)
     * @param string    $secret                 The secret used to sign tokens for the client
     * @param string    $logoUri                The URL of the client logo (recommended size: 150x150)
     * @param array     $metadata               Metadata
     * @param string[]  $callbacks              A set of URLs that are valid to call back from Auth0 when authenticating users
     * @param string[]  $allowedOrigins         A set of URLs that represents valid origins for CORS
     * @param string[]  $webOrigins             A set of URLs that represents valid web origins for use with web message
     *                                          response mode
     * @param string[]  $clientAliases          List of audiences for SAML protocol
     * @param string[] $allowedClients          Ids of clients that will be allowed to perform delegation requests. Clients that
     *                                          will be allowed to make delegation request. By default, all your clients will be
     *                                          allowed. This field allows you to specify specific clients
     * @param string[]  $allowedLogoutUrls      A set of URLs that are valid to redirect to after logout from Auth0
     * @param string[]  $grantTypes             A set of grant types that the client is authorized to use
     * @param string $endpoint                  Defines the requested authentication method for the token endpoint. Possible
     *                                          values are 'none' (public client without a client secret), 'client_secret_post'
     *                                          (client uses HTTP POST parameters) or 'client_secret_basic'
     *                                          (client uses HTTP Basic)
     *                                          ['none' or 'client_secret_post' or 'client_secret_basic']
     * @param string    $appType                The type of application this client represents
     *                                          ['native' or 'spa' or 'regular_web' or 'non_interactive' or 'rms' or 'box' or
     *                                          'cloudbees' or 'concur' or 'dropbox' or 'mscrm' or 'echosign' or 'egnyte' or
     *                                          'newrelic' or 'office365' or 'salesforce' or 'sentry' or 'sharepoint' or 'slack'
     *                                          or 'springcm' or 'zendesk' or 'zoom']
     * @param bool|null $oidcConformant         Whether this client will conform to strict OIDC specifications
     * @param array     $jwtConfiguration       JWT Configuration
     * @param array     $encryptionKey          EncrptionKey
     * @param bool $sso                         true to use Auth0 instead of the IdP to do Single Sign On, false otherwise
     *                                          (default: false)
     * @param bool $xOriginAuth                 true if this client can be used to make cross-origin authentication requests,
     *                                          false otherwise (default: false)
     * @param string $xOriginLoc                Url fo the location in your site where the cross origin verification takes place
     *                                          for the cross-origin auth flow when performing Auth in your own domain instead of
     *                                          Auth0 hosted login page.
     * @param bool      $ssoDisabled            true to disable Single Sign On, false otherwise (default: false)
     * @param bool      $customLoginPageOn      true if the custom login page is to be used, false otherwise. Defaults to true
     * @param string    $customLoginPage        The content (HTML, CSS, JS) of the custom login page
     * @param string    $customLoginPagePreview The content (HTML, CSS, JS) of the custom login preview page
     * @param string    $formTemplate           Form template for WS-Federation protocol
     * @param bool|null $herokuApp              true if the client is a heroku application, false otherwise
     * @param array     $addOns                 Addons
     * @param array     $mobile                 Mobile
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Clients/post_clients
     */
    public function create(
        string $name,
        string $description = '',
        string $logoUri = '',
        string $secret = '',
        array $metadata = [],
        array $callbacks = [],
        array $allowedOrigins = [],
        array $webOrigins = [],
        array $clientAliases = [],
        array $allowedClients = [],
        array $allowedLogoutUrls = [],
        array $grantTypes = [],
        string $endpoint = 'none',
        string $appType = 'native',
        bool $oidcConformant = null,
        array $jwtConfiguration = [],
        array $encryptionKey = [],
        bool $sso = false,
        bool $xOriginAuth = false,
        string $xOriginLoc = '',
        bool $ssoDisabled = false,
        bool $customLoginPageOn = true,
        string $customLoginPage = '',
        string $customLoginPagePreview = '',
        string $formTemplate = '',
        bool $herokuApp = null,
        array $addOns = [],
        array $mobile = []
    ) {
        $body = [
            'name' => $name,
            'sso' => $sso,
            'cross_origin_auth' => $xOriginAuth,
            'sso_disabled' => $ssoDisabled,
            'custom_login_page_on' => $customLoginPageOn,
        ];

        $this->addStringProperty($body, 'desciption', $description);
        $this->addStringProperty($body, 'client_secret', $secret);
        $this->addStringProperty($body, 'logo_uri', $logoUri);
        $this->addStringProperty($body, 'token_endpoint_auth_method', $endpoint);
        $this->addStringProperty($body, 'app_type', $appType);
        $this->addStringProperty($body, 'cross_origin_loc', $xOriginLoc);
        $this->addStringProperty($body, 'custom_login_page', $customLoginPage);
        $this->addStringProperty($body, 'custom_login_page_preview', $customLoginPagePreview);
        $this->addStringProperty($body, 'form_template', $formTemplate);
        $this->addBooleanProperty($body, 'oidc_conformant', $oidcConformant);
        $this->addBooleanProperty($body, 'is_heroku_app', $herokuApp);
        $this->addArrayProperty($body, 'client_metadata', $metadata);
        $this->addArrayProperty($body, 'callbacks', $callbacks);
        $this->addArrayProperty($body, 'allowed_origins', $allowedOrigins);
        $this->addArrayProperty($body, 'web_origins', $webOrigins);
        $this->addArrayProperty($body, 'client_aliases', $clientAliases);
        $this->addArrayProperty($body, 'allowed_clients', $allowedClients);
        $this->addArrayProperty($body, 'allowed_logout_urls', $allowedLogoutUrls);
        $this->addArrayProperty($body, 'grant_types', $grantTypes);
        $this->addArrayProperty($body, 'jwt_configuration', $jwtConfiguration);
        $this->addArrayProperty($body, 'encryption_key', $encryptionKey);
        $this->addArrayProperty($body, 'addons', $addOns);
        $this->addArrayProperty($body, 'mobile', $mobile);

        $response = $this->apiClient
            ->method('post')
            ->addPath('clients')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
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
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Clients/get_clients_by_id
     */
    public function get(string $id, string $fields = '', bool $includeFields = true)
    {
        $params = [
            'include_fields' => $includeFields,
        ];

        $this->addStringProperty($params, 'fields', $fields);

        $response = $this->apiClient
            ->method('get')
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
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Clients/delete_clients_by_id
     */
    public function delete(string $id)
    {
        $response = $this->apiClient
            ->method('delete')
            ->addPath('clients')
            ->addPath($id)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Update a client
     * Important: The client_secret and encryption_key attributes can only be updated with the update:client_keys scope.
     * Required scopes: "update:clients update:client_keys"
     *
     * @param string    $id                     The id of the client to retrieve
     * @param string    $name                   The name of the client. Must contain at least one character. Does not
     *                                          allow '<' or '>'
     * @param string    $description            Free text description of the purpose of the Client. (Max character length: 140)
     * @param string    $secret                 The secret used to sign tokens for the client
     * @param string    $logoUri                The URL of the client logo (recommended size: 150x150)
     * @param array     $metadata               meta
     * @param array     $callbacks              A set of URLs that are valid to call back from Auth0 when authenticating users
     * @param array     $allowedOrigins         A set of URLs that represents valid origins for CORS
     * @param array $webOrigins                 A set of URLs that represents valid web origins for use with web message
     *                                          response mode
     * @param array     $clientAliases          List of audiences for SAML protocol
     * @param array $allowedClients             Ids of clients that will be allowed to perform delegation requests. Clients that
     *                                          will be allowed to make delegation request. By default, all your clients will be
     *                                          allowed. This field allows you to specify specific clients
     * @param array     $allowedLogoutUrls      A set of URLs that are valid to redirect to after logout from Auth0
     * @param array     $grantTypes             A set of grant types that the client is authorized to use
     * @param string $endpoint                  Defines the requested authentication method for the token endpoint. Possible
     *                                          values are 'none' (public client without a client secret), 'client_secret_post'
     *                                          (client uses HTTP POST parameters) or 'client_secret_basic'
     *                                          (client uses HTTP Basic) ['none' or 'client_secret_post' or 'client_secret_basic']
     * @param string $appType                   The type of application this client represents ['native' or 'spa' or 'regular_web'
     *                                          or 'non_interactive' or 'rms' or 'box' or 'cloudbees' or 'concur' or 'dropbox' or
     *                                          'mscrm' or 'echosign' or 'egnyte' or 'newrelic' or 'office365' or 'salesforce' or
     *                                          'sentry' or 'sharepoint' or 'slack' or 'springcm' or 'zendesk' or 'zoom']
     * @param bool|null $oidcConformant         Whether this client will conform to strict OIDC specifications
     * @param array     $jwtConfiguration       jwt
     * @param array     $encryptionKey          encrpytion
     * @param bool|null $sso                    true to use Auth0 instead of the IdP to do Single Sign On, false otherwise
     *                                          (default: false)
     * @param bool|null $xOriginAuth            true if this client can be used to make cross-origin authentication requests,
     *                                          false otherwise (default: false)
     * @param string $xOriginLoc                Url fo the location in your site where the cross origin verification takes place
     *                                          for the cross-origin auth flow when performing Auth in your own domain instead of
     *                                          Auth0 hosted login page.
     * @param bool|null $ssoDisabled            true to disable Single Sign On, false otherwise (default: false)
     * @param bool|null $customLoginPageOn      true if the custom login page is to be used, false otherwise.
     * @param string    $customLoginPage        The content (HTML, CSS, JS) of the custom login page
     * @param string    $customLoginPagePreview The content (HTML, CSS, JS) of the custom login preview page
     * @param string    $formTemplate           Form template for WS-Federation protocol
     * @param array     $addOns                 addons
     * @param array     $mobile                 mobile
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Clients/patch_clients_by_id
     */
    public function update(
        string $id,
        string $name = '',
        string $description = '',
        string $secret = '',
        string $logoUri = '',
        array $metadata = [],
        array $callbacks = [],
        array $allowedOrigins = [],
        array $webOrigins = [],
        array $clientAliases = [],
        array $allowedClients = [],
        array $allowedLogoutUrls = [],
        array $grantTypes = [],
        string $endpoint = '',
        string $appType = '',
        bool $oidcConformant = null,
        array $jwtConfiguration = [],
        array $encryptionKey = [],
        bool $sso = null,
        bool $xOriginAuth = null,
        string $xOriginLoc = '',
        bool $ssoDisabled = null,
        bool $customLoginPageOn = null,
        string $customLoginPage = '',
        string $customLoginPagePreview = '',
        string $formTemplate = '',
        array $addOns = [],
        array $mobile = []
    ) {
        $this->addStringProperty($body, 'name', $name);
        $this->addStringProperty($body, 'desciption', $description);
        $this->addStringProperty($body, 'client_secret', $secret);
        $this->addStringProperty($body, 'logo_uri', $logoUri);
        $this->addStringProperty($body, 'token_endpoint_auth_method', $endpoint);
        $this->addStringProperty($body, 'app_type', $appType);
        $this->addStringProperty($body, 'cross_origin_loc', $xOriginLoc);
        $this->addStringProperty($body, 'custom_login_page', $customLoginPage);
        $this->addStringProperty($body, 'custom_login_page_preview', $customLoginPagePreview);
        $this->addStringProperty($body, 'form_template', $formTemplate);
        $this->addBooleanProperty($body, 'oidc_conformant', $oidcConformant);
        $this->addBooleanProperty($body, 'sso', $sso);
        $this->addBooleanProperty($body, 'cross_origin_auth', $xOriginAuth);
        $this->addBooleanProperty($body, 'sso_disabled', $ssoDisabled);
        $this->addBooleanProperty($body, 'custom_login_page_on', $customLoginPageOn);
        $this->addArrayProperty($body, 'client_metadata', $metadata);
        $this->addArrayProperty($body, 'callbacks', $callbacks);
        $this->addArrayProperty($body, 'allowed_origins', $allowedOrigins);
        $this->addArrayProperty($body, 'web_origins', $webOrigins);
        $this->addArrayProperty($body, 'client_aliases', $clientAliases);
        $this->addArrayProperty($body, 'allowed_clients', $allowedClients);
        $this->addArrayProperty($body, 'allowed_logout_urls', $allowedLogoutUrls);
        $this->addArrayProperty($body, 'grant_types', $grantTypes);
        $this->addArrayProperty($body, 'jwt_configuration', $jwtConfiguration);
        $this->addArrayProperty($body, 'encryption_key', $encryptionKey);
        $this->addArrayProperty($body, 'addons', $addOns);
        $this->addArrayProperty($body, 'mobile', $mobile);

        $response = $this->apiClient
            ->method('patch')
            ->addPath('clients')
            ->addPath($id)
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
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
     * @throws ClassNotFoundException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Clients/post_rotate_secret
     */
    public function rotateSecret(string $id)
    {
        $response = $this->apiClient
            ->method('post')
            ->addPath('clients')
            ->addPath($id)
            ->addPath('rotate')
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
