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
        self::assertInstanceOf(RuleApi::class, $ruleApi);

        return $ruleApi;
    }

    // TODO: implement
}
