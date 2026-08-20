# Development

## Repository rules

`AGENTS.md` is the canonical instruction file for people and coding agents. The files under each application add only local rules. `CLAUDE.md` imports the corresponding `AGENTS.md` so Claude Code reads the same source.

Laravel owns identity, authorization, persisted domain state, queues and broadcasts. Vinext owns presentation. Components use the generated client for Laravel application requests.

## Commands

`package.json` is the executable source of truth. The main commands are:

| Command | Purpose |
| --- | --- |
| `bun run bootstrap` | Install locked dependencies, start infrastructure and migrate |
| `bun run dev` | Start Caddy dependencies plus Vinext, Laravel, Horizon and Reverb |
| `bun run check` | Format check, lint, types, unit tests and Vinext build |
| `bun run contracts:check` | Validate AsyncAPI and detect generated HTTP drift |
| `bun run audit` | Check deduplication and dependency advisories |
| `bun run test:e2e` | Run the browser journeys against the development stack |

Bun 1.4 runs independent workspace scripts in parallel. Contract generation stays sequential because TypeSpec writes the OpenAPI input consumed by Orval.

## Contract workflow

Public application HTTP changes start in `contracts/http/main.tsp`:

```text
TypeSpec -> OpenAPI 3.1 -> Orval -> Fetch client, Query hooks, Zod and MSW
```

Zod schemas are generated for consumers that need runtime validation. The current application client does not parse every response through them. Protocol endpoints used directly by Sanctum or Echo are not generated client operations unless a screen consumes them.

Realtime messages start in `contracts/realtime/asyncapi.yaml`. The current gate validates the document; it does not compare PHP event implementations with AsyncAPI automatically.

## Quality gates

GitHub Actions runs:

- verification on PHP 8.5 with frozen installs, dependency audit, contracts and the standard gate;
- integration tests on PHP 8.3 and 8.5 against PostgreSQL and Redis;
- serial Chromium E2E for auth, mail, queues and reconnection;
- a fresh template snapshot with isolated ports, migrations, contracts, build and proxy smoke tests;
- production image builds followed by migrations, health checks, a session flow and queued Task processing;
- Gitleaks, dependency review and OpenAPI breaking-change detection;
- GitHub CodeQL default setup outside the repository workflow.

Lefthook is intentionally smaller. Pre-commit runs format checks and uses Gitleaks when installed; pre-push runs `bun run check`. Contracts, audit and E2E remain explicit local commands and CI gates.

Tests never call a real external provider. The starter has no AI provider in the current release.

## Frontend baseline

The web app uses shadcn/ui components with Base UI primitives and Tailwind CSS through PostCSS. Do not mix primitive systems in one interaction surface.

Vinext 1.0.0-beta.8 enables the experimental React Compiler through Oxc. Every Vinext update must pass `vinext check`, production build and E2E.

Vitest and Testing Library cover fast component behavior. Playwright covers browser integration. A dedicated accessibility scanner is not part of the current gate.

## Generated and runtime files

Do not commit `.env`, credentials, logs, build output, Playwright reports or generated runtime state. OpenAPI and `packages/api-client/src/generated` are exceptions: they are deterministic contract artifacts and must be committed with their source change.

Architecture changes require an ADR. Domain language changes require an update to `CONTEXT.md`.
