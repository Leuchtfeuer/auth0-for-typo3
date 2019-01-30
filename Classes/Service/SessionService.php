<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Service;

use Auth0\SDK\Store\SessionStore;
use Bitmotion\Auth0\Api\ManagementApi;
use Bitmotion\Auth0\Utility\UserUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class SessionService implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @throws \Exception
     */
    public function createAuth0Session(FrontendUserAuthentication $feUserAuthentication, SessionStore $sessionStore, int $applicationUid): array
    {
        $this->logger->notice('Found active TYPO3 session but no active Auth0 session.');
        $managementApi = GeneralUtility::makeInstance(ManagementApi::class, (int)$applicationUid);
        $auth0User = $managementApi->getUserById($feUserAuthentication->user['auth0_user_id']);
        $userInfo = [];

        if (isset($auth0User['blocked']) && $auth0User['blocked'] === true) {
            $this->logger->notice('Logoff user as it is blocked in Auth0.');
        } else {
            $this->logger->debug('Map raw auth0 user to token info array.');
            $userInfo = GeneralUtility::makeInstance(UserUtility::class)->convertAuth0UserToUserInfo($auth0User);
            $sessionStore->set('user', $userInfo);
        }

        return $userInfo;
    }
}
