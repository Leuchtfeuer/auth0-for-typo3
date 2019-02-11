#!/usr/bin/env bash

if [[ "${TYPO3_VERSION}" = *"dev"*  ]]; then
    composer config minimum-stability dev
fi

composer req friendsofphp/php-cs-fixer:"${PHP_CS_FIXER_VERSION}" typo3/cms-core:"${TYPO3_VERSION}" typo3/cms-backend:"${TYPO3_VERSION}" typo3/cms-extbase:"${TYPO3_VERSION}" typo3/cms-extensionmanager:"${TYPO3_VERSION}" typo3/cms-fluid:"${TYPO3_VERSION}" typo3/cms-frontend:"${TYPO3_VERSION}"