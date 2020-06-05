<?php

declare(strict_types=1);

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

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
        self::assertInstanceOf(JobApi::class, $jobApi);

        return $jobApi;
    }

    // TODO: implement
}
