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

use Bitmotion\Auth0\Api\Management\EmailTemplateApi;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Tests\Functional\Auth0TestCase;

class EmailTemplateTest extends Auth0TestCase
{
    protected $scopes = [
        Scope::EMAIL_TEMPLATE_CREATE,
        Scope::EMAIL_TEMPLATE_READ,
        Scope::EMAIL_TEMPLATE_UPDATE,
    ];

    /**
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getEmailTemplateApi
     */
    public function instantiateApi(): EmailTemplateApi
    {
        $emailTemplateApi = $this->getApiUtility()->getEmailTemplateApi(...$this->scopes);
        $this->assertInstanceOf(EmailTemplateApi::class, $emailTemplateApi);

        return $emailTemplateApi;
    }

    // TODO: implement
}
