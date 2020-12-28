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

namespace Bitmotion\Auth0\Utility;

use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Api\Auth0;
use Bitmotion\Auth0\Api\Management;
use Bitmotion\Auth0\Api\Management\BlacklistApi;
use Bitmotion\Auth0\Api\Management\ClientApi;
use Bitmotion\Auth0\Api\Management\ClientGrantApi;
use Bitmotion\Auth0\Api\Management\ConnectionApi;
use Bitmotion\Auth0\Api\Management\CustomDomainApi;
use Bitmotion\Auth0\Api\Management\DeviceCredentialApi;
use Bitmotion\Auth0\Api\Management\EmailApi;
use Bitmotion\Auth0\Api\Management\EmailTemplateApi;
use Bitmotion\Auth0\Api\Management\GrantApi;
use Bitmotion\Auth0\Api\Management\GuardianApi;
use Bitmotion\Auth0\Api\Management\JobApi;
use Bitmotion\Auth0\Api\Management\LogApi;
use Bitmotion\Auth0\Api\Management\ResourceServerApi;
use Bitmotion\Auth0\Api\Management\RuleApi;
use Bitmotion\Auth0\Api\Management\RuleConfigApi;
use Bitmotion\Auth0\Api\Management\StatApi;
use Bitmotion\Auth0\Api\Management\TenantApi;
use Bitmotion\Auth0\Api\Management\TicketApi;
use Bitmotion\Auth0\Api\Management\UserApi;
use Bitmotion\Auth0\Api\Management\UserBlockApi;
use Bitmotion\Auth0\Api\Management\UserByEmailApi;
use Bitmotion\Auth0\Exception\InvalidApplicationException;
use Bitmotion\Auth0\Factory\SessionFactory;
use Bitmotion\Auth0\Scope;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ApiUtility implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected $scope = 'openid profile read:current_user';

    protected $application = 0;

    protected $context = SessionFactory::SESSION_PREFIX_FRONTEND;

    public function __construct(int $application = 0)
    {
        $this->application = $application;
    }

    /**
     * @throws CoreException
     * @throws InvalidApplicationException
     */
    public function getAuth0(?string $redirectUri = null, string ...$scopes): Auth0
    {
        try {
            $this->setScope($scopes);

            return new Auth0($this->application, $redirectUri, $this->scope, [], $this->context);
        } catch (CoreException $exception) {
            throw new CoreException(
                sprintf(
                    'Cannot instantiate Auth0 API: %s (%s)',
                    $exception->getMessage(),
                    $exception->getCode()
                ),
                1548845756
            );
        }
    }

    protected function setScope(array $scopes): void
    {
        if (!empty($scopes)) {
            try {
                $reflection = new \ReflectionClass(Scope::class);
                $allowedScopes = $reflection->getConstants();
                $targetScopes = $this->getTargetScopes($scopes, $allowedScopes);

                if (!empty($targetScopes)) {
                    $this->scope = implode(' ', $targetScopes);
                }
            } catch (\ReflectionException $exception) {
                $this->logger->critical('Could not instantiate reflection class.');
            }
        }
    }

    protected function getTargetScopes(array $scopes, array $allowedScopes): array
    {
        $targetScopes = [];

        foreach ($scopes as $scope) {
            if (!in_array($scope, $allowedScopes)) {
                $this->logger->warning(sprintf('Scope %s is not allowed.', $scope));
                continue;
            }

            $targetScopes[] = $scope;
        }

        return $targetScopes;
    }

    public function getApi(string $className, string ...$scopes): Management\GeneralManagementApi
    {
        return $this->getManagement(...$scopes)->getApi($className);
    }

    public function withContext(string $context): self
    {
        $cloneObject = clone $this;
        $cloneObject->context = $context;

        return $cloneObject;
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getUserApi(string ...$scopes): UserApi
    {
        trigger_error('getUserApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(UserApi::class, ...$scopes);
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getTicketApi(string ...$scopes): TicketApi
    {
        trigger_error('getTicketApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(TicketApi::class, ...$scopes);
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getConnectionApi(string ...$scopes): ConnectionApi
    {
        trigger_error('getConnectionApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(ConnectionApi::class, ...$scopes);
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getClientGrantApi(string ...$scopes): ClientGrantApi
    {
        trigger_error('getClientGrantApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(ClientGrantApi::class, ...$scopes);
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getClientApi(string ...$scopes): ClientApi
    {
        trigger_error('getClientApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(ClientApi::class, ...$scopes);
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getUserBlockApi(string ...$scopes): UserBlockApi
    {
        trigger_error('getUserBlockApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(UserBlockApi::class, ...$scopes);
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getUserByEmailApi(string ...$scopes): UserByEmailApi
    {
        trigger_error('getUserByEmailApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(UserByEmailApi::class, ...$scopes);
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getStatApi(string ...$scopes): StatApi
    {
        trigger_error('getStatApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(StatApi::class, ...$scopes);
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getTenantApi(string ...$scopes): TenantApi
    {
        trigger_error('getTenantApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(TenantApi::class, ...$scopes);
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getLogApi(string ...$scopes): LogApi
    {
        trigger_error('getLogApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(LogApi::class, ...$scopes);
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getResourceServerApi(string ...$scopes): ResourceServerApi
    {
        trigger_error('getResourceServerApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(ResourceServerApi::class, ...$scopes);
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getBlacklistApi(string ...$scopes): BlacklistApi
    {
        trigger_error('getBlacklistApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(BlacklistApi::class, ...$scopes);
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getCustomDomainApi(string ...$scopes): CustomDomainApi
    {
        trigger_error('getCustomDomainApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(CustomDomainApi::class, ...$scopes);
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getDeviceCredentialApi(string ...$scopes): DeviceCredentialApi
    {
        trigger_error('getDeviceCredentialApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(DeviceCredentialApi::class, ...$scopes);
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getEmailApi(string ...$scopes): EmailApi
    {
        trigger_error('getEmailApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(EmailApi::class, ...$scopes);
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getEmailTemplateApi(string ...$scopes): EmailTemplateApi
    {
        trigger_error('getEmailTemplateApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(EmailTemplateApi::class, ...$scopes);
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getGrantApi(string ...$scopes): GrantApi
    {
        trigger_error('getGrantApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(GrantApi::class, ...$scopes);
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getGuardianApi(string ...$scopes): GuardianApi
    {
        trigger_error('getGuardianApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(GuardianApi::class, ...$scopes);
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getJobApi(string ...$scopes): JobApi
    {
        trigger_error('getJobApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(JobApi::class, ...$scopes);
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getRuleConfigApi(string ...$scopes): RuleConfigApi
    {
        trigger_error('getRuleConfigApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(RuleConfigApi::class, ...$scopes);
    }

    /**
     * @deprecated Getting APIs by dedicated getter is deprecated and will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getRuleApi(string ...$scopes): RuleApi
    {
        trigger_error('getRuleApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(RuleApi::class, ...$scopes);
    }

    protected function getManagement(... $scopes): Management
    {
        $this->setScope($scopes);

        return GeneralUtility::makeInstance(Management::class, $this->application, $this->scope);
    }
}
