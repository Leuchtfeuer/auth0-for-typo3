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

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use Leuchtfeuer\Auth0\Configuration\Auth0Configuration;
use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use Leuchtfeuer\Auth0\Factory\ConfigurationFactory;
use Leuchtfeuer\Auth0\Utility\TcaUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PropertyController extends BackendController
{
    public function listAction(): ResponseInterface
    {
        $tcaUtility = new TcaUtility();
        $moduleTemplate = $this->initView();
        $this->view->assignMultiple([
            'frontendUserColumns' => $tcaUtility->getColumnsFromTable('fe_users'),
            'backendUserColumns' => $tcaUtility->getColumnsFromTable('be_users'),
            'extensionConfiguration' => new EmAuth0Configuration(),
            'yamlConfiguration' => GeneralUtility::makeInstance(Auth0Configuration::class)->load(),
        ]);
        $moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    public function newAction(string $table, string $type): ResponseInterface
    {
        $moduleTemplate = $this->initView();
        $this->addButton('menu.button.cancel', 'list', 'Property', 'actions-close', $moduleTemplate);
        $this->view->assignMultiple([
            'table' => $table,
            'type' => $type,
            'properties' => (new TcaUtility())->getUnusedColumnsFromTable($table),
        ]);
        $moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * @throws StopActionException
     */
    public function createAction(array $property, string $table, string $type): ResponseInterface
    {
        if (empty($property['databaseField']) || empty($property['auth0Property'])) {
            return new ForwardResponse('new');
        }

        ksort($property);
        $propertyConfiguration = (new ConfigurationFactory())->buildProperty(...array_values($property));
        $auth0Configuration = GeneralUtility::makeInstance(Auth0Configuration::class);
        $configuration = $auth0Configuration->load();
        $configuration['properties'][$table][$type][] = $propertyConfiguration;
        $auth0Configuration->write($configuration);
        $this->addFlashMessage($this->getTranslation('message.property.created.text'), $this->getTranslation('message.property.created.title'));

        return $this->redirect('list');
    }

    /**
     * @throws StopActionException
     */
    public function deleteAction(array $property, string $table, string $type): ResponseInterface
    {
        if ((bool)$property['readOnly'] === false) {
            $auth0Configuration = GeneralUtility::makeInstance(Auth0Configuration::class);
            $configuration = $auth0Configuration->load();

            foreach ($configuration['properties'][$table][$type] as $key => $configurationProperty) {
                if ($configurationProperty['databaseField'] === $property['databaseField']) {
                    unset($configuration['properties'][$table][$type][$key]);
                    break;
                }
            }

            $auth0Configuration->write($configuration);
        }

        $this->addFlashMessage($this->getTranslation('message.property.deleted.text'), $this->getTranslation('message.property.deleted.title'));

        return $this->redirect('list');
    }

    public function editAction(array $property, string $table, string $type): ResponseInterface
    {
        $moduleTemplate = $this->initView();
        $this->addButton('menu.button.cancel', 'list', 'Property', 'actions-close', $moduleTemplate);
        $this->view->assignMultiple([
            'property' => $property,
            'table' => $table,
            'type' => $type,
            'properties' => (new TcaUtility())->getUnusedColumnsFromTable($table, $property['databaseField']),
        ]);
        $moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * @throws StopActionException
     */
    public function updateAction(array $property, string $table, string $type): ResponseInterface
    {
        $auth0Configuration = GeneralUtility::makeInstance(Auth0Configuration::class);
        $configuration = $auth0Configuration->load();

        foreach ($configuration['properties'][$table][$type] ?? [] as $key => $item) {
            if ($item['databaseField'] === $property['databaseField']) {
                $configuration['properties'][$table][$type][$key] = $property;
                break;
            }
        }

        $auth0Configuration->write($configuration);
        $this->addFlashMessage($this->getTranslation('message.property.updated.text'), $this->getTranslation('message.property.updated.title'));

        return $this->redirect('list');
    }
}
