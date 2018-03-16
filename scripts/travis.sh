#!/bin/bash

cd ${TRAVIS_BUILD_DIR}

if [[ $TRAVIS_PHP_VERSION >= '7.0' ]]; then
    phpunit
fi