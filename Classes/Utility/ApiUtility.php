<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Utility;

use Auth0\SDK\API\Management\Tickets;
use Auth0\SDK\API\Management\Users;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Api\AuthenticationApi;
use Bitmotion\Auth0\Api\Management\ConnectionApi;
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

    public function getUserApi(string ...$scopes): Users
    {
        return $this->getManagementApi($scopes)->getUserApi();
    }

    public function getTicketApi(string ...$scopes): Tickets
    {
        return $this->getManagementApi($scopes)->getTicketApi();
    }

    public function getConnectionApi(string ...$scopes): ConnectionApi
    {
        return $this->getManagementApi($scopes)->getConnectionApi();
    }

    protected function getManagementApi(array $scopes): ManagementApi
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
