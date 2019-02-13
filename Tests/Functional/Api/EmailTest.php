<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

use Bitmotion\Auth0\Api\Management\EmailApi;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Tests\Functional\Auth0TestCase;

class EmailTest extends Auth0TestCase
{
    protected $scopes = [
        Scope::EMAIL_PROVIDER_CREATE,
        Scope::EMAIL_PROVIDER_DELETE,
        Scope::EMAIL_PROVIDER_READ,
        Scope::EMAIL_PROVIDER_UPDATE,
    ];

    /**
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getEmailApi
     */
    public function instantiateApi(): EmailApi
    {
        $emailApi = $this->getApiUtility()->getEmailApi(...$this->scopes);
        $this->assertInstanceOf(EmailApi::class, $emailApi);

        return $emailApi;
    }

    // TODO: implement
}
