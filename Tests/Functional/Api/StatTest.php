<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

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

use Bitmotion\Auth0\Api\Management\StatApi;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Stat;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Tests\Functional\Auth0TestCase;

class StatTest extends Auth0TestCase
{
    protected $scopes = [
        Scope::STATS_READ,
    ];

    /**
     * Tries to instantiate the StatApi
     *
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getStatApi
     */
    public function instantiateApi(): StatApi
    {
        $statApi = $this->getApiUtility()->getStatApi(...$this->scopes);
        self::assertInstanceOf(StatApi::class, $statApi);

        return $statApi;
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\StatApi::getActiveUsersCount
     */
    public function countUsers(StatApi $statApi): void
    {
        $userCount = $statApi->getActiveUsersCount();
        self::assertIsInt($userCount);
        self::assertGreaterThan(0, $userCount);
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\StatApi::getDailyStats
     */
    public function getLogs(StatApi $statApi): void
    {
        $stats = $statApi->getDailyStats();
        self::assertIsArray($stats);
        self::assertNotEmpty($stats);

        $stat = array_shift($stats);
        self::assertInstanceOf(Stat::class, $stat);
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\StatApi::getDailyStats
     */
    public function getByDate(StatApi $statApi): void
    {
        $dateTime = new \DateTime('2019-02-05T00:00:00.000Z');
        $dateTill = new \DateTime('2019-02-10T00:00:00.000Z');
        $stats = $statApi->getDailyStats($dateTime, $dateTill);
        $stat = array_shift($stats);
        self::assertSame($dateTime->getTimestamp(), $stat->getDate()->getTimestamp());
    }
}
