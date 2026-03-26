# Contributing

## Workflow

1. **Issue first** — every change starts with a GitHub issue describing the problem and expected behavior
2. **Branch from main** — no long-lived feature branches
3. **Strict TDD** — write the smallest failing test, make it pass, refactor
4. **User-test** — verify in a real Symfony app before committing (use the test app at `../test-profiler-mcp`)
5. **One commit per issue** — commit message includes `Fixes #N` to auto-close
6. **Push to main** — no PRs for solo contributors, direct push is fine

## Release Process

1. Decide version bump per [ADR-001](docs/adr/001-versioning-strategy.md)
2. Run full checks: `vendor/bin/phpstan analyse -l 6 && vendor/bin/phpunit`
3. Create release: `gh release create vX.Y.Z --repo productowner-ro/symfony-profiler-mcp --title "vX.Y.Z - Title" --notes "changelog"`
4. Packagist picks it up automatically

## Code Standards

- PHP CS Fixer with `@Symfony` rules + `declare_strict_types`
- PHPStan level 6
- No "Service" in class names or namespaces — use semantic names
- No VarDumper in output — always extract `Data::getValue(true)`

## Adding a Collector Formatter

1. Create test in `tests/Profiler/Formatter/`
2. Implement `CollectorFormatterInterface` in `src/Profiler/Formatter/`
3. Register in `SymfonyProfilerMcpBundle::loadExtension()` with `profiler_mcp.formatter` tag
4. User-test with real profiler data