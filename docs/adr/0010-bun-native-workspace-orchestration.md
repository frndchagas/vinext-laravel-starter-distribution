# ADR 0010: Bun-native workspace orchestration

Status: accepted.
Implementation: complete.

Bun 1.4 owns dependency installation, workspace filtering and parallel script execution. The repository no longer uses Turborepo because the quality gate is short enough to run without a task cache, and maintaining a second orchestration layer is not worth the faster repeated run. Contract generation remains sequential where one artifact feeds the next. A task runner may return if measurements show a need for remote caching or a more complex graph.
