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

use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\Api\Client;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Log;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use TYPO3\CMS\Extbase\Object\Exception;

class LogApi extends GeneralManagementApi
{
    public function __construct(Client $client)
    {
        $this->extractor = new ReflectionExtractor();
        $this->normalizer[] = new DateTimeNormalizer();

        parent::__construct($client);
    }

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
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return Log|Log[]
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

        $this->addStringProperty($params, 'sort', $sorting);
        $this->addStringProperty($params, 'fields', $fields);

        $response = $this->client
            ->request(Client::METHOD_GET)
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
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return Log|Log[]
     * @see https://auth0.com/docs/api/management/v2#!/Logs/get_logs
     */
    public function searchByCheckpoint(string $from, int $take = 50)
    {
        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('logs')
            ->withParam('from', $from)
            ->withParam('take', $take)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Retrieves the data related to the log entry identified by id. This returns a single log entry representation as specified
     * in the schema.
     * Required scope: "read:logs"
     *
     * @param string $id  The log_id of the log to retrieve
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return Log
     * @see https://auth0.com/docs/api/management/v2#!/Logs/get_logs_by_id
     */
    public function get(string $id)
    {
        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('logs')
            ->addPath($id)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
