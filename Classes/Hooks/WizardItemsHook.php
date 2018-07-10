<?php
declare(strict_types=1);

namespace Bitmotion\Auth0\Hooks;

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
use TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController;
use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class WizardItemsHook
 * @package Bitmotion\Auth0\Hooks
 */
class WizardItemsHook implements NewContentElementWizardHookInterface
{
    /**
     * Modifies WizardItems array
     *
     * @param array                       $wizardItems Array of Wizard Items
     * @param NewContentElementController $parentObject Parent object New Content element wizard
     */
    public function manipulateWizardItems(&$wizardItems, &$parentObject)
    {

        // create auth0 node
        $wizardItems['auth0'] = [];

        // set header label
        $wizardItems['auth0']['header'] = $this->getLanguageService()->sL('LLL:EXT:auth0/Resources/Private/Language/locallang_be.xlf:tx_auth0_backend_layout_wizard_label');

        // Add Plugins
        $wizardItems['auth0_loginform'] = [
            'iconIdentifier' => 'auth0',
            'title' => $this->getLanguageService()->sL('LLL:EXT:auth0/Resources/Private/Language/locallang_be.xlf:plugin.loginForm.title'),
            'description' => $this->getLanguageService()->sL('LLL:EXT:auth0/Resources/Private/Language/locallang_be.xlf:plugin.loginForm.description'),
            'params' => '&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=auth0_loginform',
            'tt_content_defValues' => [
                'CType' => 'list',
            ],
        ];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}