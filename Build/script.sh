#!/usr/bin/env bash

if [ -n $MYSQL_DATABASE ]; then
	export typo3DatabaseName=$MYSQL_DATABASE
else
	echo "No environment variable TYPO3_DATABASE_NAME set."
	exit 1
fi

if [ -n $MYSQL_HOST ]; then
	export typo3DatabaseHost=$MYSQL_HOST
else
	echo "No environment variable TYPO3_DATABASE_HOST set."
	exit 1
fi

if [ -n $MYSQL_USER ]; then
	export typo3DatabaseUsername=$MYSQL_USER
else
	echo "No environment variable TYPO3_DATABASE_USERNAME set."
	exit 1
fi

if [ -n $MYSQL_PASSWORD ]; then
	export typo3DatabasePassword=$MYSQL_PASSWORD
else
	echo "No environment variable TYPO3_DATABASE_PASSWORD set."
	exit 1
fi

