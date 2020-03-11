<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Api\Management;

/***
 *
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\Api\Client;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Stat;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use TYPO3\CMS\Extbase\Object\Exception;

class StatApi extends GeneralManagementApi
{
    public function __construct(Client $client)
    {
        $this->extractor = new ReflectionExtractor();
        $this->normalizer[] = new DateTimeNormalizer();

        parent::__construct($client);
    }

    /**
     * Retrieves the number of active users that logged in during the last 30 days.
     * Required scope: "read:stats"
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @see https://auth0.com/docs/api/management/v2#!/Stats/get_active_users
     */
    public function getActiveUsersCount(): int
    {
        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('stats')
            ->addPath('active-users')
            ->setReturnType('object')
            ->call();

        return (int)$this->mapResponse($response, '', true);
    }

    /**
     * Retrieves the number of logins that occurred in the entered date range.
     * Required scope: "read:stats"
     *
     * @param \DateTime $from The first day of the period (inclusive) in YYYYMMDD format.
     * @param \DateTime $to   The last day of the period (inclusive) in YYYYMMDD format.
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return Stat|Stat[]
     * @see https://auth0.com/docs/api/management/v2#!/Stats/get_daily
     */
    public function getDailyStats(\DateTime $from = null, \DateTime $to = null)
    {
        $params = [];

        if ($from instanceof \DateTime) {
            $params['from'] = $from->format('Ymd');
        }

        if ($to instanceof \DateTime) {
            $params['to'] = $to->format('Ymd');
        }

        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('stats')
            ->addPath('daily')
            ->withDictParams($params)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
