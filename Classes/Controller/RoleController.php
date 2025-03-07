<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Leuchtfeuer Digital Marketing <dev@Leuchtfeuer.com>
 */

namespace Leuchtfeuer\Auth0\Controller;

use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use Leuchtfeuer\Auth0\Factory\ConfigurationFactory;
use Psr\Http\Message\ResponseInterface;

class RoleController extends BackendController
{
    public function listAction(): ResponseInterface
    {
        $moduleTemplate = $this->initView();
        $moduleTemplate->assignMultiple([
            'backendUserGroupMapping' => $this->backendUserGroupRepository->findAll(),
            'extensionConfiguration' => new EmAuth0Configuration(),
            'yamlConfiguration' => $this->auth0Configuration->load(),
        ]);
        return $moduleTemplate->renderResponse('Role/List');
    }

    public function updateAction(
        string $key = 'roles',
        string $adminRole = '',
        int $defaultBackendUserGroup = 0
    ): ResponseInterface {
        $configuration = $this->auth0Configuration->load();

        $configuration['roles'] = (new ConfigurationFactory())->buildRoles(
            $key,
            $adminRole,
            $defaultBackendUserGroup
        );

        $this->auth0Configuration->write($configuration);
        $this->addFlashMessage($this->getTranslation('message.role.updated.text'), $this->getTranslation('message.role.updated.title'));

        return $this->redirect('list');
    }
}
