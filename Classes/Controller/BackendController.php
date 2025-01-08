<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\Auth0\Controller;

use Leuchtfeuer\Auth0\Configuration\Auth0Configuration;
use Leuchtfeuer\Auth0\Domain\Repository\ApplicationRepository;
use Leuchtfeuer\Auth0\Domain\Repository\UserGroup\BackendUserGroupRepository;
use Leuchtfeuer\Auth0\Domain\Repository\UserGroup\FrontendUserGroupRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class BackendController extends ActionController
{
    public function __construct(
        protected readonly ApplicationRepository $applicationRepository,
        protected readonly Auth0Configuration $auth0Configuration,
        protected readonly BackendUserGroupRepository $backendUserGroupRepository,
        protected readonly FrontendUserGroupRepository $frontendUserGroupRepository,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly IconFactory $iconFactory,
        protected readonly BackendUriBuilder $backendUriBuilder
    ) {}

    public function listAction(): ResponseInterface
    {
        $moduleTemplate = $this->initView();
        return $moduleTemplate->renderResponse();
    }

    public function initView(): ModuleTemplate
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->createMenu($moduleTemplate);
        $this->createButtonBar($moduleTemplate);

        return $moduleTemplate;
    }

    protected function createMenu(ModuleTemplate $moduleTemplate): void
    {
        $menu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('auth0');

        $actions = [
            [
                'action' => 'list',
                'controller' => 'Application',
                'label' => 'menu.label.applications',
            ],
            [
                'action' => 'list',
                'controller' => 'Role',
                'label' => 'menu.label.roles',
            ],
            [
                'action' => 'list',
                'controller' => 'Property',
                'label' => 'menu.label.properties',
            ],
        ];

        foreach ($actions as $action) {
            $isActive = $this->request->getControllerName() === $action['controller'];
            $menu->addMenuItem(
                $menu->makeMenuItem()
                    ->setTitle($this->getTranslation($action['label']))
                    ->setHref(
                        $this->uriBuilder->reset()->uriFor(
                            $action['action'],
                            [],
                            $action['controller']
                        )
                    )->setActive($isActive)
            );
        }

        $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    protected function createButtonBar(ModuleTemplate $moduleTemplate): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $listButton = $buttonBar->makeLinkButton()
            ->setTitle($this->getTranslation('menu.button.overview'))
            ->setHref($this->uriBuilder->reset()->uriFor('list', [], 'Backend'))
            ->setIcon($this->iconFactory->getIcon('actions-viewmode-tiles', IconSize::SMALL));
        $buttonBar->addButton($listButton);
    }

    protected function addButton(
        string $label,
        string $actionName,
        string $controllerName,
        string $icon,
        ModuleTemplate $moduleTemplate
    ): void {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $linkButton = $buttonBar->makeLinkButton()
            ->setTitle($this->getTranslation($label))
            ->setHref($this->uriBuilder->reset()->uriFor($actionName, [], $controllerName))
            ->setIcon($this->iconFactory->getIcon($icon, IconSize::SMALL));

        $buttonBar->addButton($linkButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * @throws RouteNotFoundException
     */
    protected function getModuleUrl(bool $encoded = true, string $referenceType = BackendUriBuilder::ABSOLUTE_PATH): string
    {
        $parameters = [
            'tx_auth0_tools_auth0auth0' => [
                'action' => $this->request->getControllerActionName(),
                'controller' => $this->request->getControllerName(),
            ],
        ];

        $uri = $this->backendUriBuilder->buildUriFromRoute('tools_Auth0Auth0', $parameters, $referenceType);

        return $encoded ? rawurlencode($uri) : $uri;
    }

    protected function getTranslation(string $key): string
    {
        return $this->getLanguageService()->sL('LLL:EXT:auth0/Resources/Private/Language/locallang_mod.xlf:' . $key);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
