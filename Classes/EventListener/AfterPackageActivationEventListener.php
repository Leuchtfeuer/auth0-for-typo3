<?php

declare(strict_types=1);

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Leuchtfeuer Digital Marketing <dev@Leuchtfeuer.com>
 */

namespace Leuchtfeuer\Auth0\EventListener;

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Package\Event\AfterPackageActivationEvent;

class AfterPackageActivationEventListener
{
    /**
     * @var array<string>
     */
    protected array $excludedParameters = [
        'code',
        'state',
        'error_description',
        'error',
    ];

    public function __construct(protected readonly ConfigurationManager $configurationManager) {}

    public function __invoke(AfterPackageActivationEvent $event): void
    {
        if ($event->getPackageKey() === 'auth0') {
            $path = 'FE/cacheHash/excludedParameters';
            $excludeParameters = $this->configurationManager->getConfigurationValueByPath($path);
            $this->setValues($excludeParameters);
            $this->configurationManager->setLocalConfigurationValueByPath($path, $excludeParameters);
        }
    }

    /**
     * @param array<string> $excludeParameters
     */
    protected function setValues(array &$excludeParameters): void
    {
        foreach ($this->excludedParameters as $excludedParameter) {
            if (!in_array($excludedParameter, $excludeParameters)) {
                $excludeParameters[] = $excludedParameter;
            }
        }
    }
}
