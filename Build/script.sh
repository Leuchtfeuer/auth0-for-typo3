#!/usr/bin/env bash

if [ -n $TYPO3_DATABASE_NAME ]; then
	export typo3DatabaseName=$TYPO3_DATABASE_NAME
else
	echo "No environment variable TYPO3_DATABASE_NAME set."
	exit 1
fi

if [ -n $TYPO3_DATABASE_HOST ]; then
	export typo3DatabaseHost=$TYPO3_DATABASE_HOST
else
	echo "No environment variable TYPO3_DATABASE_HOST set."
	exit 1
fi

if [ -n $TYPO3_DATABASE_USERNAME ]; then
	export typo3DatabaseUsername=$TYPO3_DATABASE_USERNAME
else
	echo "No environment variable TYPO3_DATABASE_USERNAME set."
	exit 1
fi

if [ -n $TYPO3_DATABASE_PASSWORD ]; then
	export typo3DatabasePassword=$TYPO3_DATABASE_PASSWORD
else
	echo "No environment variable TYPO3_DATABASE_PASSWORD set."
	exit 1
fi


echo "Running functional API tests"
.Build/bin/phpunit --colors --configuration Tests/Build/FunctionalTests.xml Tests/Functional --bootstrap .Build/vendor/typo3/testing-framework/Resources/Core/Build/FunctionalTestsBootstrap.php --coverage-clover=coverage.integration.clover


rm $APPLICATION_FILE