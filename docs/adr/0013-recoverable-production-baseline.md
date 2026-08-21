# ADR 0013: recoverable production baseline

Status: accepted.
Implementation: complete.

The supported production reference must include security headers, coherent health and readiness checks, executable PostgreSQL dump and restore scripts, an automated restore round-trip in nightly and release gates, and a recipe for external managed PostgreSQL and Redis. It remains a regular Compose deployment without a zero-downtime guarantee, full observability platform or automated disaster-recovery system.
