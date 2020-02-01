<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

/***
 *
 * This file is part of the "Auth0 for TYPO3" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Florian Wessels <f.wessels@bitmotion.de>, Bitmotion GmbH
 *
 ***/

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\Api\Client;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Enrollment;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Log;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\User;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\VarExporter\Exception\ClassNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\Exception;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class UserApi extends GeneralManagementApi
{
    const ALLOWED_ATTRIBUTES_UPDATE = [
        'blocked',
        'emailVerified',
        'email',
        'verifyEmail',
        'password',
        'phoneNumber',
        'phoneVerified',
        'userMetadata',
        'appMetadata',
        'username',
    ];

    const ALLOWED_ATTRIBUTES_CREATE = [
        'userId',
        'email',
        'username',
        'password',
        'phoneNumber',
        'userMetadata',
        'blocked',
        'emailVerified',
        'phoneVerified',
        'appMetadata',
        'givenName',
        'familyName',
        'name',
        'nickname',
        'picture',
    ];

    const TYPE_USER = 'user_metadata';
    const TYPE_APP = 'app_metadata';
    const TYPE_BOTH = 'user_app_metadata';

    public function __construct(Client $client)
    {
        $this->extractor = new ReflectionExtractor();
        $this->normalizer[] = new DateTimeNormalizer();

        parent::__construct($client);
    }

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
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return User|User[]
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
            'per_page' => $perPage,
            'page' => $page,
        ];

        $this->addStringProperty($params, 'connection', $connection);
        $this->addStringProperty($params, 'sort', $sorting);
        $this->addStringProperty($params, 'fields', $fields);

        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('users')
            ->withDictParams($params)
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
     * @param User   $user           The user to create
     * @param string $connectionName The name of the connection the user belongs to
     * @param bool   $verifyEmail    If true, the user will receive a verification email after creation, even if created with
     *                               email_verified set to true. If false, the user will not receive a verification email, even if
     *                               created with email_verified set to false. If unspecified, defaults to the behavior determined
     *                               by the value of email_verified.
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return User|User[]
     * @see https://auth0.com/docs/api/management/v2#!/Users/post_users
     */
    public function create(User $user, string $connectionName, bool $verifyEmail = true)
    {
        $data = $this->normalize($user, 'array', self::ALLOWED_ATTRIBUTES_CREATE);
        $this->addStringProperty($data, 'connection', $connectionName);
        $this->addBooleanProperty($data, 'verify_email', $verifyEmail);

        $response = $this->client
            ->request(Client::METHOD_POST)
            ->addPath('users')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($data))
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
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return User|User[]
     * @see https://auth0.com/docs/api/management/v2#!/Users/get_users_by_id
     */
    public function get(string $id, string $fields = '', bool $includeFields = true)
    {
        $params = [
            'include_fields' => $includeFields,
        ];

        $this->addStringProperty($params, 'fields', $fields);

        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('users')
            ->addPath($id)
            ->withDictParams($params)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Get only users and / or app metadata
     * Required scope: "read:users read:user_idp_tokens"
     *
     * @param string $id   The user_id of the user to retrieve
     * @param string $type Whether to fetch user and or app metadata or not
     *
     * @throws ApiException
     * @throws CoreException
     * @throws Exception
     * @return array
     */
    public function getMetadata(string $id, string $type)
    {
        $user = $this->get($id, implode(',', [self::TYPE_USER, self::TYPE_APP]));

        switch ($type) {
            case self::TYPE_USER:
                $data = $user->getUserMetadata();
                break;

            case self::TYPE_APP:
                $data = $user->getAppMetadata();
                break;

            case self::TYPE_BOTH:
                $data = [
                    self::TYPE_USER => $user->getUserMetadata(),
                    self::TYPE_APP => $user->getAppMetadata(),
                ];
                break;

            default:
                $data = [];
        }

        return $data;
    }

    /**
     * This endpoint can be used to delete a single user based on the id.
     * Required scope: "delete:users"
     *
     * @param string $id The user_id of the user to delete
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return bool
     * @see https://auth0.com/docs/api/management/v2#!/Users/delete_users_by_id
     */
    public function delete(string $id)
    {
        $response = $this->client
            ->request(Client::METHOD_DELETE)
            ->addPath('users')
            ->addPath($id)
            ->setReturnType('object')
            ->call();

        return $response->getStatusCode() === 204;
    }

    /**
     * Updates a user with the object's properties received in the request's body (the object should be a JSON object).
     * Some considerations:
     *  - The properties of the new object will replace the old ones.
     *  - The metadata fields are an exception to this rule (user_metadata and app_metadata). These properties are merged instead
     *    of being replaced but be careful, the merge only occurs on the first level.
     *  - If you are updating email_verified, phone_verified, username or password you need to specify the connection property too.
     *  - If your are updating email or phone_number you need to specify the connection and the client_id properties.
     * Required scope: "update:users update:users_app_metadata"
     *
     * @param User   $user           The user object to update.
     * @param string $connectionName Connection ID. Necessary for updating email, phone_number, email_verified, phone_verified,
     *                               username or password.
     * @param string $client         Client ID. Necessary for updating email or phone_number.
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return User|User[]
     * @see https://auth0.com/docs/api/management/v2#!/Users/patch_users_by_id
     */
    public function update(User $user, $connectionName = '', $client = '')
    {
        $originUser = $this->get($user->getUserId());
        $updateMailOrPhone = $this->shouldUpdateMailOrPhone($user, $originUser, $connectionName, $client);
        $updateCoreData = $this->shouldUpdateCoreData($user, $originUser, $connectionName);

        if ($originUser->isBlocked() === true && $user->isBlocked() === false) {
            $user->setBlocked(true);
            $this->logger->warning(
                'Updating the blocked to false does not affect the user\'s blocked state from an excessive amount of ' .
                'incorrectly provided credentials. Use the "Unblock a user" endpoint from the "User Blocks" API for that.'
            );
        }

        $dataToUpdate = $this->normalize($user, 'array', self::ALLOWED_ATTRIBUTES_UPDATE);
        $this->cleanData($dataToUpdate, $originUser);
        $this->populateUpdateData($dataToUpdate, $updateMailOrPhone, $updateCoreData, $connectionName, $client);

        return (empty($dataToUpdate)) ? $user : $this->updateUser($user, $dataToUpdate);
    }

    /**
     * @param User $user   the user you want to update
     * @param array  $data data to update
     * @param string $type user for user_metadata,
     *
     * @throws ApiException
     * @throws CoreException
     * @throws Exception
     * @return bool true when the data could be updated, false otherwise
     */
    public function updateMetadata(User $user, array $data, $type): bool
    {
        if ($type !== self::TYPE_APP && $type !== self::TYPE_USER) {
            $this->logger->error('Given type is not allowed.');

            return false;
        }

        $user = $this->updateUser($user, [$type => $data]);

        return $user instanceof User;
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
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return Log|Log[]
     * @see https://auth0.com/docs/api/management/v2#!/Users/get_logs_by_user
     */
    public function getLog(string $id, int $page = 0, int $perPage = 50, string $sort = '', bool $includeTotals = false)
    {
        $params = [
            'include_totals' => $includeTotals,
            'page' => $page,
            'per_page' => $perPage,
        ];

        $this->addStringProperty($params, 'sort', $sort);

        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('users')
            ->addPath($id)
            ->addPath('logs')
            ->withDictParams($params)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response, Log::class);
    }

    /**
     * Retrieves all Guardian enrollments.
     * Required scope: "read:users"
     *
     * @param string $id The user_id of the user to delete
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return Enrollment|Enrollment[]
     * @see https://auth0.com/docs/api/management/v2#!/Users/get_enrollments
     */
    public function getEnrollments(string $id)
    {
        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('users')
            ->addPath($id)
            ->addPath('enrollments')
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response, Enrollment::class);
    }

    /**
     * This endpoint can be used to delete the multifactor provider settings for a particular user. This will force user to
     * re-configure the multifactor provider.
     * Required scope: "update:users"
     *
     * @param string $id       The user_id of the user to delete
     * @param string $provider The multifactor provider. Supported values 'duo' or 'google-authenticator'
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Users/delete_multifactor_by_provider
     */
    public function deleteMultifactorProvider(string $id, string $provider = 'duo')
    {
        $response = $this->client
            ->request(Client::METHOD_DELETE)
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
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Users/delete_user_identity_by_user_id
     */
    public function unlinkIdentity(string $id, string $idToUnlink, string $provider = 'ad')
    {
        $response = $this->client
            ->request(Client::METHOD_DELETE)
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
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Users/delete_user_identity_by_user_id
     */
    public function createRecoveryCode(string $id)
    {
        $response = $this->client
            ->request(Client::METHOD_POST)
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
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     *
     * @see https://auth0.com/docs/api/management/v2#!/Users/post_identities
     */
    private function linkAccountByToken(string $id, string $linkWith)
    {
        // TODO: Authorization Header Bearer PRIMARY_ACCOUNT_JWT
        $response = $this->client
            ->request(Client::METHOD_POST)
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
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     *
     * @see https://auth0.com/docs/api/management/v2#!/Users/post_identities
     */
    public function linkAccount(string $id, string $provider = '', string $connection = '', string $idToLink = '')
    {
        $body = [];

        $this->addStringProperty($body, 'provider', $provider);
        $this->addStringProperty($body, 'connection_id', $connection);
        $this->addStringProperty($body, 'user_id', $idToLink);

        $response = $this->client
            ->request(Client::METHOD_POST)
            ->addPath('users')
            ->addPath($id)
            ->addPath('identities')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    protected function updateUser(User $user, array $data)
    {
        $response = $this->client
            ->request(Client::METHOD_PATCH)
            ->addPath('users')
            ->addPath($user->getUserId())
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($data))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    protected function shouldUpdateMailOrPhone(User &$user, User $originUser, string $connection, string $client): bool
    {
        if ($originUser->getEmail() !== $user->getEmail() || $originUser->getPhoneNumber() !== $user->getPhoneNumber()) {
            if ($connection !== '' && $client !== '') {
                return true;
            }

            $user->setEmail($originUser->getEmail());
            $user->setPhoneNumber($originUser->getPhoneNumber());

            $this->logger->error(
                'If your are updating email or phone_number you need to specify the connection and ' .
                'the client_id properties.'
            );
        }

        return false;
    }

    protected function shouldUpdateCoreData(User &$user, User $originUser, string $connection): bool
    {
        if (
            $originUser->isEmailVerified() !== $user->isEmailVerified() ||
            $originUser->isPhoneVerified() !== $user->isPhoneVerified() ||
            $originUser->getUsername() !== $user->getUsername() ||
            !empty($user->getPassword())
        ) {
            if ($connection !== '') {
                return true;
            }

            $user->setEmailVerified($originUser->isEmailVerified());
            $user->setPhoneVerified($originUser->isPhoneVerified());
            $user->setUsername($originUser->getUsername());
            $user->setPassword(null);

            $this->logger->error(
                'If you are updating email_verified, phone_verified, username or password you need to specify ' .
                'the connection property too.'
            );
        }

        return false;
    }

    protected function cleanData(array &$data, User $user): void
    {
        foreach ($data as $key => $value) {
            $prefix = (gettype($value) === 'boolean') ? 'is' : 'get';
            $originValue = call_user_func([$user, $prefix . ucfirst(GeneralUtility::underscoredToLowerCamelCase($key))]);

            if (($value === $originValue && !is_array($value)) || (is_array($value) && empty(array_diff($value, $originValue)))) {
                unset($data[$key]);
            }
        }
    }

    protected function populateUpdateData(array &$data, bool $updateMailOrPhone, bool $updateCoreData, string $connection, string $client): void
    {
        if ($updateMailOrPhone === true) {
            $data['connection'] = $connection;
            $data['client_id'] = $client;
        }

        if ($updateCoreData) {
            $data['connection'] = $connection;
        }
    }
}
