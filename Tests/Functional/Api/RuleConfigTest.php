<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

use Bitmotion\Auth0\Api\Management\RuleConfigApi;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Tests\Functional\Auth0TestCase;

class RuleConfigTest extends Auth0TestCase
{
    protected $scopes = [
        Scope::RULE_CONFIG_DELETE,
        Scope::RULE_CONFIG_READ,
        Scope::RULE_CONFIG_UPDATE,
    ];

    /**
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getRuleConfigApi
     */
    public function instantiateApi(): RuleConfigApi
    {
        $ruleConfigApi = $this->getApiUtility()->getRuleConfigApi(...$this->scopes);
        $this->assertInstanceOf(RuleConfigApi::class, $ruleConfigApi);

        return $ruleConfigApi;
    }

    // TODO: implement
}
