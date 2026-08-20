# Contributing

Contributions are welcome. Keep changes focused, explain the behavior they change and include evidence from the relevant gate.

## Setup

Follow [Getting started](docs/getting-started.md), then read the root and application-level `AGENTS.md` files before editing.

## Before opening a pull request

Run the standard gate:

```bash
bun run check
```

Run additional gates when the change touches their boundary:

```bash
bun run contracts:check  # TypeSpec, OpenAPI, Orval or AsyncAPI
bun run audit            # dependencies or lockfiles
bun run test:e2e         # auth, mail, queue, realtime or browser behavior
```

Lefthook runs a smaller local subset. Pre-commit checks formatting and uses Gitleaks when it is installed. Pre-push runs `bun run check`. CI remains authoritative for contracts, dependency review, E2E and security scanning.

## Public contracts

Application HTTP changes begin in `contracts/http/main.tsp`. Regenerate OpenAPI and the client with `bun run contracts:build`, then commit source and generated artifacts together. Realtime messages begin in `contracts/realtime/asyncapi.yaml`.

Do not edit `contracts/http/openapi` or `packages/api-client/src/generated` by hand. Oasdiff compares proposed OpenAPI with the pull request target branch.

## Documentation and decisions

Guides describe current behavior. Accepted work that is not implemented belongs in an ADR with `Implementation: pending`. Update `CONTEXT.md` only when domain language changes; implementation terms do not belong there.

Add an ADR only for a costly, surprising trade-off. Keep it short and link to operational guidance rather than duplicating it.

## Pull request checklist

- the changed behavior has a test or reproducible proof;
- public contracts and generated files agree;
- migrations are additive and published migrations are unchanged;
- no secret, `.env`, log, build output or browser artifact is committed;
- current documentation still matches the implementation.
