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
use Bitmotion\Auth0\Domain\Transfer\EmAuth0Configuration;
use Bitmotion\Auth0\Factory\ConfigurationFactory;
use Bitmotion\Auth0\Utility\TcaUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;

class PropertyController extends BackendController
{
    public function listAction(): void
    {
        $tcaUtility = new TcaUtility();

        $this->view->assignMultiple([
            'frontendUserColumns' => $tcaUtility->getColumnsFromTable('fe_users'),
            'backendUserColumns' => $tcaUtility->getColumnsFromTable('be_users'),
            'extensionConfiguration' => new EmAuth0Configuration(),
            'yamlConfiguration' => GeneralUtility::makeInstance(Auth0Configuration::class)->load(),
        ]);
    }

    /**
     * @param string $table
     * @param string $type
     */
    public function newAction(string $table, string $type): void
    {
        $this->addButton('menu.button.cancel', 'list', 'Property', 'actions-close');
        $this->view->assignMultiple([
            'table' => $table,
            'type' => $type,
            'properties' => (new TcaUtility())->getUnusedColumnsFromTable($table),
            'foreignTables' => (new TcaUtility())->getTables($table),
        ]);
    }

    /**
     * @param array $property
     * @param string $table
     * @param string $type
     *
     * @throws StopActionException
     */
    public function createAction(array $property, string $table, string $type): void
    {
        if (empty($property['databaseField']) || empty($property['auth0Property'])) {
            $this->forward('new');
        }

        ksort($property);
        $propertyConfiguration = (new ConfigurationFactory())
            ->buildProperty(
                $property['auth0Property'],
                $property['databaseField'],
                $property['processing'] ?? 'null'
            );
        $auth0Configuration = GeneralUtility::makeInstance(Auth0Configuration::class);
        $configuration = $auth0Configuration->load();
        $configuration['properties'][$table][$type][] = $propertyConfiguration;
        $auth0Configuration->write($configuration);

        $this->addFlashMessage($this->getTranslation('message.property.created.text'), $this->getTranslation('message.property.created.title'));
        $this->redirect('list');
    }

    /**
     * @param array $property
     * @param string $table
     * @param string $type
     *
     * @throws StopActionException
     */
    public function deleteAction(array $property, string $table, string $type): void
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
        $this->redirect('list');
    }

    /**
     * @param array $property
     * @param string $table
     * @param string $type
     */
    public function editAction(array $property, string $table, string $type): void
    {
        $this->addButton('menu.button.cancel', 'list', 'Property', 'actions-close');
        $this->view->assignMultiple([
            'property' => $property,
            'table' => $table,
            'type' => $type,
            'properties' => (new TcaUtility())->getUnusedColumnsFromTable($table, $property['databaseField']),
        ]);
    }

    /**
     * @param array $property
     * @param string $table
     * @param string $type
     *
     * @throws StopActionException
     */
    public function updateAction(array $property, string $table, string $type): void
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
        $this->redirect('list');
    }
}
