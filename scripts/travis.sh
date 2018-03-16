#!/bin/bash

cd ${TRAVIS_BUILD_DIR}

if [[ $PHPUNIT == true ]]; then
    phpunit
fi