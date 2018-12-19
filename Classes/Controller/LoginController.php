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

use Auth0\SDK\Store\SessionStore;
use Bitmotion\Auth0\Api\AuthenticationApi;
use Bitmotion\Auth0\Api\ManagementApi;
use Bitmotion\Auth0\Domain\Model\Application;
use Bitmotion\Auth0\Exception\InvalidApplicationException;
use Bitmotion\Auth0\Service\RedirectService;
use Bitmotion\Auth0\Utility\ApplicationUtility;
use Bitmotion\Auth0\Utility\UpdateUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class LoginController extends ActionController
{
    /**
     * @var Application
     */
    protected $application = null;

    /**
     * form action
     */
    public function formAction()
    {
        // Get Auth0 user from session storage
        $store = new SessionStore();
        $userInfo = $store->get('user');

        // Redirect user on login
        if (GeneralUtility::_GP('logintype') === 'login' && !empty($GLOBALS['TSFE']->fe_user->user) && $userInfo !== null) {
            $this->handleRedirect(['groupLogin', 'userLogin', 'login', 'getpost', 'referrer']);
        }

        $this->view->assign('userInfo', $userInfo);
    }

    /**
     * login action
     */
    public function loginAction()
    {
        // Get Auth0 user from session storage
        $store = new SessionStore();
        $userInfo = $store->get('user');

        if ($userInfo === null) {
            try {
                $this->loadApplication();
                $authenticationApi = new AuthenticationApi($this->application, $this->getUri(), 'openid profile read:current_user', []);
                $userInfo = $authenticationApi->getUser();

                if (!$userInfo) {
                    // Try to login user to Auth0
                    $authenticationApi->login();
                } else {
                    $tokenInfo = $authenticationApi->getUser();
                    $managementApi = GeneralUtility::makeInstance(ManagementApi::class, $this->application);
                    $auth0User = $managementApi->getUserById($tokenInfo['sub']);

                    // Update existing user on every login
                    $updateUtility = GeneralUtility::makeInstance(UpdateUtility::class, 'fe_users', $auth0User);
                    $updateUtility->updateUser();
                    $updateUtility->updateGroups();

                    // Show login form
                    $this->redirect('form');
                }
            } catch (\Exception $exception) {
                if (isset($authenticationApi) && $authenticationApi instanceof AuthenticationApi) {
                    $authenticationApi->deleteAllPersistentData();
                }
            }
        }
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function logoutAction()
    {
        try {
            $this->loadApplication();
            $authenticationApi = new AuthenticationApi(
                $this->application,
                $this->getUri(),
                'openid profile read:current_user',
                []
            );

            $authenticationApi->logout();
        } catch (\Exception $exception) {
            // Delete user from SessionStore
            $store = new SessionStore();
            if ($store->get('user')) {
                $store->delete('user');
            }
        }

        $this->redirect('form');
    }

    protected function handleRedirect(array $allowedRedirectMethods)
    {
        if ((bool)$this->settings['redirectDisable'] === false && !empty($this->settings['redirectMode'])) {
            $redirectService = GeneralUtility::makeInstance(RedirectService::class, $this->settings);
            $redirectUris = $redirectService->getRedirectUri($allowedRedirectMethods);
            if (!empty($redirectUris)) {
                header('Location: ' . $redirectService->getUri($redirectUris), false, 307);
            }
        }
    }

    protected function getUri(): string
    {
        return
            $this->objectManager->get(UriBuilder::class)
                ->reset()
                ->setTargetPageUid($GLOBALS['TSFE']->id)
                ->setArguments([
                    'logintype' => 'login',
                    'application' => $this->application->getUid(),
                ])->setCreateAbsoluteUri(true)
                ->setUseCacheHash(false)
                ->buildFrontendUri();
    }

    /**
     * @throws InvalidApplicationException
     */
    protected function loadApplication()
    {
        $this->application = ApplicationUtility::getApplication((int)$this->settings['application']);
    }
}
