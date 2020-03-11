<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Utility;

/***
 *
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

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
use Bitmotion\Auth0\Scope;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ApiUtility implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected $scope = 'openid profile read:current_user';

    protected $application = 0;

    public function __construct(int $application = 0)
    {
        $this->application = $application;
    }

    /**
     * @throws CoreException
     * @throws InvalidApplicationException
     */
    public function getAuth0(string $redirectUri, string ...$scopes): Auth0
    {
        try {
            $this->setScope($scopes);

            return new Auth0($this->application, $redirectUri, $this->scope);
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

    public function getUserApi(string ...$scopes): UserApi
    {
        return $this->getManagement(...$scopes)->getUserApi();
    }

    public function getTicketApi(string ...$scopes): TicketApi
    {
        return $this->getManagement(...$scopes)->getTicketApi();
    }

    public function getConnectionApi(string ...$scopes): ConnectionApi
    {
        return $this->getManagement(...$scopes)->getConnectionApi();
    }

    public function getClientGrantApi(string ...$scopes): ClientGrantApi
    {
        return $this->getManagement(...$scopes)->getClientGrantApi();
    }

    public function getClientApi(string ...$scopes): ClientApi
    {
        return $this->getManagement(...$scopes)->getClientApi();
    }

    public function getUserBlockApi(string ...$scopes): UserBlockApi
    {
        return $this->getManagement(...$scopes)->getUserBlockApi();
    }

    public function getUserByEmailApi(string ...$scopes): UserByEmailApi
    {
        return $this->getManagement(...$scopes)->getUserByEmailApi();
    }

    public function getStatApi(string ...$scopes): StatApi
    {
        return $this->getManagement(...$scopes)->getStatApi();
    }

    public function getTenantApi(string ...$scopes): TenantApi
    {
        return $this->getManagement(...$scopes)->getTenantApi();
    }

    public function getLogApi(string ...$scopes): LogApi
    {
        return $this->getManagement(...$scopes)->getLogApi();
    }

    public function getResourceServerApi(string ...$scopes): ResourceServerApi
    {
        return $this->getManagement(...$scopes)->getResourceServerApi();
    }

    public function getBlacklistApi(string ...$scopes): BlacklistApi
    {
        return $this->getManagement(...$scopes)->getBlacklistApi();
    }

    public function getCustomDomainApi(string ...$scopes): CustomDomainApi
    {
        return $this->getManagement(...$scopes)->getCustomDomainApi();
    }

    public function getDeviceCredentialApi(string ...$scopes): DeviceCredentialApi
    {
        return $this->getManagement(...$scopes)->getDeviceCredentialApi();
    }

    public function getEmailApi(string ...$scopes): EmailApi
    {
        return $this->getManagement(...$scopes)->getEmailApi();
    }

    public function getEmailTemplateApi(string ...$scopes): EmailTemplateApi
    {
        return $this->getManagement(...$scopes)->getEmailTemplateApi();
    }

    public function getGrantApi(string ...$scopes): GrantApi
    {
        return $this->getManagement(...$scopes)->getGrantApi();
    }

    public function getGuardianApi(string ...$scopes): GuardianApi
    {
        return $this->getManagement(...$scopes)->getGuardianApi();
    }

    public function getJobApi(string ...$scopes): JobApi
    {
        return $this->getManagement(...$scopes)->getJobApi();
    }

    public function getRuleConfigApi(string ...$scopes): RuleConfigApi
    {
        return $this->getManagement(...$scopes)->getRuleConfigApi();
    }

    public function getRuleApi(string ...$scopes): RuleApi
    {
        return $this->getManagement(...$scopes)->getRuleApi();
    }

    protected function getManagement(... $scopes): Management
    {
        $this->setScope($scopes);

        return GeneralUtility::makeInstance(Management::class, $this->application, $this->scope);
    }
}
