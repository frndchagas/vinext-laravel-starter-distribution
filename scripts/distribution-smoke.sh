#!/usr/bin/env bash

set -euo pipefail

distribution_dir=$(mktemp -d)
install_dir=$(mktemp -d)
composer_home=$(mktemp -d)
composer_cache=$(composer config --global cache-dir --absolute)

cleanup() {
    rm -rf "$distribution_dir" "$install_dir" "$composer_home"
}

trap cleanup EXIT

rmdir "$distribution_dir" "$install_dir"
bun run scripts/build-distribution.mjs "$distribution_dir"

(
    cd "$distribution_dir"
    git init --initial-branch=main --quiet
    git config user.name "Distribution Smoke"
    git config user.email "distribution-smoke@example.com"
    git add .
    git commit --quiet -m "Build distribution"
    git tag v0.0.0
)

COMPOSER_CACHE_DIR="$composer_cache" COMPOSER_HOME="$composer_home" \
    composer config --global repositories.starter vcs "$distribution_dir"
COMPOSER_CACHE_DIR="$composer_cache" COMPOSER_HOME="$composer_home" composer create-project \
    frndchagas/vinext-ai-starter:0.0.0 \
    "$install_dir" \
    --no-interaction \
    --prefer-dist

(
    cd "$install_dir"
    git init --initial-branch=main --quiet
    git config user.name "Distribution Smoke"
    git config user.email "distribution-smoke@example.com"
    git add .
    git commit --quiet -m "Installed starter"
    bun ci
    bun run config:check
    bun run contracts:check
    bun run check
    bun run test:production
    test "$(php artisan migrate:status --no-ansi | grep -c '\[1\] Ran')" -eq \
        "$(find database/migrations -type f -name '*.php' | wc -l | tr -d ' ')"
)

echo "Packagist distribution smoke passed."
