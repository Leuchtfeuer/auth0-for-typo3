<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\API\Helpers\ApiClient;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\User;
use Symfony\Component\VarExporter\Exception\ClassNotFoundException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class UserByEmailApi extends GeneralManagementApi
{
    public function __construct(ApiClient $apiClient)
    {
        parent::__construct($apiClient);
        $this->objectName = User::class;
    }

    /**
     * If Auth0 is the identify provider (idP), the email address associated with a user is saved in lower case, regardless
     * of how you initially provided it. For example, if you register a user as JohnSmith@example.com, Auth0 saves the user's
     * email as johnsmith@example.com.
     * In cases where Auth0 is not the idP, the `email` is stored based on the rules of idP, so make sure the search is made
     * using the correct capitalization.
     * When using this endpoint, make sure that you are searching for users via email addresses using the correct case.
     * Required scope: "read:users"
     *
     * @param string $email         The user's email
     * @param bool   $includeFields true if the fields specified are to be included in the result, false otherwise. Defaults to true
     * @param string $fields        A comma separated list of fields to include or exclude (depending on include_fields) from the
     *                              result, empty to retrieve all fields
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Users_By_Email/get_users_by_email
     */
    public function get(string $email, bool $includeFields = true, string $fields = '')
    {
        $params = [
            'email' => $email,
        ];

        if ($includeFields !== true) {
            $params['include_fields'] = $includeFields;
        }

        if ($fields !== '') {
            $params['fields'] = $fields;
        }

        $response = $this->apiClient
            ->method('get')
            ->addPath('users-by-email')
            ->withParams($params)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
