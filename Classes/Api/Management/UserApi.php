<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Symfony\Component\VarExporter\Exception\ClassNotFoundException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class UserApi extends GeneralManagementApi
{
    /**
     * This endpoint can be used to retrieve a list of users. With this endpoint it is possible to:
     *  - Specify a search criteria for users
     *  - Sort the users to be returned
     *  - Select the fields to be returned
     *  - Specify the amount of users to retrieve per page and the page number
     * Required scopes: "read:users read:user_idp_tokens"
     *
     * @param string $query         Query in Lucene query string syntax. Some query types cannot be used on metadata fields,
     *                              for details see https://auth0.com/docs/users/search/v3/query-syntax#searchable-fields
     * @param string $connection    Connection filter. Only applies when using search_engine=v1. To filter by connection with
     *                              search_engine=v2|v3, use q=identities.connection:"connection_name"
     * @param int    $perPage       The amount of entries per page. Default: 50. Max value: 100
     * @param int    $page          The page number. Zero based
     * @param bool   $includeTotals true if a query summary must be included in the result
     * @param string $sorting       The field to use for sorting. Use field:order where order is 1 for ascending and -1 for
     *                              descending. For example created_at:1
     * @param string $fields        A comma separated list of fields to include or exclude (depending on include_fields) from
     *                              the result, empty to retrieve all fields
     * @param bool   $includeFields true if the fields specified are to be included in the result, false otherwise. Defaults
     *                              to true
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Users/get_users
     */
    public function search(
        string $query,
        string $connection = '',
        int $perPage = 50,
        int $page = 0,
        bool $includeTotals = false,
        string $sorting = '',
        string $fields = '',
        bool $includeFields = true
    ) {
        $params = [
            'q' => $query,
            'search_engine' => 'v3',
            'include_totals' => $includeTotals,
            'include_fields' => $includeFields,
        ];

        if ($connection !== '') {
            $params['connection'] = $connection;
        }

        if ($perPage !== 50) {
            $params['per_page'] = $perPage;
        }

        if ($page !== 0) {
            $params['page'] = $page;
        }

        if ($sorting !== '') {
            $params['sort'] = $sorting;
        }

        if ($fields !== '') {
            $params['fields'] = $fields;
        }

        $response = $this->apiClient
            ->method('get')
            ->addPath('users')
            ->withParams($params)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Creates a new user according to the JSON object received in body. It works only for database and passwordless connections.
     * The samples on the right show you every attribute that could be used. The attribute connection is always mandatory but
     * depending on the type of connection you are using there could be others too. For instance, database connections require
     * email and password.
     * Required scope: "create:users"
     *
     * @param string $connection    The connection the user belongs to
     * @param string $id            The id of the user
     * @param string $email         The user's email
     * @param string $username      The user's username. Only valid if the connection requires a username
     * @param string $password      The user's password (mandatory for non SMS connections)
     * @param string $givenName     The user's user given name(s)
     * @param string $familyName    The user's family name(s)
     * @param string $name          The user's full name
     * @param string $nickname      The user's nickname
     * @param string $picture       A URI pointing to the user's picture
     * @param string $phone         The user's phone number (following the E.164 recommendation), only valid for users to be
     *                              added to SMS connections
     * @param array  $userMetadata
     * @param array  $appMetadata
     * @param bool   $blocked       true if the user should be blocked
     * @param bool   $emailVerified true if the user's email is verified, false otherwise. If it is true then the user will not
     *                              receive a verification email, unless verify_email: true was specified
     * @param bool   $verifyEmail   If true, the user will receive a verification email after creation, even if created with
     *                              email_verified set to true. If false, the user will not receive a verification email, even if
     *                              created with email_verified set to false. If unspecified, defaults to the behavior determined
     *                              by the value of email_verified.
     * @param bool   $phoneVerified true if the user's phone number is verified, false otherwise. When the user is added to a SMS
     *                              connection, they will not receive an verification SMS if this is true.
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Users/post_users
     */
    public function create(
        string $connection,
        string $id = '',
        string $email = '',
        string $username = '',
        string $password = '',
        string $givenName = '',
        string $familyName = '',
        string $name = '',
        string $nickname = '',
        string $picture = '',
        string $phone = '',
        array $userMetadata = [],
        array $appMetadata = [],
        bool $blocked = false,
        bool $emailVerified = false,
        bool $verifyEmail = false,
        bool $phoneVerified = false
    ) {
        $body = [
            'connection' => $connection,
            'blocked' => $blocked,
            'email_verified' => $emailVerified,
            'verify_email' => $verifyEmail,
            'phone_verified' => $phoneVerified,
            'app_metadata' => $appMetadata,
            'user_metadata' => $userMetadata,
        ];

        if ($id !== '') {
            $body['user_id'] = $id;
        }

        if ($email !== '') {
            $body['email'] = $email;
        }

        if ($username !== '') {
            $body['username'] = $username;
        }

        if ($password !== '') {
            $body['password'] = $password;
        }

        if ($givenName !== '') {
            $body['given_name'] = $givenName;
        }

        if ($familyName !== '') {
            $body['familiy_name'] = $familyName;
        }

        if ($name !== '') {
            $body['name'] = $name;
        }

        if ($nickname !== '') {
            $body['nickname'] = $nickname;
        }

        if ($picture !== '') {
            $body['picture'] = $picture;
        }

        if ($phone !== '') {
            $body['phone_number'] = $phone;
        }

        $response = $this->apiClient
            ->method('post')
            ->addPath('users')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * This endpoint can be used to retrieve user details given the user_id.
     * Required scopes: "read:users read:user_idp_tokens"
     *
     * @param string $id            The user_id of the user to retrieve
     * @param string $fields        A comma separated list of fields to include or exclude (depending on include_fields) from
     *                              the result, empty to retrieve all fields
     * @param bool   $includeFields true if the fields specified are to be included in the result, false otherwise. Defaults to true
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Users/get_users_by_id
     */
    public function get(string $id, string $fields = '', bool $includeFields = true)
    {
        $params = [
            'include_fields' => $includeFields
        ];

        if ($fields !== '') {
            $params['fields'] = $fields;
        }

        $response = $this->apiClient
            ->method('get')
            ->addPath('users')
            ->addPath($id)
            ->withParams($params)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * This endpoint can be used to delete a single user based on the id.
     * Required scope: "delete:users"
     *
     * @param string $id The user_id of the user to delete
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Users/delete_users_by_id
     */
    public function delete(string $id)
    {
        $response = $this->apiClient
            ->method('delete')
            ->addPath('users')
            ->addPath($id)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Updates a user with the object's properties received in the request's body (the object should be a JSON object).
     * Some considerations:
     *  - The properties of the new object will replace the old ones.
     *  - The metadata fields are an exception to this rule (user_metadata and app_metadata). These properties are merged instead
     *    of being replaced but be careful, the merge only occurs on the first level.
     *  - If you are updating email_verified, phone_verified, username or password you need to specify the connection property too.
     *  - If your are updating email or phone_number you need to specify the connection and the client_id properties.
     *  - Updating the blocked to false does not affect the user's blocked state from an excessive amount of incorrectly provided
     *    credentials. Use the "Unblock a user" endpoint from the "User Blocks" API for that.
     * Required scope: "update:users update:users_app_metadata"
     *
     * @param string $id
     * @param array  $data
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Users/patch_users_by_id
     */
    public function update(string $id, array $data)
    {
        $response = $this->apiClient
            ->method('patch')
            ->addPath('users')
            ->addPath($id)
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($data))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Retrieve every log event for a specific user id
     * Required scope: "read:logs"
     *
     * @param string $id            The user_id of the logs to retrieve
     * @param int    $page          The page number. Zero based
     * @param int    $perPage       The amount of entries per page. Default: 50. Max value: 100
     * @param string $sort          The field to use for sorting. Use field:order where order is 1 for ascending and -1 for
     *                              descending. For example date:-1
     * @param bool   $includeTotals true if a query summary must be included in the result, false otherwise. Default false.
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Users/get_logs_by_user
     */
    public function getLog(string $id, int $page = 0, int $perPage = 50, string $sort = '', bool $includeTotals = false)
    {
        $params = [
            'include_totals' => $includeTotals,
        ];

        if ($page !== 0) {
            $params['page'] = $page;
        }

        if ($perPage !== 50) {
            $params['per_page'] = $perPage;
        }

        if ($sort !== '') {
            $params['sort'] = $sort;
        }

        $response = $this->apiClient
            ->method('get')
            ->addPath('users')
            ->addPath($id)
            ->addPath('logs')
            ->withParams($params)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Retrieves all Guardian enrollments.
     * Required scope: "read:users"
     *
     * @param string $id The user_id of the user to delete
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Users/get_enrollments
     */
    public function getEnrollments(string $id)
    {
        $response = $this->apiClient
            ->method('get')
            ->addPath('users')
            ->addPath($id)
            ->addPath('enrollments')
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * This endpoint can be used to delete the multifactor provider settings for a particular user. This will force user to
     * re-configure the multifactor provider.
     * Required scope: "update:users"
     *
     * @param string $id       The user_id of the user to delete
     * @param string $provider The multifactor provider. Supported values 'duo' or 'google-authenticator'
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Users/delete_multifactor_by_provider
     */
    public function deleteMultifactorProvider(string $id, string $provider = 'duo')
    {
        $response = $this->apiClient
            ->method('delete')
            ->addPath('users')
            ->addPath($id)
            ->addPath('multifactor')
            ->addPath($provider)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Unlinks an identity from the target user, and it becomes a separated user again.
     * Required scope: "update:users"
     *
     * @param string $id         The user_id of the primary user account.
     * @param string $idToUnlink The unique identifier of the secondary linked account. (Only the id after the '|' pipe.
     *                           Ex: '123456789081523216417' in google-oauth2|123456789081523216417
     * @param string $provider   The identity provider of the secondary linked account.
     *                           Ex: 'google-oauth2' in google-oauth2|123456789081523216417
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Users/delete_user_identity_by_user_id
     */
    public function unlinkIdentity(string $id, string $idToUnlink, string $provider = 'ad')
    {
        $response = $this->apiClient
            ->method('delete')
            ->addPath('users')
            ->addPath($id)
            ->addPath('identities')
            ->addPath($provider)
            ->addPath($idToUnlink)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * This endpoint removes the current Guardian recovery code then generates and returns a new one.
     * Required scope: "update:users"
     *
     * @param string $id The user_id of the user which guardian code will be regenerated
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Users/delete_user_identity_by_user_id
     */
    public function createRecoveryCode(string $id)
    {
        $response = $this->apiClient
            ->method('post')
            ->addPath('users')
            ->addPath($id)
            ->addPath('recovery-code-regeneration')
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Links the account specified in the body (secondary account) to the account specified by the id param of the URL
     * (primary account).
     * On successful linking, the endpoint returns the new array of the primary account identities.
     * In this case only the link_with param is required in the body, containing the JWT obtained upon the secondary account's
     * authentication.
     * Required scope: "update:current_user_identities"
     *
     * @param string $id       The user_id of the primary identity where you are linking the secondary account to.
     * @param string $linkWith The JWT of the secondary account being linked. If sending this parameter, the 'provider',
     *                         'user_id' and 'connection_id' params are invalid.
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     *
     * @see https://auth0.com/docs/api/management/v2#!/Users/post_identities
     */
    private function linkAccountByToken(string $id, string $linkWith)
    {
        // TODO: Authorization Header Bearer PRIMARY_ACCOUNT_JWT
        $response = $this->apiClient
            ->method('post')
            ->addPath('users')
            ->addPath($id)
            ->addPath('identities')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode(['link_with' => $linkWith]))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Links the account specified in the body (secondary account) to the account specified by the id param of the URL
     * (primary account).
     * On successful linking, the endpoint returns the new array of the primary account identities.
     * In this case you need to send provider and user_id in the body. Optionally you can also send the connection_id param which
     * is suitable for identifying a particular database connection for the 'auth0' provider.
     * Required scope: "update:users"
     *
     * @param string $id         The user_id of the primary identity where you are linking the secondary account to.
     * @param string $provider   The type of identity provider of the secondary account being linked.
     * @param string $connection The id of the connection of the secondary account being linked. This is optional and may be
     *                           useful in the case of having more than a database connection for the 'auth0' provider.
     * @param string $idToLink   The user_id of the secondary account being linked.
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     *
     * @see https://auth0.com/docs/api/management/v2#!/Users/post_identities
     */
    public function linkAccount(string $id, string $provider = '', string $connection = '', string $idToLink = '')
    {
        $body = [];

        if ($provider !== '') {
            $body['provider'] = $provider;
        }

        if ($connection !== '') {
            $body['connection_id'] = $connection;
        }

        if ($idToLink !== '') {
            $body['user_id'] = $idToLink;
        }

        $response = $this->apiClient
            ->method('post')
            ->addPath('users')
            ->addPath($id)
            ->addPath('identities')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
