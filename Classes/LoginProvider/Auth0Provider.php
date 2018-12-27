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

use Auth0\SDK\Exception\CoreException;
use Auth0\SDK\Store\SessionStore;
use Bitmotion\Auth0\Api\AuthenticationApi;
use Bitmotion\Auth0\Domain\Model\Dto\EmAuth0Configuration;
use Bitmotion\Auth0\Exception\InvalidApplicationException;
use Bitmotion\Auth0\Utility\ApplicationUtility;
use Bitmotion\Auth0\Utility\ConfigurationUtility;
use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class Auth0Provider
 */
class Auth0Provider implements LoginProviderInterface
{
    /**
     * @var AuthenticationApi
     */
    protected $authentication = null;

    protected $standaloneView = null;

    protected $pageRenderer = null;

    /**
     * @throws CoreException
     * @throws InvalidConfigurationTypeException
     */
    public function render(StandaloneView $standaloneView, PageRenderer $pageRenderer, LoginController $loginController)
    {
        // Figure out whether TypoScript is loaded
        if (!$this->isTypoScriptLoaded()) {
            // In this case we need a default template
            $this->getDefaultTemplate($standaloneView, $pageRenderer);
            $standaloneView->assign('error', 'no_typoscript');

            return;
        }

        $this->prepareView($standaloneView, $pageRenderer);

        // Try to get user info from session storage
        $store = new SessionStore();
        $userInfo = $store->get('user');

        if (($userInfo === null && GeneralUtility::_GP('login') == 1) || GeneralUtility::_GP('logout') == 1) {
            if (!$this->setAuthenticationApi()) {
                $standaloneView->assign('error', 'no_application');

                return;
            }

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
     * @throws \Auth0\SDK\Exception\CoreException
     */
    protected function setAuthenticationApi(): bool
    {
        try {
            $configuration = new EmAuth0Configuration();
            $application = ApplicationUtility::getApplication($configuration->getBackendConnection());

            $this->authentication = new AuthenticationApi(
                $application,
                GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
                'openid profile read:current_user'
            );
        } catch (InvalidApplicationException $exception) {
            return false;
        }

        return true;
    }

    protected function isTypoScriptLoaded(): bool
    {
        try {
            ConfigurationUtility::getSetting('propertyMapping');
        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * @throws InvalidConfigurationTypeException
     */
    protected function prepareView(StandaloneView &$standaloneView, PageRenderer &$pageRenderer)
    {
        $backendViewSettings = ConfigurationUtility::getSetting('backend', 'view');
        $standaloneView->setLayoutRootPaths([$backendViewSettings['layoutPath']]);
        $standaloneView->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($backendViewSettings['templateFile']));
        $pageRenderer->addCssFile($backendViewSettings['stylesheet']);
    }

    protected function getDefaultView(StandaloneView &$standaloneView, PageRenderer &$pageRenderer)
    {
        $standaloneView->setLayoutRootPaths(['EXT:auth0/Resources/Private/Layouts/']);
        $standaloneView->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:auth0/Resources/Private/Templates/Backend.html'));
        $standaloneView->assign('error', 'no_typoscript');
        $pageRenderer->addCssFile('EXT:auth0/Resources/Public/Styles/backend.css');
    }

}
