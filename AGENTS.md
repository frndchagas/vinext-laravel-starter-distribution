# Repository instructions

## Map

- `apps/web`: Vinext frontend, React, shadcn/ui and Tailwind CSS.
- the repository root: Laravel API and the source of truth for identity and domain state.
- `packages/api-client`: generated TypeScript client. Do not hand edit generated files.
- `docs/`: architecture guides. Decision records live in `docs/adr/`.
- `CONTEXT.md`: domain glossary. Use its canonical terms in code, contracts and UI.

## Commands

- `bun run bootstrap`: install dependencies, start local infrastructure and migrate.
- `bun run dev`: start the proxy, web, API, Horizon and Reverb.
- `bun run format`: format TypeScript and PHP.
- `bun run check`: run formatting checks, lint, types, unit tests and build.
- `bun run contracts:check`: validate contracts and fail on generated HTTP drift.
- `bun run test:e2e`: run the Playwright flows against the development stack.

## Rules

- Keep public HTTP types in the contract once `contracts/http` exists.
- Keep authentication, authorization and persisted domain state in Laravel.
- Do not add a second formatter, linter or package manager.
- Tests must not use real external services.
- Never commit `.env`, credentials or generated runtime artifacts.
- Update the relevant decision document when architecture changes.

## Laravel API instructions

- Laravel owns identity, authorization, domain state, queues and broadcasts.
- Use Form Requests for input, API Resources for output and Policies for resource authorization.
- Dispatch jobs and broadcast events only after the surrounding transaction commits.
- Keep controllers thin and avoid abstractions without a concrete boundary.
- Run `composer format:check`, `composer lint` and `composer test` for focused changes.
