<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\Ticket;
use Symfony\Component\VarExporter\Exception\ClassNotFoundException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class LogApi extends GeneralManagementApi
{
    /**
     * Retrieves log entries that match the specified search criteria (or list all entries if no criteria is used).
     * You can search with a criteria using the q parameter.
     * Required scope: "read:logs"
     *
     * @param string $query         Search Criteria using Query String Syntax
     * @param int    $perPage       The amount of entries per page
     * @param int    $page          The page number. Zero based
     * @param bool   $includeTotals true if a query summary must be included in the result, false otherwise. Default false.
     * @param string $sorting       The field to use for sorting. Use field:order, where order is 1 for ascending and -1 for
     *                              descending. For example date:-1
     * @param string $fields        A comma separated list of fields to include or exclude (depending on include_fields) from
     *                              the result, empty to retrieve all fields
     * @param bool   $includeFields true if the fields specified are to be included in the result, false otherwise. Defaults to
     *                              true.
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Logs/get_logs
     */
    public function search(
        string $query,
        int $perPage = 50,
        int $page = 0,
        bool $includeTotals = false,
        string $sorting = '',
        string $fields = '',
        bool $includeFields = true
    ) {
        $params = [
            'q' => $query,
            'per_page' => $perPage,
            'page' => $page,
            'include_totals' => $includeTotals,
            'include_fields' => $includeFields,
        ];

        if ($sorting !== '') {
            $params['sort'] = $sorting;
        }

        if ($fields !== '') {
            $params['fields'] = $fields;
        }

        $response = $this->apiClient
            ->method('get')
            ->addPath('logs')
            ->withDictParams($params)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Retrieves log entries that match the specified search criteria (or list all entries if no criteria is used).
     * You can search by a specific log ID (search by checkpoint).
     * When fetching logs by checkpoint, the order by date is not guaranteed
     * Required scope: "read:logs"
     *
     * @param string $from Log Event Id to start retrieving logs. You can limit the amount of logs using the take parameter
     * @param int    $take The total amount of entries to retrieve when using the from parameter.
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Logs/get_logs
     */
    public function searchByCheckpoint(string $from, int $take = 50)
    {
        $response = $this->apiClient
            ->method('get')
            ->addPath('logs')
            ->withParam('from', $from)
            ->withParams('take', $take)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Retrieves the data related to the log entry identified by id. This returns a single log entry representation as specified
     * in the schema.
     * Required scope: "read:logs"
     *
     *
     * @param string $id  The log_id of the log to retrieve
     *
     * @return object|ObjectStorage
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Logs/get_logs_by_id
     */
    public function get(string $id)
    {
        $response = $this->apiClient
            ->method('get')
            ->addPath('logs')
            ->addPath($id)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
