<?php
declare(strict_types = 1);

use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/////////////////
//   PLUGINS   //
/////////////////

$configuration = new EmAuth0Configuration();

// Register LoginForm PlugIn
if ($configuration->isEnableFrontendLogin()) {
    ExtensionUtility::registerPlugin(
        'Auth0',
        'LoginForm',
        'Auth0: Login form'
    );
}

///////////////////
//   FLEXFORMS   //
///////////////////

// LoginForm
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['auth0_loginform'] = 'pi_flexform';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['auth0_loginform'] = 'layout,select_key,pages,recursive';

ExtensionManagementUtility::addPiFlexFormValue(
    'auth0_loginform',
    'FILE:EXT:auth0/Configuration/FlexForms/LoginForm.xml'
);
