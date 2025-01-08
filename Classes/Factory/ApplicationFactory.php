<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\Auth0\Factory;

use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;
use Auth0\SDK\Exception\ConfigurationException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Leuchtfeuer\Auth0\Domain\Model\Application;
use Leuchtfeuer\Auth0\Domain\Repository\ApplicationRepository;
use Leuchtfeuer\Auth0\Middleware\CallbackMiddleware;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ApplicationFactory
{
    public const SESSION_PREFIX_BACKEND = 'BE';

    public const SESSION_PREFIX_FRONTEND = 'FE';

    protected ?Application $application = null;

    /**
     * @throws ConfigurationException
     * @throws GuzzleException
     */
    public static function build(int $applicationId, string $context = self::SESSION_PREFIX_BACKEND): Auth0
    {
        $scope = ['openid', 'profile', 'read:current_user'];
        $application = GeneralUtility::makeInstance(ApplicationRepository::class)->findByUid($applicationId);
        if ($application === null) {
            throw new \RuntimeException('Application not found: ' . $applicationId);
        }

        // Management API should be used
        if ($application->hasApi()) {
            $client = new Client();
            $response = $client->post($application->getManagementTokenDomain(), [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $application->getClientId(),
                    'client_secret' => $application->getClientSecret(),
                    'audience' => $application->getAudience(),
                ], ]);

            $result = json_decode($response->getBody()->getContents(), true);
            $managementToken = $result['access_token'];
        }

        // TODO: If management API is disabled audience needs to be empty - authorization is broken atm
        $sdkConfiguration = new SdkConfiguration([
            'audience' => [$application->getAudience(true)],
            'clientId' => $application->getClientId(),
            'clientSecret' => $application->getClientSecret(),
            'cookieSecret' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'],
            'domain' => $application->getDomain(),
            'id_token_alg' => $application->getSignatureAlgorithm(),
            'managementToken' => $managementToken ?? null,
            'redirectUri' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . CallbackMiddleware::PATH,
            'scope' => $scope,
            'sessionStorageId' => sprintf('auth0_session_%s', $context),
        ]);
        return new Auth0($sdkConfiguration);
    }
}
