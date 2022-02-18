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

use Bitmotion\Auth0\Factory\SessionFactory;
use Bitmotion\Auth0\Scope;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class ApiUtility implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected array $scope = ['openid', 'profile', 'read:current_user'];

    protected int $application = 0;

    protected string $context = SessionFactory::SESSION_PREFIX_FRONTEND;

    public function __construct(int $application = 0)
    {
        $this->application = $application;
    }

    /**
     * @throws CoreException
     */
    public function getAuth0(?string $redirectUri = null, array ...$scopes): Auth0
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
                    $this->scope = $targetScopes;
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

    public function withContext(string $context): self
    {
        $cloneObject = clone $this;
        $cloneObject->context = $context;

        return $cloneObject;
    }
}
