<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\User;
use TYPO3\CMS\Extbase\Object\Exception;

class UserByEmailApi extends GeneralManagementApi
{
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
     * @param string $fields        A comma separated list of fields to include or exclude (depending on include_fields) from the
     *                              result, empty to retrieve all fields
     * @param bool   $includeFields true if the fields specified are to be included in the result, false otherwise. Defaults to true
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return User|User[]
     * @see https://auth0.com/docs/api/management/v2#!/Users_By_Email/get_users_by_email
     */
    public function get(string $email, string $fields = '', bool $includeFields = true)
    {
        $params = [
            'email' => rawurlencode($email),
            'include_fields' => $includeFields,
        ];

        $this->addStringProperty($params, 'fields', $fields);

        $response = $this->client
            ->request('get')
            ->addPath('users-by-email')
            ->withDictParams($params)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response, User::class);
    }
}
