<?php
declare(strict_types=1);

namespace Bitmotion\Auth0\Api;

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

use Auth0\SDK\Auth0;
use Bitmotion\Auth0\Domain\Model\Application;

/**
 * Class AuthenticationApi
 * @package Bitmotion\Auth0\Api
 */
class AuthenticationApi extends Auth0
{

    /**
     * @var Application
     */
    protected $application = null;

    /**
     * Auth0Api constructor.
     *
     * @param Application $application
     * @param string      $redirectUri
     * @param string      $scope
     * @param array       $additionalOptions
     *
     * @throws \Auth0\SDK\Exception\CoreException
     */
    public function __construct($application, string $redirectUri = '', string $scope = '', array $additionalOptions = [])
    {
        $this->application = $application;

        $config = [
            'domain' => $application->getDomain(),
            'client_id' => $application->getId(),
            'client_secret' => $application->getSecret(),
            'audience' => 'https://' . $application->getDomain() . '/' . $application->getAudience(),
            'scope' => $scope,
            'redirect_uri' => $redirectUri,
            'persist_access_token' => true,
            'persist_refresh_token' => true,
            'persist_id_token' => true,
        ];

        parent::__construct(array_merge($config, $additionalOptions));
    }

    /**
     * @return Application
     */
    public function getApplication(): Application
    {
        return $this->application;
    }
}