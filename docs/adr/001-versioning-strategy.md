# ADR-001: Versioning Strategy

**Date:** 2026-03-26
**Status:** Accepted
**Decision makers:** @gabiudrescu, Claude

## Context

This is a dev-only Symfony bundle (`require-dev`). It has no production consumers and no stability promise. We needed to decide how to version after the initial v0.0.1 release turned out to be non-functional.

## Decision

Use **semver with pre-1.0 conventions**:

- `0.0.x` — broken/experimental (burned range, do not use)
- `0.x.0` — minor bumps for new features or significant fixes
- `0.x.y` — patch bumps for small fixes within a working minor

The first usable release is `v0.1.0`. Future releases follow:

| Change type | Version bump | Example |
|---|---|---|
| New formatter, new tool, new resource | Minor (`0.x+1.0`) | `0.2.0` |
| Bug fix, typo, small tweak | Patch (`0.x.y+1`) | `0.1.1` |
| Breaking API change | Minor (pre-1.0) | `0.3.0` |
| Stable public API | `1.0.0` | — |

## Rationale

- `^0.0.x` in composer locks to exact patch — no auto-update benefit from staying in `0.0.x`
- Dev-only package means zero production risk from version bumps
- Pre-1.0 minor bumps signal "new stuff" without implying stability
- Don't debate this again — follow the table above

## Consequences

- Every release needs a GitHub release with changelog
- Commits that fix issues use `Fixes #N` to auto-close
- No need for release branches — release from `main`