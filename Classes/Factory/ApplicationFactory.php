<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Leuchtfeuer Digital Marketing <dev@Leuchtfeuer.com>
 */

namespace Leuchtfeuer\Auth0\Factory;

use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;
use Auth0\SDK\Exception\ConfigurationException;
use Auth0\SDK\Store\SessionStore;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Leuchtfeuer\Auth0\Domain\Model\Application;
use Leuchtfeuer\Auth0\Domain\Repository\ApplicationRepository;
use Leuchtfeuer\Auth0\Middleware\CallbackMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
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
    public static function build(int $applicationId, string $context = self::SESSION_PREFIX_BACKEND, ?ServerRequestInterface $request = null): Auth0
    {
        $sessionStorageId = sprintf('auth0_session_%s', $context);
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
            // $GLOBALS['TYPO3_REQUEST'] is intentionally not used as fallback: TYPO3 14 no longer
            // guarantees its availability in all contexts (e.g. CLI, early middlewares). $_SERVER
            // is the last resort for callers without a request (CleanUpCommand, Auth0SessionValidator)
            // where redirectUri is constructed but never actually used in an OAuth flow.
            'redirectUri' => ($request?->getAttribute('normalizedParams') ?? NormalizedParams::createFromServerParams($_SERVER))->getRequestHost() . CallbackMiddleware::PATH,
            'scope' => $scope,
        ]);
        $auth0 = new Auth0($sdkConfiguration);
        $auth0->configuration()->setSessionStorage(new SessionStore($auth0->configuration(), $sessionStorageId));
        $auth0->configuration()->setTransientStorage(new SessionStore($auth0->configuration(), $sessionStorageId . '_transient'));

        return $auth0;
    }
}
