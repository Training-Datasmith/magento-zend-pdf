#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

PHP_IMAGE='php:7.1.33-cli@sha256:1e4671c8e26df6ae1d99b0e0059cdaa2fe72351c20c136e787dc463a165104e5'
COMPOSER_VERSION='2.2.30'
COMPOSER_INSTALLER_URL='https://getcomposer.org/installer'
COMPOSER_SIG_URL='https://composer.github.io/installer.sig'

DOCKER=(docker)
if ! docker info >/dev/null 2>&1; then
  DOCKER=(sudo docker)
fi

"${DOCKER[@]}" run --rm -v "$ROOT:/app" -w /app "$PHP_IMAGE" bash -lc "
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive
sed -i 's|deb.debian.org|archive.debian.org|g; s|security.debian.org|archive.debian.org|g' /etc/apt/sources.list
echo 'Acquire::Check-Valid-Until false;' > /etc/apt/apt.conf.d/99no-check-valid
apt-get update -qq
apt-get install -y -qq git unzip curl libzip-dev libpng-dev libjpeg62-turbo-dev
docker-php-ext-configure gd --with-jpeg-dir=/usr/include/
docker-php-ext-install -j\"\$(nproc)\" gd zip
php -v
php -m | grep -E '^(gd|zlib|iconv|ctype)$'

EXPECTED=\$(curl -fsSL '${COMPOSER_SIG_URL}')
curl -fsSL '${COMPOSER_INSTALLER_URL}' -o /tmp/composer-setup.php
ACTUAL=\$(php -r \"echo hash_file('sha384', '/tmp/composer-setup.php');\")
if [ \"\${EXPECTED}\" != \"\${ACTUAL}\" ]; then
  echo 'Composer installer checksum mismatch' >&2
  exit 1
fi
php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer --version=${COMPOSER_VERSION}
composer --version
composer install --no-interaction --prefer-dist

echo '== PHPUnit default order (run 1) =='
php -d error_reporting=-1 vendor/bin/phpunit --configuration phpunit.xml.dist
echo '== PHPUnit default order (run 2) =='
php -d error_reporting=-1 vendor/bin/phpunit --configuration phpunit.xml.dist
echo '== Seeded shuffle (run 1) =='
php -d error_reporting=-1 tests/ci/run-shuffle.php
echo '== Seeded shuffle (run 2) =='
php -d error_reporting=-1 tests/ci/run-shuffle.php
"

echo "All suite gates passed."
