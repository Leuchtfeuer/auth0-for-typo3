<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Bitmotion\Auth0\Controller;

use Bitmotion\Auth0\Configuration\Auth0Configuration;
use Bitmotion\Auth0\Domain\Repository\UserGroup\BackendUserGroupRepository;
use Bitmotion\Auth0\Domain\Repository\UserGroup\FrontendUserGroupRepository;
use Bitmotion\Auth0\Domain\Transfer\EmAuth0Configuration;
use Bitmotion\Auth0\Utility\ConfigurationUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class BackendController extends ActionController
{
    /**
     * @var BackendTemplateView
     */
    protected $view;

    protected $defaultViewObjectName = BackendTemplateView::class;

    protected $iconFactory;

    public function __construct(IconFactory $iconFactory)
    {
        $this->iconFactory = $iconFactory;

        if (version_compare(TYPO3_version, '10.0.0', '<')) {
            parent::__construct();
        }
    }

    public function listAction()
    {
        // Just an empty view
    }

    public function rolesAction()
    {
        $this->view->assignMultiple([
            'frontendUserGroupMapping' => (new FrontendUserGroupRepository())->findAll(),
            'backendUserGroupMapping' => (new BackendUserGroupRepository())->findAll(),
            'extensionConfiguration' => new EmAuth0Configuration(),
            'yamlConfiguration' => (new Auth0Configuration())->load(),
        ]);
    }

    public function updateRolesAction()
    {
        $auth0Configuration = new Auth0Configuration();
        $configuration = $auth0Configuration->load();

        if ($this->request->hasArgument('key')) {
            $configuration['roles']['key'] = $this->request->getArgument('key');
        }

        if ($this->request->hasArgument('defaultFrontendUserGroup')) {
            $configuration['roles']['default']['frontend'] = (int)$this->request->getArgument('defaultFrontendUserGroup');
        }

        if ($this->request->hasArgument('adminRole')) {
            $configuration['roles']['beAdmin'] = $this->request->getArgument('adminRole');
            $configuration['roles']['default']['backend'] = (int)$this->request->getArgument('defaultBackendUserGroup');
        }

        $auth0Configuration->write($configuration);
        $this->redirect('roles');
    }

    public function propertiesAction()
    {
    }

    public function acquireMappingTypoScriptAction()
    {
        $settings = ConfigurationUtility::getSetting('roles');
        (new FrontendUserGroupRepository())->translate($settings['fe_users']);
        (new BackendUserGroupRepository())->translate($settings['be_users']);

        $auth0Configuration = new Auth0Configuration();
        $configuration = $auth0Configuration->load();
        $configuration['roles']['key'] = $settings['key'];
        $auth0Configuration->write($configuration);

        $this->redirect('roles');
    }

    public function initializeView(ViewInterface $view): void
    {
        parent::initializeView($view);

        if ($this->request->getControllerActionName() !== 'list' && $view instanceof BackendTemplateView) {
            $this->createMenu();
            $this->createButtonBar();
        }
    }

    protected function createMenu(): void
    {
        $menu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('auth0');

        $actions = [
            [
                'action' => 'roles',
                'label' => 'menu.label.roles',
            ],
            [
                'action' => 'properties',
                'label' => 'menu.label.properties',
            ],
        ];

        foreach ($actions as $action) {
            $isActive = $this->request->getControllerActionName() === $action['action'];
            $menu->addMenuItem(
                $menu->makeMenuItem()
                    ->setTitle($this->getLabel($action['label']))
                    ->setHref(
                        $this->getUriBuilder()->reset()->uriFor(
                            $action['action'],
                            [],
                            $this->request->getControllerName()
                        )
                    )->setActive($isActive)
            );
        }

        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    protected function createButtonBar(): void
    {
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $listButton = $buttonBar->makeLinkButton()
            ->setTitle($this->getLabel('menu.button.overview'))
            ->setHref($this->getUriBuilder()->reset()->uriFor('list', [], 'Backend'))
            ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
        $buttonBar->addButton($listButton);
    }

    protected function getUriBuilder(): UriBuilder
    {
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);

        return $uriBuilder;
    }

    protected function getLabel($key): string
    {
        return $this->getLanguageService()->sL('LLL:EXT:auth0/Resources/Private/Language/locallang_mod.xlf:' . $key);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
