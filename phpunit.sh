#!/usr/bin/env bash
set -e

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"

NO_SYNC=false
while [[ $# -gt 0 ]]; do
  case "$1" in
    --no-sync)
      NO_SYNC=true
      shift
      ;;
    *)
      echo "Invalid option: $1" >&2
      exit 1
      ;;
  esac
done

runTest() {
  dir=$(dirname "$1")
  if [ ! -d "$dir/vendor" ]; then
    echo "Running 'composer install' in $dir"
    (cd "$dir" && composer install --prefer-dist --no-progress --no-suggest)
  fi

  echo "Running PHPUnit in $dir"
  cd "$dir"
  vendor/bin/phpunit --stop-on-failure --exclude-group sync
  if [ "$NO_SYNC" == false ]; then
    vendor/bin/phpunit --stop-on-failure --group sync
  fi
}

runTest "$DIR/phpunit.xml.dist"

for config in $DIR/modules/*/phpunit.xml.dist; do
  runTest "$config"
done
