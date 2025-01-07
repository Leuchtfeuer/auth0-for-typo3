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

namespace Leuchtfeuer\Auth0\Utility;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class RoutingUtility implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected ?int $targetPage = null;

    protected int $targetPageType = 0;

    protected array $arguments = [];

    protected bool $createAbsoluteUri = true;

    protected bool $buildFrontendUri = true;

    public function __construct(
        protected readonly ServerRequestInterface $request,
        protected readonly UriBuilder $uriBuilder
    ) {
        /** @var PageArguments $pageArguments */
        $pageArguments = $this->request->getAttribute('routing');
        $this->targetPage = $pageArguments->getPageId();
    }

    public function getUri(): string
    {
        $this->uriBuilder
            ->reset()
            ->setTargetPageUid($this->targetPage)
            ->setTargetPageType($this->targetPageType)
            ->setArguments($this->arguments);

        $this->uriBuilder->setCreateAbsoluteUri($this->createAbsoluteUri);

        if ($this->buildFrontendUri) {
            $uri = $this->uriBuilder->buildFrontendUri();
            $this->logger->notice(sprintf('Set URI to: %s', $uri));

            // Base of site configuration might be "/" so we have to prepend the domain
            if (str_starts_with($uri, '/')) {
                $uri = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . ltrim($uri, '/');
            }

            return $uri;
        }

        return '';
    }

    public function setTargetPage(int $targetPage): self
    {
        $this->logger->debug(sprintf('[URI] Set target page to "%s"', $targetPage));
        $this->targetPage = $targetPage;

        return $this;
    }

    public function setTargetPageType(int $targetPageType): void
    {
        $this->logger->debug(sprintf('[URI] Set target page type to "%s"', $targetPageType));
        $this->targetPageType = $targetPageType;
    }

    public function addArgument(string $key, $value): self
    {
        $this->arguments = array_merge_recursive($this->arguments, [$key => $value]);

        return $this;
    }

    public function setArguments(array $arguments): self
    {
        $this->logger->debug('[URI] Set arguments', $arguments);
        $this->arguments = $arguments;

        return $this;
    }

    public function setCreateAbsoluteUri(bool $createAbsoluteUri): void
    {
        $this->createAbsoluteUri = $createAbsoluteUri;
    }

    public function setBuildFrontendUri(bool $buildFrontendUri): void
    {
        $this->buildFrontendUri = $buildFrontendUri;
    }
}
