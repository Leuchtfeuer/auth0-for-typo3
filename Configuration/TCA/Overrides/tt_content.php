<?php
declare(strict_types = 1);

/////////////////
//   PLUGINS   //
/////////////////

$configuration = new \Bitmotion\Auth0\Domain\Transfer\EmAuth0Configuration();

// Register LoginForm PlugIn
if ($configuration->isEnableFrontendLogin()) {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
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

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'auth0_loginform',
    'FILE:EXT:auth0/Configuration/FlexForms/LoginForm.xml'
);
