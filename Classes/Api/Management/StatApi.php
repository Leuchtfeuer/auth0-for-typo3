<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Symfony\Component\VarExporter\Exception\ClassNotFoundException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class StatApi extends GeneralManagementApi
{
    /**
     * Retrieves the number of active users that logged in during the last 30 days.
     * Required scope: "read:stats"
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Stats/get_active_users
     */
    public function getActiveUsersCount()
    {
        $response = $this->apiClient
            ->method('get')
            ->addPath('stats')
            ->addPath('active-users')
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Retrieves the number of logins that occurred in the entered date range.
     * Required scope: "read:stats"
     *
     * @param \DateTime $from The first day of the period (inclusive) in YYYYMMDD format.
     * @param \DateTime $to   The last day of the period (inclusive) in YYYYMMDD format.
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Stats/get_daily
     */
    public function getDailyStats(\DateTime $from, \DateTime $to)
    {
        $response = $this->apiClient
            ->method('get')
            ->addPath('stats')
            ->addPath('daily')
            ->withParam('from', $from->format('Ymd'))
            ->withParam('to', $to->format('Ymd'))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
