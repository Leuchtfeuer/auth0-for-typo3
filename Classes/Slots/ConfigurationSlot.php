<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Slots;

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfigurationSlot
{
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
        if (!in_array('code', $excludeParameters)) {
            $excludeParameters[] = 'code';
        }

        if (!in_array('state', $excludeParameters)) {
            $excludeParameters[] = 'state';
        }

        if (!in_array('error_description', $excludeParameters)) {
            $excludeParameters[] = 'error_description';
        }

        if (!in_array('error', $excludeParameters)) {
            $excludeParameters[] = 'error';
        }
    }
}
