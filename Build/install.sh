#!/usr/bin/env bash

if [[ "${TYPO3_VERSION}" = *"dev"*  ]]; then
    composer config minimum-stability dev
fi

composer req friendsofphp/php-cs-fixer:"${PHP_CS_FIXER_VERSION}" typo3/cms-core:"${TYPO3_VERSION}" typo3/cms-backend:"${TYPO3_VERSION}" typo3/cms-extbase:"${TYPO3_VERSION}" typo3/cms-extensionmanager:"${TYPO3_VERSION}" typo3/cms-fluid:"${TYPO3_VERSION}" typo3/cms-frontend:"${TYPO3_VERSION}"


# --------------------------------------------------------------------------- #
# Write Fixture file

APPLICATION_FILE="Tests/Functional/Fixtures/tx_auth0_domain_model_application.xml"

exec 6>&1
exec > $APPLICATION_FILE

echo '<?xml version="1.0" encoding="UTF-8" ?>'
echo '<dataset>'
echo '<tx_auth0_domain_model_application>'
echo '<uid>1</uid>'
echo '<pid>0</pid>'
echo '<tstamp>1539156784</tstamp>'
echo '<crdate>1539156784</crdate>'
echo '<cruser_id>1</cruser_id>'
echo '<deleted>0</deleted>'
echo '<hidden>0</hidden>'
echo "<title>${APPLICATION}</title>"
echo "<id>${CLIENT_ID}</id>"
echo "<secret>${CLIENT_SECRET}</secret>"
echo "<domain>${CLIENT_DOMAIN}</domain>"
echo "<audience>api/v2/</audience>"
echo "</tx_auth0_domain_model_application>"
echo "</dataset>"

exec 1>&6 6>&-

# --------------------------------------------------------------------------- #