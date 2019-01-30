<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Slots;

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfigurationSlot
{
    protected $excludedParameters = [
        'code',
        'state',
        'error_description',
        'error',
    ];

    public function addCacheHashExcludedParameters()
    {
        $path = ['FE', 'cacheHash', 'excludedParameters'];
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        $excludeParameters = $configurationManager->getConfigurationValueByPath($path);

        $this->setValues($excludeParameters);

        $configurationManager->setLocalConfigurationValueByPath($path, $excludeParameters);
    }

    protected function setValues(array &$excludeParameters)
    {
        foreach ($this->excludedParameters as $excludedParameter) {
            if (!in_array($excludedParameter, $excludeParameters)) {
                $excludeParameters[] = $excludedParameter;
            }
        }
    }
}
