#!/usr/bin/env bash

if [[ "${TYPO3_VERSION}" == *"dev"* ]]; then
  composer config minimum-stability dev
fi

composer req typo3/cms-core:"${TYPO3_VERSION}" typo3/cms-backend:"${TYPO3_VERSION}" typo3/cms-extbase:"${TYPO3_VERSION}" typo3/cms-extensionmanager:"${TYPO3_VERSION}" typo3/cms-fluid:"${TYPO3_VERSION}" typo3/cms-frontend:"${TYPO3_VERSION}"

if [[ "${TYPO3_VERSION}" == *"9.5"* ]]; then
  composer req typo3/testing-framework:^4.15
else
  composer req typo3/testing-framework
fi

# --------------------------------------------------------------------------- #
# Write Fixture file

APPLICATION_FILE="Tests/Functional/Fixtures/tx_auth0_domain_model_application.xml"

exec 6>&1
exec > $APPLICATION_FILE

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>"
echo "<dataset>"
echo "  <tx_auth0_domain_model_application>"
echo "    <uid>1</uid>"
echo "    <pid>0</pid>"
echo "    <tstamp>1609231945</tstamp>"
echo "    <crdate>1539156784</crdate>"
echo "    <cruser_id>1</cruser_id>"
echo "    <deleted>0</deleted>"
echo "    <hidden>0</hidden>"
echo "    <title>Auth0 Test Application</title>"
echo "    <single_log_out>0</single_log_out>"
echo "    <id>${AUTH0_CLIENT_ID}</id>"
echo "    <secret>${AUTH0_CLIENT_SECRET}</secret>"
echo "    <domain>${AUTH0_DOMAIN}</domain>"
echo "    <audience>${AUTH0_AUDIENCE}</audience>"
echo "    <signature_algorithm>RS256</signature_algorithm>"
echo "    <api>1</api>"
echo "  </tx_auth0_domain_model_application>"
echo "  <tx_auth0_domain_model_application>"
echo "    <uid>2</uid>"
echo "    <pid>0</pid>"
echo "    <tstamp>1609232145</tstamp>"
echo "    <crdate>1609232145</crdate>"
echo "    <cruser_id>1</cruser_id>"
echo "    <deleted>0</deleted>"
echo "    <hidden>0</hidden>"
echo "    <title>Auth0 Test Application (w/o API)</title>"
echo "    <single_log_out>0</single_log_out>"
echo "    <id>${AUTH0_CLIENT_ID}</id>"
echo "    <secret>${AUTH0_CLIENT_SECRET}</secret>"
echo "    <domain>${AUTH0_DOMAIN}</domain>"
echo "    <audience/>"
echo "    <signature_algorithm>RS256</signature_algorithm>"
echo "    <api>0</api>"
echo "  </tx_auth0_domain_model_application>"
echo "</dataset>"

exec 1>&6 6>&-

# --------------------------------------------------------------------------- #
