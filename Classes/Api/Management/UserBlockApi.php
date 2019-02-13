<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\User;
use Bitmotion\Auth0\Domain\Model\Auth0\UserBlock;
use TYPO3\CMS\Extbase\Object\Exception;

class UserBlockApi extends GeneralManagementApi
{
    /**
     * This endpoint can be used to retrieve a list of blocked IP addresses for a given key.
     * Required scope: "read:users"
     *
     * @param string $identifier Should be any of: username, phone_number, email.
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return UserBlock|UserBlock[]
     */
    public function getBlocks(string $identifier)
    {
        $response = $this->apiClient
            ->method('get')
            ->addPath('user-blocks')
            ->withParam('identifier', $identifier)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * This endpoint can be used to unblock a given key that was blocked due to an excessive amount of incorrectly provided
     * credentials.
     * Required scope: "update:users"
     *
     * @param string $identifier Should be any of: username, phone_number, email.
     *
     * @throws CoreException
     * @return bool
     * @see https://auth0.com/docs/api/management/v2#!/User_Blocks/delete_user_blocks
     */
    public function unblock(string $identifier)
    {
        $response = $this->apiClient
            ->method('delete')
            ->addPath('user-blocks')
            ->withParam('identifier', $identifier)
            ->setReturnType('object')
            ->call();

        return ($response->getStatusCode() === 204);
    }

    /**
     * This endpoint can be used to retrieve a list of blocked IP addresses of a particular user given a user_id.
     * Required scope: "read:users"
     *
     * @param User $user The user to retrieve
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return UserBlock|UserBlock[]
     * @see https://auth0.com/docs/api/management/v2#!/User_Blocks/get_user_blocks_by_id
     */
    public function getUserBlocks(User $user)
    {
        $response = $this->apiClient
            ->method('get')
            ->addPath('user-blocks')
            ->addPath($user->getUserId())
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * This endpoint can be used to unblock a user that was blocked due to an excessive amount of incorrectly provided credentials.
     * This endpoint does not unblock users that were blocked by admins. Click here for more information on how to unblock a user
     * that was blocked by an admin.
     * Required scope: "update:users"
     *
     * @param User $user The user to update.
     *
     * @throws CoreException
     * @return bool
     * @see https://auth0.com/docs/api/management/v2#!/User_Blocks/delete_user_blocks_by_id
     */
    public function unblockUser(User $user)
    {
        $response = $this->apiClient
            ->method('delete')
            ->addPath('user-blocks')
            ->addPath($user->getUserId())
            ->setReturnType('object')
            ->call();

        return $response->getStatusCode() === 204;
    }
}
