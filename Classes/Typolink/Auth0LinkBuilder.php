<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Typolink;

/***
 *
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use Bitmotion\Auth0\Api\Auth0;
use Bitmotion\Auth0\Utility\ParametersUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;
use TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder;
use TYPO3\CMS\Frontend\Typolink\DatabaseRecordLinkBuilder;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;

class Auth0LinkBuilder extends AbstractTypolinkBuilder
{
    /**
     * @inheritdoc
     * @see DatabaseRecordLinkBuilder
     */
    public function build(array &$linkDetails, string $linkText, string $target, array $conf): array
    {
        $tsfe = $this->getTypoScriptFrontendController();
        $pageTsConfig = $tsfe->getPagesTSconfig();
        $configurationKey = 'tx_auth0.';
        $configuration = $tsfe->tmpl->setup['config.']['recordLinks.'];
        $linkHandlerConfiguration = $pageTsConfig['TCEMAIN.']['linkHandler.'];

        if (!isset($configuration[$configurationKey], $linkHandlerConfiguration[$configurationKey])) {
            throw new UnableToLinkException(
                'Configuration how to link "' . $linkDetails['typoLinkParameter'] . '" was not found, so "' . $linkText . '" was not linked.',
                1490989149,
                null,
                $linkText
            );
        }

        $typoScriptConfiguration = $configuration[$configurationKey]['typolink.'];
        $linkHandlerConfiguration = $linkHandlerConfiguration[$configurationKey]['configuration.'];

        if ($configuration[$configurationKey]['forceLink']) {
            $record = $tsfe->sys_page->getRawRecord($linkHandlerConfiguration['table'], $linkDetails['uid']);
        } else {
            $record = $tsfe->sys_page->checkRecord($linkHandlerConfiguration['table'], $linkDetails['uid']);
        }

        if ($record === 0) {
            throw new UnableToLinkException(
                'Record not found for "' . $linkDetails['typoLinkParameter'] . '" was not found, so "' . $linkText . '" was not linked.',
                1490989659,
                null,
                $linkText
            );
        }

        // Unset the parameter part of the given TypoScript configuration while keeping
        // config that has been set in addition.
        unset($conf['parameter.']);

        $typoLinkCodecService = GeneralUtility::makeInstance(TypoLinkCodecService::class);
        $parameterFromDb = $typoLinkCodecService->decode($conf['parameter']);
        $parameterFromDb['url'] = $this->getLinkForAuth0LinkRecord($record, $tsfe);

        $parameterFromTypoScript = $typoLinkCodecService->decode($typoScriptConfiguration['parameter']);
        $parameter = array_replace_recursive($parameterFromTypoScript, array_filter($parameterFromDb));
        $typoScriptConfiguration['parameter'] = $typoLinkCodecService->encode($parameter);

        $typoScriptConfiguration = array_replace_recursive($conf, $typoScriptConfiguration);

        if (!empty($linkDetails['fragment'])) {
            $typoScriptConfiguration['section'] = $linkDetails['fragment'];
        }

        // Build the full link to the record
        $localContentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $localContentObjectRenderer->start($record, $linkHandlerConfiguration['table']);
        $localContentObjectRenderer->parameters = $this->contentObjectRenderer->parameters;
        $link = $localContentObjectRenderer->typoLink($linkText, $typoScriptConfiguration);

        $this->contentObjectRenderer->lastTypoLinkLD = $localContentObjectRenderer->lastTypoLinkLD;
        $this->contentObjectRenderer->lastTypoLinkUrl = $localContentObjectRenderer->lastTypoLinkUrl;
        $this->contentObjectRenderer->lastTypoLinkTarget = $localContentObjectRenderer->lastTypoLinkTarget;

        // nasty workaround so typolink stops putting a link together, there is a link already built
        throw new UnableToLinkException(
            '',
            1491130170,
            null,
            $link
        );
    }

    protected function getLinkForAuth0LinkRecord(array $auth0Link, TypoScriptFrontendController $tsfe): string
    {
        if (!empty($auth0Link['application'])) {
            $application = $tsfe->sys_page->checkRecord('tx_auth0_domain_model_application', (int)$auth0Link['application']);
            $auth0Link['client_id'] = $application['id'];
            $auth0Link['domain'] = $application['domain'];
        }

        $auth0Config = [
            'domain' => $auth0Link['domain'],
            'client_id' => $auth0Link['client_id'],
            'redirect_uri' => $auth0Link['redirect_uri'],
        ];

        if (!empty($auth0Link['additional_authorize_parameters'])) {
            $additionalAuthorizeParameters = ParametersUtility::transformUrlParameters($auth0Link['additional_authorize_parameters']);
        }

        try {
            return (new Auth0(null, null, null, $auth0Config))->getLoginUrl($additionalAuthorizeParameters ?? []);
        } catch (\Exception $exception) {
            return '';
        }
    }
}
