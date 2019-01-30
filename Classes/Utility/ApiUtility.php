<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Utility;

use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Api\AuthenticationApi;
use Bitmotion\Auth0\Exception\InvalidApplicationException;

class ApiUtility
{
    const DEFAULT_SCOPE = 'openid profile read:current_user';

    /**
     * @throws CoreException
     * @throws InvalidApplicationException
     */
    public function getAuthenticationApi(int $applicationUid, string $redirectUri, string $scope = ''): AuthenticationApi
    {
        if ($scope === '') {
            $scope = self::DEFAULT_SCOPE;
        }

        try {
            return new AuthenticationApi($applicationUid, $redirectUri, $scope);
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
}
