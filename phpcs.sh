#!/usr/bin/env bash
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
runTest() {
  dir=$(dirname $1)
  if [ ! -d "$dir/vendor" ]; then
    echo "Running 'composer install' in $dir"
    (cd $dir && composer install --quiet --prefer-dist --no-progress --no-suggest)

  fi

  phpcs="vendor/bin/phpcs"
  if [ -f "$dir/$phpcs" ]; then
      echo "(cd $dir && $phpcs)"
    (cd $dir && $phpcs)
  fi
}

runTest $DIR/composer.json

for module in $DIR/modules/*/composer.json; do
  runTest $module
done
