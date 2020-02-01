<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

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

use Bitmotion\Auth0\Api\Management\LogApi;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Log;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Tests\Functional\Auth0TestCase;

class LogTest extends Auth0TestCase
{
    protected $scopes = [
        Scope::LOG_READ,
    ];

    /**
     * Tries to instantiate the LogApi
     *
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getLogApi
     */
    public function instantiateApi(): LogApi
    {
        $logApi = $this->getApiUtility()->getLogApi(...$this->scopes);
        self::assertInstanceOf(LogApi::class, $logApi);

        return $logApi;
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\LogApi::search
     */
    public function search(LogApi $logApi)
    {
        $logs = $logApi->search('');
        self::assertIsArray($logs);
        self::assertCount(50, $logs);

        $entry = $logs[10];
        self::assertInstanceOf(Log::class, $entry);

        return $entry;
    }

    /**
     * @test
     * @depends instantiateApi
     * @depends search
     * @covers \Bitmotion\Auth0\Api\Management\LogApi::searchByCheckpoint
     */
    public function searchByCheckpoint(LogApi $logApi, Log $entry): void
    {
        $logs = $logApi->searchByCheckpoint($entry->getLogid(), 5);
        self::assertIsArray($logs);
        self::assertCount(5, $logs);
    }

    /**
     * @test
     * @depends instantiateApi
     * @depends search
     * @covers \Bitmotion\Auth0\Api\Management\LogApi::get
     */
    public function get(LogApi $logApi, Log $entry): void
    {
        $log = $logApi->get($entry->getLogId());
        self::assertInstanceOf(Log::class, $log);
        self::assertEquals($entry->getLogId(), $log->getLogId());
    }
}
