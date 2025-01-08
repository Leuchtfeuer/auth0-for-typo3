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

namespace Leuchtfeuer\Auth0\Service;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\ParameterType;
use Leuchtfeuer\Auth0\Event\RedirectPreProcessingEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class RedirectService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @param array<mixed> $settings
     */
    public function __construct(protected array $settings)
    {
        if (!$this->logger instanceof Logger) {
            $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(self::class);
        }
    }

    /**
     * @param array<string> $allowedMethods
     * @param array<mixed> $additionalParameters
     * @throws DBALException
     * @throws SiteNotFoundException
     */
    public function handleRedirect(array $allowedMethods, array $additionalParameters = []): void
    {
        if ((bool)$this->settings['redirectDisable'] === false && !empty($this->settings['redirectMode'])) {
            $this->logger->notice('Try to redirect user.');
            $redirectUris = $this->getRedirectUri($allowedMethods);

            if ($redirectUris !== []) {
                $redirectUri = $this->addAdditionalParamsToRedirectUri($this->getUri($redirectUris), $additionalParameters);
                $redirectUri = $this->getEventDispatcher()
                    ->dispatch(new RedirectPreProcessingEvent($redirectUri, $this))
                    ->getRedirectUri();

                $this->logger->notice(sprintf('Redirect to: %s', $redirectUri));
                header('Location: ' . $redirectUri, false, 307);
                die;
            }

            $this->logger->warning('Redirect failed.');
        }
    }

    /**
     * @param array<mixed> $additionalParameters
     */
    public function forceRedirectByReferrer(array $additionalParameters = []): void
    {
        $this->setRedirectDisable(false);
        $this->setRedirectMode('referrer');
        $this->handleRedirect(['referrer'], $additionalParameters);
    }

    /**
     * @param array<string> $allowedRedirects
     * @return array<string>
     * @throws SiteNotFoundException
     * @throws DBALException
     */
    public function getRedirectUri(array $allowedRedirects): array
    {
        $redirect_url = [];

        if ($this->settings['redirectMode']) {
            $redirectMethods = GeneralUtility::trimExplode(',', $this->settings['redirectMode'], true);
            foreach ($redirectMethods as $redirectMethod) {
                if (in_array($redirectMethod, $allowedRedirects)) {
                    // Logintype is needed because the login-page wouldn't be accessible anymore after a login (would always redirect)
                    switch ($redirectMethod) {
                        case 'groupLogin':
                            // taken from dkd_redirect_at_login written by Ingmar Schlecht; database-field changed
                            $groupData = $this->getRequest()->getAttribute('frontend.user')?->groupData;
                            if (!empty($groupData['uid'])) {
                                // take the first group with a redirect page
                                $userGroupTable = $this->getRequest()->getAttribute('frontend.user')->usergroup_table;
                                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($userGroupTable);
                                $queryBuilder->getRestrictions()->removeAll();
                                $row = $queryBuilder
                                    ->select('felogin_redirectPid')
                                    ->from($userGroupTable)
                                    ->where(
                                        $queryBuilder->expr()->neq(
                                            'felogin_redirectPid',
                                            $queryBuilder->createNamedParameter('')
                                        ),
                                        $queryBuilder->expr()->in(
                                            'uid',
                                            $queryBuilder->createNamedParameter(
                                                $groupData['uid'],
                                                ArrayParameterType::INTEGER
                                            )
                                        )
                                    )
                                    ->executeQuery()
                                    ->fetchAssociative();
                                if ($row) {
                                    $redirect_url[] = $this->pi_getPageLink((int)$row['felogin_redirectPid']);
                                }
                            }
                            break;

                        case 'userLogin':
                            $userTable = $this->getRequest()->getAttribute('frontend.user')->user_table;
                            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($userTable);
                            $queryBuilder->getRestrictions()->removeAll();
                            $row = $queryBuilder
                                ->select('felogin_redirectPid')
                                ->from($userTable)
                                ->where(
                                    $queryBuilder->expr()->neq(
                                        'felogin_redirectPid',
                                        $queryBuilder->createNamedParameter('')
                                    ),
                                    $queryBuilder->expr()->eq(
                                        $this->getRequest()->getAttribute('frontend.user')->userid_column,
                                        $queryBuilder->createNamedParameter(
                                            $this->getRequest()->getAttribute('frontend.user')->user['uid'],
                                            ParameterType::INTEGER
                                        )
                                    )
                                )
                                ->executeQuery()
                                ->fetchAssociative();

                            if ($row) {
                                $redirect_url[] = $this->pi_getPageLink((int)$row['felogin_redirectPid']);
                            }
                            break;

                        case 'login':
                            if (isset($this->settings['redirectPageLogin'])) {
                                $redirect_url[] = $this->pi_getPageLink((int)$this->settings['redirectPageLogin']);
                            }
                            break;

                        case 'referrer':
                            $redirect_url[] = $this->validateRedirectUrl((string)($this->getRequest()->getQueryParams()['referrer'] ?? $this->getRequest()->getParsedBody()['referrer'] ?? null));
                            break;

                        case 'loginError':
                            if ($this->settings['redirectPageLoginError']) {
                                $redirect_url[] = $this->pi_getPageLink((int)$this->settings['redirectPageLoginError']);
                            }
                            break;

                        case 'logout':
                            if (isset($this->settings['redirectPageLogout'])) {
                                $redirect_url[] = $this->pi_getPageLink((int)$this->settings['redirectPageLogout']);
                            }
                            break;

                        case 'getpost':
                            $gpParameters = $this->getRequest()->getQueryParams()['tx_auth0_loginform'];
                            ArrayUtility::mergeRecursiveWithOverrule($gpParameters, $this->getRequest()->getParsedBody()['tx_auth0_loginform']);
                            if (isset($gpParameters['redirect']) && !(empty($gpParameters['redirect']))) {
                                $redirect_url[] = $gpParameters['redirect'];
                            }
                            break;
                    }
                }
            }
        }

        // Remove empty values, but keep "0" as value (that's why "strlen" is used as second parameter)
        if ($redirect_url !== []) {
            return array_filter($redirect_url, fn($var) => (bool)strlen($var));
        }

        return [];
    }

    /**
     * @param array<string> $redirectUris
     * @return string
     */
    public function getUri(array $redirectUris): string
    {
        return $this->settings['redirectFirstMethod'] ? array_shift($redirectUris) : array_pop($redirectUris);
    }

    public function setRedirectDisable(bool $value): void
    {
        $this->settings['redirectDisable'] = $value;
    }

    public function setRedirectMode(string $value): void
    {
        $this->settings['redirectMode'] = $value;
    }

    /**
     * @param array<mixed> $additionalParams
     */
    protected function addAdditionalParamsToRedirectUri(string $uri, array $additionalParams): string
    {
        if ($additionalParams !== []) {
            $uri .= '?';
        }

        foreach ($additionalParams as $key => $value) {
            $uri .= $key . '=' . $value . '&';
        }

        return rtrim($uri, '&');
    }

    /**
     * @param array<mixed> $urlParameters
     * @throws SiteNotFoundException
     */
    protected function pi_getPageLink(int $id, string $target = '', array $urlParameters = []): string
    {
        $cObj = $this->getRequest()->getAttribute('currentContentObject');
        if ($cObj instanceof ContentObjectRenderer) {
            return $this->getTypoLinkUrlFromCObj($cObj, $id, $target, $urlParameters);
        }

        return (string)GeneralUtility::makeInstance(SiteFinder::class)
            ->getSiteByPageId($id)->getRouter()->generateUri($id);
    }

    /**
     * @param array<mixed> $urlParameters
     */
    protected function getTypoLinkUrlFromCObj(ContentObjectRenderer $cObj, int $id, string $target, array $urlParameters): string
    {
        $conf = [];
        $conf['parameter'] = $id;
        if ($target) {
            $conf['target'] = $target;
            $conf['extTarget'] = $target;
            $conf['fileTarget'] = $target;
        }
        if (!empty($urlParameters)) {
            $conf['additionalParams'] = HttpUtility::buildQueryString($urlParameters, '&');
        }
        return $cObj->typoLink_URL($conf);
    }

    /**
     * Returns a valid and XSS cleaned url for redirect, checked against configuration "allowedRedirectHosts"
     *
     * @return string cleaned referrer or empty string if not valid
     */
    protected function validateRedirectUrl(string $url): string
    {
        if ($url === '') {
            return '';
        }
        // Validate the URL:
        if ($this->isRelativeUrl($url) || $this->isInCurrentDomain($url) || $this->isInLocalDomain($url)) {
            return $url;
        }
        // URL is not allowed
        $this->logger->warning('Url "' . $url . '"" for redirect was not accepted!');

        return '';
    }

    /**
     * Determines whether the URL is relative to the
     * current TYPO3 installation.
     */
    protected function isRelativeUrl(string $url): bool
    {
        $parsedUrl = @parse_url($url);
        if ($parsedUrl !== false && !isset($parsedUrl['scheme']) && !isset($parsedUrl['host'])) {
            // If the relative URL starts with a slash, we need to check if it's within the current site path
            return $parsedUrl['path'][0] !== '/' || \str_starts_with($parsedUrl['path'], GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'));
        }

        return false;
    }

    /**
     * Determines whether the URL is on the current host and belongs to the
     * current TYPO3 installation. The scheme part is ignored in the comparison.
     *
     * @return bool Whether the URL belongs to the current TYPO3 installation
     */
    protected function isInCurrentDomain(string $url): bool
    {
        $urlWithoutSchema = preg_replace('#^https?://#', '', $url);
        $siteUrlWithoutSchema = preg_replace('#^https?://#', '', GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));

        return \str_starts_with($urlWithoutSchema . '/', GeneralUtility::getIndpEnv('HTTP_HOST') . '/')
            && \str_starts_with((string) $urlWithoutSchema, $siteUrlWithoutSchema);
    }

    /**
     * Determines whether the URL matches a domain
     * in the sys_domain database table.
     *
     * @param string $url Absolute URL which needs to be checked
     * @return bool Whether the URL is considered to be local
     */
    protected function isInLocalDomain(string $url): bool
    {
        if (GeneralUtility::isValidUrl($url)) {
            $parsedUrl = parse_url($url);
            if ($parsedUrl['scheme'] === 'http' || $parsedUrl['scheme'] === 'https') {
                $host = $parsedUrl['host'];
                // Removes the last path segment and slash sequences like /// (if given):
                $path = preg_replace('#/+[^/]*$#', '', $parsedUrl['path']);

                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_domain');
                $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
                $localDomains = $queryBuilder->select('domainName')
                    ->from('sys_domain')
                    ->executeQuery()
                    ->fetchAllAssociative();

                if (is_array($localDomains)) {
                    foreach ($localDomains as $localDomain) {
                        // strip trailing slashes (if given)
                        $domainName = rtrim((string) $localDomain['domainName'], '/');
                        if (\str_starts_with($host . $path . '/', $domainName . '/')) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    protected function getEventDispatcher(): EventDispatcherInterface
    {
        return GeneralUtility::getContainer()->get(EventDispatcherInterface::class);
    }

    protected function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }
}
