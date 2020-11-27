#!/usr/bin/env bash

NEW_RELEASE=$1
LATEST_RELEASE=$(git tag --sort=committerdate --list | tail -1)
DIR=$(pwd)


if [ ! -z "${NEW_RELEASE}" ]; then
  NEW_RELEASE=v${NEW_RELEASE}
  echo "Create release of version ${NEW_RELEASE}"
else
  echo "No version defined. Exit."
  exit 1;
fi

EXISTS=$(git describe --contains ${NEW_RELEASE} 2>&1)

if [[ "$EXISTS" == "$NEW_RELEASE" ]]; then
  echo "Release already exists. Exit."
  exit 1;
fi

if [ -d "$DIR" ]; then
  echo "Installing composer dependencies..."
  cd $DIR/Libraries
  composer update --with-all-dependencies --no-dev --no-progress --quiet
  echo "Done."

  echo "Archive repository..."
  cd $DIR
  zip -qr ../auth0_$1.zip *
  echo "Done."

  echo "Please add and push the git tag: git tag ${NEW_RELEASE} && gp --tags"
else
  echo "This script has to be executed from the git root directory!"
  exit 1;
fi

exit 0;
