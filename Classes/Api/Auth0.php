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

namespace Bitmotion\Auth0\Api;

use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Bitmotion\Auth0\Exception\InvalidApplicationException;
use Bitmotion\Auth0\Factory\SessionFactory;
use Bitmotion\Auth0\Middleware\CallbackMiddleware;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Auth0 extends \Auth0\SDK\Auth0
{
    /**
     * @throws CoreException
     */
    public function __construct(?int $application = null, ?string $redirectUri = null, ?string $scope = null, array $additionalOptions = [], ?string $context = null)
    {
        $config = [
            'persist_access_token' => true,
            'persist_id_token' => true,
            'persist_refresh_token' => true,
            'redirect_uri' => $redirectUri ?? $this->getCallbackUri(),
            'scope' => $scope,
            'store' => (new SessionFactory())->getSessionStoreForApplication((int)$application, $context ?? $this->getContext()),
        ];

        if ((int)$application > 0) {
            $this->enrichConfigByApplication($application, $config);
        }

        parent::__construct(array_merge($config, $additionalOptions));
    }

    public function getLogoutUri(string $returnUrl, string $clientId, bool $federated = false): string
    {
        return $this->authentication->get_logout_link($returnUrl, $clientId, $federated);
    }

    protected function getCallbackUri(): string
    {
        return GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . CallbackMiddleware::PATH;
    }

    protected function enrichConfigByApplication(int $applicationId, array &$config): void
    {
        try {
            $application = GeneralUtility::makeInstance(ApplicationRepository::class)->findByUid($applicationId);
        } catch (InvalidApplicationException $exception) {
            // TODO: Log this

            return;
        }

        $config['audience'] = $application->getAudience(true);
        $config['client_id'] = $application->getClientId();
        $config['client_secret'] = $application->getClientSecret();
        $config['domain'] = $application->getDomain();
        $config['id_token_alg'] = $application->getSignatureAlgorithm();
        $config['secret_base64_encoded'] = $application->isSecretBase64Encoded();
    }

    private function getContext(): string
    {
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequest) {
            return ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend() ? SessionFactory::SESSION_PREFIX_FRONTEND : SessionFactory::SESSION_PREFIX_BACKEND;
        }

        return SessionFactory::SESSION_PREFIX_FRONTEND;
    }
}
