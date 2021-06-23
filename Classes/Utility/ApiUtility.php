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

    protected function getManagement(... $scopes): Management
    {
        $this->setScope($scopes);

        return GeneralUtility::makeInstance(Management::class, $this->application, $this->scope);
    }
}
