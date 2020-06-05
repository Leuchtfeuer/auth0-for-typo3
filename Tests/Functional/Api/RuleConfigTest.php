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
        self::assertInstanceOf(RuleConfigApi::class, $ruleConfigApi);

        return $ruleConfigApi;
    }

    // TODO: implement
}
