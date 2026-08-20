# ADR 0008: GitHub source with Packagist distribution

Status: accepted.
Implementation: complete.

The GitHub template remains the canonical source. After the production path is verified, each stable tag will generate a small distribution repository published on Packagist as `frndchagas/vinext-ai-starter`. It will expose the starter through `laravel new --using` and qualify for submission to the community starter directory. The generated distribution must map to one source tag and will not add an updater to applications created from the starter.

The distribution flattens the Laravel API into the repository root because Composer and the Laravel installer require `composer.json` and `artisan` there. A dedicated smoke test installs the generated repository through Composer before release. The distribution records its source tag and commit, and only stable GitHub releases may publish matching tags.
