# Distribution

The GitHub template is the source repository. Stable releases also publish a generated Laravel-root repository to Packagist as `frndchagas/vinext-ai-starter`.

```bash
laravel new my-app \
  --using=frndchagas/vinext-ai-starter \
  --phpunit \
  --bun \
  --no-boost
```

The extra flags keep the starter's PHPUnit, Bun, and agent setup intact. Applications receive a snapshot and own their code; there is no starter updater.

Each distribution tag contains `.source-tag` and `.source-commit`. They identify the exact tag and commit in the source repository. CI creates a local Composer repository from the generated tree, installs it from scratch, migrates SQLite, checks contract drift, runs the PHP and TypeScript gates, and builds Vinext.

Maintainers publish only through a stable GitHub release. The release workflow generates the flattened tree and pushes the same immutable tag to the distribution repository, which Packagist indexes.
