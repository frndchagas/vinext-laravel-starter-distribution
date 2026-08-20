# Web instructions

- Use Vinext APIs supported by `vinext check`.
- Use shadcn/ui components with the configured Base UI primitive set.
- Do not mix Radix or React Aria primitives into the same component system.
- Use the generated API client for every Laravel request.
- Keep TypeScript strict. The generated fetcher provides compile-time types but does not parse responses automatically; use the generated Zod schemas where runtime validation is required.
- Run `bun run format`, `bun run lint`, `bun run typecheck` and `bun run test` in this directory for focused changes.
