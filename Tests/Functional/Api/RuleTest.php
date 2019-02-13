<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

use Bitmotion\Auth0\Api\Management\RuleApi;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Tests\Functional\Auth0TestCase;

class RuleTest extends Auth0TestCase
{
    protected $scopes = [
        Scope::RULE_CREATE,
        Scope::RULE_DELETE,
        Scope::RULE_READ,
        Scope::RULE_UPDATE,
    ];

    /**
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getRuleApi
     */
    public function instantiateApi(): RuleApi
    {
        $ruleApi = $this->getApiUtility()->getRuleApi(...$this->scopes);
        $this->assertInstanceOf(RuleApi::class, $ruleApi);

        return $ruleApi;
    }

    // TODO: implement
}
