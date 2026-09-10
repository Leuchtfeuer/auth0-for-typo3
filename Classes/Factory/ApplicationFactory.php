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
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Leuchtfeuer\Auth0\Domain\Repository\ApplicationRepository;
use Leuchtfeuer\Auth0\Middleware\CallbackMiddleware;
use Psr\Http\Client\ClientInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ApplicationFactory
{
    public const SESSION_PREFIX_BACKEND = 'BE';

    public const SESSION_PREFIX_FRONTEND = 'FE';

    public function __construct(
        protected readonly ApplicationRepository $applicationRepository,
        protected readonly RequestFactory $requestFactory,
        protected readonly ResponseFactory $responseFactory,
        protected readonly StreamFactory $streamFactory,
        protected readonly ClientInterface $httpClient,
    ) {}

    /**
     * @deprecated since v13.0.12, will be removed in v15. Inject the factory and call create() instead.
     *
     * @throws ConfigurationException
     * @throws GuzzleException
     */
    public static function build(int $applicationId, string $context = self::SESSION_PREFIX_BACKEND): Auth0
    {
        return GeneralUtility::makeInstance(self::class)->create($applicationId, $context);
    }

    /**
     * @throws ConfigurationException
     * @throws GuzzleException
     */
    public function create(int $applicationId, string $context = self::SESSION_PREFIX_BACKEND): Auth0
    {
        $scope = ['openid', 'profile', 'read:current_user'];
        $application = $this->applicationRepository->findByUid($applicationId);
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
            // The SDK resolves these through psr-discovery when they are absent, and that
            // library gates candidates on hardcoded package constraints instead of looking at
            // the classes: guzzlehttp/psr7 is capped at ^2.0 there and TYPO3 is listed under
            // the non-existent package typo3/core. Supplying all four keeps the SDK on the
            // HTTP stack TYPO3 is configured with and out of discovery entirely.
            'httpClient' => $this->httpClient,
            'httpRequestFactory' => $this->requestFactory,
            'httpResponseFactory' => $this->responseFactory,
            'httpStreamFactory' => $this->streamFactory,
            'id_token_alg' => $application->getSignatureAlgorithm(),
            'managementToken' => $managementToken ?? null,
            'redirectUri' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . CallbackMiddleware::PATH,
            'scope' => $scope,
            'sessionStorageId' => sprintf('auth0_session_%s', $context),
        ]);
        return new Auth0($sdkConfiguration);
    }
}
