#!/bin/bash

echo "Using TYPO3 Version: $TYPO3_VERSION"
echo "Using PHP-CS Fixer Version: $PHP_CS_FIXER_VERSION"
echo "Using database host: $TYPO3_DATABASE_HOST"
echo "Using database dbname: $TYPO3_DATABASE_NAME"
echo "Using database user: $TYPO3_DATABASE_USERNAME"


composer req typo3/cms-core:"${TYPO3_VERSION}"
composer req typo3/cms-backend:"${TYPO3_VERSION}"
composer req typo3/cms-extbase:"${TYPO3_VERSION}"
composer req typo3/cms-extensionmanager:"${TYPO3_VERSION}"
composer req typo3/cms-fluid:"${TYPO3_VERSION}"
composer req typo3/cms-frontend:"${TYPO3_VERSION}"
composer req typo3/cms-saltedpasswords:"${TYPO3_VERSION}"