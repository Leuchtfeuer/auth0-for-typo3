<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Bitmotion\Auth0\Factory;

use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;
use Auth0\SDK\Exception\ConfigurationException;
use Bitmotion\Auth0\Domain\Model\Application;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Bitmotion\Auth0\Middleware\CallbackMiddleware;
use GuzzleHttp\Client;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ApplicationFactory implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected array $scope = ['openid', 'profile', 'read:current_user'];

    protected ?Application $application;

    //TODO: Context handling needs to be readded
    public function getAuth0(int $applicationId): Auth0
    {
        $this->application = GeneralUtility::makeInstance(ApplicationRepository::class)->findByUid($applicationId);
        return new Auth0($this->getConfiguration($applicationId));
    }

    //TODO: Context handling needs to be readded
    public function getConfiguration(int $applicationId): ?SdkConfiguration
    {
        $config = [];
        $application = GeneralUtility::makeInstance(ApplicationRepository::class)->findByUid($applicationId);
        $this->application = $application;
        $config['audience'] = [$application->getAudience(true)];
        $config['clientId'] = $application->getClientId();
        $config['clientSecret'] = $application->getClientSecret();
        $config['cookieSecret'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
        $config['domain'] = $application->getDomain();
        $config['id_token_alg'] = $application->getSignatureAlgorithm();
        $config['managementToken'] = $this->getManagementToken();
        // TODO: Check if this can or should be static
        $config['redirectUri'] = $redirectUri ?? $this->getCallbackUri();
        //TODO: Check scope handling and usage before introducing parameter
        $config['scope'] = $scope ?? $this->scope;
        $config['secret_base64_encoded'] = $application->isSecretBase64Encoded();

        try {
            return new SdkConfiguration($config);
        } catch (ConfigurationException $e) {
            $this->logger->error($e->getMessage());
        }
        return null;
    }

    protected function getCallbackUri(): string
    {
        return GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . CallbackMiddleware::PATH;
    }

    private function getManagementToken(): string
    {
        $client = new Client();
        $response = $client->post($this->application->getManagementTokenDomain(), [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->application->getClientId(),
                'client_secret' => $this->application->getClientSecret(),
                'audience' => $this->application->getAudience()
            ]]);

        $result = json_decode($response->getBody()->getContents(), true);
        return $result['access_token'];
    }
}
