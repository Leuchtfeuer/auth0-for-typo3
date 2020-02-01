<?php
declare(strict_types=1);
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
