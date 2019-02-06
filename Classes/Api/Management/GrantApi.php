<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\Ticket;
use Symfony\Component\VarExporter\Exception\ClassNotFoundException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class GrantApi extends GeneralManagementApi
{
    /**
     * Manage the grants associated with your account.
     * Required scope: "read:grants"
     *
     * @param string $user          The user_id of the grants to retrieve
     * @param string $client        The client_id of the grants to retrieve
     * @param string $audience      The audience of the grants to retrieve
     * @param int    $page          The page number. Zero based
     * @param int    $perPage       The amount of entries per page. Default: no paging is used, all grants are returned
     * @param bool   $includeTotals true if a query summary must be included in the result, false otherwise. Default false.
     * @param array  $scope
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Grants/get_grants
     */
    public function get(
        string $user = '',
        string $client = '',
        string $audience = '',
        int $page = 0, int
        $perPage = 50, bool
        $includeTotals = false,
        array $scope = []
    ) {
        $params = [
            'per_page' => $perPage,
            'page' => $page,
            'include_totals' => $includeTotals
        ];

        if ($user !== '') {
            $params['user_id'] = $user;
        }

        if ($client !== '') {
            $params['client_id'] = $client;
        }

        if ($audience !== '') {
            $params['audience'] = $audience;
        }

        if (!empty($scope)) {
            $params['scope'] = $scope;
        }


        $response = $this->apiClient
            ->method('get')
            ->addPath('grants')
            ->withDictParams($params)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Delete a grant
     * Required scope: "delete:grant"
     *
     * @param string $id   The id of the grant to delete
     * @param string $user The user_id of the grants to delete
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Grants/delete_grants_by_id
     */
    public function delete(string $id, string $user)
    {
        $response = $this->apiClient
            ->method('delete')
            ->addPath('grants')
            ->addPath($id)
            ->withParam('user_id', $user)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
