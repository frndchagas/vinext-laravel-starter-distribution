# Distribution

The GitHub template is the source repository. Stable releases also publish a generated Laravel-root repository to Packagist as `frndchagas/vinext-laravel-starter`.

That package keeps the legacy identifier until the coordinated v1 rename described in ADR 0012. The source tree already uses the Vinext Laravel Starter product name.

```bash
laravel new my-app \
  --using=frndchagas/vinext-laravel-starter \
  --phpunit \
  --bun \
  --no-boost
```

The extra flags keep the starter's PHPUnit, Bun, and agent setup intact. Applications receive a snapshot and own their code; there is no starter updater.

Each distribution tag contains `.source-tag` and `.source-commit`. They identify the exact tag and commit in the source repository. The generated application replaces maintainer automation with a lightweight consumer CI and Dependabot configuration. Its README starts from the installed application instead of sending the User back through starter installation.

Source CI creates a local Composer repository and invokes the current pinned Laravel Installer with the documented `--using`, `--phpunit`, `--bun` and `--no-boost` flags. The installed application migrates SQLite, checks contract drift, runs the PHP and TypeScript gates, builds Vinext and exercises the production topology.

Maintainers publish only through a stable GitHub release. The release workflow generates the flattened tree and pushes the matching release tag to the distribution repository, which Packagist indexes.

## Release preflight

Run the preflight from a clean, synchronized `main` checkout after its complete CI succeeds:

```bash
bun run release:check -- v1.0.0
```

The command rejects reused or non-SemVer tags and prints the annotated-tag and GitHub Release commands. The publisher independently requires the exact tagged SHA to have one successful full `main` push workflow. It serializes all releases and pushes the distribution commit and tag atomically.
