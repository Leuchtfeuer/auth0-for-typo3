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

use Leuchtfeuer\Auth0\Domain\Repository\UserGroup\BackendUserGroupRepository;
use Leuchtfeuer\Auth0\Domain\Repository\UserGroup\FrontendUserGroupRepository;
use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use Leuchtfeuer\Auth0\Factory\ConfigurationFactory;
use Psr\Http\Message\ResponseInterface;

class RoleController extends BackendController
{
    public function listAction(): ResponseInterface
    {
        $moduleTemplate = $this->initView();
        $moduleTemplate->assignMultiple([
            'frontendUserGroupMapping' => (new FrontendUserGroupRepository())->findAll(),
            'backendUserGroupMapping' => $this->backendUserGroupRepository->findAll(),
            'extensionConfiguration' => new EmAuth0Configuration(),
            'yamlConfiguration' => $this->auth0Configuration->load(),
        ]);
        return $moduleTemplate->renderResponse();
    }

    public function updateAction(
        string $key = 'roles',
        int $defaultFrontendUserGroup = 0,
        string $adminRole = '',
        int $defaultBackendUserGroup = 0
    ): ResponseInterface {
        $configuration = $this->auth0Configuration->load();

        $configuration['roles'] = (new ConfigurationFactory())->buildRoles(
            $key,
            $defaultFrontendUserGroup,
            $adminRole,
            $defaultBackendUserGroup
        );

        $this->auth0Configuration->write($configuration);
        $this->addFlashMessage($this->getTranslation('message.role.updated.text'), $this->getTranslation('message.role.updated.title'));

        return $this->redirect('list');
    }
}
