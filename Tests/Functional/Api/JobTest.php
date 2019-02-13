<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

use Bitmotion\Auth0\Api\Management\JobApi;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Tests\Functional\Auth0TestCase;

class JobTest extends Auth0TestCase
{
    protected $scopes = [
        Scope::PASSWORD_CHECKING_JOB_CREATE,
        Scope::PASSWORD_CHECKING_JOB_DELETE,
        Scope::USER_READ,
        Scope::USER_CREATE,
        Scope::USER_UPDATE,
    ];

    /**
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getJobApi
     */
    public function instantiateApi(): JobApi
    {
        $jobApi = $this->getApiUtility()
                       ->getJobApi(...$this->scopes);
        $this->assertInstanceOf(JobApi::class, $jobApi);

        return $jobApi;
    }

    // TODO: implement
}
