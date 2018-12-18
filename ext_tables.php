<?php
defined('TYPO3_MODE') || die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:auth0/Configuration/TsConfig/Page/ContentElementWizard/setup.tsconfig">'
);

$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
    'auth0',
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class, array(
    'source' => 'EXT:auth0/Resources/Public/Icons/auth0.svg'
));