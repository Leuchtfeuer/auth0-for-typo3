<?php
declare(strict_types=1);

namespace Bitmotion\Auth0\LoginProvider;

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
use Bitmotion\Auth0\Domain\Model\Application;
use Bitmotion\Auth0\Domain\Model\Dto\EmAuth0Configuration;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Bitmotion\Auth0\Utility\ConfigurationUtility;
use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class Auth0Provider
 * @package Bitmotion\Auth0\LoginProvider
 */
class Auth0Provider implements LoginProviderInterface
{
    /**
     * @var AuthenticationApi
     */
    protected $authentication = null;

    /**
     * @param StandaloneView  $standaloneView
     * @param PageRenderer    $pageRenderer
     * @param LoginController $loginController
     *
     * @throws \Auth0\SDK\Exception\CoreException
     */
    public function render(StandaloneView $standaloneView, PageRenderer $pageRenderer, LoginController $loginController)
    {
        $standaloneView->setLayoutRootPaths(['EXT:auth0/Resources/Private/Layouts']);
        $standaloneView->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:auth0/Resources/Private/Templates/Backend.html'));
        $pageRenderer->addCssFile('EXT:auth0/Resources/Public/Styles/backend.css');

        // Figure out whether TypoScript is loaded
        try {
            ConfigurationUtility::getSetting('propertyMapping');
        } catch (\Exception $exception) {
            $standaloneView->assign('error', 'no_typoscript');
            return;
        }

        // Try to get user info from session storage
        $store = new SessionStore();
        $userInfo = $store->get('user');

        if (($userInfo === null && GeneralUtility::_GP('login') == 1) || GeneralUtility::_GP('logout') == 1) {

            $application = $this->getApplication();
            if (!$application instanceof Application) {
                $standaloneView->assign('error', 'no_application');
                return;
            }

            $this->setAuthenticationApi($application);

            // Try to get user via authentication API
            if ($userInfo === null) {
                try {
                    $userInfo = $this->authentication->getUser();
                } catch (\Exception $exception) {
                    $this->authentication->deleteAllPersistentData();
                }
            }

            // Logout user from Auth0
            if (GeneralUtility::_GP('logout') == 1) {
                $this->authentication->logout();
                $userInfo = null;
            }

            // Login user to Auth0
            if ($userInfo === null && GeneralUtility::_GP('login') == 1) {
                $this->authentication->login();
            }
        }

        // Assign variables and Auth0 response to view
        $standaloneView->assign('auth0Error', GeneralUtility::_GP('error'));
        $standaloneView->assign('auth0ErrorDescription', GeneralUtility::_GP('error_description'));
        $standaloneView->assign('userInfo', $userInfo);
    }

    /**
     * @return Application|null
     */
    protected function getApplication()
    {
        $configuration = new EmAuth0Configuration();
        $applicationRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(ApplicationRepository::class);

        return $applicationRepository->findByUid($configuration->getBackendConnection());
    }
    /**
     * @param Application $application
     *
     * @throws \Auth0\SDK\Exception\CoreException
     */
    protected function setAuthenticationApi(Application $application)
    {
        $this->authentication = new AuthenticationApi(
            $application,
            GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
            'openid profile read:current_user'
        );
    }
}