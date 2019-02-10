<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

use Bitmotion\Auth0\Api\Management\LogApi;
use Bitmotion\Auth0\Domain\Model\Auth0\Log;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Utility\ApiUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class LogTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/auth0',
    ];

    /**
     * @var array
     */
    protected $scopes = [
        Scope::LOG_READ,
    ];

    /**
     * @var int
     */
    protected $application = 1;

    /**
     * @var ApiUtility
     */
    protected $apiUtility;

    public function setUp()
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_auth0_domain_model_application.xml');
        $this->apiUtility = GeneralUtility::makeInstance(ApiUtility::class);
        $this->apiUtility->setApplication($this->application);
    }

    /**
     * Tries to instantiate the LogApi
     *
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getLogApi
     */
    public function instantiateApi(): LogApi
    {
        $logApi = $this->apiUtility->getLogApi(...$this->scopes);
        $this->assertInstanceOf(LogApi::class, $logApi);

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
        $this->assertIsArray($logs);
        $this->assertCount(50, $logs);

        $entry = $logs[10];
        $this->assertInstanceOf(Log::class, $entry);

        return $entry;
    }

    /**
     * @test
     * @depends instantiateApi
     * @depends search
     * @covers \Bitmotion\Auth0\Api\Management\LogApi::searchByCheckpoint
     */
    public function searchByCheckpoint(LogApi $logApi, Log $entry)
    {
        $logs = $logApi->searchByCheckpoint($entry->getLogid(), 5);
        $this->assertIsArray($logs);
        $this->assertCount(5, $logs);
    }

    /**
     * @test
     * @depends instantiateApi
     * @depends search
     * @covers \Bitmotion\Auth0\Api\Management\LogApi::get
     */
    public function get(LogApi $logApi, Log $entry)
    {
        $log = $logApi->get($entry->getLogId());
        $this->assertInstanceOf(Log::class, $log);
        $this->assertEquals($entry->getLogId(), $log->getLogId());
    }
}
