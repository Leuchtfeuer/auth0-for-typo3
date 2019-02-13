<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Utility;

use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Api\AuthenticationApi;
use Bitmotion\Auth0\Api\Management\BlacklistApi;
use Bitmotion\Auth0\Api\Management\ClientApi;
use Bitmotion\Auth0\Api\Management\ClientGrantApi;
use Bitmotion\Auth0\Api\Management\ConnectionApi;
use Bitmotion\Auth0\Api\Management\LogApi;
use Bitmotion\Auth0\Api\Management\ResourceServerApi;
use Bitmotion\Auth0\Api\Management\StatApi;
use Bitmotion\Auth0\Api\Management\TenantApi;
use Bitmotion\Auth0\Api\Management\TicketApi;
use Bitmotion\Auth0\Api\Management\UserApi;
use Bitmotion\Auth0\Api\Management\UserBlockApi;
use Bitmotion\Auth0\Api\Management\UserByEmailApi;
use Bitmotion\Auth0\Api\ManagementApi;
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

    /**
     * @throws CoreException
     * @throws InvalidApplicationException
     */
    public function getAuthenticationApi(string $redirectUri, ...$scopes): AuthenticationApi
    {
        try {
            $this->setScope($scopes);

            return new AuthenticationApi($this->application, $redirectUri, $this->scope);
        } catch (CoreException $exception) {
            throw new CoreException(
                sprintf(
                    'Cannot instantiate Auth0 Authentication: %s (%s)',
                    $exception->getMessage(),
                    $exception->getCode()
                ),
                1548845756
            );
        }
    }

    protected function setScope(array $scopes)
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

    public function setApplication(int $applicationUid)
    {
        $this->application = $applicationUid;
    }

    public function getUserApi(string ...$scopes): UserApi
    {
        return $this->getManagementApi(...$scopes)->getUserApi();
    }

    public function getTicketApi(string ...$scopes): TicketApi
    {
        return $this->getManagementApi(...$scopes)->getTicketApi();
    }

    public function getConnectionApi(string ...$scopes): ConnectionApi
    {
        return $this->getManagementApi(...$scopes)->getConnectionApi();
    }

    public function getClientGrantApi(string ...$scopes): ClientGrantApi
    {
        return $this->getManagementApi(...$scopes)->getClientGrantApi();
    }

    public function getClientApi(string ...$scopes): ClientApi
    {
        return $this->getManagementApi(...$scopes)->getClientApi();
    }

    public function getUserBlockApi(string ...$scopes): UserBlockApi
    {
        return $this->getManagementApi(...$scopes)->getUserBlockApi();
    }

    public function getUserByEmailApi(string ...$scopes): UserByEmailApi
    {
        return $this->getManagementApi(...$scopes)->getUserByEmailApi();
    }

    public function getStatApi(string ...$scopes): StatApi
    {
        return $this->getManagementApi(...$scopes)->getStatApi();
    }

    public function getTenantApi(string ...$scopes): TenantApi
    {
        return $this->getManagementApi(...$scopes)->getTenantApi();
    }

    public function getLogApi(string ...$scopes): LogApi
    {
        return $this->getManagementApi(...$scopes)->getLogApi();
    }

    public function getResourceServerApi(string ...$scopes): ResourceServerApi
    {
        return $this->getManagementApi(...$scopes)->getResourceServerApi();
    }

    public function getBlacklistApi(string ...$scopes): BlacklistApi
    {
        return $this->getManagementApi(...$scopes)->getBlacklistApi();
    }

    protected function getManagementApi(... $scopes): ManagementApi
    {
        $this->setScope($scopes);

        return GeneralUtility::makeInstance(ManagementApi::class, $this->application, $this->scope);
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
}
