<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Controller;

/***
 *
 * This file is part of the "Auth0 for TYPO3" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Florian Wessels <f.wessels@bitmotion.de>, Bitmotion GmbH
 *
 ***/

use Auth0\SDK\Exception\CoreException;
use Auth0\SDK\Store\SessionStore;
use Bitmotion\Auth0\Api\AuthenticationApi;
use Bitmotion\Auth0\Api\ManagementApi;
use Bitmotion\Auth0\Domain\Model\Application;
use Bitmotion\Auth0\Service\RedirectService;
use Bitmotion\Auth0\Utility\ConfigurationUtility;
use Bitmotion\Auth0\Utility\Database\UpdateUtility;
use Bitmotion\Auth0\Utility\RoutingUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\Page\PageRepository;

class LoginController extends ActionController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const SCOPE = 'openid profile read:current_user';

    /**
     * @var Application
     */
    protected $application;

    /**
     * @throws InvalidConfigurationTypeException
     */
    public function initializeAction()
    {
        if (!ConfigurationUtility::isLoaded()) {
            throw new InvalidConfigurationTypeException('No TypoScript found.', 1547449321);
        }
    }

    public function formAction()
    {
        // Get Auth0 user from session storage
        $sessionStore = new SessionStore();
        $userInfo = $sessionStore->get('user');
        $feUserAuthentication = $GLOBALS['TSFE']->fe_user;

        if (GeneralUtility::_GP('logintype') === 'login' && $feUserAuthentication->user !== null && $userInfo !== null) {
            if (!empty(GeneralUtility::_GP('referrer'))) {
                $this->logger->notice('Handle referrer redirect prior to updating user.');
                $this->settings['redirectDisable'] = false;
                $this->settings['redirectMode'] = 'referrer';
                $this->handleRedirect(['referrer'], true);
            }

            // Try to update user
            $this->logger->notice('Update User due to login.');
            $this->updateUser();

            // Redirect user on login
            $this->handleRedirect(['groupLogin', 'userLogin', 'login', 'getpost', 'referrer']);
            $this->logger->notice('No redirect configured. Showing form.');
        }

        if ($userInfo === null && $feUserAuthentication->user !== null) {
            $this->logger->notice('Found active TYPO3 session but no active Auth0 session.');
            $applicationUid = (!empty(GeneralUtility::_GP('application'))) ? GeneralUtility::_GP('application') : $this->settings['application'];
            $managementApi = GeneralUtility::makeInstance(ManagementApi::class, (int)$applicationUid);
            $auth0User = $managementApi->getUserById($feUserAuthentication->user['auth0_user_id']);

            if (isset($auth0User['blocked']) && $auth0User['blocked'] === true) {
                $this->logger->notice('Logoff user as it is blocked in Auth0.');
            } else {
                $this->logger->debug('Map raw auth0 user to token info array.');
                $userInfo = $this->convertAuth0UserToUserInfo($auth0User);
                $sessionStore->set('user', $userInfo);
            }
        }

        $this->view->assign('userInfo', $userInfo);
    }

    protected function convertAuth0UserToUserInfo(array $auth0User): array
    {
        return [
            'sub' => $auth0User['user_id'],
            'given_name' => $auth0User['given_name'],
            'family_name' => $auth0User['family_name'],
            'nickname' => $auth0User['nickname'],
            'name' => $auth0User['name'],
            'picture' => $auth0User['picture'],
            'locale' => $auth0User['locale'],
            'updated_at' => $auth0User['updated_at'],
        ];
    }

    /**
     * @throws \Bitmotion\Auth0\Exception\InvalidApplicationException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function loginAction()
    {
        // Get Auth0 user from session storage
        $store = new SessionStore();
        $userInfo = $store->get('user');

        if ($userInfo === null) {
            // Try to login user
            $this->logger->notice('Try to login user.');
            $this->loginUser();
        }

        // Show login form
        $this->redirect('form');
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function logoutAction()
    {
        $this->logoutUser();
        $this->redirect('form');
    }

    protected function handleRedirect(array $allowedRedirectMethods, bool $bypassLoginType = false)
    {
        if ((bool)$this->settings['redirectDisable'] === false && !empty($this->settings['redirectMode'])) {
            $this->logger->notice('Try to redirect user.');
            $redirectService = GeneralUtility::makeInstance(RedirectService::class, $this->settings);
            $redirectUris = $redirectService->getRedirectUri($allowedRedirectMethods);

            if (!empty($redirectUris)) {
                $redirectUri = ($bypassLoginType) ? $redirectService->getUri($redirectUris) . '?logintype=login' : $redirectService->getUri($redirectUris);
                $this->logger->notice(sprintf('Redirect to: %s', $redirectUri));
                header('Location: ' . $redirectUri, false, 307);
                die;
            }

            $this->logger->warning('Redirect failed.');
        }
    }

    protected function getRedirectUri(): string
    {
        $routingUtility = GeneralUtility::makeInstance(RoutingUtility::class);
        $redirectUri = $routingUtility->getUri();
        $this->logger->notice(sprintf('Set redirect URI to: %s', $redirectUri));

        return $redirectUri;
    }

    protected function getCallbackUri(): string
    {
        $pageType = $this->settings['frontend']['callback']['targetPageType'];
        $pageUid = $this->settings['frontend']['callback']['targetPageUid'];
        $routingUtility = GeneralUtility::makeInstance(RoutingUtility::class);
        $routingUtility->setArguments([
            'logintype' => 'login',
            'application' => (int)$this->settings['application'],
            'referrer' => $this->getRedirectUri(),
        ]);

        if (!empty($pageUid)) {
            // Check whether page exists
            $page = $this->objectManager->get(PageRepository::class)->checkRecord('pages', $pageUid);

            if (!empty($page)) {
                $routingUtility->setTargetPage((int)$pageUid);
            } else {
                $this->logger->warning(sprintf('No page found for given uid "%s".', $pageUid));
            }
        }

        if (!empty($pageType)) {
            $routingUtility->setTargetPageType((int)$pageType);
        }

        $uri = $routingUtility->getUri();
        $this->logger->notice(sprintf('Set callback URI to: %s', $uri));

        return $uri;
    }

    /**
     * @throws \Bitmotion\Auth0\Exception\InvalidApplicationException
     */
    protected function getAuthenticationApi()
    {
        try {
            return new AuthenticationApi(
                (int)$this->settings['application'],
                $this->getCallbackUri(),
                self::SCOPE
            );
        } catch (CoreException $exception) {
            $this->logger->critical(
                sprintf(
                    'Cannot instantiate Auth0 Authentication: %s (%s)',
                    $exception->getMessage(),
                    $exception->getCode()
                )
            );
        }
    }

    protected function updateUser()
    {
        try {
            $tokenInfo = $this->getAuthenticationApi()->getUser();
            $managementApi = GeneralUtility::makeInstance(ManagementApi::class, (int)$this->settings['application']);
            $auth0User = $managementApi->getUserById($tokenInfo['sub']);

            // Update existing user on every login
            $updateUtility = GeneralUtility::makeInstance(UpdateUtility::class, 'fe_users', $auth0User);
            $updateUtility->updateUser();
            $updateUtility->updateGroups();
        } catch (\Exception $exception) {
            $this->logger->warning(
                sprintf(
                    'Updating user failed with following message: %s (%s)',
                    $exception->getMessage(),
                    $exception->getCode()
                )
            );
        }
    }

    protected function logoutUser()
    {
        try {
            $this->logger->notice('Log out user');
            $this->getAuthenticationApi()->logout();
        } catch (\Exception $exception) {
            // Delete user from SessionStore
            $store = new SessionStore();
            if ($store->get('user')) {
                $store->delete('user');
            }
        }
    }

    /**
     * @throws \Bitmotion\Auth0\Exception\InvalidApplicationException
     */
    protected function loginUser()
    {
        $authenticationApi = $this->getAuthenticationApi();

        try {
            $userInfo = $authenticationApi->getUser();

            if (!$userInfo) {
                // Try to login user to Auth0
                $this->logger->notice('Try to login user to Auth0.');
                $authenticationApi->login();
            }
        } catch (\Exception $exception) {
            if (isset($authenticationApi) && $authenticationApi instanceof AuthenticationApi) {
                $authenticationApi->deleteAllPersistentData();
            }
        }
    }
}
