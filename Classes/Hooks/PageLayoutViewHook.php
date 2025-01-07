<?php

declare(strict_types=1);

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\Auth0\Hooks;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageLayoutViewHook
{
    protected string $listType = '';

    protected array $flexFormData = [];

    public function getSummary(array $params): string
    {
        $this->listType = $params['row']['list_type'];
        $this->flexFormData = GeneralUtility::xml2array($params['row']['pi_flexform']);

        $header = '<p><strong>Auth0: ' . $this->getLanguageService()->sL('LLL:EXT:auth0/Resources/Private/Language/locallang_be.xlf:plugin.loginForm.title') . '</strong></p>';
        $content = '';

        if ($this->flexFormData !== '' && $this->flexFormData !== '0' && $this->flexFormData !== []) {
            $content = sprintf(
                '<strong>%s</strong><span style="padding-left: 15px">%s</span><br/>',
                $this->getLanguageService()->sL('LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_application'),
                $this->getApplicationName()
            );

            if (isset($this->flexFormData['data']['sDEF']['lDEF']['settings.rawAdditionalAuthorizeParameters'])) {
                $additionalAuthorizeParameters = $this->flexFormData['data']['sDEF']['lDEF']['settings.rawAdditionalAuthorizeParameters']['vDEF'];
                if (!empty($additionalAuthorizeParameters)) {
                    $content .= sprintf(
                        '<strong>%s</strong><span style="padding-left: 15px">%s</span><br/>',
                        $this->getLanguageService()->sL('LLL:EXT:auth0/Resources/Private/Language/Database.xlf:backend.page.view.additionalAuthorizeParameters'),
                        $additionalAuthorizeParameters
                    );
                }
            }
        }

        return $header . $content;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getApplicationName(): string
    {
        $applicationUid = $this->getFieldFromFlexForm('settings.application');

        if ($applicationUid === '' || $applicationUid === '0') {
            return 'Not defined';
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_auth0_domain_model_application');

        return (string)$queryBuilder
            ->select('title')
            ->from('tx_auth0_domain_model_application')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($applicationUid, ParameterType::INTEGER)
                )
            )->executeQuery()
            ->fetchOne();
    }

    protected function getFieldFromFlexForm(string $key, string $sheet = 'sDEF'): string
    {
        $flexForm = $this->flexFormData;

        if (isset($flexForm['data'])) {
            $flexForm = $flexForm['data'];
            if (is_array($flexForm) && is_array($flexForm[$sheet]) && is_array($flexForm[$sheet]['lDEF']) && is_array($flexForm[$sheet]['lDEF'][$key]) && isset($flexForm[$sheet]['lDEF'][$key]['vDEF'])) {
                return $flexForm[$sheet]['lDEF'][$key]['vDEF'];
            }
        }

        return '';
    }
}
