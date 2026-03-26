# CLAUDE.md

## Using this bundle in a Symfony project

This bundle exposes Symfony profiler data via MCP protocol. After `composer require --dev productowner-ro/symfony-profiler-mcp`, ensure MCP transports are enabled — see README.md for config.

Available MCP tools: `symfony-profiler-list`, `symfony-profiler-get`.
Available resource templates: `symfony-profiler://profile/{token}`, `symfony-profiler://profile/{token}/{collector}`.

Built-in collector formatters: `request` (with sensitive data redaction), `exception`, `logger`. Other collectors return a generic fallback.

## Contributing to this bundle

Read CONTRIBUTING.md for the full workflow. Quick reference:

- Versioning: see `docs/adr/001-versioning-strategy.md` — don't debate, follow the table
- TDD: RED -> GREEN -> REFACTOR, no exceptions
- Issue first: every change needs a GitHub issue, commits use `Fixes #N`
- No "Service" in class names or namespaces
- VarDumper `Data` objects must be extracted via `->getValue(true)` — never pass them to `json_encode`
- Run all checks before pushing: `vendor/bin/phpunit && vendor/bin/phpstan analyse -l 6 && vendor/bin/php-cs-fixer fix --dry-run`
- Integration tests in `tests/Integration/` boot a real Symfony kernel and validate the full chain — if you add a formatter, add an integration test