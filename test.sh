#!/bin/bash
#vendor/bin/phpunit --coverage-clover=coverage.clover
vendor/bin/phpunit --coverage-html=coverage --coverage-clover=coverage.xml
export CODECOV_TOKEN=34a0530a-b406-47a0-8730-86b06d9d8a98
bash <(curl -s https://codecov.io/bash)

