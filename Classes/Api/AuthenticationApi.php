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
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AuthenticationApi extends Auth0
{
    const ERROR_403 = 'unauthorized';

    /**
     * @throws \Auth0\SDK\Exception\CoreException
     * @throws \Bitmotion\Auth0\Exception\InvalidApplicationException
     */
    public function __construct(int $applicationUid, string $redirectUri = '', string $scope = '', array $additionalOptions = [])
    {
        $applicationRepository = GeneralUtility::makeInstance(ApplicationRepository::class);
        $application = $applicationRepository->findByUid($applicationUid);

        $config = [
            'domain' => $application['domain'],
            'client_id' => $application['id'],
            'client_secret' => $application['secret'],
            'audience' => 'https://' . $application['domain'] . '/' . $application['audience'],
            'scope' => $scope,
            'redirect_uri' => $redirectUri,
            'persist_access_token' => true,
            'persist_refresh_token' => true,
            'persist_id_token' => true,
        ];

        parent::__construct(array_merge($config, $additionalOptions));
    }
}
