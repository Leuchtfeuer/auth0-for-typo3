<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

use Bitmotion\Auth0\Api\Management\StatApi;
use Bitmotion\Auth0\Domain\Model\Auth0\Stat;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Utility\ApiUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class StatTest extends FunctionalTestCase
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
        Scope::STATS_READ,
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
     * Tries to instantiate the StatApi
     *
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getStatApi
     */
    public function instantiateApi(): StatApi
    {
        $statApi = $this->apiUtility->getStatApi(...$this->scopes);
        $this->assertInstanceOf(StatApi::class, $statApi);

        return $statApi;
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\StatApi::getActiveUsersCount
     */
    public function countUsers(StatApi $statApi)
    {
        $userCount = $statApi->getActiveUsersCount();
        $this->assertIsInt($userCount);
        $this->assertGreaterThan(0, $userCount);
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\StatApi::getDailyStats
     */
    public function getLogs(StatApi $statApi)
    {
        $stats = $statApi->getDailyStats();
        $this->assertIsArray($stats);
        $this->assertNotEmpty($stats);

        $stat = array_shift($stats);
        $this->assertInstanceOf(Stat::class, $stat);
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\StatApi::getDailyStats
     */
    public function getByDate(StatApi $statApi)
    {
        $dateTime = new \DateTime('2019-02-05T00:00:00.000Z');
        $dateTill = new \DateTime('2019-02-10T00:00:00.000Z');
        $stats = $statApi->getDailyStats($dateTime, $dateTill);
        $stat = array_shift($stats);
        $this->assertSame($dateTime->getTimestamp(), $stat->getDate()->getTimestamp());
    }
}
